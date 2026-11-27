<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NoticeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $type, public array $params, public ?string $url, string $locale)
    {
        $this->locale($locale);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: '[FreshRoute] '.strip_tags(\App\Support\NoticeText::render($this->type, $this->params, $this->locale)));
    }

    public function content(): Content
    {
        return new Content(text: 'mail.notice');
    }
}
