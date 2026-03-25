<?php

namespace App\Http\Controllers\Api\Employee;

use App\Models\User;
use App\Models\Report;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Mail\SendReportMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class ReportController extends Controller
{
    use ApiResponse;
    public function report(Request $request)
    {

       
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string',
            'last_name' => 'nullable|string',
            'email' => 'required|email',
        
        
        ]);


        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 200);
        }

        $report = new Report();
        $report->first_name = $request->first_name;
        $report->last_name = $request->last_name;
        $report->email = $request->email;

        $report->number = $request->number;
        $report->subject = $request->subject;
        $report->message = $request->message;
        $report->save();

        // Send email to admin
        $admin = User::where('role', 'admin')->first();

        // Mail::to($admin->email)->send(new SendReportMail($report));

        

        
        return $this->success($report, 'Report submitted successfully', 200);



    }
}
