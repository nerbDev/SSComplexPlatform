<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Show the combined sign in / sign up page.
     * ?mode=signup opens straight into the sign-up panel (used by the
     * "Start a Booking" button on the landing page).
     */
    public function show(Request $request): View
    {
        $mode = $request->query('mode') === 'signup' ? 'signup' : 'signin';

        return view('Index', [
            'activeMode' => session('active_mode', $mode),
            'bgImage'    => 'images/hero/SSCfrontview.jpg',
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator, 'signin')
                ->withInput()
                ->with('active_mode', 'signin');
        }

        $credentials = $validator->validated();

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'Those credentials don\'t match our records.'], 'signin')
                ->onlyInput('email')
                ->with('active_mode', 'signin');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('landing'));
    }

    public function register(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'first_name'    => ['required', 'string', 'max:255'],
            'last_name'     => ['required', 'string', 'max:255'],
            'email'         => ['required', 'email', 'unique:users,email'],
            'phone_number'  => ['nullable', 'string', 'max:20'],
            'password'      => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator, 'signup')
                ->withInput()
                ->with('active_mode', 'signup');
        }

        $data = $validator->validated();

        $user = User::create([
            'first_name'   => $data['first_name'],
            'last_name'    => $data['last_name'],
            'email'        => $data['email'],
            'phone_number' => $data['phone_number'] ?? null,
            'password'     => Hash::make($data['password']),
            'role'         => 'client',
        ]);

        Auth::login($user);

        return redirect()->route('landing');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landing');
    }
}