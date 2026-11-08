<?php

namespace App\View\Composers;

use App\Support\TeamNavigation;
use App\Support\Tenancy\CurrentTeam;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

/**
 * Data untuk layout area tim: struktur menu + notifikasi lonceng.
 * Query dijalankan di sini, bukan di Blade.
 */
class TeamLayoutComposer
{
    public function __construct(private readonly CurrentTeam $current) {}

    public function compose(View $view): void
    {
        $user = auth()->user();
        $team = $this->current->get();

        if ($user === null || $team === null) {
            $view->with(['teamNav' => [], 'notifications' => [], 'notificationCount' => 0]);

            return;
        }

        $view->with([
            'teamNav' => TeamNavigation::groups($team, $user),
            'notifications' => $user->notifications()->latest()->limit(6)->get()
                ->map(fn (DatabaseNotification $n) => [
                    'title' => (string) ($n->data['title'] ?? ''),
                    'url' => route('team.notifications.open', $n->id),
                    'icon' => (string) ($n->data['icon'] ?? 'ki-notification-on'),
                    'color' => (string) ($n->data['color'] ?? 'primary'),
                    'actor' => (string) ($n->data['actor'] ?? config('app.name')),
                    'time' => $n->created_at?->locale('id')->diffForHumans() ?? '',
                    'unread' => $n->read_at === null,
                ])->all(),
            'notificationCount' => $user->unreadNotifications()->count(),
        ]);
    }
}
