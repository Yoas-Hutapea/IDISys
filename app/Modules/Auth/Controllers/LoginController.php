<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Requests\LoginRequest;
use App\Modules\Auth\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    public function show()
    {
        return view('account.login');
    }

    public function login(LoginRequest $request)
    {
        $account = $this->authService->attemptLogin(
            $request->input('UserId'),
            $request->input('Password')
        );

        if (!$account) {
            return back()
                ->withErrors([
                    'UserId' => 'User ID atau password salah.',
                ])
                ->withInput();
        }

        Auth::login($account, false);
        $request->session()->regenerate();

        $request->session()->put('employee', $account->employee);

        return redirect()->intended('/');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/api/login');
    }
}
