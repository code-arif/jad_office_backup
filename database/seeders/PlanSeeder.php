<?php
namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Planfeature;
use Illuminate\Database\Seeder;
use Stripe\Price;
use Stripe\Product;
use Stripe\Stripe;

class PlanSeeder extends Seeder
{
    public function run()
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $plans = [
            [
                'name'       => 'Basic',
                'slug'       => 'basic',
                'price'      => 500,
                'currency'   => 'usd',
                'interval'   => 'month',
                'trial_days' => 7,
                'is_active'  => true,
                'features'   => [
                    'Access to basic content',
                    'Standard support',
                    'Single user license',
                ],
            ],
            [
                'name'       => 'Pro',
                'slug'       => 'pro',
                'price'      => 1000,
                'currency'   => 'usd',
                'interval'   => 'month',
                'trial_days' => 0,
                'is_active'  => true,
                'features'   => [
                    'Everything in Basic',
                    'Priority support',
                    'Multi-user license',
                    'Access to premium content',
                ],
            ],
            [
                'name'       => 'Premium',
                'slug'       => 'premium',
                'price'      => 2000,
                'currency'   => 'usd',
                'interval'   => 'month',
                'trial_days' => 0,
                'is_active'  => true,
                'features'   => [
                    'Everything in Pro',
                    'Dedicated account manager',
                    'Unlimited users',
                    'Advanced analytics',
                    'Custom integrations',
                ],
            ],
        ];

        foreach ($plans as $planData) {
            // Create Stripe Product
            $product = Product::create([
                'name' => $planData['name'] . ' Plan',
            ]);

            // Create Stripe Price if price > 0
            $priceId = null;
            if ($planData['price'] > 0) {
                $price = Price::create([
                    'unit_amount' => $planData['price'],
                    'currency'    => $planData['currency'],
                    'recurring'   => ['interval' => $planData['interval']],
                    'product'     => $product->id,
                ]);
                $priceId = $price->id;
            }

            // Create or update plan in DB
            $plan = Plan::updateOrCreate(
                ['slug' => $planData['slug']],
                [
                    'name'              => $planData['name'],
                    'price'             => $planData['price'],
                    'currency'          => $planData['currency'],
                    'interval'          => $planData['interval'],
                    'trial_days'        => $planData['trial_days'],
                    'is_active'         => $planData['is_active'],
                    'stripe_product_id' => $product->id,
                    'stripe_price_id'   => $priceId,
                ]
            );

            // Delete old features and add new ones
            $plan->features()->delete();

            foreach ($planData['features'] as $feature) {
                Planfeature::create([
                    'plan_id' => $plan->id,
                    'feature' => $feature,
                ]);
            }
        }
    }
}
