<?php

declare(strict_types=1);

namespace KilnCleanup\Domain;

enum JobState: string
{
    case Draft = 'draft';
    case Ready = 'ready';
    case Running = 'running';
    case Paused = 'paused';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Draft => in_array($next, [self::Ready, self::Cancelled], true),
            self::Ready => in_array($next, [self::Running, self::Cancelled], true),
            self::Running => in_array($next, [self::Paused, self::Completed, self::Failed, self::Cancelled], true),
            self::Paused => in_array($next, [self::Ready, self::Cancelled], true),
            self::Failed => in_array($next, [self::Ready, self::Cancelled], true),
            self::Completed, self::Cancelled => false,
        };
    }
}
