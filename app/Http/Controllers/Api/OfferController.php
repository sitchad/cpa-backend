<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Click;
use App\Models\Offer;
use App\Services\FraudService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OfferController extends Controller
{
    public function __construct(private FraudService $fraudService) {}

    public function index(Request $request)
    {
        $user   = $request->user();
        $offers = Offer::where('is_active', true)
            ->when($request->category, fn($q) => $q->where('category', $request->category))
            ->when($user->country, fn($q) => $q->where(fn($s) => $s->where('country_targeting', 'ALL')->orWhere('country_targeting', 'LIKE', "%{$user->country}%")->orWhereNull('country_targeting')))
            ->orderByDesc('payout')->paginate(20);
        return response()->json(['success' => true, 'data' => $offers->through(fn($o) => $this->fmt($o))]);
    }

    public function show(int $id)
    {
        return response()->json(['success' => true, 'data' => $this->fmt(Offer::where('is_active', true)->findOrFail($id))]);
    }

    public function click(Request $request, int $id)
    {
        $user  = $request->user();
        $offer = Offer::where('is_active', true)->findOrFail($id);
        $ip    = $request->ip();

        $fraud = $this->fraudService->checkIP($ip);
        if ($fraud['block']) return response()->json(['success' => false, 'message' => 'Click blocked.'], 403);

        if (Click::where('user_id', $user->id)->where('offer_id', $offer->id)->where('created_at', '>=', now()->subHours(24))->whereIn('status', ['clicked', 'converted'])->exists())
            return response()->json(['success' => false, 'message' => 'Already clicked recently.'], 429);

        $clickId = Str::uuid()->toString();
        Click::create(['user_id' => $user->id, 'offer_id' => $offer->id, 'click_id' => $clickId, 'ip_address' => $ip, 'user_agent' => $request->userAgent(), 'device_fingerprint' => $request->header('X-Device-Fingerprint'), 'country' => $user->country, 'status' => 'clicked']);
        $url = str_replace(['{click_id}', '{user_id}'], [$clickId, $user->id], $offer->offer_url);

        return response()->json(['success' => true, 'data' => ['click_id' => $clickId, 'tracking_url' => $url, 'offer' => $this->fmt($offer)]]);
    }

    private function fmt(Offer $o): array
    {
        return ['id' => $o->id, 'title' => $o->title, 'description' => $o->description, 'network' => $o->network, 'payout' => (float)$o->payout, 'currency' => $o->currency, 'category' => $o->category, 'thumbnail_url' => $o->thumbnail_url, 'targeting' => $o->country_targeting];
    }
}
