<?php

namespace App\Support;

use App\Enums\DispositionStatus;
use App\Models\Disposition;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * Struktur menu area tim — dipakai bersama oleh sidebar (mobile) dan menu
 * atas (desktop) agar keduanya selalu sama. Item yang tidak boleh diakses
 * peran user tidak ditampilkan.
 */
class TeamNavigation
{
    /**
     * @return list<array{title: string, items: list<array{label: string, route: string, icon: string, active: string, badge?: int}>}>
     */
    public static function groups(Team $team, User $user): array
    {
        $myOpen = Disposition::query()
            ->whereHas('recipient', fn ($q) => $q->where('user_id', $user->id))
            ->where('status', '!=', DispositionStatus::Selesai->value)
            ->count();

        $groups = [
            ['title' => '', 'items' => [
                ['label' => 'Dashboard', 'route' => 'team.dashboard', 'icon' => 'ki-element-11', 'active' => 'team.dashboard'],
            ]],
            ['title' => 'Surat', 'items' => [
                ['label' => 'Surat Masuk', 'route' => 'team.incoming.index', 'icon' => 'ki-sms', 'active' => 'team.incoming.*'],
                ['label' => 'Disposisi Saya', 'route' => 'team.dispositions.index', 'icon' => 'ki-send', 'active' => 'team.dispositions.*', 'badge' => $myOpen],
                ['label' => 'Surat Keluar', 'route' => 'team.outgoing.index', 'icon' => 'ki-directbox-default', 'active' => 'team.outgoing.*'],
                ['label' => 'Arsip & Pencarian', 'route' => 'team.archive.index', 'icon' => 'ki-archive', 'active' => 'team.archive.*'],
                ['label' => 'Laporan', 'route' => 'team.reports.index', 'icon' => 'ki-chart-simple', 'active' => 'team.reports.*'],
            ]],
        ];

        $optional = [];
        foreach (['lembur' => ['team.overtimes.index', 'ki-time', 'team.overtimes.*'],
            'perjadin' => ['team.trips.index', 'ki-airplane', 'team.trips.*'],
            'honorarium' => ['team.honoraria.index', 'ki-dollar', 'team.honoraria.*']] as $key => [$route, $icon, $active]) {
            if ($team->moduleEnabled($key)) {
                $optional[] = ['label' => Team::OPTIONAL_MODULES[$key], 'route' => $route, 'icon' => $icon, 'active' => $active];
            }
        }
        if ($optional !== []) {
            $groups[] = ['title' => 'Modul Tambahan', 'items' => $optional];
        }

        $data = [
            ['label' => 'Pegawai & Divisi', 'route' => 'team.employees.index', 'icon' => 'ki-profile-user', 'active' => 'team.employees.*'],
            ['label' => 'Klasifikasi & Kategori', 'route' => 'team.master.index', 'icon' => 'ki-category', 'active' => 'team.master.*'],
            ['label' => 'Template Surat', 'route' => 'team.templates.index', 'icon' => 'ki-document', 'active' => 'team.templates.*'],
        ];
        $groups[] = ['title' => 'Data', 'items' => $data];

        $teamItems = [];
        if (Gate::forUser($user)->allows('team.members.manage')) {
            $teamItems[] = ['label' => 'Anggota', 'route' => 'team.members.index', 'icon' => 'ki-people', 'active' => 'team.members.*'];
        }
        if (Gate::forUser($user)->allows('team.settings.manage')) {
            $teamItems[] = ['label' => 'Pengaturan Tim', 'route' => 'team.settings.edit', 'icon' => 'ki-setting-2', 'active' => 'team.settings.*'];
        }
        if (Gate::forUser($user)->allows('team.billing.manage')) {
            $teamItems[] = ['label' => 'Langganan', 'route' => 'team.billing.index', 'icon' => 'ki-wallet', 'active' => 'team.billing.*'];
        }
        if ($teamItems !== []) {
            $groups[] = ['title' => 'Tim', 'items' => $teamItems];
        }

        return $groups;
    }
}
