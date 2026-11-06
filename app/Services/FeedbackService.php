<?php

namespace App\Services;

use App\Mail\FeedbackRequestMail;
use App\Models\Appointment;
use App\Models\FeedbackRequest;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Creates the personal feedback link for a finished event and emails it to the client.
 * One FeedbackRequest per appointment; the same link is reused for the single reminder and for the
 * "Give feedback" button on the client's My Appointments page.
 */
class FeedbackService
{
    public function ensureRequest(Appointment $appointment): FeedbackRequest
    {
        return FeedbackRequest::firstOrCreate(
            ['appointment_id' => $appointment->id],
            ['user_id' => $appointment->user_id, 'token' => Str::random(48)]
        );
    }

    /** @return bool false when the client has no email address */
    public function send(Appointment $appointment, bool $reminder = false): bool
    {
        $appointment->loadMissing(['client', 'facility']);

        $email = $appointment->client->email ?? null;
        if (!$email) {
            return false;
        }

        $request = $this->ensureRequest($appointment);

        Mail::to($email)->send(new FeedbackRequestMail($appointment, $request, $reminder));

        $request->forceFill($reminder ? ['reminded_at' => now()] : ['sent_at' => now()])->save();

        return true;
    }
}