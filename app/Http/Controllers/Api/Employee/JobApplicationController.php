<?php

namespace App\Http\Controllers\Api\Employee;

use App\Helper\Helper;
use App\Http\Controllers\Controller;
use App\Mail\JobAppliedMail;
use App\Models\Company;
use App\Models\CompanyJob;
use App\Models\DeviceToken;
use App\Models\Employee;
use App\Models\FirebaseTokens;
use App\Models\JobApplicant;
use App\Models\JobSave;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Throwable;

class JobApplicationController extends Controller
{

    use ApiResponse;
    // Method to apply for a job
    public function applyJob(Request $request)
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                'status'  => false,
                'code'    => 401,
                'message' => 'Unauthorized'
            ], 401);
        }
        // Validate the request data
        $request->validate([
            'job_id' => 'required|exists:company_jobs,id',
            'full_name' => 'required|string|max:255',
            'resume' => 'required|file|mimes:pdf,doc,docx|max:2048',
            'cell_number' => 'required',
            'email' => 'required'
        ]);

        $company_job = CompanyJob::find($request->job_id);

        if (!$company_job) {
            return $this->error('Job not found', 404);
        }

        $company = Company::find($company_job->company_id);
        if (!$company) {
            return $this->error('Company not found', 404);
        }

        // 🔥 Get employee ID from employee table (Not user table)
        $user = auth('api')->user();
        $employee = Employee::where('user_id', $user->id)->first();

        if (!$employee) {
            return $this->error('Employee not found', 404);
        }

        // 🔥 Check if this employee already applied
        $existingApplication = JobApplicant::where('job_id', $request->job_id)
            ->where('employee_id', $employee->id)
            ->first();

        if ($existingApplication) {
            $existingApplication->resume = url($existingApplication->resume);
            return $this->success($existingApplication, 'You have already applied for this job', 200);
        }

        // Upload resume
        $resumePath = null;
        if ($request->hasFile('resume')) {
            $resumePath = Helper::uploadImage($request->file('resume'), 'resumes');
        }

        // Create new application
        $applicant = JobApplicant::create([
            'company_id'   => $company->id,
            'job_id'       => $request->job_id,
            'employee_id'  => $employee->id, // 🔥 THE CORRECT EMPLOYEE ID
            'full_name'    => $request->full_name,
            'email'        => $request->email,
            'cell_number'  => $request->cell_number,
            'address'      => $request->address ?? null,
            'resume'       => $resumePath,
        ]);

        // Attach full URL for response
        $applicant->resume = url($applicant->resume);

        // =============================================
        // FIREBASE NOTIFICATION → COMPANY USER
        // =============================================
        $companyUserId = $company->user_id;

        // Get all device tokens for that user
        $tokens = DeviceToken::where('user_id', $companyUserId)->pluck('token')->toArray();

        // Notification body includes employee name, email, and cell number
        $notificationBody = $employee->full_name . " applied for your job at {$company->name}";
        $notificationBody .= " (Email: {$applicant->email}, Phone: {$applicant->cell_number})";

        foreach ($tokens as $token) {
            try {
                Helper::sendNotifyMobile($token, [
                    'title' => 'New Job Application',
                    'body'  => $notificationBody,
                    'icon'  => url('path/to/icon.png'), // optional app icon
                ]);
            } catch (NotFound $e) {
                // Token invalid or unregistered → remove it
                Log::warning("FCM token not found: {$token}");
                FirebaseTokens::where('token', $token)->delete();
            } catch (Throwable $e) {
                Log::error("Firebase send failed for token {$token}: {$e->getMessage()}");
            }
        }

        // ===============================
        // EMAIL NOTIFICATION (FIXED)
        // ===============================
        $companyUser = \App\Models\User::find($companyUserId);

        if ($companyUser && $companyUser->email) {
            try {
                Mail::to($companyUser->email)
                    ->send(new JobAppliedMail(
                        $applicant,
                        $company_job,
                        $company
                    ));
            } catch (Throwable $e) {
                Log::error(
                    'Job application email failed: ' . $e->getMessage()
                );
            }
        }

        return $this->success($applicant, 'Job application submitted successfully');
    }


    // job details
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
        $company = Company::first();
        $job = CompanyJob::where('company_id', $company->id)->find($id);
        if (!$job) {
            return response()->json([
                'status' => false,
                'code'   => 404,
                'message' => 'Job not found'
            ]);
        }
        $job->description_image = $job->description_image ? url($job->description_image) : null;
        $job->description_video = $job->description_video ? url($job->description_video) : null;

        return response()->json([
            'status'  => true,
            'code'    => 200,
            'data'    => $job
        ]);
    }

    // user APPLied job List
    public function getAppliedJobs()
    {
        $user = auth('api')->user();

        $employee = $user->employee;


        $applications = JobApplicant::with(['job.company'])
            ->where('employee_id', $employee->id)
            ->get();


        if (!$applications) {
            return response()->json([
                'status'  => false,
                'code'    => 200,
                'message' => 'Job Applicatient not found'
            ]);
        }

        // Format response
        $data = $applications->map(function ($app) {
            $job = $app->job;

            $title = $job->title ?? '';

            // Create abbreviation from job title
            $abbreviation = collect(explode(' ', $title))
                ->filter()
                ->map(fn($word) => strtoupper(substr($word, 0, 1)))
                ->implode('');

            return [
                'id'            => $job->id,
                'job_title'     => $title,
                'abbreviation'  => $abbreviation,
                'company_name'  => $job->company->name ?? null,
                'salary'        => $job->salary,
                'image'         => $job->description_image ? url($job->description_image) : null,
                'video'         => $job->description_video ? url($job->description_video) : null,
                'deadline'      => $job->dedline,
                'job_type'      => $job->job_type,
                'job_location'  => $job->location,
                'is_applied'    => true
            ];
        });

        return response()->json([
            'status'  => true,
            'code'    => 200,
            'data'    => $data
        ]);
    }

    // job preview

    public function appliedJobPreview(Request $request)
    {
        $user = auth('api')->user();
        $employee = $user->employee;

        if (!$employee) {
            return response()->json([
                'status'  => false,
                'message' => 'Employee not found'
            ], 404);
        }

        // ✅ Validate param
        $request->validate([
            'application_id' => 'required|exists:job_applicants,id'
        ]);

        // 🔐 Secure: employee can only view OWN application
        $application = JobApplicant::with('job.company')
            ->where('id', $request->application_id)
            ->where('employee_id', $employee->id)
            ->first();

        if (!$application) {
            return response()->json([
                'status'  => false,
                'message' => 'Applied job not found'
            ], 404);
        }

        $data = [
            'application_id' => $application->id,
            'applied_at'     => $application->created_at->format('d M Y'),

            // Applicant
            'full_name'      => $application->full_name,
            'email'          => $application->email,
            'cell_number'    => $application->cell_number,
            'resume'         => $application->resume ? url($application->resume) : null,

            // Job
            'job' => [
                'id'        => $application->job->id,
                'title'     => $application->job->title,
                'salary'    => $application->job->salary,
                'job_type'  => $application->job->employment_type,
                'location'  => $application->job->location,
                'deadline'  => $application->job->dedline,
                'image'     => $application->job->description_image
                    ? url($application->job->description_image)
                    : null,
                'video'     => $application->job->description_video
                    ? url($application->job->description_video)
                    : null,
            ],

            // Company
            'company' => [
                'name' => $application->job->company->name,
                'logo' => $application->job->company->logo
                    ? url($application->job->company->logo)
                    : null,
            ]
        ];

        return response()->json([
            'status' => true,
            'code'   => 200,
            'data'   => $data
        ]);
    }

    // applied job delete
    public function appliedJobDelete(Request $request)
    {
        $user = auth('api')->user();
        $employee = $user->employee;

        if (!$employee) {
            return response()->json([
                'status'  => false,
                'message' => 'Employee not found'
            ], 404);
        }

        // ✅ Validate param
        $request->validate([
            'application_id' => 'required|exists:job_applicants,id'
        ]);

        // 🔐 Ensure employee owns this application
        $application = JobApplicant::where('id', $request->application_id)
            ->where('employee_id', $employee->id)
            ->first();

        if (!$application) {
            return response()->json([
                'status'  => false,
                'message' => 'Applied job not found or access denied'
            ], 404);
        }

        // 🗑️ Delete resume file (optional but recommended)
        if ($application->resume && file_exists(public_path($application->resume))) {
            @unlink(public_path($application->resume));
        }

        // 🗑️ Delete application
        $application->delete();

        return response()->json([
            'status'  => true,
            'code'    => 200,
            'message' => 'Job application withdrawn successfully'
        ]);
    }


    // Save job
    public function saveJob(Request $request)
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                'status'  => false,
                'code'    => 401,
                'message' => 'Unauthorized'
            ], 401);
        }

        // Get employee record
        $employee = Employee::where('user_id', $user->id)->first();
        if (!$employee) {
            return response()->json([
                'status'  => false,
                'code'    => 404,
                'message' => 'Employee record not found'
            ], 404);
        }

        // Validate request
        $request->validate([
            'job_id' => 'required|exists:company_jobs,id',
        ]);

        $jobId = $request->job_id;

        // Check if already saved
        $existing = JobSave::where('employee_id', $employee->id)
            ->where('job_id', $jobId)
            ->first();

        if ($existing) {
            // Toggle off: remove saved job
            $existing->delete();

            return response()->json([
                'status'  => true,
                'code'    => 200,
                'message' => 'Job removed from saved list',
            ]);
        } else {
            // Toggle on: save job
            $savedJob = JobSave::create([
                'employee_id' => $employee->id,
                'job_id'      => $jobId,
            ]);

            return response()->json([
                'status'  => true,
                'code'    => 201,
                'message' => 'Job saved successfully',
                'data'    => $savedJob
            ]);
        }
    }


    //show save job list

    public function savedJobList(Request $request)
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                'status'  => false,
                'code'    => 401,
                'message' => 'Unauthorized'
            ], 401);
        }

        $employee = Employee::where('user_id', $user->id)->first();
        if (!$employee) {
            return response()->json([
                'status'  => false,
                'code'    => 200,
                'message' => 'Employee record not found'
            ], 404);
        }

        // Get saved jobs with job details
        $savedJobs = JobSave::with('job.company')
            ->where('employee_id', $employee->id)
            ->get()
            ->map(function ($item) {

                $title = $item->job->title ?? '';

                // Generate abbreviation (e.g. "Senior Software Engineer" => "SSE")
                $abbreviation = collect(explode(' ', $title))
                    ->filter()
                    ->map(fn($word) => strtoupper(substr($word, 0, 1)))
                    ->implode('');
                return [
                    'job_id'       => $item->job->id,
                    'job_title'    => $title ?? null,
                    'abbreviation'  => $abbreviation,
                    'company_name' => $item->job->company->name ?? null,
                    'salary'       => $item->job->salary ?? null,
                    'image'        => $item->job->description_image ? url($item->job->description_image) : null,
                    'video'        => $item->job->description_video ? url($item->job->description_video) : null,
                    'deadline'     => $item->job->dedline ?? null,
                    'job_type'     => $item->job->job_type ?? null,
                    'job_location' => $item->job->location ?? null,
                    'is_saved'     => true,
                ];
            });

        if ($savedJobs->isEmpty()) {
            return response()->json([
                'status'  => true,
                'code'    => 200,
                'message' => 'Save job not found'
            ]);
        }


        return response()->json([
            'status'  => true,
            'code'    => 200,
            'message' => 'Saved jobs fetched successfully',
            'data'    => $savedJobs
        ]);
    }

    // Delete joblist

    public function DeleteSaveJob(Request $request, $job_id)
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                'status'  => false,
                'code'    => 401,
                'message' => 'Unauthorized'
            ], 401);
        }

        $employeeId = $user->employee->id;
        // Validate save_id exists
        $saveJob = JobSave::where('job_id', $job_id)
            ->where('employee_id', $employeeId)
            ->first();

        if (!$saveJob) {
            return response()->json([
                'status'   => false,
                'code'     => 404,
                'message'  => 'Saved job not found'
            ]);
        }

        $saveJob->delete();

        return response()->json([
            'status'   => true,
            'code'     => 200,
            'message'  => 'Save job deleted successfully'
        ]);
    }
}
