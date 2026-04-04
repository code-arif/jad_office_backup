<?php

namespace App\Providers;

use Firebase\JWT\JWT;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Apple\Provider;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{


    public function register(): void
    {
        //
    }


    public function boot(): void
    {
        // Load Apple credentials from .env
        $clientId = env('APPLE_CLIENT_ID');
        $teamId   = env('APPLE_TEAM_ID');
        $keyId    = env('APPLE_KEY_ID');
        $privateKeyPath = base_path(env('APPLE_PRIVATE_KEY_PATH'));

        // ✅ Read the actual private key contents
        $privateKey = @file_get_contents($privateKeyPath);

        if (!$privateKey) {
            throw new \Exception("Apple private key not found or unreadable at: $privateKeyPath");
        }

        // ✅ Make sure there are no extra spaces or hidden characters
        $privateKey = trim($privateKey);

        // Prepare JWT payload
        $now = time();
        $exp = $now + (86400 * 180); // 6 months max

        $payload = [
            'iss' => $teamId,
            'iat' => $now,
            'exp' => $exp,
            'aud' => 'https://appleid.apple.com',
            'sub' => $clientId,
        ];

        // ✅ Generate Apple client secret using ES256
        try {
            $clientSecret = JWT::encode($payload, $privateKey, 'ES256', $keyId);
        } catch (\Exception $e) {
            throw new \Exception("Failed to generate Apple client secret: " . $e->getMessage());
        }


        // ✅ Inject client secret into config for Socialite
        config()->set('services.apple.client_secret', $clientSecret);

        // Register the Apple Socialite provider
        $this->app['events']->listen(SocialiteWasCalled::class, function ($event) {
            $event->extendSocialite(
                'apple',
                Provider::class
            );
        });
    }
}
