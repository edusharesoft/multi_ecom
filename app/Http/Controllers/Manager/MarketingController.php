<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Campaign;
use App\Models\MessageLog;
use App\Services\Messaging\CampaignService;
use App\Services\Messaging\MessageService;
use App\Services\Messaging\OrderNotifier;
use App\Support\Tenancy;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class MarketingController extends Controller
{
    public function __construct(protected CampaignService $campaigns, protected MessageService $messages) {}

    /* ---------------- campaigns ---------------- */

    public function index()
    {
        $b = Tenancy::branch();

        return view('manager.marketing.index', [
            'campaigns' => $b->campaigns()->latest()->paginate(20),
            'smsLeft' => $this->messages->smsRemaining($b),
            'smsUsed' => $this->messages->smsUsedThisMonth($b),
            'smsLimit' => $b->planLimit('sms_month'),
            'canSms' => $b->hasFeature('sms'),
            'canEmail' => $b->hasFeature('email_marketing'),
        ]);
    }

    public function create()
    {
        $b = Tenancy::branch();

        return view('manager.marketing.create', ['canSms' => $b->hasFeature('sms'), 'canEmail' => $b->hasFeature('email_marketing'), 'smsLeft' => $this->messages->smsRemaining($b)]);
    }

    /** JSON: how many recipients an audience has (live preview on the form). */
    public function preview(Request $request)
    {
        $data = $request->validate(['audience' => ['required', Rule::in(array_keys(CampaignService::AUDIENCES))], 'channel' => ['required', Rule::in(['sms', 'email'])], 'custom_numbers' => ['nullable', 'string', 'max:50000']]);

        return response()->json(['count' => $this->campaigns->audience(Tenancy::branch(), $data['audience'], $data['channel'], $data['custom_numbers'] ?? null)->count()]);
    }

    public function store(Request $request)
    {
        $b = Tenancy::branch();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'channel' => ['required', Rule::in(['sms', 'email'])],
            'subject' => ['nullable', 'required_if:channel,email', 'string', 'max:200'],
            'message' => ['required', 'string', 'max:'.($request->channel === 'email' ? 20000 : 1000)],
            'audience' => ['required', Rule::in(array_keys(CampaignService::AUDIENCES))],
            'custom_numbers' => ['nullable', 'required_if:audience,custom', 'string', 'max:50000'],
        ]);
        if (! $b->hasFeature($data['channel'] === 'sms' ? 'sms' : 'email_marketing')) {
            return redirect()->route('manager.billing.plans')->with('warning', ($data['channel'] === 'sms' ? 'SMS' : 'Email marketing').' আপনার প্ল্যানে নেই।');
        }
        $c = $b->campaigns()->create($data + ['status' => 'draft', 'actor_name' => Tenancy::actorName()]);
        ActivityLog::record('campaign.created', "Campaign: {$c->name} ({$c->channel})", $c);

        return redirect()->route('manager.marketing.show', $c)->with('success', 'ক্যাম্পেইন সেভ হয়েছে — প্রিভিউ দেখে Send চাপুন।');
    }

    public function show(Campaign $campaign)
    {
        $this->own($campaign);
        $b = Tenancy::branch();

        return view('manager.marketing.show', [
            'c' => $campaign,
            'sample' => MessageService::render($campaign->message, ['name' => 'রহিম', 'store' => $b->store_name ?: $b->name, 'link' => route('store.home', $b), 'phone' => $b->store_phone ?: $b->mobile]),
            'recipients' => $campaign->recipients()->orderByRaw("field(status, 'failed', 'pending', 'sending', 'sent')")->limit(100)->get(),
            'smsLeft' => $this->messages->smsRemaining($b),
        ]);
    }

    public function start(Campaign $campaign)
    {
        $this->own($campaign);
        try {
            $this->campaigns->start($campaign);
        } catch (RuntimeException $e) {
            return back()->with('warning', $e->getMessage());
        }
        ActivityLog::record('campaign.started', "Campaign started: {$campaign->name} ({$campaign->fresh()->total} recipients)", $campaign);

        return back()->with('success', 'পাঠানো শুরু হয়েছে — এই পাতা খোলা রাখুন।');
    }

    /** JSON batch step used by the progress bar. */
    public function step(Campaign $campaign)
    {
        $this->own($campaign);
        $left = $this->campaigns->sendBatch($campaign, 20);
        $c = $campaign->fresh();

        return response()->json(['left' => $left, 'sent' => $c->sent, 'failed' => $c->failed, 'total' => $c->total, 'status' => $c->status]);
    }

    public function cancel(Campaign $campaign)
    {
        $this->own($campaign);
        if (in_array($campaign->status, ['draft', 'sending'], true)) {
            $campaign->update(['status' => 'cancelled', 'finished_at' => now()]);
        }

        return back()->with('success', 'ক্যাম্পেইন বন্ধ করা হয়েছে।');
    }

    public function duplicate(Campaign $campaign)
    {
        $this->own($campaign);
        $copy = $campaign->replicate(['status', 'total', 'sent', 'failed', 'started_at', 'finished_at']);
        $copy->fill(['name' => $campaign->name.' (copy)', 'status' => 'draft', 'total' => 0, 'sent' => 0, 'failed' => 0, 'actor_name' => Tenancy::actorName()])->save();

        return redirect()->route('manager.marketing.show', $copy);
    }

    /* ---------------- notifications & integrations ---------------- */

    public function settings()
    {
        $b = Tenancy::branch();

        return view('manager.marketing.settings', [
            'events' => OrderNotifier::config($b),
            'placeholders' => OrderNotifier::PLACEHOLDERS,
            'b' => $b,
            'canSms' => $b->hasFeature('sms'),
        ]);
    }

    public function saveSettings(Request $request)
    {
        $b = Tenancy::branch();
        $data = $request->validate([
            'events' => ['nullable', 'array'],
            'events.*.sms' => ['nullable', 'boolean'], 'events.*.email' => ['nullable', 'boolean'],
            'events.*.text' => ['nullable', 'string', 'max:500'],
            'fb_pixel_id' => ['nullable', 'regex:/^\d{5,20}$/'],
            'ga4_id' => ['nullable', 'regex:/^G-[A-Z0-9]{4,15}$/'],
            'gtm_id' => ['nullable', 'regex:/^GTM-[A-Z0-9]{4,12}$/'],
            'whatsapp_number' => ['nullable', 'regex:/^(\+?88)?01[3-9]\d{8}$/'],
            'whatsapp_button' => ['nullable', 'boolean'],
            'messenger_url' => ['nullable', 'url', 'max:200', 'regex:#^https?://#i'],
            'ai_chat' => ['nullable', 'boolean'],
        ], ['fb_pixel_id.regex' => 'Pixel ID শুধু সংখ্যা।', 'ga4_id.regex' => 'GA4 ID দেখতে G-XXXXXXX', 'gtm_id.regex' => 'GTM ID দেখতে GTM-XXXXXX']);

        $notify = [];
        foreach (array_keys(OrderNotifier::EVENTS) as $key) {
            $e = $data['events'][$key] ?? [];
            $notify[$key] = ['sms' => (bool) ($e['sms'] ?? false), 'email' => (bool) ($e['email'] ?? false), 'text' => trim((string) ($e['text'] ?? ''))];
        }
        $opts = $b->options ?? [];
        $opts['notify'] = $notify;
        foreach (['fb_pixel_id', 'ga4_id', 'gtm_id', 'whatsapp_number', 'messenger_url'] as $k) {
            $opts[$k] = $data[$k] ?? null;
        }
        $opts['whatsapp_button'] = (bool) ($data['whatsapp_button'] ?? false);
        $opts['ai_chat'] = (bool) ($data['ai_chat'] ?? false) && $b->hasFeature('ai');
        $b->forceFill(['options' => $opts])->save();
        ActivityLog::record('settings.notifications', 'Notification & marketing settings updated');

        return back()->with('success', 'সংরক্ষিত হয়েছে।');
    }

    public function testSms(Request $request)
    {
        $b = Tenancy::branch();
        $to = (string) $request->validate(['to' => ['required', 'regex:/^(\+?88)?01[3-9]\d{8}$/']])['to'];
        $ok = $this->messages->sms($b, $to, 'Test SMS from '.($b->store_name ?: $b->name).' ✓', 'test');

        return back()->with($ok ? 'success' : 'warning', $ok ? 'টেস্ট SMS পাঠানো হয়েছে (Message Log দেখুন)।' : 'SMS পাঠানো যায়নি — Message Log এ কারণ দেখুন।');
    }

    public function logs(Request $request)
    {
        $b = Tenancy::branch();

        return view('manager.marketing.logs', [
            'logs' => MessageLog::where('branch_id', $b->id)
                ->when($request->filled('channel'), fn ($q) => $q->where('channel', $request->channel))
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
                ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w->where('to', 'like', '%'.$request->q.'%')->orWhere('body', 'like', '%'.$request->q.'%')))
                ->latest('id')->paginate(50)->withQueryString(),
            'smsUsed' => $this->messages->smsUsedThisMonth($b),
            'smsLimit' => $b->planLimit('sms_month'),
        ]);
    }

    protected function own(Campaign $c): void
    {
        abort_unless($c->branch_id === Tenancy::branch()->id, 404);
    }
}
