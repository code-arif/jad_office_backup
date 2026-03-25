<?php
namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Stripe\Stripe;

class SubscriptionController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    // get all plans
    public function getPlans(Request $request)
    {
        $plans = Plan::with('features')->where('is_active', true)->get();
        if ($plans->isEmpty()) {
            return $this->error('No active plans found', 404);
        }

        return $this->success($plans, 'Active plans retrieved successfully', 200);
    }

    // get plan details
    public function getPlanDetails($id)
    {
        $plan = Plan::with('features')->find($id);
        if (! $plan) {
            return $this->error('Plan not found', 404);
        }
        return $this->success($plan, 'Plan details retrieved successfully', 200);
    }

    public function createSetupIntent(Request $request)
    {
        $user = auth('api')->user();

        if (! $user) {
            return $this->error('Unauthorized', 401);
        }

        if (! $user->role == "company") {
            return $this->error('Only companies can create a setup intent', 403);
        }

        try {
            $setupIntent = $user->createSetupIntent();

            if (! $setupIntent) {
                return $this->error('Failed to create setup intent', 500);
            }
            return $this->success($setupIntent, 'Payment intent created successfully ', 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create setup intent: ' . $e->getMessage(),
            ], 400);
        }
    }

    public function createSubscription(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'plan_id'        => ['required', 'string'],
            'payment_method' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 404);
        }

        $user = auth('api')->user();

        if (! $user) {
            return $this->error([], 'Unauthorized', 401);
        }

        // plan
        $plan_id = $request->input('plan_id');

        $plan = Plan::where('id', $plan_id)->first();
        if (! $plan) {
            return $this->error([], 'Plan not found', 404);
        }

        if (! $user->role == "company") {
            return $this->error([], 'Only companies can create a subscription', 400);
        }

        // check if user already has a subscription
        $subscription = $user->subscription('default');

        if ($subscription && $subscription->valid()) {
            return $this->error([], 'You already have an active subscription.', 400);
        }

        if ($subscription && $subscription->cancelled() && ! $subscription->ended()) {
            return $this->error([],'You have cancelled your plan. You can resubscribe after current period ends.' , 400);
        }

        try {
            $subscriptionBuilder = $user->newSubscription('default', $plan->stripe_price_id);

            // Apply trial if exists
            if ($plan->trial_days > 0) {
                $subscriptionBuilder->trialDays($plan->trial_days);
            }

            $subscription = $subscriptionBuilder->create($request->payment_method);

            return $this->success($subscription, 'Subscription created successfully', 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create subscription: ' . $e->getMessage(),
            ], 400);
        }
    }

    // update subscription
    public function updateSubscription(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plan_id' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 400);
        }

        $user = auth('api')->user();
        if (! $user || $user->role !== 'company') {
            return $this->error([], 'Only companies can update subscriptions', 403);
        }

        $plan = Plan::find($request->input('plan_id'));
        if (! $plan) {
            return $this->error([], 'Plan not found', 404);
        }

        $subscription = $user->subscription('default');
        if (! $subscription || ! $subscription->valid()) {
            return $this->error([], 'No active subscription found', 404);
        }

        try {
            // Update the subscription to the new plan
            $subscription->swap($plan->stripe_price_id);

            return $this->success($subscription, 'Subscription updated successfully', 200);

        } catch (\Exception $e) {
            return $this->error([], 'Failed to update subscription: ' . $e->getMessage(), 500);
        }
    }

    public function cancelSubscription(Request $request)
    {
        $user = auth('api')->user();

        if (! $user || $user->role !== 'company') {
            return $this->error([], 'Only companies can cancel subscriptions', 403);
        }

        $subscription = $user->subscription('default');

        if (! $subscription || ! $subscription->valid()) {
            return $this->error([], 'No active subscription found', 404);
        }

        try {
            // Cancel the subscription immediately
            $subscription->cancel();

            return $this->success([
                'status'  => 'canceled',
                'ends_at' => $subscription->ends_at,
            ], 'Subscription canceled successfully', 200);

        } catch (\Exception $e) {
            return $this->error('Failed to cancel subscription: ' . $e->getMessage(), 500);
        }
    }

    // status
    public function subscriptionStatus(Request $request)
    {
        $user = auth('api')->user();

        if (! $user || $user->role !== 'company') {
            return $this->error([], 'Only companies can check subscription status', 403);
        }

        $subscription = $user->subscription('default');

        if (! $subscription) {
            return $this->success([
                'active'    => false,
                'cancelled' => false,
                'on_grace'  => false,
                'ends_at'   => null,
            ], 'No subscription found', 200);
        }

        return $this->success([
            'id'            => $subscription->id,
            'stripe_price'  => $subscription->stripe_price,

            'active'        => $subscription->active(),
            'canceled'      => $subscription->canceled(),
            'on_grace'      => $subscription->onGracePeriod(),
            'ends_at'       => $subscription->ends_at,
            'trial_ends_at' => $subscription->trial_ends_at,
        ], 'Subscription status retrieved', 200);

    }

}
