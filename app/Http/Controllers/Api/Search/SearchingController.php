<?php

namespace App\Http\Controllers\Api\Search;

use App\Models\JobSearch;
use App\Models\CompanyJob;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class SearchingController extends Controller
{
    public function search(Request $request)
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $request->validate([
            'query' => 'required|string|max:255',
        ]);

        // ✅ Correct input access
        $query = trim($request->input('query'));

        // 🔥 Store or update search (NO DUPLICATE)
        JobSearch::updateOrCreate(
            [
                'user_id' => $user->id,
                'query'   => $query,
            ],
            [
                'updated_at' => now()
            ]
        );

        // 🔎 Global search
        $jobs = CompanyJob::with([
            'company:id,name,location,image_url',
            'job_category:id,title'
        ])
            ->where(function ($q) use ($query) {

                // Job fields
                $q->where('title', 'LIKE', "%{$query}%")
                    ->orWhere('location', 'LIKE', "%{$query}%")
                    ->orWhere('description', 'LIKE', "%{$query}%")

                    // Company name
                    ->orWhereHas('company', function ($company) use ($query) {
                        $company->where('name', 'LIKE', "%{$query}%")
                            ->orWhere('display_name', 'LIKE', "%{$query}%");
                    })

                    // Category title
                    ->orWhereHas('job_category', function ($cat) use ($query) {
                        $cat->where('title', 'LIKE', "%{$query}%");
                    });
            })
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'code'    => 200,
            'message' => 'Search results fetched successfully',
            'data'    => $jobs
        ]);
    }


    public function mySearches()
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'code'    => 401,
                'message' => 'Unauthorized'
            ]);
        }

        $searches = JobSearch::where('user_id', $user->id)
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'id'          => $item->id,
                    'query'       => $item->query,
                    'searched_at' => $item->updated_at->format('Y-m-d'), // 👉 2024-06-19
                ];
            });

        return response()->json([
            'success' => true,
            'code'    => 200,
            'message' => 'Search history fetched successfully',
            'data'    => $searches
        ]);
    }


    public function delete($id)
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $search = JobSearch::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$search) {
            return response()->json([
                'success' => false,
                'code'    => 200,
                'message' => 'Search not found'
            ]);
        }

        $search->delete();

        return response()->json([
            'success' => true,
            'code'    => 200,
            'message' => 'Search deleted successfully'
        ]);
    }


    public function clearAll()
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'code'    => 401,
                'message' => 'Unauthorized'
            ]);
        }

        JobSearch::where('user_id', $user->id)->delete();

        return response()->json([
            'success' => true,
            'code'    => 200,
            'message' => 'All search history cleared successfully'
        ]);
    }
}
