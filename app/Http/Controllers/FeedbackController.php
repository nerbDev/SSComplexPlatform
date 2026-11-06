<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Feedback;
use App\Models\FeedbackRequest;
use App\Services\FeedbackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Post-event feedback. The page at /feedback/{token} needs no login — the token in the emailed link is the
 * client's personal key. It opens a rule-based chatbot (no AI service involved) with a plain form as a fallback.
 */
class FeedbackController extends Controller
{
    public function __construct(private FeedbackService $feedback)
    {
    }

    public function show(Request $request, string $token): View
    {
        $feedbackRequest = FeedbackRequest::where('token', $token)->firstOrFail();
        $appointment     = Appointment::with(['facility', 'client'])->findOrFail($feedbackRequest->appointment_id);

        $existing = Feedback::where('appointment_id', $appointment->id)->first();
        $rating   = (int) $request->query('rating');

        return view('feedback.show', [
            'token'      => $token,
            'done'       => $existing !== null || $feedbackRequest->completed_at !== null,
            'firstName'  => $appointment->client->first_name ?? 'there',
            'event'      => $appointment->activity_title,
            'facility'   => $appointment->facility->name ?? 'Subic Sports Complex',
            'eventDate'  => $appointment->event_date->format('F j, Y'),
            'preRating'  => ($rating >= 1 && $rating <= 5) ? $rating : null,
            'src'        => $request->query('src') === 'email' ? 'email' : 'web',
            'submitUrl'  => route('feedback.store', $token),
        ]);
    }

    public function store(Request $request, string $token): JsonResponse|RedirectResponse
    {
        $feedbackRequest = FeedbackRequest::where('token', $token)->firstOrFail();

        $data = $request->validate([
            'rating'   => ['required', 'integer', 'between:1,5'],
            'comments' => ['nullable', 'string', 'max:2000'],
            'mode'     => ['nullable', 'in:chatbot,form'],
            'src'      => ['nullable', 'in:email,web'],
        ]);

        if (Feedback::where('appointment_id', $feedbackRequest->appointment_id)->exists()) {
            return $request->expectsJson()
                ? response()->json(['ok' => true, 'already' => true])
                : redirect()->route('feedback.show', $token);
        }

        $via = ($data['mode'] ?? 'form') === 'chatbot' ? 'chatbot' : (($data['src'] ?? 'web') === 'email' ? 'email' : 'web');

        // forceFill: works whatever the Feedback model's $fillable says.
        $feedback = new Feedback();
        $feedback->forceFill([
            'appointment_id' => $feedbackRequest->appointment_id,
            'user_id'        => $feedbackRequest->user_id,
            'rating'         => $data['rating'],
            'comments'       => $data['comments'] ?? null,
            'submitted_via'  => $via,
        ])->save();

        $feedbackRequest->forceFill(['completed_at' => now()])->save();

        AuditLog::record('feedback.submitted', $feedback, "Feedback received for appointment #{$feedbackRequest->appointment_id}: {$data['rating']}/5 via {$via}");

        return $request->expectsJson()
            ? response()->json(['ok' => true])
            : redirect()->route('feedback.show', $token)->with('thanks', true);
    }

    /** Logged-in client's "Give feedback" button on My Appointments: make sure a link exists, then open it. */
    public function start(Appointment $appointment): RedirectResponse
    {
        abort_unless($appointment->user_id === Auth::id(), 403);

        if ($appointment->status !== 'finished') {
            return redirect()->route('client.appointments.index')->with('error', 'Feedback opens once the event is finished.');
        }

        if (Feedback::where('appointment_id', $appointment->id)->exists()) {
            return redirect()->route('client.appointments.index')->with('status', 'You already sent feedback for this event. Thank you!');
        }

        $request = $this->feedback->ensureRequest($appointment);

        return redirect()->route('feedback.show', ['token' => $request->token, 'src' => 'web']);
    }
}