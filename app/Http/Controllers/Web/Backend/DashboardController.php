<?php

namespace App\Http\Controllers\Web\Backend;

use App\Http\Controllers\Controller;
use App\Models\BmwComponentList;
use App\Models\BmwModelList;
use App\Models\BmwSeriesList;
use App\Models\Booking;
use App\Models\Category;
use App\Models\JobCategory;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $totalCategories = JobCategory::count();
   
       
        return view('backend.layouts.dashboard', compact('totalCategories',));
    }
}
