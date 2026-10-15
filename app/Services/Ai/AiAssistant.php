<?php

namespace App\Services\Ai;

use App\Models\Branch;
use App\Models\Page;
use App\Models\Product;
use App\Models\Review;
use App\Services\ReportService;
use App\Support\Features;
use App\Support\HtmlSanitizer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Business prompts built on AiService. All user-facing text is Bangladesh-market aware. */
class AiAssistant
{
    public function __construct(protected AiService $ai, protected ReportService $reports) {}

    protected function langName(string $lang): string
    {
        return $lang === 'en' ? 'English' : 'Bangla (বাংলা, natural conversational Bangladeshi style; product names/brands may stay in English)';
    }

    /** Product description + SEO fields. */
    public function productCopy(Branch $b, array $in): array
    {
        $prompt = "Write e-commerce copy for this product sold by the online shop \"{$b->display_name}\" in Bangladesh (price in BDT ৳).\n"
            .'Product name: '.$in['name']."\n"
            .(! empty($in['category']) ? 'Category: '.$in['category']."\n" : '')
            .(! empty($in['brand']) ? 'Brand: '.$in['brand']."\n" : '')
            .(! empty($in['price']) ? 'Price: ৳'.$in['price']."\n" : '')
            .(! empty($in['features']) ? 'Key facts from the seller (do NOT invent other specs): '.$in['features']."\n" : '')
            .'Tone: '.($in['tone'] ?? 'friendly')."\nLanguage: ".$this->langName($in['lang'] ?? 'bn')."\n\n"
            .'Return JSON: {"description": "3 short paragraphs + a list of 4-6 bullet lines starting with • (plain text, use \n for new lines, 120-220 words)", '
            .'"meta_title": "max 60 chars", "meta_description": "140-160 chars, with a call to action", "keywords": "6-10 comma separated search keywords (mix Bangla and English)"}';
        $d = $this->ai->json($b, 'product', 'You are an expert e-commerce copywriter and SEO specialist for Bangladeshi online shops. Never make up materials, sizes, warranty or specifications that were not given.', $prompt, 1200);

        return [
            'description' => Str::limit(trim((string) ($d['description'] ?? '')), 5000, ''),
            'meta_title' => Str::limit(trim((string) ($d['meta_title'] ?? '')), 70, ''),
            'meta_description' => Str::limit(trim((string) ($d['meta_description'] ?? '')), 170, ''),
            'keywords' => Str::limit(trim((string) ($d['keywords'] ?? '')), 250, ''),
        ];
    }

    /** Full blog post (sanitized HTML). */
    public function blogPost(Branch $b, array $in): array
    {
        $words = ['short' => 400, 'medium' => 700, 'long' => 1100][$in['length'] ?? 'medium'] ?? 700;
        $prompt = "Write a helpful, original blog post for the online shop \"{$b->display_name}\" (Bangladesh).\n"
            .'Topic: '.$in['topic']."\n".(! empty($in['keywords']) ? 'SEO keywords to use naturally: '.$in['keywords']."\n" : '')
            ."Length: about {$words} words.\nLanguage: ".$this->langName($in['lang'] ?? 'bn')."\n"
            .'Structure: engaging intro, 3-5 sections with <h2>/<h3> headings, <p> paragraphs, one <ul> list, and a short conclusion that invites readers to visit the shop.'
            ."\n\nReturn JSON: {\"title\": \"...\", \"excerpt\": \"max 300 chars\", \"content_html\": \"HTML using only h2,h3,p,ul,ol,li,strong,em,blockquote\", "
            .'"meta_title": "max 60 chars", "meta_description": "max 160 chars", "tags": "3-6 comma separated"}';
        $d = $this->ai->json($b, 'blog', 'You are a professional content writer and SEO editor for Bangladeshi e-commerce blogs. Write facts carefully; avoid medical, legal or financial claims.', $prompt, 3000);

        return [
            'title' => Str::limit(trim((string) ($d['title'] ?? '')), 200, ''),
            'excerpt' => Str::limit(trim((string) ($d['excerpt'] ?? '')), 500, ''),
            'content' => HtmlSanitizer::clean((string) ($d['content_html'] ?? '')),
            'meta_title' => Str::limit(trim((string) ($d['meta_title'] ?? '')), 70, ''),
            'meta_description' => Str::limit(trim((string) ($d['meta_description'] ?? '')), 170, ''),
            'tags' => Str::limit(trim((string) ($d['tags'] ?? '')), 250, ''),
        ];
    }

    /** 3 variants of an SMS / email campaign message. */
    public function campaignCopy(Branch $b, array $in): array
    {
        $sms = ($in['channel'] ?? 'sms') === 'sms';
        $prompt = 'Write 3 different '.($sms ? 'SMS' : 'email')." marketing messages for the shop \"{$b->display_name}\" in Bangladesh.\n"
            .'Goal / offer: '.$in['goal']."\nLanguage: ".$this->langName($in['lang'] ?? 'bn')."\n"
            .($sms ? 'Each SMS: max '.(($in['lang'] ?? 'bn') === 'en' ? '155' : '130').' characters, no emojis, include {name} once and {link} at the end.'
                : 'Each email: 80-150 words, include {name} in the greeting and {link} as the call to action.')
            ."\n\nReturn JSON: {\"variants\": [\"...\", \"...\", \"...\"]".($sms ? '' : ', "subject": "email subject, max 60 chars"').'}';
        $d = $this->ai->json($b, 'campaign', 'You are a direct-response marketing copywriter for Bangladeshi retail. Be honest — no fake urgency or false claims beyond the given offer.', $prompt, 900);
        $variants = array_values(array_filter(array_map(fn ($v) => trim((string) $v), (array) ($d['variants'] ?? []))));

        return ['variants' => array_slice($variants, 0, 3), 'subject' => Str::limit(trim((string) ($d['subject'] ?? '')), 120, '')];
    }

    public function reviewReply(Branch $b, Review $r): string
    {
        $prompt = "Customer review for product \"{$r->product?->name}\" — {$r->rating}/5 stars by {$r->name}:\n\"".Str::limit((string) $r->comment, 1200)."\"\n\n"
            .'Write a short (max 60 words), warm, professional public reply from the shop. Thank them; if the rating is 3 or less, apologise and invite them to contact '
            .($b->store_phone ?: $b->mobile).'. Reply in the same language the customer used. Plain text only.';

        return Str::limit($this->ai->chat($b, 'review_reply', "You write customer-care replies for the online shop \"{$b->display_name}\".", [['role' => 'user', 'content' => $prompt]], 250, 0.6), 1000, '');
    }

    /** Numbers for the insights page (also passed to the AI). */
    public function metrics(Branch $b): array
    {
        $now = now()->endOfDay();
        $cur = $this->reports->salesSummary($b, now()->subDays(29)->startOfDay(), $now);
        $prev = $this->reports->salesSummary($b, now()->subDays(59)->startOfDay(), now()->subDays(30)->endOfDay());
        $top = $this->reports->products($b, now()->subDays(29)->startOfDay(), $now)->take(8);
        $byMethod = $this->reports->salesBy($b, now()->subDays(29)->startOfDay(), $now, 'payment_method');
        $bySource = $this->reports->salesBy($b, now()->subDays(29)->startOfDay(), $now, 'source');

        // reorder suggestions: days of stock left at the last-30-days sales speed
        $sold = DB::table('order_items as i')->join('orders as o', 'o.id', '=', 'i.order_id')
            ->where('o.branch_id', $b->id)->whereNotIn('o.status', ReportService::VOID)->where('o.created_at', '>=', now()->subDays(30))
            ->selectRaw('i.product_id, sum(i.quantity) q')->groupBy('i.product_id')->pluck('q', 'product_id');
        $reorder = Product::where('branch_id', $b->id)->where('is_active', true)->where('product_type', 'physical')->whereIn('id', $sold->keys())->get(['id', 'name', 'stock', 'min_stock'])
            ->map(function ($p) use ($sold) {
                $perDay = max(0.01, (float) $sold[$p->id] / 30);
                $p->per_day = round($perDay, 2);
                $p->days_left = (int) floor(max(0, $p->stock) / $perDay);
                $p->suggest = (int) ceil($perDay * 30 - max(0, $p->stock));

                return $p;
            })->filter(fn ($p) => $p->days_left < 14)->sortBy('days_left')->take(10)->values();

        $pct = fn ($a, $b) => $b > 0 ? round(($a - $b) / $b * 100, 1) : null;

        return compact('cur', 'prev', 'top', 'byMethod', 'bySource', 'reorder') + [
            'growth' => ['sales' => $pct($cur['net'], $prev['net']), 'orders' => $pct($cur['orders'], $prev['orders']), 'avg' => $pct($cur['avg'], $prev['avg'])],
        ];
    }

    public function insights(Branch $b, array $m, string $lang = 'bn'): string
    {
        $lines = [
            "Shop: {$b->display_name} (Bangladesh, BDT). Period: last 30 days vs previous 30 days.",
            "Net sales: {$m['cur']['net']} (prev {$m['prev']['net']}); orders: {$m['cur']['orders']} (prev {$m['prev']['orders']}); average order: {$m['cur']['avg']} (prev {$m['prev']['avg']}); discount given: {$m['cur']['discount']}; refunds: {$m['cur']['refunded']}; cancelled/failed orders: {$m['cur']['cancelled']}.",
            'Top products (qty, revenue, profit): '.$m['top']->map(fn ($r) => "{$r->name} ({$r->qty}, {$r->revenue}, {$r->profit})")->implode('; '),
            'Sales by payment method: '.$m['byMethod']->map(fn ($r) => "{$r->k}: {$r->total}")->implode(', '),
            'Sales by channel: '.$m['bySource']->map(fn ($r) => "{$r->k}: {$r->total}")->implode(', '),
            'Low stock soon (days left): '.($m['reorder']->map(fn ($p) => "{$p->name} ({$p->days_left}d)")->implode(', ') ?: 'none'),
        ];
        $prompt = implode("\n", $lines)."\n\nWrite a concise business review for the shop owner in ".$this->langName($lang).":\n"
            ."1) 3 key observations with numbers, 2) likely reasons, 3) 4 concrete, low-cost actions for the next 2 weeks (marketing, stock, pricing, COD/cancellation), 4) a cautious sales expectation for next month (range, based only on the trend above).\n"
            .'Use short headings and bullet points (plain text, • for bullets). Max 280 words. Do not invent numbers that are not given.';

        return $this->ai->chat($b, 'insights', 'You are a practical retail business analyst for small Bangladeshi shops.', [['role' => 'user', 'content' => $prompt]], 900, 0.5);
    }

    /** Storefront shopping assistant. $history = [['role' => user|assistant, 'content' => ...], ...] */
    public function shopChat(Branch $b, array $history, string $message): string
    {
        $terms = collect(preg_split('/[\s,?।!.]+/u', mb_strtolower($message)))->filter(fn ($t) => mb_strlen($t) >= 3)->take(6);
        $products = Product::where('branch_id', $b->id)->where('is_active', true)
            ->when($terms->isNotEmpty(), fn ($q) => $q->where(fn ($w) => $terms->each(fn ($t) => $w->orWhere('name', 'like', '%'.$t.'%')->orWhere('meta_keywords', 'like', '%'.$t.'%'))))
            ->with('branch')->orderByDesc('sold_count')->take(8)->get();
        if ($products->isEmpty()) {
            $products = Product::where('branch_id', $b->id)->where('is_active', true)->with('branch')->orderByDesc('sold_count')->take(6)->get();
        }
        $catalog = $products->map(fn ($p) => "- {$p->name} | ৳".number_format($p->currentPrice(), 0).' | '.($p->product_type !== 'physical' || $p->stock > 0 ? 'in stock' : 'out of stock').' | '.$p->url)->implode("\n");
        $policies = Features::ready('pages')
            ? Page::where('branch_id', $b->id)->where('is_published', true)->whereIn('slug', ['return-policy', 'delivery-info', 'faq', 'about-us'])->get()
                ->map(fn ($p) => $p->title.': '.Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags((string) $p->content))), 500))->implode("\n")
            : '';

        $system = "You are the friendly shopping assistant of the online shop \"{$b->display_name}\" in Bangladesh.\n"
            .'Shop phone: '.($b->store_phone ?: $b->mobile).'. Address: '.$b->address.'. Opening hours: '.($b->opening_hours ?: 'n/a')
            .'. Delivery charge: ৳'.number_format((float) $b->shipping_charge, 0).'. Payment: '.($b->cod_enabled ? 'Cash on Delivery, ' : '').'bKash, Nagad'.($b->shatajpay_enabled ? ', cards (ShatajPay)' : '').".\n"
            .'Order tracking page: '.route('store.track', $b)."\n"
            .($policies ? "Shop policies:\n{$policies}\n" : '')
            ."Relevant products from the catalog (the ONLY products you may recommend; include the link):\n{$catalog}\n\n"
            .'Rules: reply in the customer\'s language (Bangla or English), max 90 words, plain text. Only discuss this shop, its products, orders, delivery, payment and returns. '
            .'Never invent products, prices, discounts or stock. You cannot place, change or cancel orders and you cannot see order details — send them to the tracking page or the phone number. '
            .'Never ask for passwords, OTP or card numbers. If unsure, say so and give the phone number.';

        $turns = collect($history)->filter(fn ($h) => in_array($h['role'] ?? '', ['user', 'assistant'], true) && is_string($h['content'] ?? null) && trim($h['content']) !== '')
            ->take(-6)->map(fn ($h) => ['role' => $h['role'], 'content' => Str::limit($h['content'], 600, '')])->values()->all();
        // providers require the conversation to start with a user turn
        while ($turns && $turns[0]['role'] !== 'user') {
            array_shift($turns);
        }
        $turns[] = ['role' => 'user', 'content' => Str::limit($message, 500, '')];

        return Str::limit($this->ai->chat($b, 'chat', $system, $turns, 350, 0.4), 1500);
    }
}
