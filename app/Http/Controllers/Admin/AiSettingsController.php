<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiLog;
use App\Models\Setting;
use App\Services\Ai\AiService;
use App\Support\Features;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class AiSettingsController extends Controller
{
    public function index(AiService $ai)
    {
        $ready = Features::ready('ai_logs');

        return view('admin.ai', [
            'provider' => $ai->provider(), 'model' => Setting::get('ai_model'), 'hasKey' => (bool) Setting::get('ai_api_key'),
            'usage' => $ready ? AiLog::with('branch:id,name,slug')->where('created_at', '>=', now()->startOfMonth())
                ->selectRaw('branch_id, count(*) c, sum(success) ok, sum(input_tokens) tin, sum(output_tokens) tout')->groupBy('branch_id')->orderByDesc('c')->get() : collect(),
            'recent' => $ready ? AiLog::with('branch:id,name,slug')->latest('id')->take(30)->get() : collect(),
        ]);
    }

    public function save(Request $request)
    {
        $data = $request->validate([
            'ai_provider' => ['nullable', Rule::in(array_keys(AiService::PROVIDERS))],
            'ai_model' => ['nullable', 'string', 'max:80', 'regex:/^[A-Za-z0-9._:\-\/]+$/'],
            'ai_api_key' => ['nullable', 'string', 'max:300'],
            'remove_key' => ['nullable', 'boolean'],
        ]);
        $put = ['ai_provider' => $data['ai_provider'] ?? '', 'ai_model' => $data['ai_model'] ?? ''];
        if ($request->boolean('remove_key')) {
            $put['ai_api_key'] = '';
        } elseif (! empty($data['ai_api_key'])) {
            $put['ai_api_key'] = AiService::storeKey($data['ai_api_key']);
        }
        Setting::put($put);

        return back()->with('success', 'AI settings saved.');
    }

    public function test(AiService $ai)
    {
        try {
            $reply = $ai->chat(null, 'test', 'You are a test endpoint.', [['role' => 'user', 'content' => 'Reply with exactly: OK — বাংলা ঠিক আছে']], 30, 0);
        } catch (RuntimeException $e) {
            return back()->with('warning', $e->getMessage());
        }

        return back()->with('success', 'Connected ✓ — '.$ai->model().' replied: '.$reply);
    }
}
