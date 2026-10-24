<?php

declare(strict_types=1);

namespace KilnCleanup\Detection;

enum ScoreBand: string
{
    case Informational = 'informational';
    case Review = 'review';
    case EligibleForQuarantine = 'eligible_for_quarantine';

    public static function fromScore(int $score): self
    {
        return match (true) {
            $score >= 70 => self::EligibleForQuarantine,
            $score >= 30 => self::Review,
            default => self::Informational,
        };
    }
}
