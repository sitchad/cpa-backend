<?php

namespace App\Http\Middleware;

use App\Services\FraudService;
use Closure;
use Illuminate\Http\Request;

class AntiFraudMiddleware
{
    public function __construct(private FraudService $fraudService) {}

    public function handle(Request $request, Closure $next)
    {
        $user        = $request->user();
        $ip          = $request->ip();
        $fingerprint = $request->header('X-Device-Fingerprint', '');
        $check       = $this->fraudService->fullCheck($user->id, $ip, $fingerprint);

        if ($check['block']) {
            return response()->json(['success' => false, 'message' => 'Access blocked by security system.'], 403);
        }
        if ($fingerprint && !$user->device_fingerprint) {
            $user->update(['device_fingerprint' => $fingerprint]);
        }
        return $next($request);
    }
}
