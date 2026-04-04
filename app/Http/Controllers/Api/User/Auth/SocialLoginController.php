<?php

namespace App\Http\Controllers\Api\User\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialLoginController extends Controller
{
    use ApiResponse;

    public function socialSignin(Request $request)
    {
        $request->validate([
            'token'    => 'required|string',
            'provider' => 'required|string|in:google,apple',
        ]);

        try {

            $provider = $request->provider;

            // verify google token (access token)
            $socialUser = $this->verifyGoogleToken($request->token);

            if (!$socialUser || !$socialUser->email) {
                return $this->error([], 'Invalid social credentials', 401);
            }

            /**
             * -----------------------------------------
             * Find Existing User
             * -----------------------------------------
             */
            $user = User::where('email', $socialUser->email)->first();

            // Check inactive user
            if ($user && $user->status !== 'active') {
                return $this->error([], 'Your account is inactive.', 403);
            }

            $isNewUser = false;

            /**
             * -----------------------------------------
             * Create User
             * -----------------------------------------
             */
            if (!$user) {

                $user = User::create([
                    'name'             => $socialUser->name ?? 'Google User',
                    'email'            => $socialUser->email,
                    'google_id'        => $socialUser->id,
                    'avatar'           => $socialUser->avatar ?? null,
                    'is_social_logged' => true,
                    'password'         => bcrypt(Str::random(16)),
                    'status'           => 'active',
                    'email_verified_at' => now(),
                ]);

                $isNewUser = true;
            } else {

                // update existing user
                $user->update([
                    'google_id'        => $socialUser->id,
                    'avatar'           => $socialUser->avatar ?? $user->avatar,
                    'is_social_logged' => true,
                    'email_verified_at' => now(),
                ]);
            }

            /**
             * -----------------------------------------
             * Login (JWT)
             * -----------------------------------------
             */
            $token = auth('api')->login($user);

            return $this->success([
                'user_id'    => $user->id,
                'token_type' => 'bearer',
                'token'      => $token,
                'is_new'     => $isNewUser,
            ], $isNewUser ? 'User registered successfully.' : 'User logged in successfully.');
        } catch (Exception $e) {

            Log::error('Social login error: ' . $e->getMessage());

            return $this->error([], 'Social login failed', 500);
        }
    }

    /**
     * Verify Google token from Flutter
     * Uses Google's tokeninfo endpoint (no JWT library needed)
     */
    private function verifyGoogleToken($token)
    {
        // ID token (JWT)
        if (str_starts_with($token, 'eyJ')) {
            $response = file_get_contents(
                "https://oauth2.googleapis.com/tokeninfo?id_token=" . $token
            );

            $data = json_decode($response, true);
        }
        // Access token
        else {
            $ch = curl_init();

            curl_setopt_array($ch, [
                CURLOPT_URL => "https://www.googleapis.com/oauth2/v3/userinfo",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    "Authorization: Bearer " . $token
                ],
            ]);

            $response = curl_exec($ch);
            curl_close($ch);

            $data = json_decode($response, true);
        }

        if (!$data || isset($data['error'])) {
            throw new Exception("Invalid Google token");
        }

        return (object)[
            'id'     => $data['sub'] ?? null,
            'name'   => $data['name'] ?? '',
            'email'  => $data['email'] ?? null,
            'avatar' => $data['picture'] ?? null,
        ];
    }
    private function generateUniqueSlug()
    {
        do {
            $slug = 'user_' . rand(1000000000, 9999999999);
        } while (User::where('slug', $slug)->exists());

        return $slug;
    }
}
