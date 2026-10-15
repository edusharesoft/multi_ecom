<?php

namespace App\Services\Ai;

use App\Models\AiLog;
use App\Models\Branch;
use App\Models\Setting;
use App\Support\Features;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Provider-agnostic text generation (Anthropic Claude / OpenAI / Google Gemini).
 * Super Admin sets provider, API key (stored encrypted) and model in Admin → AI Settings.
 * Every call is counted against the branch plan limit "ai_requests_month" and logged in ai_logs.
 */
class AiService
{
    public const PROVIDERS = [
        'anthropic' => ['Anthropic Claude', 'claude-haiku-4-5-20251001'],
        'openai' => ['OpenAI', 'gpt-4o-mini'],
        'gemini' => ['Google Gemini', 'gemini-2.5-flash'],
    ];

    public function provider(): ?string
    {
        $p = Setting::get('ai_provider');

        return isset(self::PROVIDERS[$p]) ? $p : null;
    }

    public function model(): string
    {
        return (string) (Setting::get('ai_model') ?: (self::PROVIDERS[$this->provider()][1] ?? ''));
    }

    protected function key(): ?string
    {
        $enc = Setting::get('ai_api_key');
        if (! $enc) {
            return null;
        }
        try {
            return Crypt::decryptString($enc);
        } catch (Throwable) {
            return null;
        }
    }

    public static function storeKey(?string $plain): string
    {
        return $plain ? Crypt::encryptString(trim($plain)) : '';
    }

    public function configured(): bool
    {
        return $this->provider() !== null && $this->key() !== null;
    }

    /** Usable for this branch right now (platform configured + plan feature). */
    public function availableFor(?Branch $b): bool
    {
        return $this->configured() && (! $b || $b->hasFeature('ai'));
    }

    public function usedThisMonth(Branch $b): int
    {
        return Features::ready('ai_logs') ? AiLog::where('branch_id', $b->id)->where('success', true)->where('created_at', '>=', now()->startOfMonth())->count() : 0;
    }

    public function remaining(Branch $b): ?int
    {
        $limit = $b->planLimit('ai_requests_month');

        return $limit < 0 ? null : max(0, $limit - $this->usedThisMonth($b));
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $messages  user/assistant turns
     */
    public function chat(?Branch $b, string $feature, string $system, array $messages, int $maxTokens = 800, float $temperature = 0.7): string
    {
        if (! $this->configured()) {
            throw new RuntimeException('AI চালু করা নেই — Super Admin → AI Settings এ API key দিন।');
        }
        if ($b) {
            if (! $b->hasFeature('ai')) {
                throw new RuntimeException('AI Assistant আপনার প্ল্যানে নেই — প্ল্যান আপগ্রেড করুন।');
            }
            $left = $this->remaining($b);
            if ($left !== null && $left < 1) {
                throw new RuntimeException('এই মাসের AI ব্যবহারের লিমিট শেষ।');
            }
        }
        $provider = $this->provider();
        $model = $this->model();
        $start = microtime(true);
        try {
            [$text, $in, $out] = match ($provider) {
                'anthropic' => $this->anthropic($model, $system, $messages, $maxTokens, $temperature),
                'openai' => $this->openai($model, $system, $messages, $maxTokens, $temperature),
                'gemini' => $this->gemini($model, $system, $messages, $maxTokens, $temperature),
            };
        } catch (Throwable $e) {
            $this->log($b, $feature, $provider, $model, 0, 0, false, $e->getMessage(), $start);
            throw new RuntimeException('AI সার্ভার থেকে উত্তর পাওয়া যায়নি: '.Str::limit($e->getMessage(), 160));
        }
        $this->log($b, $feature, $provider, $model, $in, $out, true, null, $start);

        return trim($text);
    }

    /** Ask for JSON and parse it robustly (models sometimes wrap it in ```json fences). */
    public function json(?Branch $b, string $feature, string $system, string $prompt, int $maxTokens = 1200): array
    {
        $raw = $this->chat($b, $feature, $system."\n\nReply with ONE valid JSON object only — no markdown fences, no commentary.", [['role' => 'user', 'content' => $prompt]], $maxTokens, 0.6);
        $s = strpos($raw, '{');
        $e = strrpos($raw, '}');
        $data = ($s !== false && $e !== false) ? json_decode(substr($raw, $s, $e - $s + 1), true) : null;
        if (! is_array($data)) {
            throw new RuntimeException('AI এর উত্তর বোঝা যায়নি — আবার চেষ্টা করুন।');
        }

        return $data;
    }

    /* ---------------- providers ---------------- */

    protected function anthropic(string $model, string $system, array $messages, int $max, float $temp): array
    {
        $r = Http::timeout(60)->withHeaders(['x-api-key' => $this->key(), 'anthropic-version' => '2023-06-01'])
            ->acceptJson()->asJson()->post('https://api.anthropic.com/v1/messages', [
                'model' => $model, 'max_tokens' => $max, 'temperature' => $temp, 'system' => $system, 'messages' => array_values($messages),
            ]);
        if (! $r->successful()) {
            throw new RuntimeException('HTTP '.$r->status().' '.($r->json('error.message') ?? Str::limit($r->body(), 200)));
        }
        $text = collect($r->json('content', []))->where('type', 'text')->pluck('text')->implode("\n");

        return [$text, (int) $r->json('usage.input_tokens', 0), (int) $r->json('usage.output_tokens', 0)];
    }

    protected function openai(string $model, string $system, array $messages, int $max, float $temp): array
    {
        $r = Http::timeout(60)->withToken($this->key())->acceptJson()->asJson()->post('https://api.openai.com/v1/chat/completions', [
            'model' => $model, 'max_completion_tokens' => $max,
            'messages' => array_merge([['role' => 'system', 'content' => $system]], array_values($messages)),
        ] + (preg_match('/^(o\d|gpt-5)/', $model) ? [] : ['temperature' => $temp]));   // reasoning models accept only the default temperature
        if (! $r->successful()) {
            throw new RuntimeException('HTTP '.$r->status().' '.($r->json('error.message') ?? Str::limit($r->body(), 200)));
        }

        return [(string) $r->json('choices.0.message.content', ''), (int) $r->json('usage.prompt_tokens', 0), (int) $r->json('usage.completion_tokens', 0)];
    }

    protected function gemini(string $model, string $system, array $messages, int $max, float $temp): array
    {
        $contents = array_map(fn ($m) => ['role' => $m['role'] === 'assistant' ? 'model' : 'user', 'parts' => [['text' => $m['content']]]], array_values($messages));
        $r = Http::timeout(60)->acceptJson()->asJson()->withHeaders(['x-goog-api-key' => $this->key()])
            ->post('https://generativelanguage.googleapis.com/v1beta/models/'.rawurlencode($model).':generateContent', [
                'systemInstruction' => ['parts' => [['text' => $system]]], 'contents' => $contents,
                // Gemini 2.5 Flash "thinks" by default and thinking tokens count against maxOutputTokens → empty answers
                'generationConfig' => ['maxOutputTokens' => $max, 'temperature' => $temp]
                    + (preg_match('/^gemini-2\.5-flash/', $model) ? ['thinkingConfig' => ['thinkingBudget' => 0]] : []),
            ]);
        if (! $r->successful()) {
            throw new RuntimeException('HTTP '.$r->status().' '.($r->json('error.message') ?? Str::limit($r->body(), 200)));
        }
        $text = collect($r->json('candidates.0.content.parts', []))->reject(fn ($p) => ! empty($p['thought']))->pluck('text')->implode('');

        return [$text, (int) $r->json('usageMetadata.promptTokenCount', 0), (int) $r->json('usageMetadata.candidatesTokenCount', 0)];
    }

    protected function log(?Branch $b, string $feature, ?string $provider, string $model, int $in, int $out, bool $ok, ?string $err, float $start): void
    {
        if (! Features::ready('ai_logs')) {
            return;
        }
        try {
            AiLog::create(['branch_id' => $b?->id, 'feature' => $feature, 'provider' => (string) $provider, 'model' => Str::limit($model, 78, ''),
                'input_tokens' => $in, 'output_tokens' => $out, 'success' => $ok, 'error' => $err ? Str::limit($err, 250) : null,
                'duration_ms' => (int) ((microtime(true) - $start) * 1000)]);
        } catch (Throwable) {
        }
    }
}
