<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\Ai\AiAssistant;
use App\Services\Ai\AiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use RuntimeException;

/** Storefront AI shopping assistant (chat bubble). History lives in the visitor's session only. */
class ChatController extends Controller
{
    public function __invoke(Request $request, Branch $branch, AiAssistant $assistant, AiService $ai)
    {
        abort_unless($branch->option('ai_chat') && $ai->availableFor($branch), 404);
        $data = $request->validate(['message' => ['required', 'string', 'max:500']]);

        // per session AND per IP (a cookie-less client gets a fresh session every request and could drain the shop's AI quota)
        $key = 'shopchat:'.$branch->id.':'.$request->session()->getId();
        $ipKey = 'shopchat-ip:'.$branch->id.':'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 20) || RateLimiter::tooManyAttempts($ipKey, 60)) {
            return response()->json(['message' => 'অনেক প্রশ্ন করা হয়েছে — কিছুক্ষণ পরে আবার চেষ্টা করুন অথবা ফোন করুন '.($branch->store_phone ?: $branch->mobile)], 429);
        }
        RateLimiter::hit($key, 3600);
        RateLimiter::hit($ipKey, 3600);

        $sk = 'shopchat_'.$branch->id;
        $history = (array) $request->session()->get($sk, []);
        try {
            $reply = $assistant->shopChat($branch, $history, $data['message']);
            if (trim($reply) === '') {
                throw new RuntimeException('empty reply');   // an empty assistant turn in history makes later provider calls fail
            }
        } catch (RuntimeException) {
            return response()->json(['message' => 'এই মুহূর্তে উত্তর দেওয়া যাচ্ছে না। অনুগ্রহ করে ফোন করুন '.($branch->store_phone ?: $branch->mobile)], 503);
        }
        $history[] = ['role' => 'user', 'content' => $data['message']];
        $history[] = ['role' => 'assistant', 'content' => $reply];
        $request->session()->put($sk, array_slice($history, -12));

        return response()->json(['reply' => $reply]);
    }
}
