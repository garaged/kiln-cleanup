<?php

declare(strict_types=1);

namespace KilnCleanup\Application;

use KilnCleanup\Detection\DetectionEngine;
use KilnCleanup\Infrastructure\ReviewJobAuditRecorder;
use KilnCleanup\Infrastructure\WpdbCandidateSnapshotRepository;
use KilnCleanup\Infrastructure\WpdbReviewJobCursorStore;
use KilnCleanup\Infrastructure\WpdbReviewJobStateStore;
use KilnCleanup\Infrastructure\WpdbUserInventoryRepository;

final class ReviewJobRuntime
{
    public static function progress(): ReviewJobProgressService
    {
        return new ReviewJobProgressService(
            new ReviewJobScanStep(new WpdbUserInventoryRepository(), new WpdbCandidateSnapshotRepository(), new ReviewJobLifecycle()),
            new WpdbReviewJobCursorStore(),
            new WpdbReviewJobStateStore(),
            new ReviewJobAuditRecorder(),
        );
    }

    public static function engine(): DetectionEngine
    {
        return new DetectionEngine();
    }
}
