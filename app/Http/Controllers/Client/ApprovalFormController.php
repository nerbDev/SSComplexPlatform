<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * The free_use-track counterpart to PaymentController — instead of paying,
 * the client attaches their Subic Administration Office / Mayor's approval
 * form once Staff sets the appointment to `verified`. Staff review of the
 * attached form itself is future work (not built yet); this only captures
 * the attachment and moves the appointment to `form_submitted`.
 */
class ApprovalFormController extends Controller
{
    public function store(Request $request, Appointment $appointment): RedirectResponse
    {
        abort_unless($appointment->user_id === Auth::id(), 403);

        if ($appointment->track !== 'free_use') {
            return redirect()->route('client.dashboard')->with('status', 'This isn\'t a free-use booking.');
        }

        if ($appointment->status !== 'verified') {
            return redirect()->route('client.dashboard')->with('status', 'This booking isn\'t ready for document attachment yet.');
        }

        $validator = Validator::make($request->all(), [
            'attachment'          => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'date_time_submitted' => ['required', 'date'],
            'who_submitted'       => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator, 'attachForm_' . $appointment->id)->withInput();
        }

        $path = $request->file('attachment')->store('approval-forms', 'public');

        $appointment->update([
            'approval_form_path'         => $path,
            'approval_form_submitted_at' => $request->input('date_time_submitted'),
            'approval_form_submitted_by' => $request->input('who_submitted'),
            'status'                     => 'form_submitted',
        ]);

        AuditLog::record('approval_form.attached', $appointment, 'Client attached the municipal approval form');

        return redirect()
            ->route('client.dashboard')
            ->with('status', 'Approval form attached. Staff will review it next.');
    }
}