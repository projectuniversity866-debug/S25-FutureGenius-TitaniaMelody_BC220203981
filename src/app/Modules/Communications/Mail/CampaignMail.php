<?php

namespace App\Modules\Communications\Mail;

use App\Modules\Communications\Models\Campaign;
use App\Modules\Platform\Services\MarkdownRenderer;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;

/**
 * A campaign as one subscriber receives it (roadmap 3.16), with RFC 8058
 * one-click unsubscribe headers so mail apps can show their own
 * "Unsubscribe" button. A test send has no subscriber and no link.
 */
class CampaignMail extends Mailable
{
    public function __construct(public Campaign $campaign, public ?string $unsubscribeUrl) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->campaign->subject);
    }

    public function headers(): Headers
    {
        return new Headers(text: $this->unsubscribeUrl ? [
            'List-Unsubscribe'      => '<'.$this->unsubscribeUrl.'>',
            'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
        ] : []);
    }

    public function content(): Content
    {
        return new Content(markdown: 'communications::mail.campaign', with: [
            'html' => app(MarkdownRenderer::class)->render($this->campaign->body),
        ]);
    }
}
