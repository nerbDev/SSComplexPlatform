<?php

namespace App\Mail;

use App\Models\Appointment;
use App\Models\FeedbackRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FeedbackRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Appointment $appointment,
        public FeedbackRequest $feedbackRequest,
        public bool $reminder = false
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: ($this->reminder ? 'Reminder: ' : '') . 'How was "' . $this->appointment->activity_title . '" at Subic Sports Complex?'
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.feedback-request');
    }
}