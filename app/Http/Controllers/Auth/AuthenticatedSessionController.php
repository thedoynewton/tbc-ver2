<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Handle the email-based login request.
     */
    public function loginWithEmail(Request $request): RedirectResponse
    {
        // Log the login attempt
        //Log::info('Login attempt with email: ' . $request->input('email'));
    
        // Validate the email format
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);
    
        if ($validator->fails()) {
            //Log::error('Email format validation failed', ['errors' => $validator->errors()]);
            return redirect()->back()->with('error', 'Email format validation failed');

        }
    
        // Extract the domain from the email
        $email = $request->input('email');
        $domain = substr(strrchr($email, "@"), 1);
    
        //Log::info('Email domain check: ' . $domain);
    
        // Check if the email domain is 'usep.edu.ph'
        if ($domain !== 'usep.edu.ph') {
            //Log::warning('Access denied: Non-USeP email used for login', ['email' => $email]);
            return redirect()->back()->with('error', 'Access denied. You must use your USeP email.');

        }
    
        // Check if the email exists in the users table
        $user = User::where('email', $email)->first();
    
        if ($user) {
            //Log::info('User found with email: ' . $user->email . ' | Role: ' . ($user->role ?? 'No role assigned'));
    
            // Deny access if user doesn't have a role
            if (is_null($user->role)) {
                //Log::warning('Access denied: User has no role.', ['email' => $user->email]);
                return redirect()->back()->with('error', 'Access denied. You do not have the required permissions.');
            }
    
            // Log the user in
            Auth::login($user);
    
            // Redirect based on role
            if ($user->role === 'admin') {
                //Log::info('Admin logged in.');
                return redirect()->intended('/dashboard');
            } elseif ($user->role === 'subadmin') {
                //Log::info('Subadmin logged in.');
                return redirect()->intended('/dashboard');
            } else {
                //Log::info('User logged in as regular user.');
                return redirect()->intended('/dashboard');
            }
        } else {
            //Log::error('User not found for email: ' . $email);
            return redirect()->back()->with('error', 'Login failed. Please try again.');
            
        }
    }    

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
