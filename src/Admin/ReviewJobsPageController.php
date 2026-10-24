<?php

declare(strict_types=1);

namespace KilnCleanup\Admin;

use KilnCleanup\Application\PersistentReviewJobCommandService;
use KilnCleanup\Application\ReviewJobCommandService;
use KilnCleanup\Application\ReviewJobLifecycle;
use KilnCleanup\Application\ReviewJobProgressService;
use KilnCleanup\Application\ReviewJobScanStep;
use KilnCleanup\Application\ReviewJobSnapshotCodec;
use KilnCleanup\Detection\DetectionEngine;
use KilnCleanup\Domain\InventoryFilter;
use KilnCleanup\Infrastructure\ReviewJobAuditRecorder;
use KilnCleanup\Infrastructure\WordPressDetectionPolicyRepository;
use KilnCleanup\Infrastructure\WpdbCandidateSnapshotRepository;
use KilnCleanup\Infrastructure\WpdbReviewJobCursorStore;
use KilnCleanup\Infrastructure\WpdbReviewJobRepository;
use KilnCleanup\Infrastructure\WpdbReviewJobStateStore;
use KilnCleanup\Infrastructure\WpdbUserInventoryRepository;
use RuntimeException;

final class ReviewJobsPageController
{
    private const PAGE = 'kiln-cleanup-review-jobs';
    private const ACTION = 'kiln_cleanup_review_job';

    public function register(): void
    {
        add_management_page(__('Kiln Cleanup Review Jobs', 'kiln-cleanup'), __('Kiln Cleanup Jobs', 'kiln-cleanup'), 'manage_options', self::PAGE, [$this, 'render']);
        add_action('admin_post_' . self::ACTION, [$this, 'handlePost']);
    }

    public function handlePost(): void
    {
        $this->authorize();
        check_admin_referer(self::ACTION);

        try {
            $operation = isset($_POST['operation']) ? sanitize_key(wp_unslash($_POST['operation'])) : '';
            if ($operation === 'create') {
                $registeredBefore = isset($_POST['registered_before']) ? sanitize_text_field(wp_unslash($_POST['registered_before'])) : '';
                $filter = InventoryFilter::fromRegisteredBeforeInput($registeredBefore);
                $codec = new ReviewJobSnapshotCodec();
                $job = (new WpdbReviewJobRepository())->create(
                    $codec->policySnapshot((new WordPressDetectionPolicyRepository())->load()),
                    $codec->filterSnapshot($filter),
                    get_current_user_id(),
                );
                (new ReviewJobAuditRecorder())->recordCreated($job->id, get_current_user_id(), $job->policySnapshot, $job->filterSnapshot);
                (new PersistentReviewJobCommandService(
                    new ReviewJobCommandService(new ReviewJobLifecycle()),
                    new WpdbReviewJobStateStore(),
                    new ReviewJobAuditRecorder(),
                ))->prepare($job->id, $job->state, get_current_user_id());
                $this->redirect('created', $job->id);
            }

            $jobId = isset($_POST['job_id']) ? absint(wp_unslash($_POST['job_id'])) : 0;
            $repository = new WpdbReviewJobRepository();
            $job = $repository->get($jobId) ?? throw new RuntimeException('Review job was not found.');
            $audit = new ReviewJobAuditRecorder();
            $commands = new PersistentReviewJobCommandService(new ReviewJobCommandService(new ReviewJobLifecycle()), new WpdbReviewJobStateStore(), $audit);

            if ($operation === 'pause') {
                $commands->pause($job->id, $job->state, get_current_user_id());
            } elseif ($operation === 'resume') {
                $commands->resume($job->id, $job->state, get_current_user_id());
            } elseif ($operation === 'cancel') {
                $commands->cancel($job->id, $job->state, get_current_user_id());
            } elseif ($operation === 'scan') {
                if ($job->state->value === 'ready') {
                    $commands->start($job->id, $job->state, get_current_user_id());
                    $job = $repository->get($job->id) ?? throw new RuntimeException('Review job was not found.');
                }
                if ($job->state->value !== 'running') {
                    throw new RuntimeException('Only a ready or running review job can scan.');
                }
                $codec = new ReviewJobSnapshotCodec();
                (new ReviewJobProgressService(
                    new ReviewJobScanStep(new WpdbUserInventoryRepository(), new WpdbCandidateSnapshotRepository(), new ReviewJobLifecycle()),
                    new WpdbReviewJobCursorStore(),
                    new WpdbReviewJobStateStore(),
                    $audit,
                ))->run($job, new DetectionEngine(), $codec->policyFromSnapshot($job->policySnapshot), $codec->filterFromSnapshot($job->filterSnapshot), 100);
            } else {
                throw new RuntimeException('Unsupported review job operation.');
            }

            $this->redirect('updated', $job->id);
        } catch (\Throwable $error) {
            $this->redirect('error', 0, $error->getMessage());
        }
    }

    public function render(): void
    {
        $this->authorize();
        $notice = isset($_GET['kiln_cleanup_notice']) ? sanitize_key(wp_unslash($_GET['kiln_cleanup_notice'])) : '';
        $error = isset($_GET['kiln_cleanup_error']) ? sanitize_text_field(wp_unslash($_GET['kiln_cleanup_error'])) : '';
        echo '<div class="wrap"><h1>' . esc_html__('Kiln Cleanup: Review Jobs', 'kiln-cleanup') . '</h1>';
        echo '<p>' . esc_html__('Review jobs snapshot the current local policy and filter. They only create evidence; they never change a WordPress user, role, login, session, or content.', 'kiln-cleanup') . '</p>';
        if ($notice !== '') {
            echo '<div class="notice notice-success"><p>' . esc_html__('Review job updated.', 'kiln-cleanup') . '</p></div>';
        }
        if ($error !== '') {
            echo '<div class="notice notice-error"><p>' . esc_html($error) . '</p></div>';
        }
        $this->renderCreateForm();
        $this->renderJobs();
        echo '</div>';
    }

    private function renderCreateForm(): void
    {
        echo '<h2>' . esc_html__('Create review job', 'kiln-cleanup') . '</h2><form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field(self::ACTION);
        echo '<input type="hidden" name="action" value="' . esc_attr(self::ACTION) . '"><input type="hidden" name="operation" value="create">';
        echo '<label for="registered_before">' . esc_html__('Registered before', 'kiln-cleanup') . '</label> <input id="registered_before" name="registered_before" type="date"> ';
        submit_button(__('Create immutable review job', 'kiln-cleanup'), 'primary', 'submit', false);
        echo '</form>';
    }

    private function renderJobs(): void
    {
        $jobs = (new WpdbReviewJobRepository())->listRecent();
        echo '<h2>' . esc_html__('Recent jobs', 'kiln-cleanup') . '</h2><table class="widefat striped"><thead><tr><th>ID</th><th>State</th><th>Cursor</th><th>Policy version</th><th>Filter</th><th>Controls</th></tr></thead><tbody>';
        foreach ($jobs as $job) {
            $filter = $job->filterSnapshot['registered_before'] ?? __('None', 'kiln-cleanup');
            $version = $job->policySnapshot['version'] ?? __('Unknown', 'kiln-cleanup');
            echo '<tr><td>' . esc_html((string) $job->id) . '</td><td>' . esc_html($job->state->value) . '</td><td>' . esc_html((string) $job->cursorUserId) . '</td><td>' . esc_html((string) $version) . '</td><td>' . esc_html((string) ($filter ?: __('None', 'kiln-cleanup'))) . '</td><td>';
            foreach ($this->operationsFor($job->state->value) as $operation => $label) {
                $this->renderOperation($job->id, $operation, $label);
            }
            echo '</td></tr>';
        }
        if ($jobs === []) {
            echo '<tr><td colspan="6">' . esc_html__('No review jobs yet.', 'kiln-cleanup') . '</td></tr>';
        }
        echo '</tbody></table>';
    }

    /** @return array<string, string> */
    private function operationsFor(string $state): array
    {
        return match ($state) {
            'ready' => ['scan' => __('Run one safe scan step', 'kiln-cleanup'), 'cancel' => __('Cancel', 'kiln-cleanup')],
            'running' => ['scan' => __('Run one safe scan step', 'kiln-cleanup'), 'pause' => __('Pause', 'kiln-cleanup'), 'cancel' => __('Cancel', 'kiln-cleanup')],
            'paused', 'failed' => ['resume' => __('Resume', 'kiln-cleanup'), 'cancel' => __('Cancel', 'kiln-cleanup')],
            'draft' => ['cancel' => __('Cancel', 'kiln-cleanup')],
            default => [],
        };
    }

    private function renderOperation(int $jobId, string $operation, string $label): void
    {
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="display:inline">';
        wp_nonce_field(self::ACTION);
        echo '<input type="hidden" name="action" value="' . esc_attr(self::ACTION) . '"><input type="hidden" name="operation" value="' . esc_attr($operation) . '"><input type="hidden" name="job_id" value="' . esc_attr((string) $jobId) . '">';
        submit_button($label, 'secondary small', 'submit', false);
        echo '</form> ';
    }

    private function authorize(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to manage review jobs.', 'kiln-cleanup'));
        }
    }

    private function redirect(string $notice, int $jobId = 0, string $error = ''): never
    {
        $query = ['page' => self::PAGE, 'kiln_cleanup_notice' => $notice];
        if ($jobId > 0) {
            $query['job_id'] = $jobId;
        }
        if ($error !== '') {
            $query['kiln_cleanup_error'] = $error;
        }
        wp_safe_redirect(add_query_arg($query, admin_url('tools.php')));
        exit;
    }
}
