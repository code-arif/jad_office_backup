<?php

namespace App\Http\Controllers\Api\Company;

use App\Helper\Helper;
use App\Models\Company;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class ManageCompanyController extends Controller
{
    use ApiResponse;

    // get company

    public function getCompany()
    {


        $company = Company::where('user_id', auth('api')->user()->id)->first();

        // dd($company);

        if (!$company) {
            return $this->error([], 'Company not found.', 404);
        }

        $company_specializes = $company->company_specializes->map(function ($spe) {
            $specialize = $spe->specialize;

            return [
                'id' => $specialize->id,
                'name' => $specialize->name,
            ];
        });



        $company_projects = $company->company_projects->map(function ($project) {
            return [
                'id' => $project->id,
                'title' => $project->title,
                'image_url' => $project->image_url,
                'description' => $project->description,
                'start_date' => $project->start_date,
                'end_date' => $project->end_date,
            ];
        });

        $response = [

            'id' => $company->id,
            'name' => $company->name ?? null,
            'display_name' => $company->display_name ?? null,
            'location' => $company->location ?? null,
            'size' => $company->size ?? null,
            'website_url' => $company->website_url ?? null,
            'bio' => $company->bio ?? null,
            'image_url' => $company->image_url ?? null,
            'company_specializes' => $company_specializes ?? null,
            'company_projects' => $company_projects ?? null,

        ];

        return $this->success($response, 'Company profile fetched successfully.', 200);
    }



    // create company
    public function store(Request $request)
    {


        $validator = Validator::make($request->all(), [

            'name' => 'required|string',
            'display_name' => 'nullable|string',
            'location' => 'nullable|string',
            'size' => 'nullable|string',
            'website_url' => 'nullable|url',

            'bio' => 'nullable|string',
            'image_url' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg',
            'specialize_id' => 'nullable|array',

        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 404);
        }



        // Check if the employee already exists
        // $existingEmployee = Company::where('user_id', auth('api')->user()->id)->first();

        // if ($existingEmployee) {

        //     $company_specializes = $existingEmployee->company_specializes->map(function ($spe) {

        //         $specialize = $spe->specialize;

        //         return [
        //             'id' => $specialize->id,
        //             'name' => $specialize->name,


        //         ];
        //     });

        //     $response = [

        //         'id' => $existingEmployee->id,
        //         'name' => $existingEmployee->name ?? null,
        //         'display_name' => $existingEmployee->display_name ?? null,
        //         'location' => $existingEmployee->location ?? null,
        //         'size' => $existingEmployee->size ?? null,
        //         'website_url' => $existingEmployee->website_url ?? null,
        //         'bio' => $existingEmployee->bio ?? null,
        //         'past_project' => $existingEmployee->past_project,
        //         'image_url' => $existingEmployee->image_url ?? null,
        //         'company_specializes' => $company_specializes ?? null,

        //     ];
        //     return $this->success($response, 'Company profile already exists.', 200);
        // }

        // Upload profile image
        $imagePath = null;
        if ($request->hasFile('image_url')) {

            $image = $request->file('image_url');
            $imagePath = Helper::uploadImage($image, 'profile');
        }

        // Store new employee profile
        $company = Company::updateOrCreate([
            'user_id' => auth('api')->user()->id,
        ],
        [
            'name' => $request->name,
            'display_name' => $request->display_name,
            'location' => $request->location,
            'size' => $request->size,
            'website_url' => $request->website_url,
            'bio' => $request->bio,
            'past_project' => $request->past_project ?? null,
            'image_url' => $imagePath,
        ]);

        // // Save experiences
        // if ($request->has('projects')) {
        //     foreach ($request->projects as $exp) {
        //         $company->company_projects()->create([
        //             'project_name' => $exp['project_name'] ?? null,
        //             'start_date' => $exp['start_date'] ?? null,
        //             'end_date' => $exp['end_date'] ?? null,
        //             'project_type' => $exp['project_type'] ?? null,
        //             'project_location' => $exp['project_location'] ?? null,
        //         ]);
        //     }

        // }


        // Store specializations (many)
        if ($request->has('specialize_id')) {
            foreach ($request->specialize_id as $specializeId) {
                $company->company_specializes()->create([
                    'company_id' => $company->id,
                    'specialize_id' => $specializeId,
                ]);
            }
        }

        $company_specializes = $company->company_specializes->map(function ($spe) {
            $specialize = $spe->specialize;
            return [
                'id' => $specialize->id,
                'name' => $specialize->name,
            ];
        });

        $response = [

            'id' => $company->id,
            'name' => $company->name ?? null,
            'display_name' => $company->display_name ?? null,
            'location' => $company->location ?? null,
            'size' => $company->size ?? null,
            'website_url' => $company->website_url ?? null,
            'bio' => $company->bio ?? null,
            'past_project' => $company->past_project,
            'image_url' => $company->image_url ?? null,
            'company_specializes' => $company_specializes ?? null,

        ];


        return $this->success($response, 'Company profile create successfully.', 200);
    }



    // compnay project store

    public function storeProject(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_projects' => 'required|array',
            'company_projects.*.title' => 'required|string',
            'company_projects.*.image_url' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg',
            'company_projects.*.description' => 'nullable|string',
            'company_projects.*.start_date' => 'nullable|date',
            'company_projects.*.end_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 422);
        }

        // Retrieve the company associated with the authenticated user
        $company = Company::where('user_id', auth('api')->user()->id)->first();
        if (!$company) {
            return $this->error([], 'Company not found.', 404);
        }

        // Check if projects are provided
        if (!$request->has('company_projects')) {
            return $this->error([], 'No projects provided.', 422);
        }

        $newProjects = [];



        foreach ($request->company_projects as $project) {
            // Handle image upload
            $imagePath = null;
            if (isset($project['image_url']) && is_file($project['image_url'])) {
                $imagePath = Helper::uploadImage($project['image_url'], 'projects');
            }

            // Create the project for the company
            $project = $company->company_projects()->create([
                'title' => $project['title'],
                'image_url' => $imagePath ?? null,
                'description' => $project['description'] ?? null,
                'start_date' => $project['start_date'] ?? null,
                'end_date' => $project['end_date'] ?? null,
            ]);

            $newProjects[] = $project;
        }




        return $this->success($newProjects, 'Company project(s) added successfully.', 200);
    }


    // update specialize
    public function updateSpecialize(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'specialize_id' => 'required|array',
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 422);
        }


        $company = Company::where('user_id', auth('api')->user()->id)->first();

        if (!$company) {
            return $this->error([], 'Company not found.', 404);
        }


        // Clear existing specializations
        $company->company_specializes()->delete();


        foreach ($request->specialize_id as $specializeId) {
            $company->company_specializes()->create([
                'company_id' => $company->id,
                'specialize_id' => $specializeId,
            ]);
        }


        // Retrieve the updated specialization data
        $specialize = $company->company_specializes->map(function ($spe) {
            $specialize = $spe->specialize;
            return [
                'id' => $specialize->id,
                'name' => $specialize->name,
            ];
        });


        return $this->success($specialize, 'Company specialization updated successfully.', 200);
    }
}
