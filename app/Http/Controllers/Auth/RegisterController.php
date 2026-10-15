<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function show()
    {
        if (! \App\Models\Setting::get('allow_registration')) {
            return redirect()->route('login')->with('warning', 'নতুন ব্রাঞ্চ রেজিস্ট্রেশন বর্তমানে বন্ধ আছে।');
        }

        return view('auth.register');
    }

    public function checkSlug(Request $request)
    {
        $slug = Str::lower(trim((string) $request->query('slug')));
        $valid = (bool) preg_match('/^[a-z0-9][a-z0-9-]{1,58}[a-z0-9]$/', $slug);
        $available = $valid && ! in_array($slug, Branch::RESERVED_SLUGS, true) && ! Branch::where('slug', $slug)->exists();

        return response()->json(['slug' => $slug, 'valid' => $valid, 'available' => $available]);
    }

    public function store(Request $request, OtpService $otp)
    {
        abort_unless(\App\Models\Setting::get('allow_registration'), 403, 'Registration is closed.');

        $request->merge([
            'slug' => Str::lower(trim((string) $request->input('slug'))),
            'mobile' => preg_replace('/^\+?88/', '', preg_replace('/[^\d+]/', '', (string) $request->input('mobile'))),
            'email' => Str::lower(trim((string) $request->input('email'))),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'mobile' => ['required', 'regex:/^(\+?88)?01[3-9]\d{8}$/', 'unique:branches,mobile'],
            'email' => ['required', 'email', 'max:150', 'unique:branches,email'],
            'slug' => ['required', 'regex:/^[a-z0-9][a-z0-9-]{1,58}[a-z0-9]$/', Rule::notIn(Branch::RESERVED_SLUGS), 'unique:branches,slug'],
            'address' => ['required', 'string', 'max:500'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'terms' => ['accepted'],
        ], [
            'mobile.regex' => 'সঠিক বাংলাদেশি মোবাইল নম্বর দিন (যেমন 017XXXXXXXX)।',
            'slug.regex' => 'শুধু ছোট হাতের ইংরেজি অক্ষর, সংখ্যা ও হাইফেন (-) ব্যবহার করুন, কমপক্ষে ৩ অক্ষর।',
            'slug.not_in' => 'এই নামটি সংরক্ষিত, অন্য নাম দিন।',
            'slug.unique' => 'এই URL টি ইতিমধ্যে নেওয়া হয়েছে।',
            'terms.accepted' => 'Terms & Conditions এ সম্মতি দিন।',
        ]);

        unset($data['terms']);
        $data['store_name'] = $data['name'];

        $branch = Branch::create($data);

        Auth::guard('branch')->login($branch);
        $request->session()->regenerate();

        $sent = $otp->send($branch);

        return redirect()->route('otp.show')->with(
            $sent ? 'success' : 'warning',
            $sent ? "আপনার মোবাইল ({$branch->mobile}) এ একটি OTP পাঠানো হয়েছে।" : 'OTP পাঠাতে সমস্যা হয়েছে, আবার চেষ্টা করুন।'
        );
    }
}
