<?php

namespace App\Http\Controllers\Api\Company;

use App\Helper\Helper;
use App\Http\Controllers\Controller;
use App\Http\Resources\IndividualJobsListResource;
use App\Models\Company;
use App\Models\CompanyJob;
use App\Models\DeviceToken;
use App\Models\Employee;
use App\Models\FirebaseTokens;
use App\Models\JobApplicant;
use App\Models\Payment;
use App\Services\FirebaseNotificationService;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Throwable;
use UnexpectedValueException;


class JobManageController extends Controller
{
    use ApiResponse;

    // Get authenticated company
    private function getAuthenticatedCompany()
    {
        $user = auth('api')->user();
        return Company::where('user_id', $user->id)
            ->select('id', 'user_id', 'image_url', 'name', 'location') // only these fields will be returned
            ->first();
    }

    // Get all jobs
    public function getAllJobs(Request $request)
    {

        $company = $this->getAuthenticatedCompany();

        if (! $company) {
            return $this->error([], 'Company not found.', 404);
        }

        $search = $request->search;

        $jobs = CompanyJob::where('company_id', $company->id)
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {

                    if (is_numeric($search)) {
                        $q->where('salary', '>=', (int) $search);
                    } else {
                        $q->where('title', 'LIKE', '%' . $search . '%')
                            ->orWhere('description', 'LIKE', '%' . $search . '%')
                            ->orWhere('location', 'LIKE', '%' . $search . '%')
                            ->orWhere('job_type', 'LIKE', '%' . $search . '%')
                            ->orWhere('requirement', 'LIKE', '%' . $search . '%')
                            ->orWhere('benefits', 'LIKE', '%' . $search . '%');
                    }
                });
            })
            ->orderBy('created_at', 'desc')
            ->get();

        if ($jobs->isEmpty()) {
            return $this->success([], 'No jobs found for this company.');
        }
        $formattedJobs = $jobs->map(function ($job) {

            $words = preg_split('/\s+/', trim($job->title), -1, PREG_SPLIT_NO_EMPTY);

            $shortForm = '';

            if (count($words) >= 2) {
                // Take first letter of first two words
                $shortForm = strtoupper($words[0][0] . $words[1][0]);
            } elseif (count($words) === 1) {
                // If only one word, take first two letters
                $shortForm = strtoupper(substr($words[0], 0, 2));
            }
            return [
                'id'                 => $job->id,
                'job_title'          => $job->title,
                'short_form'        => $shortForm,
                'job_location'       => $job->location,
                'job_salary'         => $job->salary,
                'job_vacancy'        => $job->vacancy,
                'dedline' => $job->dedline ? Carbon::parse($job->dedline)->format('d F y') : null,
                'job_type'           => $job->job_type,
                'requirement'        => $job->requirement ?? [],
                'video_url'          => $job->description_video ? url($job->description_video) : null,
                'image_url'          => $job->description_image ? url($job->description_image) : null,
                'created_at'         => Carbon::parse($job->created_at)->format('Y-m-d H:i:s'),
            ];
        });

        $data = [
            'company' => $company,
            'jobs'    => $formattedJobs,

        ];

        return $this->success($data, 'Company jobs retrieved successfully.');
    }

    // Get job details
    public function jobDetails($id)
    {
        $company = $this->getAuthenticatedCompany();


        if (! $company) {
            return $this->error([], 'Company not found.', 404);
        }

        $job = CompanyJob::where('company_id', $company->id)->find($id);

        if (! $job) {
            return $this->error([], 'Job not found.', 404);
        }
        // Convert relative paths to full URLs
        $job->description_image = $job->description_image ? url($job->description_image) : null;
        $job->description_video = $job->description_video ? url($job->description_video) : null;

        return $this->success([
            'company' => $company,
            'job'     => $job,
        ], 'Job retrieved successfully.');
    }

    // Store new job
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'              => 'required|string|max:255',
            'job_type'           => 'nullable|string',
            'vacancy'            => 'nullable|integer',
            'description'        => 'nullable|string',
            'location'           => 'nullable|string',
            'salary'             => 'nullable',
            'salary_type'        => 'nullable',

            'benefits' => 'nullable|array',
            'benefits.*' => 'nullable|string',

            'year_of_experience' => 'nullable|string',
            'certification'      => 'nullable|string',
            'education'          => 'nullable|string',
            'employment_type'    => 'nullable|string',
            'vacency'            => 'nullable|string',
            // 🔥 Convert these to array fields
            'responsivilities'   => 'nullable|array',
            'responsivilities.*' => 'nullable|string',

            'requirement'        => 'nullable|array',
            'requirement.*'      => 'nullable|string',

            'description_image'  => 'nullable|image',
            'description_video'  => 'nullable|file',
            'dedline'            => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation failed.', 422);
        }

        $company = $this->getAuthenticatedCompany();

        if (! $company) {
            return $this->error([], 'Company not found.', 200);
        }

        $user = $company->user;
        // $accountAge = now()->diffInDays($user->created_at);
        $accountAge = now()->floatDiffInDays($user->created_at, false); // Signed float


        $jobsCount = CompanyJob::where('company_id', $company->id)->count();

        // $needsPayment = false;
        // if ($accountAge <= 30 && $jobsCount >= 5) {
        //     $needsPayment = true;
        // } elseif ($accountAge > 30) {
        //     $needsPayment = true;
        // }

        // if ($needsPayment) {
        //     \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

        //     $paymentIntent = \Stripe\PaymentIntent::create([
        //         'amount' => 1000, // $10 in cents
        //         'currency' => 'usd',
        //         'metadata' => [
        //             'company_id' => $company->id,
        //             'job_data'   => json_encode($request->all()),
        //         ],
        //     ]);

        //     return response()->json([
        //         'requires_payment' => true,
        //         'client_secret'    => $paymentIntent->client_secret,
        //     ]);
        // }

        // Free posting
        $job = $this->createJob($request, $company);

        $jobseekersTokens = FirebaseTokens::whereHas('user', function ($q) {
            $q->where('role', 'jobseeker');
        })->pluck('token')->toArray();

        // dd($jobseekersTokens);
        foreach ($jobseekersTokens as $token) {
            try {
                Helper::sendNotifyMobile($token, [
                    'title' => 'New Job Posted',
                    'body'  => $job->title . ' at ' . $company->name . ' is now available.',
                    'icon'  => url('path/to/icon.png'),
                ]);
            } catch (NotFound $e) {
                // Token is invalid or unregistered
                Log::warning("FCM token not found: {$token}");
                // Optionally remove it from DB
                FirebaseTokens::where('token', $token)->delete();
            } catch (Throwable $e) {
                Log::error("Firebase send failed for token {$token}: {$e->getMessage()}");
            }
        }

        return $this->success($job, 'Job created successfully.', 201);
    }


    protected function createJob($request, $company)
    {
        $benefitsArray = null;
        if ($request->filled('benefits') && !empty($request->benefits[0])) {
            $benefitsArray = array_map('trim', explode(',', $request->benefits[0]));
        }

        $responsibilitiesArray = null;
        if ($request->filled('responsivilities') && !empty($request->responsivilities[0])) {
            $responsibilitiesArray = array_map('trim', explode(',', $request->responsivilities[0]));
        }

        $requirementsArray = null;
        if ($request->filled('requirement') && !empty($request->requirement[0])) {
            $requirementsArray = array_map('trim', explode(',', $request->requirement[0]));
        }

        $jobData = [
            'company_id'         => $company->id,
            'title'              => $request->title,
            'description'        => $request->description,
            'location'           => $request->location,
            'salary'             => $request->salary,
            'salary_type'        => $request->salary_type,
            'benefits'           => $benefitsArray,
            'year_of_experience' => $request->year_of_experience,
            'certification'      => $request->certification,
            'education'          => $request->education,
            'employment_type'    => $request->employment_type,
            'vacency'            => $request->vacency,
            'requirement'        => $requirementsArray,
            'responsivilities'   => $responsibilitiesArray,
            'dedline'            => $request->dedline,
            'job_type'          => $request->job_type,
            'vacancy'           => $request->vacancy,
        ];

        if ($request->hasFile('description_image')) {
            $imagePath = Helper::uploadImage($request->file('description_image'), 'companyjobs/image');
            $jobData['description_image'] = $imagePath;
        }

        // Video
        if ($request->hasFile('description_video')) {
            $jobData['description_video'] = Helper::fileUpload($request->file('description_video'), 'companyjobs/videos', 'description_video_' . time());
        }

        return CompanyJob::create($jobData);
    }


    // webhook
    public function handleStripeWebhook(Request $request)
    {
        $payload    = $request->getContent();
        $sigHeader  = $request->server('HTTP_STRIPE_SIGNATURE');
        $endpointSecret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                $endpointSecret
            );
        } catch (UnexpectedValueException $e) {
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (SignatureVerificationException $e) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        if ($event->type === 'payment_intent.succeeded') {
            $paymentIntent = $event->data->object;

            $companyId = $paymentIntent->metadata->company_id;
            $jobData   = json_decode($paymentIntent->metadata->job_data, true);

            // Create job
            $jobData['company_id'] = $companyId;
            $job = $this->createJob(new Request($jobData), (object)['id' => $companyId]);

            // Store payment history
            Payment::create([
                'company_id'        => $companyId,
                'job_id'            => $job->id,
                'payment_intent_id' => $paymentIntent->id,
                'currency'          => $paymentIntent->currency,
                'amount'            => $paymentIntent->amount,
                'status'            => $paymentIntent->status,
                'meta_data'         => $paymentIntent,
            ]);
        }

        return response()->json(['status' => 'success']);
    }


    // Update job
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title'              => 'nullable|string|max:255',
            'description'        => 'nullable|string',
            'location'           => 'nullable|string',
            'salary'             => 'nullable',
            'salary_type'        => 'nullable',
            'benefits'           => 'nullable|array',
            'benefits.*'         => 'nullable|string',
            'year_of_experience' => 'nullable|string',
            'certification'      => 'nullable|string',
            'education'          => 'nullable|string',
            'employment_type'    => 'nullable|string',
            'vacency'            => 'nullable|string',
            'responsivilities'   => 'nullable|array',
            'responsivilities.*' => 'nullable|string',
            'requirement'        => 'nullable|array',
            'requirement.*'      => 'nullable|string',
            'description_image'  => 'nullable|image|mimes:jpg,jpeg,png',
            'description_video'  => 'nullable|file|mimes:mp4,mov,avi,wmv',
            'dedline'            => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 422);
        }

        $company = $this->getAuthenticatedCompany();
        if (!$company) {
            return $this->error([], 'Company not found.', 200);
        }

        $job = CompanyJob::where('company_id', $company->id)->find($id);
        if (!$job) {
            return $this->error([], 'Job not found.', 200);
        }

        // Convert comma-separated strings to arrays if provided
        $benefitsArray = $request->filled('benefits')
            ? array_map('trim', explode(',', $request->benefits[0] ?? ''))
            : $job->benefits;

        $responsibilitiesArray = $request->filled('responsivilities')
            ? array_map('trim', explode(',', $request->responsivilities[0] ?? ''))
            : $job->responsivilities;

        $requirementsArray = $request->filled('requirement')
            ? array_map('trim', explode(',', $request->requirement[0] ?? ''))
            : $job->requirement;

        // Handle image upload
        if ($request->hasFile('description_image')) {
            if ($job->description_image && file_exists(public_path($job->description_image))) {
                unlink(public_path($job->description_image));
            }
            $job->description_image = Helper::fileUpload($request->file('description_image'), 'companyjobs/image', 'description_image_' . time());
        }

        // Handle video upload
        if ($request->hasFile('description_video')) {
            if ($job->description_video && file_exists(public_path($job->description_video))) {
                unlink(public_path($job->description_video));
            }
            $job->description_video = Helper::fileUpload($request->file('description_video'), 'companyjobs/videos', 'description_video_' . time());
        }

        // Update job data
        $job->update([
            'title'              => $request->title ?? $job->title,
            'description'        => $request->description ?? $job->description,
            'location'           => $request->location ?? $job->location,
            'salary'             => $request->salary ?? $job->salary,
            'salary_type'        => $request->salary_type ?? $job->salary_type,
            'benefits'           => $benefitsArray,
            'year_of_experience' => $request->year_of_experience ?? $job->year_of_experience,
            'certification'      => $request->certification ?? $job->certification,
            'education'          => $request->education ?? $job->education,
            'employment_type'    => $request->employment_type ?? $job->employment_type,
            'vacency'            => $request->vacency ?? $job->vacency,
            'responsivilities'   => $responsibilitiesArray,
            'requirement'        => $requirementsArray,
            'dedline'            => $request->dedline ?? $job->dedline,
            'job_type'          => $request->job_type ?? $job->job_type,
        ]);

        return $this->success($job, 'Job updated successfully.', 200);
    }



    public function jobApplicants(Request $request, $id)
    {

        $company = $this->getAuthenticatedCompany();

        if (! $company) {
            return $this->error([], 'Company not found.', 200);
        }

        $job = CompanyJob::where('company_id', $company->id)->find($id);

        if (! $job) {
            return $this->error([], 'Job not found.', 200);
        }

        $years_of_experience = $request->years_of_experience;
        $job_category_ids    = $request->job_category_ids;
        $certification       = $request->certification;
        $location            = $request->location;
        $age                 = $request->age;
        $search              = $request->search;

        $ageRange = explode('-', $age);
        $minAge   = isset($ageRange[0]) ? (int) $ageRange[0] : null;
        $maxAge   = isset($ageRange[1]) ? (int) $ageRange[1] : null;

        // dd($minAge, $maxAge);

        $yearRange     = explode('-', $years_of_experience);
        $minExperience = isset($yearRange[0]) ? (int) $yearRange[0] : null;
        $maxExperience = isset($yearRange[1]) ? (int) $yearRange[1] : null;
        // dd($minExperience, $maxExperience);

        $applicantsQuery = $job->jobApplicants()->with(['employee.experiences', 'employee.user']);

        if ($location || ! empty($job_category_ids) || $minAge || $maxAge || $years_of_experience || $certification) {
            $applicantsQuery->whereHas('employee', function ($query) use ($location, $job_category_ids, $minAge, $maxAge, $minExperience, $maxExperience, $certification) {

                if ($location) {
                    $query->where('address', 'LIKE', '%' . $location . '%');
                }

                $min = min($minAge, $maxAge);
                $max = max($minAge, $maxAge);

                if ($minAge && $maxAge) {
                    $query->whereHas('user', function ($q) use ($min, $max) {
                        $q->whereBetween(DB::raw('CAST(age AS UNSIGNED)'), [$min, $max]);
                    });
                } elseif ($minAge) {
                    $query->whereHas('user', function ($q) use ($minAge) {
                        $q->where(DB::raw('CAST(age AS UNSIGNED)'), '>=', $minAge);
                    });
                } elseif ($maxAge) {
                    $query->whereHas('user', function ($q) use ($maxAge) {
                        $q->where(DB::raw('CAST(age AS UNSIGNED)'), '<=', $maxAge);
                    });
                }

                // Filter by job category if provided
                if (! empty($job_category_ids)) {
                    $query->whereHas('employee_job_categories', function ($q) use ($job_category_ids) {
                        $q->whereIn('job_category_id', $job_category_ids);
                    });
                }

                // Filter by years of experience if provided

                $minEx = min($minExperience, $maxExperience);
                $maxEx = max($minExperience, $maxExperience);

                if (isset($minExperience) && isset($maxExperience)) {
                    $query->whereBetween(DB::raw('CAST(year_of_experice AS UNSIGNED)'), [$minEx, $maxEx]);
                } elseif (isset($minExperience)) {
                    $query->where(DB::raw('CAST(year_of_experice AS UNSIGNED)'), '>=', $minExperience);
                } elseif (isset($maxExperience)) {
                    $query->where(DB::raw('CAST(year_of_experice AS UNSIGNED)'), '<=', $maxExperience);
                }

                // Filter by job category if provided
                if (! empty($job_category_ids)) {
                    $query->whereHas('employee_job_categories', function ($q) use ($job_category_ids) {
                        $q->whereIn('job_category_id', $job_category_ids);
                    });
                }

                // Filter by certification if provided
                if ($certification) {
                    $query->whereHas('certifications', function ($q) use ($certification) {
                        $q->where('name', 'LIKE', '%' . $certification . '%')
                            ->orWhere('issue_organization', 'LIKE', '%' . $certification . '%')
                            ->orWhere('creadential_id', 'LIKE', '%' . $certification . '%');
                    });
                }
            });
        }

        // Get the filtered applicants
        $applicants = $applicantsQuery
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('full_name', 'LIKE', '%' . $search . '%')
                        ->orWhere('email', 'LIKE', '%' . $search . '%')
                        ->orWhere('address', 'LIKE', '%' . $search . '%')
                        ->orWhere('cell_number', 'LIKE', '%' . $search . '%');
                });
            })

            ->get();

        if ($applicants->isEmpty()) {
            return $this->success([], 'No applicants found for the given criteria.');
        }

        // Format the applicants data
        $formattedApplicants = $applicants->map(function ($applicant) {
            $job             = $applicant->job;
            $firstExperience = $applicant->employee->experiences->first();

            return [
                'id'          => $applicant->id,
                'job_title'   => $job->title,
                'full_name'   => $applicant->full_name,
                'email'       => $applicant->email,
                'cell_number' => $applicant->cell_number,
                'address'     => $applicant->address,
                'resume'      => url($applicant->resume),
                'employee'    => [
                    'id'                  => $applicant->employee->id,
                    'avatar'              => $applicant->employee->image_url ? url($applicant->employee->image_url) : null,

                    'age'                 => $applicant->employee->user->age ? (int) $applicant->employee->user->age : null,
                    'years_of_experience' => $applicant->employee->year_of_experice ? (int) $applicant->employee->year_of_experice : null,
                ],
            ];
        });

        return $this->success([
            'applicants' => $formattedApplicants,
        ], 'Job applicants retrieved successfully.');
    }

    // Calculate years of experience
    private function calculateYearsOfExperience($experience)
    {
        if (! $experience) {
            return 0;
        }

        $start = Carbon::parse($experience->start_date);
        $end   = $experience->end_date ? Carbon::parse($experience->end_date) : now();

        return floor($start->diffInYears($end));
    }

    public function jobApplicantDetails($employeeId)
    {
        $company = $this->getAuthenticatedCompany();

        if (! $company) {
            return $this->error([], 'Company not found.', 404);
        }

        $applicant = $company->companyJobs()
            ->whereHas('jobApplicants', function ($query) use ($employeeId) {
                $query->where('employee_id', $employeeId);
            })
            ->first()
            ->jobApplicants()
            ->where('employee_id', $employeeId)
            ->with(['employee.experiences', 'employee.user'])
            ->first();

        if (! $applicant) {
            return $this->error([], 'Applicant not found.', 404);
        }

        $firstExperience = $applicant->employee->experiences->first();

        $data = [
            'id'          => $applicant->id,
            'full_name'   => $applicant->full_name,
            'email'       => $applicant->email,
            'cell_number' => $applicant->cell_number,
            'address'     => $applicant->address,
            'resume'      => $applicant->resume ? url($applicant->resume) : null,

            'employee'    => [
                'id'                      => $applicant->employee->id,
                'user_id'                 => $applicant->employee->user->id,
                'avatar'                  => $applicant->employee->image_url ? url($applicant->employee->image_url) : null,
                'age'                     => optional($applicant->employee->user)->date_of_birth
                    ? Carbon::parse($applicant->employee->user->date_of_birth)->age
                    : null,
                'years_of_experience'     => $applicant->employee->year_of_experice
                    ? (int) $applicant->employee->year_of_experice
                    : null,

                'specializations'         => $applicant->employee->specializations->map(function ($spe) {

                    $specialize = $spe->specialize;

                    return [
                        'id'   => $specialize->id,
                        'name' => $specialize->name,

                    ];
                }),

                'experiences'             => $applicant->employee->experiences->map(function ($exp) {
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

                'qualifications'          => $applicant->employee->qualifications->map(function ($exp) {
                    return [
                        'id'             => $exp->id,
                        'institute_name' => $exp->institute_name,
                        'qaulification'  => $exp->qaulification,
                        'start_date'     => $exp->start_date,
                        'end_date'       => $exp->end_date,
                        'description'    => $exp->description,

                    ];
                }),

                'certifications'          => $applicant->employee->certifications->map(function ($cert) {
                    return [
                        'name'               => $cert->name,
                        'date_issue'         => $cert->date_issue,
                        'issue_organization' => $cert->issue_organization,
                        'credential_id'      => $cert->creadential_id,
                    ];
                }),

                'employee_job_categories' => $applicant->employee->employee_job_categories->map(function ($job_cate) {

                    $category = $job_cate->job_category;

                    return [
                        'id'   => $category->id,
                        'name' => $category->title,

                    ];
                }),

            ],
        ];

        return $this->success($data, 'Applicant details retrieved successfully.');
    }

    public function employee_details($id)
    {

        $employee = Employee::with(['experiences', 'certifications', 'specializations', 'qualifications', 'employee_job_categories'])

            ->where('id', $id)
            ->first();

        if (! $employee) {
            return $this->error('Employee profile not found.', 404);
        }

        $yearsOfExperience = $this->calculateYearsOfExperience($employee->experiences->first());

        $user = auth('api')->user();

        $data = [
            'user_id'                 => auth('api')->user()->id,
            'employee_id'             => $employee->id,
            'employee_name'           => $employee->user->name,
            'email'                   => $employee->user->email,
            'phone_number'            => $employee->user->phone_number,
            'date_of_birth'           => $employee->user->date_of_birth,
            'age'                     => $employee->user->age,
            'location'                => $employee->location,
            'bio'                     => $employee->bio,
            'image_url'               => $employee->image_url ? url($employee->image_url) : null,
            'years_of_experience'     => $employee->year_of_experice
                ? (int) $employee->year_of_experice
                : null,

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


    // job details
    public function jobDelete($id)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json([
                'status'  => true,
                'code'    => 401,
                'message' => 'Unauthorized'
            ]);
        }
        $company = $user->company;
        if (!$company) {
            return null;
        }
        $companyJob = CompanyJob::where('company_id', $company->id)->where('id', $id)->firstOrFail();

        if (!$companyJob) {
            return response()->json([
                'status' => false,
                'code'   => 404,
                'message' => 'Job not found'
            ]);
        }

        $companyJob->delete();
        return response()->json([
            'status'   => true,
            'code'     => 200,
            'message'  => 'Company Job Delete successfully'
        ]);
    }

    // applicant list
    public function companyJobApplicants(Request $request)
    {
        // Get authenticated company
        $company = $this->getAuthenticatedCompany();

        if (!$company) {
            return $this->error([], 'Company not found.', 404);
        }

        // Get all jobs for this company
        $jobs = CompanyJob::where('company_id', $company->id)->pluck('id'); // Get job IDs

        if ($jobs->isEmpty()) {
            return $this->success([], 'No jobs found for this company.');
        }

        // Get all applicants for these jobs
        $applicants = JobApplicant::with(['job', 'employee.user'])
            ->whereIn('job_id', $jobs)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($applicants->isEmpty()) {
            return $this->success([], 'No applicants found.');
        }

        // Format response
        $formatted = $applicants->map(function ($applicant) {
            return [
                'id'           => $applicant->id,
                'job_id'       => $applicant->job_id,
                'job_title'    => $applicant->job->title ?? null,
                'employee_name' => $applicant->employee->user->name ?? $applicant->full_name,
                'email'        => $applicant->email,
                'cell_number'  => $applicant->cell_number,
                'address'      => $applicant->address,
                'resume'       => $applicant->resume ? url($applicant->resume) : null,
                'applied_at'   => $applicant->created_at->toDateTimeString(),
            ];
        });

        return $this->success($formatted, 'Applicants retrieved successfully.');
    }

    // jobvideo

    public function getCompanyAllJobs(Request $request)
    {

        $company = $this->getAuthenticatedCompany();

        if (! $company) {
            return $this->error([], 'Company not found.', 404);
        }

        $search = $request->search;

        $jobs = CompanyJob::where('company_id', $company->id)
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {

                    if (is_numeric($search)) {
                        $q->where('salary', '>=', (int) $search);
                    } else {
                        $q->where('title', 'LIKE', '%' . $search . '%')
                            ->orWhere('description', 'LIKE', '%' . $search . '%')
                            ->orWhere('location', 'LIKE', '%' . $search . '%')
                            ->orWhere('job_type', 'LIKE', '%' . $search . '%')
                            ->orWhere('requirement', 'LIKE', '%' . $search . '%')
                            ->orWhere('benefits', 'LIKE', '%' . $search . '%');
                    }
                });
            })
            ->orderBy('created_at', 'desc')
            ->get();

        if ($jobs->isEmpty()) {
            return $this->success([], 'No jobs found for this company.');
        }
        $formattedJobs = $jobs->map(function ($job) {
            return [
                'id'                 => $job->id,
                'job_title'          => $job->title,
                'job_location'       => $job->location,
                'job_salary'         => $job->salary,
                'job_vacancy'        => $job->vacency,
                'dedline' => $job->dedline ? Carbon::parse($job->dedline)->format('d F y') : null,
                'job_type'           => $job->employment_type,
                'requirement'        => $job->requirement ?? [],
                'job_video'          => $job->description_video ? url($job->description_video) : null,
                'job_image'          => $job->description_image ? url($job->description_image) : null,
                'created_at'         => Carbon::parse($job->created_at)->format('Y-m-d H:i:s'),
            ];
        });

        $data = [
            'company' => $company,
            'jobs'    => $formattedJobs,

        ];

        return $this->success($data, 'Company jobs retrieved successfully.');
    }

    // applicants list job wise

    public function jobApplicantList(Request $request, $id)
    {
        $company = $this->getAuthenticatedCompany();

        if (! $company) {
            return $this->error([], 'Company not found.', 404);
        }

        $job = CompanyJob::with(['jobApplicants.employee'])
            ->where('company_id', $company->id)
            ->where('id', $id)
            ->first();




        if (! $job) {
            return $this->error([], 'Job not found', 404);
        }

        return response()->json([
            'status'  => true,
            'code'    => 200,
            'message' => 'Applicants fetch successfully!',
            'data'    => IndividualJobsListResource::collection($job->jobApplicants)
        ]);
    }
}
