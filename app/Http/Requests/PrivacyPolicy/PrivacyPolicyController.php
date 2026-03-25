<?php

namespace App\Http\Controllers\Api\PrivacyPolicy;

use App\Http\Controllers\Controller;
use App\Models\DynamicPage;
use Illuminate\Http\Request;

class PrivacyPolicyController extends Controller
{
    public function privacy()
    {
        $privacy = DynamicPage::where('page_title', 'Privacy Policy')->first();
        if (!$privacy) {
            return response()->json([
                'status' => 'false',
                'message' => 'Privacy Policy page not found.',
            ], 404);
        }
        return response()->json([
            'status' => 'success',
            'data' => $privacy,
        ], 200);
    }

    public function terms()
    {
        $terms = DynamicPage::where('page_title', 'Terms & Condation')->first();
        if (!$terms) {
            return response()->json([
                'status' => 'false',
                'message' => 'Terms and Conditions page not found.',
            ], 404);
        }
        return response()->json([
            'status' => 'success',
            'data' => $terms,
        ], 200);
    }

}
