<?php

namespace App\Http\Controllers\Api\User\Auth;

use App\Helper\Helper;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UserProfileController extends Controller
{
    use ApiResponse;

    public function updateProfile(Request $request)
    {

        try {
            $validator = Validator::make($request->all(), [
                'name'                 => ['nullable', 'string', 'max:255'],
                'role'                 => ['nullable'],
                'avatar'               => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:5120'],

                'date_of_birth'        => ['nullable', 'date'],
                'location'             => ['nullable', 'string'],
                'company_name'         => ['nullable', 'string'],
                'company_bio'          => ['nullable', 'string'],
                'size'                 => ['nullable', 'string'],
                'head_office_location' => ['nullable', 'string'],
                'separate_email'       => ['nullable', 'string'],
                'abn'                  => ['nullable', 'string'],
                'industry_type'        => ['nullable', 'string'],

            ]);

            if ($validator->fails()) {
                return $this->error($validator->errors(), 'Validation Failed', 422);
            }


            $user = auth('api')->user();
            if (! $user) {
                return response()->json([
                    'status'  => false,
                    'code'    => 401,
                    'message' => 'Unauthorized.',

                ]);
            }

            // Note: Hey Chagur ( HONEY RASHED WANT TO YOU ---> HE ALWAYS MISS YOU AND MISS YOU WORK CONVERSATION. AND HE ALWAYS MISS YOUR VOICE )  A LITTLE MESSAG BY ZOBAYER HOSEN


            // Handle user avatar
            if ($request->hasFile('avatar')) {
                if ($user->avatar) {
                    Helper::deleteImage($user->avatar);
                }
                $avatar = Helper::uploadImage($request->file('avatar'), 'profile');
            }

            // Update user fields
            $user->name          = $request->input('name', $user->name);
            $user->date_of_birth = $request->input('date_of_birth', $user->date_of_birth);
            $user->avatar        = $avatar ?? $user->avatar;

            // ✅ Update role ONLY if role exists in request
            if ($request->has('role') && $request->role !== null) {
                $user->role = $request->role;
            }

            Log::info('Role in request: ' . $request->role);

            $user->save();
            Log::info('User role after save: ' . $user->role);
            // Update or create company
            $company = Company::where('user_id', $user->id)->first();

            // Retrieve existing company or create a new one
            $company = Company::firstOrNew(['user_id' => $user->id]);

            // Now you can safely update properties
            $company->location = $request->input('location', $company->location);
            $company->name = $request->input('company_name', $company->name);
            $company->bio = $request->input('company_bio', $company->bio);
            $company->display_name = $request->input('company_display_name', $company->display_name);
            $company->size = $request->input('size', $company->size);
            $company->head_office_location = $request->input('head_office_location', $company->head_office_location);
            $company->separate_email = $request->input('separate_email', $company->separate_email);
            $company->abn = $request->input('abn', $company->abn);
            $company->industry_type = $request->input('industry_type', $company->industry_type);
            $company->image_url = $avatar ?? $company->image_url;

            $company->user_id = $user->id;  // Make sure to set user_id on new company

            $company->save();


            // Response data
            $userData = [
                'id'                   => $user->id,
                'name'                 => $user->name,
                'email'                => $user->email,
                'avatar'               => $user->company ? $user->company->image_url : $user->avatar,
                'date_of_birth'        => $user->date_of_birth,
                'location'             => $company->location,
                'company_name'         => $company->name,
                'company_bio'          => $company->bio,
                'industry_type'        => $company->industry_type,
                'size'                 => $company->size,
                'abn'                  => $company->abn,
                'head_office_location' => $company->head_office_location,
                'separate_email'       => $company->separate_email,
                'role'                 => $user->role,

            ];

            return $this->success($userData, 'Profile updated successfully.', 200);
        } catch (\Exception $e) {
            Log::error('Profile update failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error([], 'An unexpected error occurred. Please try again.', 500);
        }
    }

    public function updatePassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'current_password' => ['required', 'string'],
                'password'         => ['required', 'string', 'min:8', 'confirmed'],
            ]);

            if ($validator->fails()) {
                return $this->error([], $validator->errors()->first(), 422);
            }

            $user = auth('api')->user();

            if (! Hash::check($request->current_password, $user->password)) {
                return $this->error([], 'Current password is incorrect.', 403);
            }

            $user->password = Hash::make($request->password);
            $user->save();

            return $this->success(true, 'Password updated successfully.', 200);
        } catch (Exception $e) {
            Log::error('Password update failed: ' . $e->getMessage());
            return $this->error([], $e->getMessage(), 500);
        }
    }

    public function updateAvatar(Request $request)
    {
        try {
            $request->validate([
                'avatar' => ['required', 'image', 'mimes:jpeg,png,jpg,gif', 'max:5120'],
            ]);

            $user = Auth::user();

            if ($user->avatar) {
                Helper::deleteImage($user->avatar);
            }

            if ($request->hasFile('avatar')) {
                $image        = $request->file('avatar');
                $imagePath    = Helper::uploadImage($image, 'profile');
                $user->avatar = $imagePath;
            }

            $user->save();

            $updatedUser = User::select('id', 'avatar')->find(auth('api')->id());

            return $this->success($updatedUser, 'Avatar updated successfully.', 200);
        } catch (Exception $e) {

            Log::error('Avatar update failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->error([], 'An unexpected error occurred. Please try again.', 500);
        }
    }

    public function me()
    {
        try {
            $user = auth('api')->user();

            if (!$user) {
                return response()->json([
                    'status'  => false,
                    'code'    => 401,
                    'message' => 'Unauthorized.',
                ]);
            }

            $userData = [
                'id'            => $user->id,
                'name'          => $user->name,
                'email'         => $user->email,
                'avatar'        => $user->avatar ?? null,
                'date_of_birth' => $user->date_of_birth ?? null,
                'location'      => $user->location ?? null,
                'phone_number'  => $user->phone_number ?? null,
            ];

            // Add role-based data
            if ($user->role === 'company') {
                $company = Company::where('user_id', $user->id)->first();

                if ($company) {
                    $userData['company'] = $company; // all company fields
                }
            } elseif ($user->role === 'jobseeker') {
                $employee = Employee::where('user_id', $user->id)->first();

                if ($employee) {
                    $userData['employee'] = $employee; // all employee fields
                }
            }

            return $this->success($userData, 'User Profile Retrieved successfully', 200);
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage(), 500);
        }
    }


    public function deleteProfile()
    {
        $user = User::find(auth('api')->id());

        if (! $user) {
            return response()->json([
                'status'  => false,
                'code'    => 401,
                'message' => 'Unauthorized.',

            ]);
        }

        if ($user->avatar) {
            Helper::deleteImage($user->avatar);
        }

        $user->delete();
        return $this->success(true, 'Profile deleted successfully', 200);
    }

    public function employee_profile()
    {
        try {
            $user = auth('api')->user();

            if (! $user) {
                return response()->json([
                    'status'  => false,
                    'code'    => 401,
                    'message' => 'Unauthorized.',

                ]);
            }
            // company

            $employee = $user->employee;

            $company = $user->company;

            $userData = [

                'id'                      => $user->id,
                'name'                    => $user->name,
                'email'                   => $user->email,
                'phone'                   => $user->phone_number,
                'avatar'                  => $user->employee ? $user->employee->image_url : $user->avatar,
                'resume'                   => $user && $user->resume ? url($user->resume) : null,
                'date_of_birth'           => $user->date_of_birth ?? null,
                'location'                => $employee && $employee->location ? $employee->location : null,
                'bio'                     => $employee && $employee->bio ? $employee->bio : null,
                'role'                    => $user->role,


                // Employee presence flags
                'employee_profile'        => $employee ? true : false,
                'employee_location'       => $employee && $employee->location ? true : false,
                'employee_specialize'     => $employee && ! $employee->specializations->isEmpty() ? true : false,
                'employee_job_categories' => $employee && ! $employee->employee_job_categories->isEmpty() ? true : false,

                // Company presence flags
                'company_image'           => $company && $company->image_url ? true : false,
                'company_specialize'      => $company && $company->company_specializes ? true : false,
                

            ];

            return $this->success($userData, 'Employee Profile Retrived successfull', 200);
        } catch (Exception $e) {
            return $this->error([], $e->getMessage(), 500);
        }
    }
    //
    // employee profile update
    public function employee_profile_update(Request $request)
    {
        try {

            // Validate fields
            $validator = Validator::make($request->all(), [
                'name'          => ['nullable', 'string', 'max:255'],
                'avatar'        => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:5120'],
                'date_of_birth' => ['nullable', 'date'],
                'location'      => ['nullable', 'string'],
                'bio'           => ['nullable', 'string'],
                'phone_number'  => ['nullable', 'string'],
            ]);

            if ($validator->fails()) {
                return $this->error($validator->errors(), 'Validation Failed', 422);
            }

            $user = auth('api')->user();
            if (! $user) {
                return response()->json([
                    'status'  => false,
                    'code'    => 401,
                    'message' => 'Unauthorized.',

                ]);
            }

            // 🔥 AUTO-CREATE employee record if not found
            $employee = Employee::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'image_url' => null,
                    'location'  => null,
                    'bio'       => null,
                ]
            );

            // 🔹 Handle avatar upload & delete old
            if ($request->hasFile('avatar')) {
                $image = $request->file('avatar');

                if ($user->avatar) {
                    Helper::deleteImage($user->avatar);
                }

                if ($employee->image_url) {
                    Helper::deleteImage($employee->image_url);
                }

                $uploadedPath = Helper::uploadImage($image, 'profile');

                // Save to both tables
                $user->avatar = $uploadedPath;
                $employee->image_url = $uploadedPath;
            }

            // 🔹 Update user fields
            $user->name          = $request->input('name', $user->name);
            $user->phone_number  = $request->input('phone_number', $user->phone_number);
            $user->date_of_birth = $request->input('date_of_birth', $user->date_of_birth);

            if ($request->filled('date_of_birth')) {
                $user->age = Helper::calculateAge($request->date_of_birth);
            }

            $user->save();

            // 🔹 Update employee fields
            $employee->location = $request->input('location', $employee->location);
            $employee->bio      = $request->input('bio', $employee->bio);
            $employee->save();

            // 🔹 Response data
            $userData = [
                'id'            => $user->id,
                'name'          => $user->name,
                'email'         => $user->email,
                'age'           => $user->age,
                'avatar'        => url($employee->image_url ?? $user->avatar),
                'date_of_birth' => $user->date_of_birth,
                'location'      => $employee->location,
                'bio'           => $employee->bio,
                'phone_number'  => $user->phone_number,
            ];

            return $this->success($userData, 'Employee Profile updated successfully.', 200);
        } catch (\Exception $e) {
            Log::error('Profile update failed: ' . $e->getMessage());
            return $this->error([], 'An unexpected error occurred. Please try again.', 500);
        }
    }


    // role switching

    public function roleSwitching(Request $request)
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                'status'  => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        // Determine new role
        $newRole = $user->role === 'company' ? 'jobseeker' : 'company';

        // Update user role
        $user->role = $newRole;
        $user->save();

        // Conditional table updates
        if ($newRole === 'company') {
            // Check if company record exists
            $company = Company::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'name' => $user->name ?? null, // Optional default values
                    'bio'  => null,
                    'size' => null
                ]
            );
        } elseif ($newRole === 'jobseeker') {
            // Check if employee record exists
            $employee = Employee::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'full_name' => $user->name ?? null,
                    'experience' => null,
                    'resume' => null
                ]
            );
        }

        return response()->json([
            'status'  => true,
            'message' => 'Role switched successfully',
            'data'    => [
                'user_id'  => $user->id,
                'new_role' => $newRole
            ]
        ], 200);
    }
}
