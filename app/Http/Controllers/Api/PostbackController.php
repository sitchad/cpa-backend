<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Click;
use App\Models\Postback;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PostbackController extends Controller
{
    public function __construct(private WalletService $walletService) {}

    public function handle(Request $request)
    {
        $payload = $request->all();
        $clickId = $payload['click_id'] ?? $payload['clickid'] ?? null;
        $payout  = isset($payload['payout']) ? (float)$payload['payout'] : null;

        if (!$clickId) return response('ok', 200);

        $postback = Postback::create(['click_id' => $clickId, 'network' => $payload['network'] ?? 'unknown', 'payout' => $payout, 'currency' => strtoupper($payload['currency'] ?? 'USD'), 'raw_payload' => $payload, 'ip_address' => $request->ip(), 'status' => 'pending']);

        $click = Click::where('click_id', $clickId)->with('user', 'offer')->first();
        if (!$click) { $postback->update(['status' => 'rejected', 'reject_reason' => 'Click not found']); return response('ok', 200); }
        if (Postback::where('click_id', $clickId)->where('status', 'approved')->where('id', '!=', $postback->id)->exists()) { $postback->update(['status' => 'duplicate']); return response('ok', 200); }
        if ($click->status === 'converted') { $postback->update(['status' => 'duplicate']); return response('ok', 200); }
        if ($click->user->status === 'banned') { $postback->update(['status' => 'rejected', 'reject_reason' => 'User banned']); return response('ok', 200); }

        $finalPayout = $payout ?? (float)$click->offer->payout;
        if ($finalPayout <= 0) { $postback->update(['status' => 'rejected', 'reject_reason' => 'Invalid payout']); return response('ok', 200); }

        DB::transaction(function () use ($click, $postback, $finalPayout) {
            $this->walletService->credit($click->user_id, $finalPayout, "Conversion: {$click->offer->title}", "postback_{$postback->id}", ['postback_id' => $postback->id], $postback);
            $click->update(['status' => 'converted', 'converted_at' => now()]);
            $postback->update(['status' => 'approved', 'user_id' => $click->user_id, 'offer_id' => $click->offer_id, 'click_db_id' => $click->id, 'payout' => $finalPayout, 'wallet_credited' => true]);
            $click->offer->increment('conversions_today');
        });

        Log::info('[POSTBACK] Approved', ['click_id' => $clickId, 'user_id' => $click->user_id, 'payout' => $finalPayout]);
        return response('ok', 200);
    }
}
