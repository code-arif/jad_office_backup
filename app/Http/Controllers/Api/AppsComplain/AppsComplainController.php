<?php

namespace App\Http\Controllers\Api\AppsComplain;

use App\Models\AppComplain;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\ComplainRequest;

class AppsComplainController extends Controller
{
    public function storeComplain(ComplainRequest $request)
    {
        $user = auth()->guard('api')->user();
        if (!$user) {
            return response()->json([
                'status'  => false,
                'code'    => 401,
                'message' => 'Unauthorized'
            ], 401);
        }

        try {

            $complain = AppComplain::create([
                'user_id'  => $user->id,
                'subject'  => $request->subject,
                'comments' => $request->comments,
            ]);

            return response()->json([
                'status'  => true,
                'code'    => 201,
                'message' => 'Complaint submitted successfully.',
            ], 201);
        } catch (\Throwable $th) {

            return response()->json([
                'status'  => false,
                'code'    => 500,
                'message' => 'Something went wrong.',
                'error'   => $th->getMessage()
            ], 500);
        }
    }
}
