<?php

use KilnCleanup\Application\QuarantineService;
use KilnCleanup\Application\ReviewJobSnapshotCodec;
use KilnCleanup\Detection\DetectionPolicy;
use KilnCleanup\Domain\CandidateSnapshot;
use KilnCleanup\Domain\InventoryFilter;
use KilnCleanup\Infrastructure\QuarantineAuditRecorder;
use KilnCleanup\Infrastructure\WordPressUserAccountGateway;
use KilnCleanup\Infrastructure\WpdbCandidateSnapshotRepository;
use KilnCleanup\Infrastructure\WpdbQuarantineCandidateRepository;
use KilnCleanup\Infrastructure\WpdbQuarantineRepository;
use KilnCleanup\Infrastructure\WpdbReviewJobRepository;

global $wpdb;

$login = 'm8-delete-' . strtolower(wp_generate_password(12, false, false));
$email = $login . '@example.test';
$userId = wp_create_user($login, 'not-a-real-secret', $email);
if (is_wp_error($userId)) {
    throw new RuntimeException('M8 manual fixture account could not be created: ' . $userId->get_error_message());
}

$userId = (int) $userId;
(new WP_User($userId))->set_role('subscriber');

$policy = new DetectionPolicy('m8-manual-fixture-v1');
$snapshots = new ReviewJobSnapshotCodec();
$jobs = new WpdbReviewJobRepository();
$job = $jobs->create(
    $snapshots->policySnapshot($policy),
    $snapshots->filterSnapshot(new InventoryFilter()),
    1,
);
$wpdb->update($wpdb->prefix . 'kiln_cleanup_jobs', ['state' => 'completed'], ['id' => $job->id]);

$candidates = new WpdbCandidateSnapshotRepository();
if (! $candidates->insert(new CandidateSnapshot($job->id, $userId, 75, 'eligible_for_quarantine', 'm8-manual-fixture-v1', ['blocked_email_domain']))) {
    throw new RuntimeException('M8 manual fixture candidate could not be created.');
}

$quarantine = new QuarantineService(
    new WpdbQuarantineCandidateRepository(),
    new WpdbQuarantineRepository(),
    new WordPressUserAccountGateway(),
    new QuarantineAuditRecorder(),
);
$quarantine->quarantine($job->id, $userId, $policy, 1);

WP_CLI::success(sprintf('M8 deletion fixture ready: user ID %d (%s).', $userId, $email));
