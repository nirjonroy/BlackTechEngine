<?php

namespace App\Console\Commands;

use App\Services\Subscriptions\SubscriptionPeriodManager;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('subscriptions:process-periods {--limit=100 : Maximum subscriptions to inspect}')]
#[Description('Advance or expire due subscription periods without charging customers')]
class ProcessSubscriptionPeriods extends Command
{
    public function handle(SubscriptionPeriodManager $periods): int
    {
        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT);

        if ($limit === false || $limit < 1 || $limit > 500) {
            $this->error('The --limit option must be an integer between 1 and 500.');

            return self::FAILURE;
        }

        $result = $periods->processDue($limit);

        $this->components->info(sprintf(
            'Inspected %d subscription(s): %d advanced, %d past due, %d cancelled, %d expired.',
            $result['inspected'],
            $result['advanced'],
            $result['past_due'],
            $result['cancelled'],
            $result['expired'],
        ));

        return self::SUCCESS;
    }
}
