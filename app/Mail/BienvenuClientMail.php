<?php

namespace App\Mail;

use App\Models\Client;
use App\Models\Etablissement;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BienvenuClientMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Client        $client,
        public string        $motDePasse,
        public Etablissement $etablissement,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Bienvenue chez {$this->etablissement->nom} — Vos accès client",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.client.bienvenu',
        );
    }
}
