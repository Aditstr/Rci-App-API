<?php

namespace App\Notifications;

use App\Models\ExpertProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExpertRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public ExpertProfile $profile;

    /**
     * Create a new notification instance.
     */
    public function __construct(ExpertProfile $profile)
    {
        $this->profile = $profile;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $roleLabel = $notifiable->role === 'lawyer' ? 'Pengacara' : 'Paralegal';

        return (new MailMessage)
            ->subject('❌ Pendaftaran ' . $roleLabel . ' Anda Ditolak — RCI App')
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line('Mohon maaf, pendaftaran Anda sebagai **' . $roleLabel . '** telah **ditolak** oleh tim verifikasi kami.')
            ->line('**Alasan penolakan:** ' . ($this->profile->rejection_reason ?? 'Tidak ada keterangan.'))
            ->line('Anda dapat mengunggah ulang dokumen Anda melalui aplikasi untuk ditinjau kembali.')
            ->action('Upload Ulang Dokumen', url('/'))
            ->line('Jika Anda memiliki pertanyaan, silakan hubungi tim support kami.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type'             => 'expert_rejected',
            'message'          => 'Pendaftaran Anda sebagai ' . $notifiable->role . ' ditolak: ' . $this->profile->rejection_reason,
            'profile_id'       => $this->profile->id,
            'rejection_reason' => $this->profile->rejection_reason,
        ];
    }
}
