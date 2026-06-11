<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wallet;
use App\Services\FraudService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function __construct(private FraudService $fraudService) {}

    public function register(Request $request)
    {
        // Validation
        $v = Validator::make($request->all(), [
            'name'     => 'required|string|min:2|max:100',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($v->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $v->errors()
            ], 422);
        }

        $ip = $request->ip();

        // Fraud check
        $fraud = $this->fraudService->checkIP($ip);

        if ($fraud['block']) {
            return response()->json([
                'success' => false,
                'message' => 'Registration not allowed.'
            ], 403);
        }

        if (User::where('ip_address', $ip)->count() >= 2) {
            $this->fraudService->logFraud(null, $ip, 'multi_account', $fraud, 'flagged');

            return response()->json([
                'success' => false,
                'message' => 'Too many accounts from this IP.'
            ], 403);
        }

        // Create user
        $user = User::create([
            'name'               => $request->name,
            'email'              => $request->email,
            'password'           => Hash::make($request->password),
            'referral_code'      => strtoupper(Str::random(8)),
            'ip_address'         => $ip,
            'device_fingerprint' => $request->header('X-Device-Fingerprint'),
            'country'            => $this->fraudService->getCountry($ip),
        ]);

        // Create wallet
        Wallet::create([
            'user_id' => $user->id,
            'balance' => 0
        ]);

        // Token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $this->fmt($user),
                'token' => $token,
                'token_type' => 'Bearer'
            ]
        ], 201);
    }

    public function login(Request $request)
    {
        $v = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        if ($v->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $v->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials.'
            ], 401);
        }

        if ($user->status === 'banned') {
            return response()->json([
                'success' => false,
                'message' => 'Account banned: ' . $user->ban_reason
            ], 403);
        }

        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $this->fmt($user),
                'token' => $token,
                'token_type' => 'Bearer'
            ]
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out.'
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $this->fmt($request->user()->load('wallet'), true)
        ]);
    }

    // FIXED FUNCTION
    private function fmt(User $u, bool $wallet = false): array
    {
        $d = [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'role' => $u->role,
            'status' => $u->status,
            'referral_code' => $u->referral_code,
            'country' => $u->country,
            'created_at' => $u->created_at?->toISOString()
        ];

        if ($wallet && $u->wallet) {
            $d['wallet'] = [
                'balance' => (float) $u->wallet->balance,
                'pending' => (float) $u->wallet->pending,
                'total_earned' => (float) $u->wallet->total_earned,
                'total_withdrawn' => (float) $u->wallet->total_withdrawn
            ];
        }

        return $d;
    }
}