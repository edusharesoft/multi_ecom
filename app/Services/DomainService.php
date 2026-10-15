<?php

namespace App\Services;

use App\Models\Domain;
use App\Models\Setting;
use Throwable;

/**
 * Custom domain verification:
 *   1. ownership  — TXT record  _mbverify.<host>  =  <verify_token>
 *   2. pointing   — CNAME to the platform host, or A record = server IP (Super Admin → Domains settings)
 * When both pass the domain becomes active (or "verified" if the Super Admin wants to approve manually).
 */
class DomainService
{
    public function target(): string
    {
        return (string) (Setting::get('domain_cname_target') ?: parse_url((string) config('app.url'), PHP_URL_HOST));
    }

    public function check(Domain $d): array
    {
        $txtOk = $this->hasTxt($d->txtName(), $d->verify_token) || $this->hasTxt($d->host, $d->verify_token);
        [$pointOk, $pointMsg] = $this->pointsHere($d->host);

        $messages = [];
        $messages[] = $txtOk ? 'TXT ✓' : "TXT রেকর্ড পাওয়া যায়নি ({$d->txtName()} = {$d->verify_token})";
        $messages[] = $pointMsg;

        $status = $d->status;
        if ($txtOk && $pointOk && $d->status !== 'rejected') {
            $status = Setting::get('domain_manual_approval') ? ($d->status === 'active' ? 'active' : 'verified') : 'active';
        }
        $d->update([
            'status' => $status, 'last_checked_at' => now(), 'check_message' => mb_substr(implode(' · ', $messages), 0, 250),
            'verified_at' => $txtOk ? ($d->verified_at ?? now()) : $d->verified_at,
        ]);

        $ok = $txtOk && $pointOk;
        $msg = $ok ? ($status === 'active' ? "{$d->host} চালু হয়েছে ✓ (SSL সার্টিফিকেট সার্ভারে সেট করা থাকতে হবে)" : "{$d->host} ভেরিফাইড — Super Admin অনুমোদনের অপেক্ষায়।")
            : implode(' · ', $messages).' — DNS আপডেট হতে কয়েক মিনিট থেকে ২৪ ঘণ্টা লাগতে পারে।';

        return ['ok' => $ok, 'message' => $msg];
    }

    protected function hasTxt(string $name, string $token): bool
    {
        try {
            foreach ((array) @dns_get_record($name, DNS_TXT) as $r) {
                $txt = $r['txt'] ?? implode('', (array) ($r['entries'] ?? []));
                if (trim($txt, " \"") === $token) {
                    return true;
                }
            }
        } catch (Throwable) {
        }

        return false;
    }

    /** @return array{0:bool,1:string} */
    protected function pointsHere(string $host): array
    {
        $target = strtolower($this->target());
        $ip = trim((string) Setting::get('server_ip'));
        try {
            foreach ((array) @dns_get_record($host, DNS_CNAME) as $r) {
                if (rtrim(strtolower($r['target'] ?? ''), '.') === $target) {
                    return [true, 'CNAME ✓'];
                }
            }
            $ips = array_column((array) @dns_get_record($host, DNS_A), 'ip');
            if ($ip !== '' && in_array($ip, $ips, true)) {
                return [true, 'A record ✓'];
            }
            // host resolves to the same IPs as the platform itself
            $platformIps = array_column((array) @dns_get_record($target, DNS_A), 'ip');
            if ($ips && $platformIps && array_intersect($ips, $platformIps)) {
                return [true, 'A record ✓'];
            }

            return [false, 'ডোমেইন এখনো আমাদের সার্ভারে পয়েন্ট করা নেই ('.($ips ? 'A: '.implode(', ', $ips) : 'no A/CNAME').")। CNAME → {$target}".($ip ? " অথবা A → {$ip}" : '').' দিন।'];
        } catch (Throwable $e) {
            return [false, 'DNS lookup failed: '.$e->getMessage()];
        }
    }
}
