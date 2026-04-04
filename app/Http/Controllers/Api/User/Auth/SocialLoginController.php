<?php

namespace App\Http\Controllers\Api\User\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;

class SocialLoginController extends Controller
{
    public function socialSignin(Request $request)
    {
        $request->validate([
            'token'    => 'required',
            'provider' => 'required|in:google,facebook,apple',
            'name'     => 'nullable|string'
        ]);

        try {
            $provider = $request->provider;

            if ($provider === 'apple') {
                $socialUser = $this->verifyAppleToken($request->token, $request->name);
            } else {
                $socialUser = \Laravel\Socialite\Facades\Socialite::driver($provider)
                    ->stateless()
                    ->userFromToken($request->token);
            }

            if (!$socialUser || empty($socialUser['email'])) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized',
                ], 401);
            }

            // Check existing user (including soft deleted)
            $user = User::
                where('email', $socialUser['email'])
                ->first();

            if ($user && $user->deleted_at) {
                return response()->json([
                    'status' => false,
                    'message' => 'Your account has been deleted.',
                ], 410);
            }

            $isNewUser = false;

            if (!$user) {
                $user = User::create([
                    'name'            => $socialUser['name'],
                    'email'           => $socialUser['email'],
                    'avatar'          => $socialUser['avatar'] ?? null,
                    'password'        => bcrypt(Str::random(16)),
                    'status'          => 'active',
                    'otp_verified_at' => now(),
                ]);

             

                $isNewUser = true;
            }

            Auth::login($user);
            $token = auth('api')->login($user);

            return response()->json([
                'status'     => true,
                'message'    => 'User logged in successfully.',
                'token'      => $token,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
                'is_new_user'=> $isNewUser,
                'data'       => [
                    'id'     => $user->id,
                    'name'   => $user->name,
                    'email'  => $user->email,
                    'avatar' => $user->avatar,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ✅ VERIFY APPLE TOKEN (IMPORTANT PART)
     */
    private function verifyAppleToken($idToken, $name = null)
    {
        // Get Apple public keys
        $response = Http::get('https://appleid.apple.com/auth/keys');

        if (!$response->ok()) {
            throw new \Exception('Unable to fetch Apple public keys');
        }

        $keys = $response->json();

        // Decode token header
        $header = json_decode(base64_decode(explode('.', $idToken)[0]), true);

        $kid = $header['kid'];

        // Find matching key
        $key = collect($keys['keys'])->firstWhere('kid', $kid);

        if (!$key) {
            throw new \Exception('Invalid Apple public key');
        }

        // Convert JWK to PEM
        $publicKeys = JWK::parseKeySet(['keys' => [$key]]);

        // Decode JWT
        $decoded = JWT::decode($idToken, $publicKeys);

        return [
            'email'  => $decoded->email ?? null,
            'name'   => $name ?? 'Apple User',
            'avatar' => null,
        ];
    }
}
