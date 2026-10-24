<?php

declare(strict_types=1);

namespace KilnCleanup\Domain;

use InvalidArgumentException;

final readonly class InventoryFilter
{
    public function __construct(
        public ?\DateTimeImmutable $registeredBefore = null,
    ) {
    }

    public static function fromRegisteredBeforeInput(?string $value): self
    {
        if ($value === null || $value === '') {
            return new self();
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value, new \DateTimeZone('UTC'));
        $errors = \DateTimeImmutable::getLastErrors();

        if (
            $date === false
            || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
            || $date->format('Y-m-d') !== $value
        ) {
            throw new InvalidArgumentException('Registered-before must be an ISO date (YYYY-MM-DD).');
        }

        return new self($date);
    }
}
