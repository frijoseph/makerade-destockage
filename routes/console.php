<?php

use App\Models\AiConversation;
use App\Models\Setting;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

Schedule::call(function (): void {
    // Previewed spreadsheets that were never confirmed.
    foreach (Storage::disk('local')->files('imports') as $file) {
        if (Storage::disk('local')->lastModified($file) < now()->subDay()->getTimestamp()) {
            Storage::disk('local')->delete($file);
        }
    }
})->daily()->name('product-imports-cleanup');

Schedule::call(function (): void {
    $days = (int) Setting::get('ai_retention_days');

    if ($days > 0) {
        AiConversation::query()->where('last_message_at', '<', now()->subDays($days))->delete();
    }
})->daily()->name('ai-conversations-retention');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
