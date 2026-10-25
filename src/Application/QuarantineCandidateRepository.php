<?php

declare(strict_types=1);

namespace KilnCleanup\Application;

interface QuarantineCandidateRepository
{
    /** @return array{job_id:int,user_id:int,band:string,policy_snapshot:array<string,mixed>}|null */
    public function eligibleCandidate(int $jobId, int $userId): ?array;

    /** @return list<array{job_id:int,user_id:int,email:string,band:string,policy_snapshot:array<string,mixed>}> */
    public function listEligible(int $limit): array;
}
