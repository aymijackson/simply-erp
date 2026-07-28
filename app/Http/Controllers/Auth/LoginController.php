<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function show()
    {
        return view('auth.login'); // create this blade
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'platform' => ['required', 'in:erp,admin'],
            'remember' => ['nullable'],
        ]);

        $remember = $request->boolean('remember');

        if (! Auth::attempt(['email' => $data['email'], 'password' => $data['password']], $remember)) {
            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        $user = Auth::user();

        // Access checks (based on your plan)
        if ($data['platform'] === 'admin' && ! $user->can_access_admin) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'platform' => __('You do not have access to the Admin Platform.'),
            ]);
        }

        if ($data['platform'] === 'erp' && ! $user->can_access_erp) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'platform' => __('You do not have access to the ERP platform.'),
            ]);
        }

        return $data['platform'] === 'admin'
            ? redirect()->intended(route('admin.control_center'))
            : redirect()->intended(route('dashboard'));
    }
    
    public function logout(Request $request)
    {
        Auth::logout();
    
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    
        return redirect()->route('login');
    }
}
