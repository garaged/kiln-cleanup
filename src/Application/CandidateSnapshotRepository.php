<?php

declare(strict_types=1);

namespace KilnCleanup\Application;

use KilnCleanup\Domain\CandidateSnapshot;

interface CandidateSnapshotRepository
{
    /** Returns false when the job/user candidate was already persisted. */
    public function insert(CandidateSnapshot $candidate): bool;
}
