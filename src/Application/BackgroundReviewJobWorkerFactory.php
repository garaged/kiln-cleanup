<?php

declare(strict_types=1);

namespace KilnCleanup\Application;

use KilnCleanup\Infrastructure\ReviewJobAuditRecorder;
use KilnCleanup\Infrastructure\WpdbReviewJobClaimStore;
use KilnCleanup\Infrastructure\WpdbReviewJobRepository;
use KilnCleanup\Infrastructure\WpdbReviewJobStateStore;

final class BackgroundReviewJobWorkerFactory
{
    public static function create(): BackgroundReviewJobWorker
    {
        $audit = new ReviewJobAuditRecorder();
        return new BackgroundReviewJobWorker(
            new WpdbReviewJobClaimStore(),
            new WpdbReviewJobRepository(),
            new PersistentReviewJobCommandService(new ReviewJobCommandService(new ReviewJobLifecycle()), new WpdbReviewJobStateStore(), $audit),
            ReviewJobRuntime::progress(),
            new ReviewJobSnapshotCodec(),
        );
    }
}
