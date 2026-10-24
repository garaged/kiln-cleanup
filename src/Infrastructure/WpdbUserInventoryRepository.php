<?php

declare(strict_types=1);

namespace KilnCleanup\Infrastructure;

use KilnCleanup\Application\UserInventoryRepository;
use KilnCleanup\Domain\InventoryFilter;
use KilnCleanup\Domain\UserInventoryPage;
use KilnCleanup\Domain\UserInventoryRecord;

final class WpdbUserInventoryRepository implements UserInventoryRepository
{
    public function pageAfter(int $cursor, int $limit, InventoryFilter $filter): UserInventoryPage
    {
        global $wpdb;

        $clauses = ['ID > %d'];
        $arguments = [$cursor];

        if ($filter->registeredBefore !== null) {
            $clauses[] = 'user_registered < %s';
            $arguments[] = $filter->registeredBefore->format('Y-m-d H:i:s');
        }

        $arguments[] = $limit + 1;
        $sql = sprintf(
            'SELECT ID, user_login, user_email, user_registered FROM %s WHERE %s ORDER BY ID ASC LIMIT %%d',
            $wpdb->users,
            implode(' AND ', $clauses),
        );

        $rows = $wpdb->get_results(
            $wpdb->prepare($sql, $arguments),
            ARRAY_A,
        );

        $hasNextPage = count($rows) > $limit;
        if ($hasNextPage) {
            array_pop($rows);
        }

        $records = array_map(
            static fn (array $row): UserInventoryRecord => new UserInventoryRecord(
                (int) $row['ID'],
                (string) $row['user_login'],
                (string) $row['user_email'],
                new \DateTimeImmutable((string) $row['user_registered'], new \DateTimeZone('UTC')),
            ),
            $rows,
        );

        $nextCursor = $hasNextPage ? $records[array_key_last($records)]->id : null;

        return new UserInventoryPage($records, $nextCursor);
    }
}
