<?php

declare(strict_types=1);

namespace KilnCleanup\Detection;

use InvalidArgumentException;

final readonly class DetectionPolicy
{
    /**
     * @param list<int> $protectedUserIds
     * @param list<string> $protectedEmails
     * @param list<string> $blockedEmailDomains
     */
    public function __construct(
        public string $version,
        public array $protectedUserIds = [],
        public array $protectedEmails = [],
        public array $blockedEmailDomains = [],
    ) {
        foreach ($this->protectedUserIds as $id) {
            if ($id < 1) {
                throw new InvalidArgumentException('Protected user IDs must be positive.');
            }
        }

        foreach ($this->protectedEmails as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                throw new InvalidArgumentException('Protected emails must be valid email addresses.');
            }
        }

        foreach ($this->blockedEmailDomains as $domain) {
            if (! preg_match('/\A[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)+\z/i', $domain)) {
                throw new InvalidArgumentException('Blocked email domains must be domain names, without @.');
            }
        }
    }

    public function protects(int $userId, string $email): bool
    {
        return in_array($userId, $this->protectedUserIds, true)
            || in_array(strtolower($email), array_map('strtolower', $this->protectedEmails), true);
    }

    public function blocksDomainFor(string $email): bool
    {
        $domain = strtolower((string) substr(strrchr($email, '@') ?: '', 1));

        return in_array($domain, array_map('strtolower', $this->blockedEmailDomains), true);
    }
}
