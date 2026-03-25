<?php

namespace App\Http\Controllers\Web;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\DynamicPage;

class SubscriberController extends Controller
{
    public function index()
    {
        $page = DynamicPage::where('page_slug', 'privacy-policy')
                    ->where('status', 'active')
                    ->first();

        if (!$page) {
            abort(404, 'Privacy Policy not found');
        }

        return view('privacy_policy', compact('page'));
    }
  
    public function termsConditions()
    {
        return view('terms_conditions');
    }
    public function Userlogin()
    {
        return view('user_login');
    }

    public function userProfile()
    {
        $user = Auth::user();
        return view('user_profile', compact('user'));
    }

    public function submitLogin(Request $request)
    {
        // Validate input
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Attempt login using Laravel Auth
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            // Regenerate session to prevent fixation attacks
            $request->session()->regenerate();

            // Redirect to profile page
            return redirect()->intended('/user-profile');
        }

        return back()->with('error', 'Invalid email or password');
    }

    public function Userlogout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()-> route('sign-in');
    }


    public function deleteAccount(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login.page')->with('error', 'User not found.');
        }

        // Optional: delete related data, e.g. posts, profile, etc.
        // $user->posts()->delete(); 

        // Delete the user
        $user->delete();

        // Logout after deletion
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/sign-in')->with('success', 'Your account has been deleted successfully.');
    }
}
