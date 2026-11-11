<?php

namespace App\Console\Commands;

use App\Models\ArticleSchedule;
use App\Services\Autopilot\AutopilotProcessor;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('autopilot:process {--limit=10 : Maximum due schedules to process} {--schedule= : Run a specific schedule ID}')]
#[Description('Process due SEO autopilot article schedules safely and bounded')]
class ProcessAutopilotSchedulesCommand extends Command
{
    public function handle(AutopilotProcessor $processor): int
    {
        if (! config('ai.autopilot_enabled')) {
            $this->components->warn('Autopilot execution is disabled globally (AI_AUTOPILOT_ENABLED=false). No schedules were processed.');

            return self::SUCCESS;
        }

        if (! config('ai.content_generation_enabled')) {
            $this->components->warn('AI content generation is disabled globally (AI_CONTENT_GENERATION_ENABLED=false). No schedules were processed.');

            return self::SUCCESS;
        }

        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT);
        if ($limit === false || $limit < 1 || $limit > 100) {
            $this->error('The --limit option must be an integer between 1 and 100.');

            return self::FAILURE;
        }

        $query = ArticleSchedule::query()
            ->where('is_active', true)
            ->where('failure_count', '<', (int) config('ai.autopilot_max_retries', 3));

        if ($specificId = $this->option('schedule')) {
            $query->whereKey($specificId);
        } else {
            $query->where('next_run_at', '<=', now());
        }

        $schedules = $query->orderBy('next_run_at')->limit($limit)->get();

        if ($schedules->isEmpty()) {
            $this->components->info('No autopilot schedules are due for processing.');

            return self::SUCCESS;
        }

        $processed = 0;
        $failed = 0;

        foreach ($schedules as $schedule) {
            $this->line("Processing schedule #{$schedule->id}: {$schedule->title}...");
            $result = $processor->process($schedule);

            if ($result['success']) {
                $processed++;
                $this->components->info("Schedule #{$schedule->id}: {$result['message']}");
            } else {
                $failed++;
                $this->components->error("Schedule #{$schedule->id}: {$result['message']}");
            }
        }

        $this->components->info(sprintf(
            'Autopilot run finished: %d schedule(s) processed (%d successful, %d failed).',
            $schedules->count(),
            $processed,
            $failed,
        ));

        return self::SUCCESS;
    }
}

