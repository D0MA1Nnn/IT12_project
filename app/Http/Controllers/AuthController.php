<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Show Login
    |--------------------------------------------------------------------------
    */

    public function showLogin()
    {
        if (Auth::check()) {

            if (Auth::user()->role === 'SALES_CLERK') {
                return redirect()->route('sales.create');
            }

            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }


    /*
    |--------------------------------------------------------------------------
    | Login
    |--------------------------------------------------------------------------
    */

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => [
                'required',
                'string',
                'max:100',
            ],

            'password' => [
                'required',
                'string',
            ],
        ]);


        $username = strtolower(
            trim($credentials['username'])
        );


        /*
        |--------------------------------------------------------------------------
        | Rate Limiter Keys
        |--------------------------------------------------------------------------
        */

        $identifier =
            $username . '|' . $request->ip();

        $lockKey =
            'login-lock:' . $identifier;

        $attemptKey =
            'login-attempt:' . $identifier;

        $escalationKey =
            'login-escalation:' . $identifier;


        /*
        |--------------------------------------------------------------------------
        | Check Current Lock
        |--------------------------------------------------------------------------
        */

        if (
            RateLimiter::tooManyAttempts(
                $lockKey,
                1
            )
        ) {

            $seconds = max(
                1,
                RateLimiter::availableIn(
                    $lockKey
                )
            );


            return back()
                ->withInput(
                    $request->only('username')
                )
                ->with(
                    'login_locked',
                    true
                )
                ->with(
                    'lockout_seconds',
                    $seconds
                )
                ->with(
                    'login_error',
                    'Too many incorrect login attempts. Please wait before trying again.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Find User
        |--------------------------------------------------------------------------
        */

        $user = User::whereRaw(
            'LOWER(username) = ?',
            [$username]
        )->first();


        /*
        |--------------------------------------------------------------------------
        | Invalid Username
        |--------------------------------------------------------------------------
        */

        if (!$user) {

            return $this->failedLogin(
                $request,
                $attemptKey,
                $lockKey,
                $escalationKey
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Inactive Account
        |--------------------------------------------------------------------------
        */

        if (!$user->is_active) {

            return back()
                ->withInput(
                    $request->only('username')
                )
                ->with(
                    'login_error',
                    'This account is currently inactive. Please contact the Owner.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Incorrect Password
        |--------------------------------------------------------------------------
        */

        if (
            !Hash::check(
                $credentials['password'],
                $user->password_hash
            )
        ) {

            return $this->failedLogin(
                $request,
                $attemptKey,
                $lockKey,
                $escalationKey
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Validate Role
        |--------------------------------------------------------------------------
        */

        if (
            !in_array(
                $user->role,
                [
                    'OWNER',
                    'SALES_CLERK',
                ],
                true
            )
        ) {

            return back()
                ->withInput(
                    $request->only('username')
                )
                ->with(
                    'login_error',
                    'This account does not have a valid system role.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Reset Failed Login Security
        |--------------------------------------------------------------------------
        */

        RateLimiter::clear(
            $attemptKey
        );

        RateLimiter::clear(
            $lockKey
        );

        RateLimiter::clear(
            $escalationKey
        );


        /*
        |--------------------------------------------------------------------------
        | Authenticate
        |--------------------------------------------------------------------------
        */

        Auth::login($user);

        $request
            ->session()
            ->regenerate();


        /*
        |--------------------------------------------------------------------------
        | Activity Log
        |--------------------------------------------------------------------------
        */

        ActivityLog::create([
            'user_id' =>
                $user->user_id,

            'module' =>
                'AUTHENTICATION',

            'action' =>
                'LOGIN',

            'description' =>
                "User '{$user->username}' logged in.",

            'reference_type' =>
                'User',

            'reference_id' =>
                $user->user_id,

            'created_at' =>
                now(),
        ]);


        /*
        |--------------------------------------------------------------------------
        | Display Name
        |--------------------------------------------------------------------------
        */

        $displayName =
            !empty($user->first_name)
                ? $user->first_name
                : $user->username;


        /*
        |--------------------------------------------------------------------------
        | Sales Clerk
        |--------------------------------------------------------------------------
        */

        if ($user->role === 'SALES_CLERK') {

            return redirect()
                ->route('sales.create')
                ->with(
                    'login_success',
                    'Welcome back, ' .
                    $displayName .
                    '.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Owner
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route('dashboard')
            ->with(
                'login_success',
                'Welcome back, ' .
                $displayName .
                '.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Failed Login
    |--------------------------------------------------------------------------
    */

    private function failedLogin(
        Request $request,
        string $attemptKey,
        string $lockKey,
        string $escalationKey
    ) {

        RateLimiter::hit(
            $attemptKey,
            86400
        );


        $attempts =
            RateLimiter::attempts(
                $attemptKey
            );


        /*
        |--------------------------------------------------------------------------
        | First Wrong Attempt
        |--------------------------------------------------------------------------
        */

        if ($attempts === 1) {

            return back()
                ->withInput(
                    $request->only('username')
                )
                ->with(
                    'login_error',
                    'Invalid username or password.'
                )
                ->with(
                    'attempt_warning',
                    '2 attempts remaining before a temporary cooldown.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Second Wrong Attempt
        |--------------------------------------------------------------------------
        */

        if ($attempts === 2) {

            return back()
                ->withInput(
                    $request->only('username')
                )
                ->with(
                    'login_error',
                    'Invalid username or password.'
                )
                ->with(
                    'attempt_warning',
                    '1 attempt remaining before a temporary cooldown.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Third Wrong Attempt / Escalation
        |--------------------------------------------------------------------------
        */

        RateLimiter::hit(
            $escalationKey,
            86400
        );


        $level =
            RateLimiter::attempts(
                $escalationKey
            );


        /*
        |--------------------------------------------------------------------------
        | Cooldown
        |--------------------------------------------------------------------------
        |
        | Level 1 = 30 seconds
        | Level 2 = 60 seconds
        | Level 3 = 120 seconds
        | Level 4 = 240 seconds
        | Level 5 = 480 seconds
        | Level 6 = 960 seconds
        | Maximum = 1800 seconds / 30 minutes
        |
        */

        $seconds =
            30 * (2 ** ($level - 1));


        $seconds = min(
            $seconds,
            1800
        );


        /*
        |--------------------------------------------------------------------------
        | Apply Lock
        |--------------------------------------------------------------------------
        */

        RateLimiter::clear(
            $lockKey
        );


        RateLimiter::hit(
            $lockKey,
            $seconds
        );


        /*
        |--------------------------------------------------------------------------
        | Keep Next Failure At Escalation Stage
        |--------------------------------------------------------------------------
        */

        RateLimiter::clear(
            $attemptKey
        );


        RateLimiter::hit(
            $attemptKey,
            86400
        );


        RateLimiter::hit(
            $attemptKey,
            86400
        );


        return back()
            ->withInput(
                $request->only('username')
            )
            ->with(
                'login_locked',
                true
            )
            ->with(
                'lockout_seconds',
                $seconds
            )
            ->with(
                'login_error',
                'Too many incorrect login attempts.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Logout
    |--------------------------------------------------------------------------
    */

    public function logout(Request $request)
    {
        $user = Auth::user();


        /*
        |--------------------------------------------------------------------------
        | Save Logout To Activity Log
        |--------------------------------------------------------------------------
        */

        if ($user) {

            ActivityLog::create([
                'user_id' =>
                    $user->user_id,

                'module' =>
                    'AUTHENTICATION',

                'action' =>
                    'LOGOUT',

                'description' =>
                    "User '{$user->username}' logged out.",

                'reference_type' =>
                    'User',

                'reference_id' =>
                    $user->user_id,

                'created_at' =>
                    now(),
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Logout User
        |--------------------------------------------------------------------------
        */

        Auth::logout();


        /*
        |--------------------------------------------------------------------------
        | Destroy Session
        |--------------------------------------------------------------------------
        */

        $request
            ->session()
            ->invalidate();


        $request
            ->session()
            ->regenerateToken();


        /*
        |--------------------------------------------------------------------------
        | Back To Login
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route('login')
            ->with(
                'logout_success',
                'You have been signed out successfully.'
            );
    }
}