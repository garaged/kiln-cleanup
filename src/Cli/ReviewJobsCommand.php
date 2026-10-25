<?php

declare(strict_types=1);

namespace KilnCleanup\Cli;

use KilnCleanup\Application\BackgroundReviewJobWorkerFactory;

final class ReviewJobsCommand
{
    /** @param array<int, string> $args @param array<string, string> $assocArgs */
    public function run(array $args, array $assocArgs): void
    {
        $jobId = isset($assocArgs['job']) ? (int) $assocArgs['job'] : null;
        $batches = min(100, max(1, (int) ($assocArgs['batches'] ?? 1)));
        $batchSize = min(500, max(25, (int) ($assocArgs['batch-size'] ?? 100)));
        $worker = BackgroundReviewJobWorkerFactory::create();
        $ran = 0;
        for ($i = 0; $i < $batches; $i++) {
            if (! $worker->runOne($jobId, $batchSize)) {
                break;
            }
            $ran++;
            if ($jobId !== null) {
                $jobId = null;
            }
        }
        \WP_CLI::success(sprintf('Processed %d bounded review-job batch(es).', $ran));
    }
}
