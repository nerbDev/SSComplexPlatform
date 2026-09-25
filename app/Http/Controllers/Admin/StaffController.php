<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

/**
 * Staff account creation path (Sprint 3 checklist item). Either admin_type
 * can create staff accounts — this sits alongside the other capability
 * both OB Admin and Sports/Activity Admin share (staff assignment,
 * appointment monitoring). No edit/deactivate yet — add that once there's
 * a real need to manage existing staff beyond just creating them.
 */
class StaffController extends Controller
{
    public function index(): View
    {
        $staff = User::where('role', 'staff')->orderBy('first_name')->get();

        return view('admin.staff.index', compact('staff'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'first_name'   => ['required', 'string', 'max:255'],
            'last_name'    => ['required', 'string', 'max:255'],
            'email'        => ['required', 'email', 'unique:users,email'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'password'     => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();

        User::create([
            'first_name'   => $data['first_name'],
            'last_name'    => $data['last_name'],
            'email'        => $data['email'],
            'phone_number' => $data['phone_number'] ?? null,
            'password'     => Hash::make($data['password']),
            'role'         => 'staff',
        ]);

        return back()->with('status', 'Staff account created.');
    }
}