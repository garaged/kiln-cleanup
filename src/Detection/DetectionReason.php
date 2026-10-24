<?php

declare(strict_types=1);

namespace KilnCleanup\Detection;

final readonly class DetectionReason
{
    public function __construct(
        public string $ruleId,
        public int $points,
        public string $message,
    ) {
    }
}
