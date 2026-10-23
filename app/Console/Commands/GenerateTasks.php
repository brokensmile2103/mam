<?php

namespace App\Console\Commands;

use App\Models\Center;
use App\Services\TaskScheduler;
use Illuminate\Console\Command;

class GenerateTasks extends Command
{
    protected $signature = 'mam:generate-tasks';

    protected $description = 'Sinh công việc từ các lịch định kỳ cho mọi nhóm trẻ đang hoạt động (an toàn khi chạy lặp lại)';

    public function handle(TaskScheduler $scheduler): int
    {
        $total = 0;

        Center::query()->where('is_active', true)->chunkById(100, function ($centers) use ($scheduler, &$total) {
            foreach ($centers as $center) {
                $total += $scheduler->generateForCenter($center);
            }
        });

        $this->info("Đã sinh {$total} công việc mới.");

        return self::SUCCESS;
    }
}
