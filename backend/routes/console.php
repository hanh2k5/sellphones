<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\DB;

Artisan::command('chat:cleanup', function () {
    $deleted = DB::table('ai_chats')->where('created_at', '<', now()->subDays(30))->delete();
    $this->info("Đã dọn dẹp {$deleted} tin nhắn cũ hơn 30 ngày.");
})->purpose('Dọn dẹp lịch sử chat AI cũ hơn 30 ngày');

Schedule::command('chat:cleanup')->daily();

