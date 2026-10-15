<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Local recovery: create / repair the Super Admin login.
 *   php artisan admin:reset                                   → admin@example.com / password
 *   php artisan admin:reset you@mail.com --password=Secret123 --no-2fa
 */
class AdminReset extends Command
{
    protected $signature = 'admin:reset {email=admin@example.com} {--password=password} {--name=Super Admin} {--no-2fa : also turn off two-factor login}';

    protected $description = 'Create or reset a Super Admin account (email, password, is_super_admin)';

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->argument('email')));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('ইমেইল সঠিক নয়: '.$email);

            return self::FAILURE;
        }
        $password = (string) $this->option('password');
        if (strlen($password) < 6) {
            $this->error('পাসওয়ার্ড কমপক্ষে ৬ অক্ষরের হতে হবে।');

            return self::FAILURE;
        }

        $user = User::whereRaw('LOWER(email) = ?', [$email])->first() ?? new User(['email' => $email, 'name' => (string) $this->option('name')]);
        $user->forceFill([
            'password' => $password,               // "hashed" cast hashes it
            'is_super_admin' => true,
            'remember_token' => Str::random(60),
        ]);
        if ($this->option('no-2fa')) {
            $user->forceFill(['two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null]);
        }
        $user->save();

        $this->info(($user->wasRecentlyCreated ? 'নতুন Super Admin তৈরি হয়েছে' : 'Super Admin রিসেট হয়েছে').": {$email}");
        $this->line('এখন /admin/login এ এই ইমেইল ও নতুন পাসওয়ার্ড দিয়ে লগইন করুন।');

        $others = User::where('is_super_admin', true)->where('id', '!=', $user->id)->pluck('email');
        if ($others->isNotEmpty()) {
            $this->line('অন্য Super Admin অ্যাকাউন্ট: '.$others->implode(', '));
        }

        return self::SUCCESS;
    }
}
