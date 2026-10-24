<?php

declare(strict_types=1);

namespace KilnCleanup\Application;

use KilnCleanup\Detection\DetectionPolicy;
use KilnCleanup\Domain\InventoryFilter;
use InvalidArgumentException;

final class ReviewJobSnapshotCodec
{
    /** @return array<string, mixed> */
    public function policySnapshot(DetectionPolicy $policy): array
    {
        return [
            'version' => $policy->version,
            'protected_user_ids' => $policy->protectedUserIds,
            'protected_emails' => $policy->protectedEmails,
            'blocked_email_domains' => $policy->blockedEmailDomains,
        ];
    }

    /** @return array<string, scalar|null> */
    public function filterSnapshot(InventoryFilter $filter): array
    {
        return ['registered_before' => $filter->registeredBefore?->format('Y-m-d')];
    }

    public function policyFromSnapshot(array $snapshot): DetectionPolicy
    {
        return new DetectionPolicy(
            $this->string($snapshot, 'version'),
            $this->integerList($snapshot, 'protected_user_ids'),
            $this->stringList($snapshot, 'protected_emails'),
            $this->stringList($snapshot, 'blocked_email_domains'),
        );
    }

    public function filterFromSnapshot(array $snapshot): InventoryFilter
    {
        $value = $snapshot['registered_before'] ?? null;
        if ($value !== null && ! is_string($value)) {
            throw new InvalidArgumentException('Review-job filter snapshot is invalid.');
        }

        return InventoryFilter::fromRegisteredBeforeInput($value);
    }

    private function string(array $snapshot, string $key): string
    {
        if (! isset($snapshot[$key]) || ! is_string($snapshot[$key])) {
            throw new InvalidArgumentException('Review-job policy snapshot is invalid.');
        }
        return $snapshot[$key];
    }

    /** @return list<int> */
    private function integerList(array $snapshot, string $key): array
    {
        $value = $snapshot[$key] ?? [];
        if (! is_array($value) || array_filter($value, static fn (mixed $item): bool => ! is_int($item)) !== []) {
            throw new InvalidArgumentException('Review-job policy snapshot is invalid.');
        }
        return array_values($value);
    }

    /** @return list<string> */
    private function stringList(array $snapshot, string $key): array
    {
        $value = $snapshot[$key] ?? [];
        if (! is_array($value) || array_filter($value, static fn (mixed $item): bool => ! is_string($item)) !== []) {
            throw new InvalidArgumentException('Review-job policy snapshot is invalid.');
        }
        return array_values($value);
    }
}
