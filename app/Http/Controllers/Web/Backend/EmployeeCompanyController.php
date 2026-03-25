<?php

namespace App\Http\Controllers\Web\Backend;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class EmployeeCompanyController extends Controller
{
    //
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = User::where('role', 'employee')->get();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('name', function ($data) {
                    return $data->name;
                })
                ->addColumn('email', function ($data) {
                    return $data->email;
                })
                ->addColumn('phone', function ($data) {
                    return $data->phone_number;
                })
                ->addColumn('status', function ($data) {
                    return ''; // or remove if you don't use
                })
                ->addColumn('action', function ($data) {
                    $viewUrl = route('admin.employees.show', $data->id);
                    $editUrl = '#'; // Or use route('admin.employees.edit', $data->id) if you have it
                    $deleteOnclick = "showDeleteConfirm(" . $data->id . ")";

                    return '<div class="btn-group btn-group-sm" role="group" aria-label="Basic example">
                <a href="' . $viewUrl . '" class="btn btn-info fs-14 text-white" title="View">
                    <i class="fe fe-eye"></i>
                </a>


            </div>';
                })

                ->rawColumns(['action']) // remove status since empty
                ->make();
        }

        return view("backend.layouts.employeecompany.employee-index");
    }
    public function show($id)
    {
        // Find employee by ID
        $user_employee = Employee::with(['certifications','experiences', 'qualifications','specializations','employee_job_categories'])->where('user_id', $id)->first();


        return view('backend.layouts.employeecompany.employee-show', compact('user_employee'));
    }


    public function index2(Request $request)
    {
        if ($request->ajax()) {
            $data = User::where('role', 'company')->get();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('name', function ($data) {
                    return $data->name;
                })
                ->addColumn('email', function ($data) {
                    return $data->email;
                })
                ->addColumn('phone', function ($data) {
                    return $data->phone_number;
                })
                ->addColumn('status', function ($data) {
                    return ''; // or remove if you don't use
                })
                ->addColumn('action', function ($data) {
                    $viewUrl = route('admin.company.show', $data->id);
                    $jobDetailsUrl = route('admin.company.jobs', $data->id); // Make sure this route exists
                    $deleteOnclick = "showDeleteConfirm(" . $data->id . ")";

                    return '
        <div class="btn-group btn-group-sm" role="group" aria-label="Basic example">
            <a href="' . $viewUrl . '" class="btn btn-info fs-14 text-white" title="View">
                <i class="fe fe-eye"></i>
            </a>
            <a href="' . $jobDetailsUrl . '" class="btn btn-primary fs-14 text-white" title="Job Details">
                <i class="fe fe-briefcase"></i> Job Details
            </a>
        </div>
    ';
                })


                ->rawColumns(['action']) // remove status since empty
                ->make();
        }

        return view("backend.layouts.employeecompany.company-index");
    }

    public function show2($id)
    {
        // Find employee by ID
        $company = User::where('role', 'company')->findOrFail($id);

        return view('backend.layouts.employeecompany.company-show', compact('company'));
    }
    public function showJobs($companyId)
    {
        $company = User::where('role', 'company')->findOrFail($companyId);

        $jobs = $company->company->CompanyJobs ?? [];
        $jobs = collect($jobs);


        return view('backend.layouts.employeecompany.company-jobs', compact('company', 'jobs'));
    }
}
