<?php
namespace App\Http\Controllers\Api\Employee;

use App\Helper\Helper;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EmployeeController extends Controller
{

    use ApiResponse;

    public function index()
    {

        $employee = Employee::with(['experiences', 'certifications', 'specializations', 'qualifications', 'employee_job_categories'])
            ->where('user_id', auth('api')->user()->id)
            ->first();

        if (! $employee) {
            return $this->error('Employee profile not found.', 404);
        }

        // $yearsOfExperience = $this->calculateYearsOfExperience($employee->experiences->first());

        $user = auth('api')->user();

        $data = [

            'user_id'                 => auth('api')->user()->id,
            'employee_id'             => $employee->id,
            'employee_name'           => $employee->user->name,
            'location'                => $employee->location,
            'bio'                     => $employee->bio,
            'image_url'               => $employee->image_url ? $employee->image_url : $employee->user->avatar,
            'years_of_experience'     => (int) $employee->year_of_experice,
            'age'                     => (int) $user->age,

            'specializations'         => $employee->specializations->map(function ($spe) {

                $specialize = $spe->specialize;
                return [
                    'id'   => $specialize->id,
                    'name' => $specialize->name,

                ];
            }),

            'experiences'             => $employee->experiences->map(function ($exp) {
                return [
                    'id'           => $exp->id,
                    'company_name' => $exp->company_name,
                    'job_title'    => $exp->job_title,
                    'start_date'   => $exp->start_date,
                    'end_date'     => $exp->end_date,
                    'job_type'     => $exp->job_type,
                    'job_location' => $exp->job_location,
                ];
            }),

            'qualifications'          => $employee->qualifications->map(function ($exp) {
                return [
                    'id'             => $exp->id,
                    'institute_name' => $exp->institute_name,
                    'qaulification'  => $exp->qaulification,
                    'start_date'     => $exp->start_date,
                    'end_date'       => $exp->end_date,
                    'description'    => $exp->description,

                ];
            }),

            'certifications'          => $employee->certifications->map(function ($cert) {
                return [
                    'name'               => $cert->name,
                    'date_issue'         => $cert->date_issue,
                    'issue_organization' => $cert->issue_organization,
                    'credential_id'      => $cert->creadential_id,
                ];
            }),

            'employee_job_categories' => $employee->employee_job_categories->map(function ($job_cate) {

                $category = $job_cate->job_category;
                return [
                    'id'   => $category->id,
                    'name' => $category->title,

                ];
            }),
        ];

        return $this->success($data, 'Employee profile fetched successfully.', 200);
    }

    private function calculateYearsOfExperience($experience)
    {
        $totalYears = 0;

        $startDate = \Carbon\Carbon::parse($experience->start_date);
        $endDate   = $experience->end_date ? \Carbon\Carbon::parse($experience->end_date) : \Carbon\Carbon::now();

        $totalYears += $startDate->diffInYears($endDate);

        return floor($totalYears);
    }

    // new experience update
    public function addExperience(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'experiences' => 'nullable|array',
        ]);

        $employee = Employee::where('user_id', auth('api')->user()->id)->first();


        if (!$employee) {
            return $this->error('Employee profile not found.', 404);
        }

        $createdExperiences = [];

        // Check if experiences data exists
        if ($request->has('experiences')) {
            foreach ($request->experiences as $exp) {

                $experience = $employee->experiences()->create([
                    'company_name' => $exp['company_name'],
                    'start_date'   => $exp['start_date'],
                    'end_date'     => $exp['end_date'],
                    'job_type'     => $exp['job_type'],
                    'job_title'    => $exp['job_title'],
                    'job_location' => $exp['job_location'],
                ]);

                $createdExperiences[] = $experience;

                // Calculate total experience
                $start = Carbon::parse($exp['start_date']);
                $end   = Carbon::parse($exp['end_date']);
                $years = $start->diffInYears($end);
                $employee->year_of_experice += (int) $years;
                $employee->save();
            }
        }

        return $this->success($createdExperiences, 'Employee Experience added successfully.', 200);
    }

    // new certification update

    public function addCertification(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'certifications' => 'nullable|array',

        ]);

        $employee = Employee::where('user_id', auth('api')->user()->id)->first();

        if (! $employee) {
            return $this->error('Employee profile not found.', 404);
        }

        $newExperiences = [];

        if ($request->has('certifications')) {
            foreach ($request->certifications as $cert) {

                $certification = $employee->certifications()->create([
                    'name'               => $cert['name'],
                    'date_issue'         => $cert['date_issue'],
                    'issue_organization' => $cert['issue_organization'],
                    'creadential_id'     => $cert['credential_id'],
                ]);

                $newExperiences[] = $certification;
            }
        }

        return $this->success($newExperiences, 'Employee Certification added successfully.', 200);
    }

    // new qualification update

    public function addQualification(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'qualifications' => 'nullable|array',

        ]);

        $employee = Employee::where('user_id', auth('api')->user()->id)->first();

        if (! $employee) {
            return $this->error('Employee profile not found.', 404);
        }

        $createdQualifications = [];

        if ($request->has('qualifications')) {
            foreach ($request->qualifications as $qual) {

                $qualification = $employee->qualifications()->create([
                    'institute_name' => $qual['institute_name'],
                    'qualification'  => $qual['qualification'],
                    'start_date'     => $qual['start_date'],
                    'end_date'       => $qual['end_date'],
                    'description'    => $qual['description'],
                ]);

                $createdQualifications[] = $qualification;
            }
        }

        return $this->success($createdQualifications, 'Employee Qualifications added successfully.', 200);
    }

    public function store(Request $request)
    {

        $request->validate([

            'location'       => 'required|string',
            'bio'            => 'nullable|string|max:255',
            'image_url'      => 'nullable|file|mimes:jpeg,png,jpg,gif,svg',
            'specialize_id'  => 'nullable|array',
            'experiences'    => 'nullable|array',
            'certifications' => 'nullable|array',
        ]);

        // Upload profile image
        $imagePath = null;
        if ($request->hasFile('image_url')) {

            $image     = $request->file('image_url');
            $imagePath = Helper::uploadImage($image, 'profile');
        }

        // Store new employee profile
        $employee = Employee::updateOrCreate([
            'user_id' => auth('api')->user()->id,
        ],
            [
                'location'  => $request->location,
                'bio'       => $request->bio,
                'image_url' => $imagePath,
            ]);

        $totalExperience = 0;

        if ($request->has('experiences')) {
            foreach ($request->experiences as $exp) {
                $start = Carbon::parse($exp['start_date']);
                $end   = Carbon::parse($exp['end_date']);

                $years = $start->diffInYears($end);
                $totalExperience += $years;

                $employee->experiences()->create([
                    'company_name' => $exp['company_name'],
                    'start_date'   => $exp['start_date'],
                    'end_date'     => $exp['end_date'],
                    'job_type'     => $exp['job_type'],
                    'job_title'    => $exp['job_title'],
                    'job_location' => $exp['job_location'],
                ]);
            }

            $employee->year_of_experice = (int) $totalExperience;
            $employee->save();
        }

        // Save certifications
        if ($request->has('certifications')) {
            foreach ($request->certifications as $cert) {
                $employee->certifications()->create([
                    'name'               => $cert['name'],
                    'date_issue'         => $cert['date_issue'],
                    'issue_organization' => $cert['issue_organization'],
                    'creadential_id'     => $cert['credential_id'],
                ]);
            }
        }

        // Store specializations (many)
        // if ($request->has('specialize_id')) {
        //     foreach ($request->specialize_id as $specializeId) {
        //         $employee->specializations()->updateOrCreate([
        //             'employee_id' => $employee->id,
        //             'specialize_id' => $specializeId,
        //         ]);
        //     }
        // }

        if ($request->has('specialize_id')) {
            foreach ($request->specialize_id as $specializeId) {
                $employee->specializations()->updateOrCreate([
                    'employee_id'   => $employee->id,
                    'specialize_id' => $specializeId,
                ]);
            }
        }

        // Store job categories (many)
        // if ($request->has('job_category_id')) {
        //     foreach ($request->job_category_id as $categoryId) {
        //         $employee->employee_job_categories()->updateOrCreate([
        //             'employee_id'     => $employee->id,
        //             'job_category_id' => $categoryId,
        //         ]);
        //     }
        // }

        if ($request->has('job_category_id')) {
            foreach ($request->job_category_id as $categoryId) {
                $employee->employee_job_categories()->updateOrCreate([
                    'employee_id'     => $employee->id,
                    'job_category_id' => $categoryId,
                ]);
            }
        }

        $response = $employee->load(['experiences', 'certifications', 'specializations', 'employee_job_categories']);

        return $this->success($response, 'Employee profile create successfully.', 200);
    }

    // search company
    public function searchCompany(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'search' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 200);
        }

        $search = $request->input('search');

        $companies = Company::where('name', 'LIKE', "%$search%")
            ->orWhere('bio', 'LIKE', "%$search%")
            ->get();

        if ($companies->isEmpty()) {
            return $this->error('No companies found.', 404);
        }

        return $this->success($companies, 'Company search successful.', 200);
    }
}
