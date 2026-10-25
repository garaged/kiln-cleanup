<?php

declare(strict_types=1);

namespace KilnCleanup\Application;

use KilnCleanup\Domain\JobState;
use RuntimeException;

final readonly class BackgroundReviewJobWorker
{
    public function __construct(
        private ReviewJobClaimStore $claims,
        private ReviewJobRepository $jobs,
        private PersistentReviewJobCommandService $commands,
        private ReviewJobProgressService $progress,
        private ReviewJobSnapshotCodec $snapshots,
    ) {
    }

    public function runOne(?int $requestedJobId = null, int $batchSize = 100): bool
    {
        $batchSize = min(500, max(25, $batchSize));
        $token = wp_generate_uuid4();
        $job = $this->claims->claimNext($token, 120, $requestedJobId);
        if ($job === null) {
            return false;
        }
        try {
            if ($job->state === JobState::Ready) {
                $this->commands->start($job->id, $job->state, $job->createdBy);
                $job = $this->jobs->get($job->id) ?? throw new RuntimeException('Claimed review job disappeared.');
            }
            if ($job->state !== JobState::Running) {
                throw new RuntimeException('Only ready or running review jobs may run in the background.');
            }
            $this->progress->run(
                $job,
                ReviewJobRuntime::engine(),
                $this->snapshots->policyFromSnapshot($job->policySnapshot),
                $this->snapshots->filterFromSnapshot($job->filterSnapshot),
                $batchSize,
            );
            $this->claims->release($job->id, $token);
            return true;
        } catch (\Throwable $error) {
            $this->claims->fail($job->id, $token, $error->getMessage());
            throw $error;
        }
    }
}
