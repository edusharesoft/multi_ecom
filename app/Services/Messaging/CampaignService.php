<?php

namespace App\Services\Messaging;

use App\Models\Branch;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Bulk SMS / email campaigns. Recipients are frozen when the campaign starts; sending happens in
 * small batches (browser-driven progress bar, or `php artisan marketing:send`) so no queue worker is needed.
 */
class CampaignService
{
    public const AUDIENCES = [
        'all' => 'সব রেজিস্টার্ড কাস্টমার',
        'buyers' => 'সব ক্রেতা (গেস্ট সহ, অর্ডার থেকে)',
        'segment:new' => 'Segment: New',
        'segment:regular' => 'Segment: Regular',
        'segment:vip' => 'Segment: VIP',
        'segment:high_value' => 'Segment: High Value',
        'segment:inactive' => 'Segment: Inactive (৯০ দিন অর্ডার নেই)',
        'group:wholesale' => 'Group: Wholesale',
        'ordered_days:30' => 'গত ৩০ দিনে অর্ডার করেছে',
        'inactive_days:60' => '৬০ দিন ধরে অর্ডার করেনি',
        'custom' => 'নিজের নম্বর / ইমেইল তালিকা',
    ];

    public function __construct(protected MessageService $messages) {}

    /** [[to, name, customer_id]] unique by "to" */
    public function audience(Branch $b, string $audience, string $channel, ?string $custom = null): Collection
    {
        $field = $channel === 'email' ? 'email' : 'phone';
        $valid = fn ($v) => $channel === 'email' ? (bool) filter_var($v, FILTER_VALIDATE_EMAIL) : (bool) preg_match('/^(\+?88)?01[3-9]\d{8}$/', (string) $v);

        if ($audience === 'custom') {
            return collect(preg_split('/[\s,;]+/', (string) $custom))->map(fn ($v) => trim($v))->filter($valid)->unique()
                ->map(fn ($v) => [$v, null, null])->values();
        }

        if ($audience === 'buyers') {
            $col = $channel === 'email' ? 'customer_email' : 'customer_phone';

            $opted = Customer::where('branch_id', $b->id)->where('marketing_opt_out', true)->pluck('id')->flip();

            return Order::where('branch_id', $b->id)->whereNotNull($col)->where($col, '!=', '')
                ->selectRaw("{$col} as t, max(customer_name) as n, max(customer_id) as c")->groupBy($col)->get()
                ->filter(fn ($r) => $valid($r->t))
                ->reject(fn ($r) => $r->c && isset($opted[$r->c]))
                ->map(fn ($r) => [$r->t, $r->n, $r->c])->values();
        }

        $q = Customer::where('branch_id', $b->id)->where('is_active', true)->where('marketing_opt_out', false)->whereNotNull($field);
        [$type, $arg] = array_pad(explode(':', $audience, 2), 2, null);
        match ($type) {
            'group' => $q->where('group', $arg),
            'ordered_days' => $q->whereHas('orders', fn ($o) => $o->where('created_at', '>=', now()->subDays((int) $arg))),
            'inactive_days' => $q->whereDoesntHave('orders', fn ($o) => $o->where('created_at', '>=', now()->subDays((int) $arg))),
            default => null,
        };
        $rows = $q->withSum(['orders as spent' => fn ($o) => $o->where('status', '!=', 'cancelled')], 'total')
            ->withCount(['orders as orders_n' => fn ($o) => $o->where('status', '!=', 'cancelled')])
            ->withMax('orders as last_order', 'created_at')->get();
        if ($type === 'segment') {
            $rows = $rows->filter(fn (Customer $c) => $c->setRelation('branch', $b)->segment((float) $c->spent, (int) $c->orders_n, $c->last_order ?? false) === $arg);
        }

        return $rows->filter(fn ($c) => $valid($c->{$field}))->unique($field)->map(fn ($c) => [$c->{$field}, $c->name, $c->id])->values();
    }

    public function start(Campaign $c): Campaign
    {
        if ($c->status !== 'draft') {
            throw new RuntimeException('এই ক্যাম্পেইন আগেই শুরু হয়েছে।');
        }
        $b = Branch::findOrFail($c->branch_id);
        $list = $this->audience($b, $c->audience, $c->channel, $c->custom_numbers);
        if ($list->isEmpty()) {
            throw new RuntimeException('এই অডিয়েন্সে কোনো বৈধ নম্বর/ইমেইল পাওয়া যায়নি।');
        }
        if ($c->channel === 'sms') {
            $left = $this->messages->smsRemaining($b);
            $need = $list->count() * MessageService::segments(MessageService::render($c->message, ['name' => 'Customer', 'store' => $b->store_name ?: $b->name]));
            if ($left !== null && $need > $left) {
                throw new RuntimeException("SMS কোটা যথেষ্ট নয়: দরকার ~{$need} SMS, বাকি আছে {$left}। প্ল্যান আপগ্রেড করুন বা অডিয়েন্স ছোট করুন।");
            }
        }
        DB::transaction(function () use ($c, $list) {
            foreach ($list->chunk(500) as $chunk) {
                CampaignRecipient::insertOrIgnore($chunk->map(fn ($r) => ['campaign_id' => $c->id, 'to' => mb_substr($r[0], 0, 150),
                    'name' => $r[1] ? mb_substr($r[1], 0, 120) : null, 'customer_id' => $r[2], 'status' => 'pending'])->all());
            }
            $c->update(['status' => 'sending', 'total' => $c->recipients()->count(), 'started_at' => now()]);
        });

        return $c->fresh();
    }

    /** Send up to $limit pending messages. Returns remaining count. */
    public function sendBatch(Campaign $c, int $limit = 25): int
    {
        if ($c->status !== 'sending') {
            return 0;
        }
        $b = Branch::findOrFail($c->branch_id);
        $store = $b->store_name ?: $b->name;
        $link = route('store.home', $b);

        // claim rows atomically so two browser tabs never send twice
        $ids = $c->recipients()->where('status', 'pending')->orderBy('id')->limit($limit)->pluck('id');
        foreach ($ids as $id) {
            $claimed = CampaignRecipient::whereKey($id)->where('status', 'pending')->update(['status' => 'sending']);
            if (! $claimed) {
                continue;
            }
            $r = CampaignRecipient::find($id);
            $vars = ['name' => $r->name ?: 'Customer', 'store' => $store, 'link' => $link, 'phone' => $b->store_phone ?: $b->mobile];
            $text = MessageService::render($c->message, $vars);
            $ok = $c->channel === 'email'
                ? $this->messages->email($b, $r->to, MessageService::render($c->subject ?: $c->name, $vars), $text."\n\n—\n{$store}\n{$link}\n(এই ইমেইল আর না চাইলে উত্তর দিন: STOP)", 'campaign', ['campaign_id' => $c->id])
                : $this->messages->sms($b, $r->to, $text, 'campaign', ['campaign_id' => $c->id]);
            // a parallel run may already have swept this row as "interrupted" (and counted it) — then only fix its status
            $mine = CampaignRecipient::whereKey($id)->where('status', 'sending')->update(['status' => $ok ? 'sent' : 'failed', 'sent_at' => now(), 'error' => $ok ? null : 'send failed / quota']);
            if ($mine) {
                $c->increment($ok ? 'sent' : 'failed');
            } elseif ($ok) {
                CampaignRecipient::whereKey($id)->update(['status' => 'sent', 'sent_at' => now(), 'error' => null]);
                $c->increment('sent');
                $c->decrement('failed');
            }
        }

        $left = $c->recipients()->where('status', 'pending')->count();
        if ($left === 0) {
            // rows left "sending" by an interrupted request
            $stuck = $c->recipients()->where('status', 'sending')->update(['status' => 'failed', 'error' => 'interrupted']);
            if ($stuck) {
                $c->increment('failed', $stuck);
            }
            $c->update(['status' => 'sent', 'finished_at' => now()]);
        }

        return $left;
    }
}
