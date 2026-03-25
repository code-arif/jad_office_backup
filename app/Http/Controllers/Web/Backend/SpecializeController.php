<?php

namespace App\Http\Controllers\Web\Backend;

use Exception;
use App\Helper\Helper;
use App\Models\Specialize;
use App\Models\JobCategory;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Yajra\DataTables\Facades\DataTables;

class SpecializeController extends Controller
{

    use ApiResponse;

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Specialize::all();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('image', function ($data) {
                    if ($data->image_url) {
                        $url = asset($data->image_url);
                        return '<img src="' . $url . '" alt="image" width="50px" height="50px" style="margin-left:20px;">';
                    } else {
                        return '<img src="' . asset('default/logo.png') . '" alt="image" width="50px" height="50px" style="margin-left:20px;">';
                    }
                })
                ->addColumn('status', function ($data) {
                    $backgroundColor = $data->status == "active" ? '#4CAF50' : '#ccc';
                    $sliderTranslateX = $data->status == "active" ? '26px' : '2px';
                    $sliderStyles = "position: absolute; top: 2px; left: 2px; width: 20px; height: 20px; background-color: white; border-radius: 50%; transition: transform 0.3s ease; transform: translateX($sliderTranslateX);";

                    $status = '<div class="form-check form-switch" style="margin-left:40px; position: relative; width: 50px; height: 24px; background-color: ' . $backgroundColor . '; border-radius: 12px; transition: background-color 0.3s ease; cursor: pointer;">';
                    $status .= '<input onclick="showStatusChangeAlert(' . $data->id . ')" type="checkbox" class="form-check-input" id="customSwitch' . $data->id . '" getAreaid="' . $data->id . '" name="status" style="position: absolute; width: 100%; height: 100%; opacity: 0; z-index: 2; cursor: pointer;">';
                    $status .= '<span style="' . $sliderStyles . '"></span>';
                    $status .= '<label for="customSwitch' . $data->id . '" class="form-check-label" style="margin-left: 10px;"></label>';
                    $status .= '</div>';

                    return $status;
                })
                ->addColumn('action', function ($data) {
                    return '<div class="btn-group btn-group-sm" role="group" aria-label="Basic example">
                              <a href="' . route('admin.specialize.edit', ['id' => $data->id]) . '" class="btn btn-primary text-white" title="Edit">
                              <i class="bi bi-pencil"></i>
                              </a>
                              <a href="#" onclick="showDeleteConfirm(' . $data->id . ')" type="button" class="btn btn-danger text-white" title="Delete">
                              <i class="bi bi-trash"></i>
                            </a>
                            </div>';
                })
                ->rawColumns(['image', 'status', 'action'])
                ->make();
        }
        return view("backend.layouts.specialize.index");
    }


    public function create()
    {
        return view('backend.layouts.specialize.create');
    }


    public function store(Request $request)
    {

        // dd($request->all());


        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imagePath = Helper::uploadImage($image, 'specializes');
        } else {
            $imagePath = '-';
        }

        Specialize::create([
            'name' => $request->name,
            'type' => $request->type,
            'image_url' => $imagePath,

        ]);



        return redirect()->route('admin.specialize.index')->with('success', 'Category created successfully');
    }




    public function show(Specialize $specialize, $id)
    {
        $specialize = Specialize::findOrFail($id);
        return view('backend.layouts.specialize.edit', compact('specialize'));
    }


    public function edit(Specialize $specialize, $id)
    {
        $specialize = Specialize::find($id);
        

        return view('backend.layouts.specialize.edit', compact('specialize'));

        
    }


    public function update(Request $request, $id)
    {
        $specialize = Specialize::findOrFail($id);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imagePath = Helper::uploadImage($image, 'specializes');
        } else {
            $imagePath = $specialize->image_url;
        }

        $specialize->update([
            'name' => $request->name,
            'type' => $request->type,
            'image_url' => $imagePath,

        ]);
        
        return redirect()->route('admin.specialize.index')->with('success', 'Specialize updated successfully');
    }


    public function destroy(string $id)
    {

        $data = Specialize::findOrFail($id);
        if (empty($data)) {
            return response()->json([
                'success' => false,
                'message' => 'Speciliaze not found.',
            ], 404);
        }

        if ($data->image) {
            $oldImagePath = public_path($data->image);
            if (file_exists($oldImagePath)) {
                unlink($oldImagePath);
            }
        }


        $data->delete();

        return response()->json([
            'success' => true,
            'message' => 'Speciliaze deleted successfully!',
        ], 200);
    }

    public function status(int $id): JsonResponse
    {
        $data = JobCategory::findOrFail($id);

        if (!$data) {
            return response()->json([
                'status' => 'error',
                'message' => 'Category not found.',
            ]);
        }
        $data->status = $data->status === 'active' ? 'inactive' : 'active';
        $data->save();
        return response()->json([
            'status' => 'success',
            'message' => 'Status Changed successful!',
        ]);
    }


    public function employee_list()
    {
        try {

            $specializes = Specialize::where('type', 'employee')->get();

            if ($specializes->isEmpty()) {
                return $this->success([], 'Items not found', 200);
            }


            $data =   $specializes->map(function ($spe) {

                $totalJob = $spe->employee_specializes()->count();
                
                return [
                    'id' => $spe->id,
                    'type' => $spe->type,
                    'name' => $spe->name,
                    'image_url' => $spe->image_url ? url($spe->image_url) : null,
                    'total_job' => $totalJob

                ];
            });



            return $this->success($data, 'Item  retrieved successfully', 200);
        } catch (Exception $e) {


            Log::info($e->getMessage());
            return $this->error($e->getMessage(), 'Error while listing item types', 500);
        }
    }





    public function company_list()
    {
        try {

            $specializes = Specialize::where('type', 'company')->get();

            if ($specializes->isEmpty()) {
                return $this->success([], 'Items not found', 200);
            }

            $data =   $specializes->map(function ($spe) {

                $totalJob = $spe->employee_specializes()->count();
                
                return [
                    'id' => $spe->id,
                    'type' => $spe->type,
                    'name' => $spe->name,
                    'image_url' => $spe->image_url ? url($spe->image_url) : null,
                    'total_job' => $totalJob

                ];
            });



            return $this->success($data, 'Item  retrieved successfully', 200);
        } catch (Exception $e) {

            Log::info($e->getMessage());
            return $this->error($e->getMessage(), 'Error while listing item types', 500);
        }
    }


    public function job_categories()
    {
        try {

            $categories = JobCategory::all();

            if ($categories->isEmpty()) {
                return $this->success([], 'Items not found', 200);
            }



            return $this->success($categories, 'Item  retrieved successfully', 200);
        } catch (Exception $e) {

            Log::info($e->getMessage());
            return $this->error($e->getMessage(), 'Error while listing item types', 500);
        }
    }
}
