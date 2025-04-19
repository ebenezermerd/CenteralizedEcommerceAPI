<?php

namespace App\Mail;

use App\Models\SupportInquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SupportInquiryResponse extends Mailable
{
    use Queueable, SerializesModels;

    public $inquiry;
    public $responseMessage;

    /**
     * Create a new message instance.
     */
    public function __construct(SupportInquiry $inquiry, string $responseMessage)
    {
        $this->inquiry = $inquiry;
        $this->responseMessage = $responseMessage;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Re: ' . $this->inquiry->subject . ' - Support Response',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.support-inquiry-response',
            with: [
                'inquiry' => $this->inquiry,
                'responseMessage' => $this->responseMessage,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
} 