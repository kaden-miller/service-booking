<?php
require_once 'vendor/autoload.php';

use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

function send_calendar_invite($email, $event_start, $event_end, $subject, $description, $location) {
    // Create the .ics content
    $ics_content = "BEGIN:VCALENDAR\r\n";
    $ics_content .= "VERSION:2.0\r\n";
    $ics_content .= "PRODID:-//Your Company//Your Booking System//EN\r\n";
    $ics_content .= "BEGIN:VEVENT\r\n";
    $ics_content .= "UID:" . uniqid() . "@yourdomain.com\r\n";
    $ics_content .= "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";
    $ics_content .= "DTSTART:" . gmdate('Ymd\THis\Z', strtotime($event_start)) . "\r\n";
    $ics_content .= "DTEND:" . gmdate('Ymd\THis\Z', strtotime($event_end)) . "\r\n";
    $ics_content .= "SUMMARY:" . $subject . "\r\n";
    $ics_content .= "DESCRIPTION:" . $description . "\r\n";
    $ics_content .= "LOCATION:" . $location . "\r\n";
    $ics_content .= "END:VEVENT\r\n";
    $ics_content .= "END:VCALENDAR\r\n";

    // Create the email message
    $email_message = (new Email())
        ->from('you@yourdomain.com')
        ->to($email)
        ->subject($subject)
        ->text($description)
        ->attachFromPath('event.ics', 'text/calendar');

    // Send the message
    $transport = Transport::fromDsn('smtp://user:pass@your.smtp.server:587');
    $mailer = new Mailer($transport);
    $mailer->send($email_message);
}
