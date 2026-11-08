<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi area tim (lonceng + real-time via Reverb).
 * Satu kelas generik agar format tampilan di navbar selalu seragam.
 */
class TeamNotification extends Notification
{
    use Queueable;

    /**
     * @param  string  $title  Kalimat utama, mis. "Disposisi baru: Undangan Rapat".
     * @param  string  $url  Tujuan saat diklik (dalam tim yang sama).
     * @param  string  $icon  Ikon Keenicons, mis. "ki-send".
     * @param  string  $color  Warna Bootstrap: primary|success|warning|danger|info.
     * @param  string|null  $actor  Nama pengirim aksi.
     */
    public function __construct(
        public readonly string $title,
        public readonly string $url,
        public readonly string $icon = 'ki-notification-on',
        public readonly string $color = 'primary',
        public readonly ?string $actor = null,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return in_array(config('broadcasting.default'), ['reverb', 'pusher', 'ably'], true)
            ? ['database', 'broadcast']
            : ['database'];
    }

    /** @return array<string, string|null> */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'url' => $this->url,
            'icon' => $this->icon,
            'color' => $this->color,
            'actor' => $this->actor,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
