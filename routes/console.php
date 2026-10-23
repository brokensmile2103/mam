<?php

use Illuminate\Support\Facades\Schedule;

// Sinh sẵn công việc định kỳ mỗi đêm. Không bắt buộc: app cũng tự sinh khi mở "Hôm nay"/"Công việc".
// Bật bằng cron: * * * * * cd /đường/dẫn/dự/án && php artisan schedule:run >> /dev/null 2>&1
Schedule::command('mam:generate-tasks')->dailyAt('00:10');
