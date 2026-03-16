<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Requests\ChangePasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ChangePasswordController extends Controller
{
    public function show(): View
    {
        return view('account.change_password');
    }

    public function update(ChangePasswordRequest $request): RedirectResponse
    {
        $account = Auth::user();
        $account->PasswordHash = Hash::make($request->input('new_password'));
        $account->save();

        $request->session()->forget('must_change_password');

        return redirect('/')
            ->with('success', 'Password has been changed successfully. Please use your new password for future logins.');
    }
}
