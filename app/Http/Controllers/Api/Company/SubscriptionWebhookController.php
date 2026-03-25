<?php
namespace App\Http\Controllers\Api\Company;

use Stripe\Stripe;
use Stripe\Climate\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class SubscriptionWebhookController extends Controller
{

    public function handleWebhook(Request $request)
    {


        Stripe::setApiKey(config('services.stripe.secret'));


        $payload        = $request->getContent();
        $sigHeader      = $request->header('Stripe-Signature');
        $endpointSecret = config('services.stripe.webhook_secret');

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Webhook signature verification failed.'], 400);
        }


        Log::info("stripe received " );




        switch ($event->type) {
            case 'payment_intent.succeeded':


                return response()->json(['message' => 'Payment succeeded.']);

            case 'checkout.session.expired':



            default:
                return response()->json(['message' => 'Event type not handled.']);
        }
    }
}
