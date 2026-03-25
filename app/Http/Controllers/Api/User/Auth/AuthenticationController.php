<?php

namespace App\Http\Controllers\Api\User\Auth;

use App\Helper\Helper;
use App\Http\Controllers\Controller;
use App\Mail\OtpMail;
use App\Mail\RegisterOtpMail;
use App\Models\Employee;
use App\Models\User;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class AuthenticationController extends Controller
{
    use ApiResponse;

    // User regisration
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name'          => ['required', 'string', 'max:255'],
                'email'         => ['required', 'string', 'email', 'unique:users', 'max:255'],
                'password'      => ['required', 'string', 'min:8'],
            ]);

            if ($validator->fails()) {
                return $this->error([], $validator->errors()->first(), 404);
            }

            $validatedData = $validator->validated();
            $user = User::where('email', $validatedData['email'])->first();
            $otp = rand(1000, 9999);
            $otpExpiresAt = Carbon::now()->addMinutes(5);

            $user = User::create([
                'name'            => $validatedData['name'],
                'email'           => $validatedData['email'],
                'password'        => Hash::make($validatedData['password']),
                'otp'             => $otp,
                'otp_expires_at'  => $otpExpiresAt,
                'is_otp_verified' => false,
            ]);

            Mail::to($user->email)->send(new RegisterOtpMail($otp, $user->name));

            return $this->success([
                'message' => 'OTP has been sent to your email. Please verify to complete registration.',
                'email'   => $user->email,
                'otp'     => $user->otp,
            ], 'OTP Sent', 201);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error([], $e->getMessage(), 500);
        }
    }

    // Email Verify
    public function RegistrationVerifyOtp(Request $request)
    {
        $validator = validator()->make($request->all(), [
            'email' => ['required', 'email', 'exists:users,email'],
            'otp'   => ['required', 'digits:4'],
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 400);
        }

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return $this->error([], 'User not found', 200);
        }

        if ($user->otp !== $request->otp) {
            return $this->error([], 'Your OTP is Invalid.', 403);
        }

        if (Carbon::now()->gt($user->otp_expires_at)) {
            return $this->error([], 'OTP has expired');
        }

        $user->update([
            'email_verified_at' => Carbon::now(),
            'is_otp_verified'   => true,
            'otp'               => null,
            'otp_expires_at'    => null,
        ]);

        $token = auth('api')->login($user);

        $userData = [

            'id'            => $user['id'],
            'name'          => $user['name'],
            'email'         => $user['email'],
            'phone_number'  => $user['phone_number'],
            'date_of_birth' => $user['date_of_birth'],
            'role'          => $user['role'],

            'created_at'    => Carbon::parse($user['created_at'])->format('Y-m-d H:i:s'),

            'token'         => $token,
            'otp'       => $user->otp,
        ];

        return $this->success($userData, 'User Registration successful.', 200);
    }

    
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email'    => ['required', 'string', 'email', 'max:255'],
                'password' => ['required', 'string', 'min:8'],
            ]);

            if ($validator->fails()) {
                return $this->error([], $validator->errors()->first(), 422);
            }

            $data = $validator->validated();

            $user = User::where('email', $data['email'])->first();


            if (! $user) {
                return $this->error([], 'No account was found with this email address..', 401);
            }

            // Check if OTP is verified
            if (! $user->is_otp_verified) {
                return $this->error([], 'Please verify your email with the OTP before logging in.', 401);
            }

            if (! $token = auth('api')->attempt($data)) {
                return $this->error([], 'The provided credentials are incorrect', 401);
            }

            // if ($user->role == null) {
            //     return $this->error([], 'Unauthorized role access.', 403);
            // }
            // Load related employee and company profiles (if applicable)
            $employee = $user->employee;
            $company  = $user->company;

            $userData = [
                'token'                     => $token,
            ];

            return response()->json([
                'status'  => true,
                'message' => 'Login successful.',
                'code'    => 200,
                'user_id'   => $user->id,
                'role'      => $user->role,
                'token'    => $token,
            ]);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error([], $e->getMessage(), 500);
        }
    }

    public function updateRole(Request $request)
    {
        try {
            $user = auth('api')->user();

            if (! $user) {
                return $this->error([], 'Unauthorized.', 400);
            }

            $dataToUpdate = [];

            // Role update
            if ($request->has('role')) {
                if ($user->role != null) {
                    return $this->error([], 'You have already updated your role. Role cannot be changed again.', 400);
                }
                $dataToUpdate['role'] = $request->role;
            }

            // if ($request->hasFile('resume')) {
            //     $resume = Helper::uploadImage($request->file('resume'), 'jobseeker');
            //     $dataToUpdate['resume'] = $resume;
            // }

            if ($request->hasFile('resume')) {
                $file = $request->file('resume');

                $originalName = $file->getClientOriginalName();
                $originalName = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $originalName);

                // Unique but clean: userID_originalName
                $filename = $user->id . '_' . $originalName;

                $path = $file->storeAs('jobseeker', $filename, 'public');

                $dataToUpdate['resume'] = $path;

                // Optional: also store clean name for display/download
                // $dataToUpdate['resume_original_name'] = $originalName;
            }


            // Avatar update
            if ($request->hasFile('avatar')) {
                if ($user->avatar) {
                    Helper::deleteImage($user->avatar);
                }
                $avatar = Helper::uploadImage($request->file('avatar'), 'profile');
                $dataToUpdate['avatar'] = $avatar;
            }

            // Phone update
            $sendOtp = false;
            if ($request->has('phone_number')) {
                $dataToUpdate['phone_number'] = $request->phone_number;
                $sendOtp = true;
            }

            // Apply updates
            if (!empty($dataToUpdate)) {
                $user->update($dataToUpdate);
            }


            if (!empty($dataToUpdate['role']) && $dataToUpdate['role'] === 'jobseeker') {

                // If jobseeker profile exists → update user_id only
                $jobseeker = Employee::where('user_id', $user->id)->first();

                if (!$jobseeker) {
                    Employee::create([
                        'user_id' => $user->id,
                        ''
                    ]);
                }
            }
            // // Send OTP if phone number updated
            // if ($sendOtp) {
            //     $otp = rand(100000, 999999);
            //     $user->update([
            //         'otp' => $otp,
            //         'otp_expires_at' => now()->addMinutes(10)
            //     ]);

            //     $twilio = new Client(env('TWILIO_SID'), env('TWILIO_AUTH_TOKEN'));
            //     $twilio->messages->create($user->phone_number, [
            //         'from' => env('TWILIO_PHONE_NUMBER'),
            //         'body' => "Verification code: $otp"
            //     ]);

            //     // Return early with OTP-specific message
            //     return $this->success([], 'OTP sent to your phone. Please verify your phone number.', 200);
            // }

            // Prepare response
            $userData = [
                'id'            => $user->id,
                'name'          => $user->name,
                'email'         => $user->email,
                'phone_number'  => $user->phone_number,
                'role'          => $user->role,
                'resume'        => $user->resume ?? null,
                'created_at'    => $user->created_at->format('Y-m-d H:i:s'),
                'updated_at'    => $user->updated_at->format('Y-m-d H:i:s'),
            ];

            return $this->success($userData, 'Profile updated successfully.', 200);
        } catch (Exception $e) {
            Log::error('Profile update error: ' . $e->getMessage());
            return $this->error([], 'An error occurred while updating the profile.', 500);
        }
    }

    public function phoneVerifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|numeric',
        ]);

        $user = auth('api')->user();

        if (! $user) {
            return $this->error([], 'User not found.', 400);
        }

        if ($user->otp != $request->otp) {
            return $this->error([], 'Invalid OTP.', 400);
        }

        // OTP verified: clear OTP and expiration
        $user->update([
            'otp' => null,
            'otp_expires_at' => null,
            'phone_verified_at' => now(),
            'is_phone_otp_verified' => true,
        ]);

        return $this->success([], 'Phone number verified successfully.', 200);
    }


    public function logout()
    {
        try {
            auth('api')->logout();
            return $this->success([], 'Successfully logged out.', 200);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error([], $e->getMessage(), 500);
        }
    }

    public function deleteProfile(Request $request)
    {
        $user = auth('api')->user();

        if (! $user) {
            return $this->error([], 'User not found.', 400);
        }

        if ($user->avatar) {
            Helper::deleteImage($user->avatar);
        }

        if ($user->resume) {
            Helper::deleteImage($user->resume);
        }

        // delete firebase tokens first
        DB::table('firebase_tokens')->where('user_id', $user->id)->delete();

        if ($request->user() && $request->user()->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }

        $user->delete();

        return $this->success([], 'User profile deleted successfully.', 200);
    }


    public function ResendOtp(Request $request)
    {

        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        try {
            $user = User::where('email', $request->input('email'))->first();

            if (!$user) {
                return $this->error([], 'User not found.', 404);
            }

            if ($user->otp_verified_at) {
                return $this->error([], 'Email already verified.', 409);
            }

            $newOtp               = rand(1000, 9999);
            $otpExpiresAt         = Carbon::now()->addMinutes(60);
            $user->otp            = $newOtp;
            $user->otp_expires_at = $otpExpiresAt;
            $user->save();

            //* Send the new OTP to the user's email
            Mail::to($user->email)->send(new OtpMail($newOtp, $user, 'Verify Your Email Address'));

            return response()->json([
                'status'  => true,
                'message' => 'A new OTP has been sent to your email address.',
                'code'    => 200,
                'otp'     => $newOtp // Remove this line in production
            ], 200);
        } catch (Exception $e) {
            return $this->error([], $e->getMessage(), 200);
        }
    }
}
