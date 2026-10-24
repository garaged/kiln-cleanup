<?php

declare(strict_types=1);

namespace KilnCleanup\Detection;

final readonly class DetectionResult
{
    /**
     * @param list<DetectionReason> $reasons
     */
    public function __construct(
        public int $score,
        public ScoreBand $band,
        public array $reasons,
        public bool $allowlisted,
        public string $policyVersion,
    ) {
    }

    public static function allowlisted(string $policyVersion): self
    {
        return new self(0, ScoreBand::Informational, [], true, $policyVersion);
    }
}
