<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\OtpService;
use Illuminate\Http\Request;

class OtpController extends Controller
{
    public function show(Request $request, OtpService $otp)
    {
        $branch = $request->user('branch');
        if ($branch->isVerified()) {
            return redirect()->route('manager.dashboard');
        }

        return view('auth.otp', [
            'branch' => $branch,
            'wait' => $otp->secondsUntilResend($branch),
        ]);
    }

    public function verify(Request $request, OtpService $otp)
    {
        $request->validate(['otp' => ['required', 'digits:'.config('shataj.otp.length')]]);
        $branch = $request->user('branch');

        if ($error = $otp->verify($branch, $request->input('otp'))) {
            return back()->withErrors(['otp' => $error]);
        }

        return redirect()->route('manager.onboarding.plan')
            ->with('success', 'মোবাইল ভেরিফাই হয়েছে! এখন আপনার প্ল্যান বেছে নিন।');
    }

    public function resend(Request $request, OtpService $otp)
    {
        $branch = $request->user('branch');
        if ($branch->isVerified()) {
            return redirect()->route('manager.dashboard');
        }
        if ($wait = $otp->secondsUntilResend($branch)) {
            return back()->with('warning', "{$wait} সেকেন্ড পরে আবার চেষ্টা করুন।");
        }

        $sent = $otp->send($branch);

        return back()->with($sent ? 'success' : 'warning', $sent ? 'নতুন OTP পাঠানো হয়েছে।' : 'OTP পাঠাতে সমস্যা হয়েছে।');
    }
}
