<?php

namespace App\Notifications;

use App\Models\LegalCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CaseStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public LegalCase $case;
    public string $message;

    /**
     * Create a new notification instance.
     */
    public function __construct(LegalCase $case, string $message)
    {
        $this->case = $case;
        $this->message = $message;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Status Kasus Diperbarui: ' . $this->case->case_number)
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line('Status untuk kasus Anda "' . $this->case->title . '" telah diperbarui.')
            ->line('Pesan: ' . $this->message)
            ->line('Status Saat Ini: ' . strtoupper($this->case->status))
            ->action('Lihat Detail Kasus', url('/cases/' . $this->case->id))
            ->line('Terima kasih telah menggunakan layanan RCI!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'case_id' => $this->case->id,
            'case_number' => $this->case->case_number,
            'status' => $this->case->status,
            'message' => $this->message,
        ];
    }
}
