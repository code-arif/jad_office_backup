<?php

namespace App\Http\Controllers\Api\Employee;

use App\Models\CompanyJob;
use App\Traits\ApiResponse;
use App\Models\JobApplicant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\JobSave;

class JobBoardController extends Controller
{
    use ApiResponse;

    // Get all job
    public function featuredJobList(Request $request)
    {
        $user = auth('api')->user();

        // Get employee if user is logged in
        $employee = null;

        if ($user) {
            $employee = Employee::where('user_id', $user->id)->first();
        }

        dd($employee);

        $appliedJobIds = [];

        if ($employee) {
            $appliedJobIds = JobApplicant::where('employee_id', $employee->id)
                ->pluck('job_id')
                ->toArray();
        }

        $jobs = CompanyJob::with('company')
            ->whereDate('dedline', '>=', now());

        // 🔹 Location
        if ($request->filled('location')) {
            $jobs->where('location', 'LIKE', '%' . $request->location . '%');
        }

        // 🔹 Certification
        if ($request->filled('certification')) {
            $jobs->where('certification', 'LIKE', '%' . $request->certification . '%');
        }

        // 🔹 Salary range
        if ($request->has('min_salary') && $request->has('max_salary')) {
            $salaryMin = (int) $request->min_salary;
            $salaryMax = (int) $request->max_salary;
            $jobs->whereBetween(DB::raw('CAST(salary AS SIGNED)'), [$salaryMin, $salaryMax]);
        }

        // 🔹 Search (company + salary + job type + location + title)
        if ($request->filled('search')) {
            $search = $request->search;
            $jobs->where(function ($q) use ($search) {
                $q->where('location', 'LIKE', '%' . $search . '%')
                    ->orWhere('job_type', 'LIKE', '%' . $search . '%')
                    ->orWhere('salary', 'LIKE', '%' . $search . '%')
                    ->orWhere('title', 'LIKE', '%' . $search . '%')
                    ->orWhereHas('company', function ($company) use ($search) {
                        $company->where('name', 'LIKE', '%' . $search . '%')
                            ->orWhere('display_name', 'LIKE', '%' . $search . '%');
                    });
            });
        }

        $featuredJobs = $jobs->orderBy('created_at', 'desc')->get();

        if ($featuredJobs->isEmpty()) {
            return $this->success([], 'No jobs found');
        }

        $formatted = $featuredJobs->map(function ($job) use ($employee) {

            $isSaved = false;
            $isApplied = false;

            if ($employee) {

                $isSaved = JobSave::where('employee_id', $employee->id)
                    ->where('job_id', $job->id)
                    ->exists();

                $isApplied = JobApplicant::where('employee_id', $employee->id) // FIXED
                    ->where('job_id', $job->id)
                    ->exists();
            }

            return [
                'id'           => $job->id,
                'job_title'    => $job->title,
                'company_name' => $job->company->name ?? null,
                'salary'       => $job->salary,
                'image'        => $job->description_image ? url($job->description_image) : null,
                'video'        => $job->description_video ? url($job->description_video) : null,
                'deadline'     => $job->dedline ?? null,
                'job_type'     => $job->job_type ?? null,
                'job_location' => $job->location ?? null,
                'requirement'  => (object) ($job->requirement ?? []),
                'video_url'    => $job->description_video ? url($job->description_video) : null,
                'image_url'    => $job->description_image ? url($job->description_image) : null,
                'is_saved'     => $isSaved,
                'is_applied'   => $isApplied,
            ];
        });

        return $this->success($formatted, 'Jobs retrieved successfully');
    }

    public function jobSeekerjobDetails($id)
    {
        $user = auth()->guard('api')->user();

        if (!$user) {
            return response()->json([
                'status'  => false,
                'code'    => 401,
                'message' => 'Unauthorized'
            ], 401);
        }

        // Get employee
        $employee = Employee::where('user_id', $user->id)->first();

        // Get job with company
        $job = CompanyJob::with('company')->find($id);

        if (!$job) {
            return response()->json([
                'status'  => false,
                'code'    => 404,
                'message' => 'Job not found'
            ], 404);
        }

        // Check applied status
        $isApplied = JobApplicant::where('employee_id', $user->id)
            ->where('job_id', $job->id)
            ->exists();

        // Check saved status
        $isSaved = $employee
            ? JobSave::where('employee_id', $employee->id)
            ->where('job_id', $job->id)
            ->exists()
            : false;

        // Format output SAME AS LIST
        $formattedJob = [
            "id"               => $job->id,
            "job_title"        => $job->title,
            "job_location"     => $job->location,
            "job_salary"       => $job->salary,
            "job_vacancy"      => $job->vacency,
            "dedline"          => $job->dedline,
            "job_type"         => $job->job_type,
            "requirement"      => $job->requirement ?? [],
            "responsibilities" => $job->responsivilities ?? [],
            "benefits"         => $job->benefits ?? [],
            "image"            => $job->description_image ? url($job->description_image) : null,
            "video"            => $job->description_video ? url($job->description_video) : null,
            "is_applied"       => $isApplied,
            "is_saved"         => $isSaved,
            "created_at"       => $job->created_at,
        ];

        $companyData = [
            "id"          => $job->company->id ?? null,
            "user_id"     => $job->company->user_id ?? null,
            "image_url"   => $job->company->image_url ?? null,
            "name"        => $job->company->name ?? null,
            "location"    => $job->company->location ?? null,
        ];

        return response()->json([
            'status' => true,
            'code'   => 200,
            'data'   => [
                "company" => $companyData,
                "job"     => $formattedJob
            ]
        ]);
    }

    // recommended jobs
    public function recommendedJobList(Request $request)
    {
        $jobs = CompanyJob::with(['company'])->orderBy('created_at', 'desc')->get();

        if ($jobs->isEmpty()) {
            return $this->success('No jobs found');
        }

        return $this->success($jobs, 'Jobs retrieved successfully');
    }
}
