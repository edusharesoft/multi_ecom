<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ApiToken;
use App\Models\WebhookDelivery;
use App\Services\WebhookService;
use App\Support\Tenancy;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** REST API tokens, outgoing webhooks and API docs for the branch. */
class ApiSettingsController extends Controller
{
    public function index()
    {
        $b = Tenancy::branch();

        return view('manager.api.index', [
            'b' => $b,
            'tokens' => ApiToken::where('branch_id', $b->id)->latest()->get(),
            'deliveries' => WebhookDelivery::where('branch_id', $b->id)->latest('id')->take(20)->get(),
            'base' => url('/api/v1'),
        ]);
    }

    public function store(Request $request)
    {
        $b = Tenancy::branch();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'abilities' => ['required', 'array', 'min:1'], 'abilities.*' => [Rule::in(array_keys(ApiToken::ABILITIES))],
            'expires' => ['nullable', Rule::in(['30', '90', '365', 'never'])],
        ]);
        if (ApiToken::where('branch_id', $b->id)->count() >= 10) {
            return back()->with('warning', 'সর্বোচ্চ ১০টি token রাখা যায় — পুরনো একটি মুছুন।');
        }
        $expires = ($data['expires'] ?? 'never') === 'never' ? null : now()->addDays((int) $data['expires']);
        [$t, $plain] = ApiToken::issue($b, $data['name'], $data['abilities'], $expires, Tenancy::actorName());
        ActivityLog::record('api.token_created', "API token created: {$t->name}", $t);

        return back()->with('success', 'Token তৈরি হয়েছে — এখনই কপি করুন, এটি আর দেখানো হবে না।')->with('plain_token', $plain);
    }

    public function destroy(ApiToken $token)
    {
        abort_unless($token->branch_id === Tenancy::branch()->id, 404);
        $token->delete();
        ActivityLog::record('api.token_revoked', "API token revoked: {$token->name}");

        return back()->with('success', 'Token বাতিল করা হয়েছে।');
    }

    public function saveWebhook(Request $request)
    {
        $b = Tenancy::branch();
        $data = $request->validate([
            'webhook_url' => ['nullable', 'url', 'max:250', 'regex:#^https?://#i'],
            'webhook_events' => ['nullable', 'array'], 'webhook_events.*' => [Rule::in(array_keys(WebhookService::EVENTS))],
            'regenerate_secret' => ['nullable', 'boolean'],
        ]);
        $opts = $b->options ?? [];
        $opts['webhook_url'] = $data['webhook_url'] ?? null;
        $opts['webhook_events'] = array_values($data['webhook_events'] ?? []);
        if (empty($opts['webhook_secret']) || $request->boolean('regenerate_secret')) {
            $opts['webhook_secret'] = 'whsec_'.Str::random(32);
        }
        $b->forceFill(['options' => $opts])->save();

        return back()->with('success', 'Webhook সেটিংস সংরক্ষিত।');
    }

    public function testWebhook(WebhookService $webhooks)
    {
        $b = Tenancy::branch();
        $d = $webhooks->send($b, 'ping', ['message' => 'Test webhook from '.$b->display_name]);
        if (! $d) {
            return back()->with('warning', 'আগে Webhook URL সেভ করুন।');
        }

        return back()->with($d->success ? 'success' : 'warning', $d->success ? "Webhook OK (HTTP {$d->status_code}, {$d->duration_ms} ms)" : 'Webhook ব্যর্থ: '.($d->status_code ? "HTTP {$d->status_code} " : '').$d->response);
    }
}
