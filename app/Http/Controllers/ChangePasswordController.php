<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ChangePasswordController extends Controller
{
    public function edit(): View
    {
        return view('auth.change-password');
    }

    public function update(ChangePasswordRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->password = $request->string('password')->toString();
        $user->save();

        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->event('password_changed')
            ->withProperties(['username' => $user->username])
            ->log('Kata sandi diubah');

        return back()->with('status', 'Kata sandi berhasil diubah.');
    }
}
