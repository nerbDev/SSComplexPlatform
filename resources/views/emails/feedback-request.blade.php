    <!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="margin:0;padding:0;background:#f3fbf7;font-family:Arial,Helvetica,sans-serif;color:#0e1c17;">
@php
    $a = $appointment;
    $name = $a->client->first_name ?? 'there';
    $link = fn($rating = null) => route('feedback.show', array_filter(['token' => $feedbackRequest->token, 'rating' => $rating, 'src' => 'email']));
@endphp
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3fbf7;padding:24px 12px;">
    <tr><td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e4ece8;">
            <tr><td style="background:#07231a;padding:20px 24px;color:#ffffff;font-size:16px;font-weight:bold;">Subic Sports Complex</td></tr>
            <tr><td style="padding:24px;">
                <p style="margin:0 0 12px;font-size:16px;">Hi {{ $name }},</p>
                <p style="margin:0 0 16px;font-size:14px;line-height:1.55;">
                    @if($reminder)
                        Just a gentle reminder — we'd still love to hear how your event went.
                    @else
                        Thank you for choosing SSC. How was your event?
                    @endif
                </p>
                <p style="margin:0 0 4px;font-size:14px;"><strong>{{ $a->activity_title }}</strong></p>
                <p style="margin:0 0 18px;font-size:13px;color:#5b6b64;">{{ $a->facility->name ?? '' }} · {{ $a->event_date->format('F j, Y') }}</p>

                <p style="margin:0 0 10px;font-size:13px;font-weight:bold;">Tap a rating to start (1 = poor, 5 = excellent):</p>
                <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 20px;"><tr>
                    @foreach([1, 2, 3, 4, 5] as $n)
                        <td style="padding-right:8px;"><a href="{{ $link($n) }}" style="display:inline-block;width:42px;height:42px;line-height:42px;text-align:center;border-radius:10px;background:#e8f7ef;color:#146245;font-weight:bold;text-decoration:none;font-size:16px;border:1px solid #cdeedd;">{{ $n }}</a></td>
                    @endforeach
                </tr></table>

                <a href="{{ $link() }}" style="display:inline-block;background:#178a5e;color:#ffffff;text-decoration:none;font-weight:bold;font-size:14px;padding:12px 22px;border-radius:10px;">Give feedback</a>
                <p style="margin:18px 0 0;font-size:12px;color:#5b6b64;line-height:1.5;">It takes under a minute. Your answers help us improve the facility and the booking experience.</p>
            </td></tr>
            <tr><td style="padding:14px 24px;background:#f3fbf7;font-size:11px;color:#93a29c;">This link is personal to you. If the buttons don't work, copy this address: {{ $link() }}</td></tr>
        </table>
    </td></tr>
</table>
</body>
</html>