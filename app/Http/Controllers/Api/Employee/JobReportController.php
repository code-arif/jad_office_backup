<?php

namespace App\Http\Controllers\Api\Employee;

use App\Models\JobReport;
use App\Models\CompanyJob;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class JobReportController extends Controller
{
   public function jobReport(Request $request)
   {
    $user = auth('api')->user();
    if(!$user)
    {
        return response()->json([
            'status'   =>false,
            'code'     =>401,
            'message'  => 'Unauthorized'
         ]);
    }

    $employee = $user->employee;
    if(!$employee)
    {
        return response()->json([
            'status'  =>false,
            'code'    => 200,
            'message' => 'Employee not found'
        ]);
    }
        // Validate request
        $validator = Validator::make($request->all(), [
            'job_id'    => 'required|exists:company_jobs,id',
            'comments'  => 'required|string|max:1000',
            'subjects'   => 'nullable|string|max:255',  // single string
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'code' => 422,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $company = CompanyJob::where('job_id', $request->job_id)->first();
        if(!$company)
        {
            return response()->json([
                'status'  => false,
                'code'    => 404,
                'message' => 'Company Not found'
            ]);
        }

        $jobReport = JobReport::create([
            'job_id'      => $request->job_id,
            'employee_id' => $employee->id,
            'comments'    => $request->comments,
            'subjects'     => $request->subjects ?? null,  // single string
                      // single file path
        ]);

        return response()->json([
            'status' => true,
            'code' => 200,
            'message' => 'Job report submitted successfully',
            'data' => $jobReport
        ], 201);
    }
}
