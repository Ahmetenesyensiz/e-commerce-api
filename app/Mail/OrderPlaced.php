<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderPlaced extends Mailable
{
    use Queueable, SerializesModels;

    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Siparişiniz Alındı #' . $this->order->id,
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: '
                <h1>Teşekkürler!</h1>
                <p>Siparişiniz başarıyla alındı.</p>
                <p><strong>Sipariş No:</strong> ' . $this->order->id . '</p>
                <p><strong>Toplam Tutar:</strong> ' . $this->order->total_amount . ' TL</p>
                <br>
                <p>Bizi tercih ettiğiniz için teşekkür ederiz.</p>
            '
        );
    }

    public function attachments(): array
    {
        return [];
    }
}