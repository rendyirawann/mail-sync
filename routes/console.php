<?php

use App\Services\Saas\SubscriptionService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Bekukan tim yang lewat jatuh tempo + masa tenggang. Aman dijalankan berulang.
Artisan::command('saas:freeze-expired', function (SubscriptionService $subscriptions) {
    $count = $subscriptions->freezeExpired();
    $this->info("{$count} tim dibekukan.");
})->purpose('Bekukan tim yang masa langganannya habis (setelah masa tenggang)');

Schedule::command('saas:freeze-expired')->dailyAt('00:15')->withoutOverlapping();
