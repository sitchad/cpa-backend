<?php

namespace App\Services;

use App\Models\FraudLog;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FraudService
{
    private string $ipqsKey;

    public function __construct()
    {
        $this->ipqsKey = config('services.ipqualityscore.key', '');
    }

    public function checkIP(string $ip): array
    {
        return Cache::remember("fraud_ip_{$ip}", 3600, function () use ($ip) {
            if (!$this->ipqsKey) return ['block' => false, 'score' => 0, 'details' => [], 'proxy' => false, 'vpn' => false];
            try {
                $response = Http::timeout(5)->get("https://ipqualityscore.com/api/json/ip/{$this->ipqsKey}/{$ip}", [
                    'strictness' => 1, 'allow_public_access_points' => true, 'fast' => false,
                ]);
                if (!$response->ok()) return ['block' => false, 'score' => 0, 'details' => []];
                $data  = $response->json();
                $score = (int) ($data['fraud_score'] ?? 0);
                $proxy = (bool) ($data['proxy'] ?? false);
                $vpn   = (bool) ($data['vpn'] ?? false);
                $tor   = (bool) ($data['tor'] ?? false);
                $bot   = (bool) ($data['bot_status'] ?? false);
                $block = $score > 85 || $tor || $bot || ($proxy && $score > 60) || ($vpn && $score > 70);
                return ['block' => $block, 'score' => $score, 'proxy' => $proxy, 'vpn' => $vpn, 'tor' => $tor, 'bot' => $bot, 'country' => $data['country_code'] ?? null, 'details' => $data];
            } catch (\Exception $e) {
                Log::error('[FRAUD] IPQualityScore error: ' . $e->getMessage());
                return ['block' => false, 'score' => 0, 'details' => []];
            }
        });
    }

    public function getCountry(string $ip): ?string
    {
        return $this->checkIP($ip)['country'] ?? null;
    }

    public function checkDeviceFingerprint(string $fingerprint, int $currentUserId): bool
    {
        return User::where('device_fingerprint', $fingerprint)->where('id', '!=', $currentUserId)->count() >= 2;
    }

    public function logFraud(?int $userId, string $ip, string $type, array $details = [], string $action = 'flagged', string $fingerprint = ''): FraudLog
    {
        return FraudLog::create([
            'user_id'            => $userId,
            'ip_address'         => $ip,
            'device_fingerprint' => $fingerprint,
            'type'               => $type,
            'fraud_score'        => $details['score'] ?? null,
            'details'            => $details,
            'action_taken'       => $action,
        ]);
    }

    public function fullCheck(int $userId, string $ip, string $fingerprint = ''): array
    {
        $ipCheck = $this->checkIP($ip);
        if ($ipCheck['proxy'] ?? false || $ipCheck['vpn'] ?? false) {
            $this->logFraud($userId, $ip, 'proxy_vpn', $ipCheck, $ipCheck['block'] ? 'blocked' : 'flagged', $fingerprint);
        }
        $multiAccount = false;
        if ($fingerprint) {
            $multiAccount = $this->checkDeviceFingerprint($fingerprint, $userId);
            if ($multiAccount) $this->logFraud($userId, $ip, 'multi_account', ['fingerprint' => $fingerprint], 'flagged', $fingerprint);
        }
        return ['block' => $ipCheck['block'], 'multi_account' => $multiAccount, 'fraud_score' => $ipCheck['score'] ?? 0, 'ip_check' => $ipCheck];
    }
}
