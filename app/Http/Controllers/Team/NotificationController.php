<?php

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

/**
 * Notifikasi lonceng: daftar, buka (tandai dibaca), tandai semua dibaca.
 */
class NotificationController extends Controller
{
    public function index(Request $request, Team $team): View
    {
        return view('team.notifications.index', [
            'notifications' => $request->user()->notifications()->latest()->paginate(20),
        ]);
    }

    public function open(Team $team, DatabaseNotification $notification): RedirectResponse
    {
        $notification->markAsRead();

        // Hanya izinkan tujuan di dalam tim ini (cegah open redirect).
        $url = (string) ($notification->data['url'] ?? '');
        $base = url($team->slug);

        return str_starts_with($url, $base.'/') ? redirect()->to($url) : redirect()->route('team.dashboard');
    }

    public function readAll(Request $request, Team $team): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }
}
