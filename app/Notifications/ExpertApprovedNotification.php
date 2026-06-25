<?php

namespace App\Notifications;

use App\Models\ExpertProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExpertApprovedNotification extends Notification implements ShouldQueue
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
            ->subject('✅ Pendaftaran ' . $roleLabel . ' Anda Disetujui — RCI App')
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line('Selamat! Pendaftaran Anda sebagai **' . $roleLabel . '** telah **disetujui** oleh tim verifikasi kami.')
            ->line('Anda sekarang dapat mulai menangani kasus dan mengakses seluruh fitur platform.')
            ->action('Mulai Bekerja', url('/'))
            ->line('Terima kasih telah bergabung dengan RCI App!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'expert_approved',
            'message' => 'Pendaftaran Anda sebagai ' . $notifiable->role . ' telah disetujui.',
            'profile_id' => $this->profile->id,
        ];
    }
}
