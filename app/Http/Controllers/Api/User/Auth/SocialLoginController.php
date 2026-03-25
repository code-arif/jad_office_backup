<?php

namespace App\Http\Controllers\Api\User\Auth;

use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialLoginController extends Controller
{
    use ApiResponse;

    public function socialSignin(Request $request)
    {
        $request->validate([
            'token'    => 'required',
            'provider' => 'required', // Only Google allowed
            // 'role'     => 'nullable|string'
        ]);

        try {
            $provider = $request->provider;

            // Fetch user from Google using token
            $socialUser = Socialite::driver($provider)
                ->stateless()
                ->userFromToken($request->token);

            if (!$socialUser) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            // Check if user exists including soft deleted
            $user = User::where('email', $socialUser->getEmail())->first();

            if ($user && !empty($user->deleted_at)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Your account has been deleted.'
                ], 410);
            }

            $isNewUser = false;

            if (!$user) {
                // Generate unique slug
                $slug = "user_" . rand(1000000000, 9999999999);
                while (User::where('slug', $slug)->exists()) {
                    $slug = "user_" . rand(1000000000, 9999999999);
                }

                $password = Str::random(16);

                // Create new user
                $user = User::create([
                    'name'            => $socialUser->getName() ?? 'Google User',
                    'email'           => $socialUser->getEmail(),
                    'password'        => bcrypt($password),
                    'avatar'          => $socialUser->getAvatar(),
                    'status'          => 'active',
                    'slug'            => $slug,
                    'otp_verified_at' => now(),
                ]);

                // if ($request->filled('role')) {
                //     $user->assignRole($request->input('role'));
                // }

                $isNewUser = true;
            } else {
                // Update otp_verified_at for existing users
                $user->update([
                    'otp_verified_at' => now(),
                ]);
            }

            // Login and generate JWT token
            Auth::login($user);
            $token = auth('api')->login($user);
            $data = User::find($user->id);

            return response()->json([
                'status'     => true,
                'message'    => $isNewUser ? 'User registered successfully.' : 'User logged in successfully.',
                'code'       => 200,
                'user_id'    => auth('api')->user()->id,       
                'token_type' => 'bearer',
                'token'      => $token,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify Google token from Flutter
     * Uses Google's tokeninfo endpoint (no JWT library needed)
     */
    private function verifyGoogleToken($idToken)
    {
        try {
            // Use Google's tokeninfo endpoint
            $url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($idToken);

            // Using file_get_contents (simpler)
            $response = @file_get_contents($url);

            if ($response === false) {
                // Try with cURL if file_get_contents fails
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode !== 200) {
                    throw new \Exception('Failed to verify Google token. HTTP Code: ' . $httpCode);
                }
            }

            $userData = json_decode($response, true);

            if (!$userData || isset($userData['error'])) {
                throw new \Exception('Invalid Google token: ' . ($userData['error_description'] ?? 'Unknown error'));
            }

            // Verify the token is for our app
            $clientId = config('services.google.client_id');
            if (!isset($userData['aud']) || $userData['aud'] !== $clientId) {
                throw new \Exception('Token audience mismatch. Expected: ' . $clientId . ', Got: ' . ($userData['aud'] ?? 'none'));
            }

            // Check if token is expired
            if (isset($userData['exp']) && $userData['exp'] < time()) {
                throw new \Exception('Token has expired');
            }

            // Create user object compatible with Socialite
            $socialUser = new \stdClass();
            $socialUser->id = $userData['sub'];
            $socialUser->email = $userData['email'] ?? null;
            $socialUser->name = $userData['name'] ?? '';
            $socialUser->avatar = $userData['picture'] ?? null;
            $socialUser->user = $userData;

            // Add Socialite-compatible methods
            $socialUser->getId = function () use ($socialUser) {
                return $socialUser->id;
            };

            $socialUser->getEmail = function () use ($socialUser) {
                return $socialUser->email;
            };

            $socialUser->getName = function () use ($socialUser) {
                return $socialUser->name;
            };

            $socialUser->getAvatar = function () use ($socialUser) {
                return $socialUser->avatar;
            };

            return $socialUser;
        } catch (\Exception $e) {
            Log::error("Google token verification failed: " . $e->getMessage());
            throw new \Exception('Google authentication failed: ' . $e->getMessage());
        }
    }

    private function extractUserData($socialUser, $provider)
    {
        $email = $socialUser->getEmail();
        $fullName = $socialUser->getName();
        $firstName = null;
        $lastName = null;

        if ($provider === 'google') {
            // For Google, we have the user data from token verification
            if (isset($socialUser->user['given_name'])) {
                $firstName = $socialUser->user['given_name'];
                $lastName = $socialUser->user['family_name'] ?? '';
            } else {
                // Fallback: split full name
                $nameParts = explode(' ', $fullName, 2);
                $firstName = $nameParts[0] ?? $fullName;
                $lastName = $nameParts[1] ?? '';
            }
        } elseif ($provider === 'facebook') {
            if (isset($socialUser->user['first_name'])) {
                $firstName = $socialUser->user['first_name'];
                $lastName = $socialUser->user['last_name'] ?? '';
            } else {
                $nameParts = explode(' ', $fullName, 2);
                $firstName = $nameParts[0] ?? $fullName;
                $lastName = $nameParts[1] ?? '';
            }
        } elseif ($provider === 'apple') {
            if (!isset($socialUser->user['name'])) {
                $nameParts = explode('@', $email);
                $firstName = $nameParts[0] ?? 'AppleUser';
                $lastName = '';
            } else {
                $firstName = $socialUser->user['name']['firstName'] ?? $fullName;
                $lastName = $socialUser->user['name']['lastName'] ?? '';
            }
        } elseif ($provider === 'twitter') {
            $nameParts = explode(' ', $fullName, 2);
            $firstName = $nameParts[0] ?? $fullName;
            $lastName = $nameParts[1] ?? '';
        }

        return [
            'email' => $email,
            'f_name' => $firstName,
            'l_name' => $lastName,
        ];
    }
}
