<?php

declare(strict_types=1);

namespace KilnCleanup\Infrastructure;

final class DatabaseSchema
{
    public const VERSION = '4';

    public static function maybeUpgrade(): void
    {
        if (get_option('kiln_cleanup_schema_version') !== self::VERSION) {
            self::install();
        }
    }

    public static function install(): void
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charsetCollate = $wpdb->get_charset_collate();
        $jobs = $wpdb->prefix . 'kiln_cleanup_jobs';
        $candidates = $wpdb->prefix . 'kiln_cleanup_candidates';
        $audit = $wpdb->prefix . 'kiln_cleanup_audit_events';
        $quarantines = $wpdb->prefix . 'kiln_cleanup_quarantines';

        dbDelta("CREATE TABLE {$jobs} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            type varchar(64) NOT NULL,
            state varchar(32) NOT NULL,
            policy_snapshot longtext NOT NULL,
            cursor_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            total_candidates bigint(20) unsigned NOT NULL DEFAULT 0,
            processed_candidates bigint(20) unsigned NOT NULL DEFAULT 0,
            created_by bigint(20) unsigned NOT NULL,
            created_at datetime NOT NULL,
            started_at datetime NULL,
            finished_at datetime NULL,
            PRIMARY KEY  (id),
            KEY state_created_at (state, created_at)
        ) {$charsetCollate};");

        dbDelta("CREATE TABLE {$candidates} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            job_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            score smallint(5) unsigned NOT NULL,
            band varchar(32) NOT NULL,
            policy_version varchar(64) NOT NULL,
            reasons longtext NOT NULL,
            state varchar(32) NOT NULL,
            created_at datetime NOT NULL,
            acted_at datetime NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY job_user (job_id, user_id),
            KEY job_state_user (job_id, state, user_id),
            KEY user_id (user_id)
        ) {$charsetCollate};");

        dbDelta("CREATE TABLE {$audit} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            event_type varchar(64) NOT NULL,
            job_id bigint(20) unsigned NULL,
            target_user_id bigint(20) unsigned NULL,
            actor_user_id bigint(20) unsigned NOT NULL,
            context longtext NOT NULL,
            occurred_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY job_occurred_at (job_id, occurred_at),
            KEY target_occurred_at (target_user_id, occurred_at)
        ) {$charsetCollate};");

        dbDelta("CREATE TABLE {$quarantines} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            source_job_id bigint(20) unsigned NOT NULL,
            state varchar(16) NOT NULL,
            original_capabilities longtext NOT NULL,
            original_user_level bigint(20) NOT NULL,
            quarantined_by bigint(20) unsigned NOT NULL,
            quarantined_at datetime NOT NULL,
            restored_by bigint(20) unsigned NULL,
            restored_at datetime NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY user_id (user_id),
            KEY state_quarantined_at (state, quarantined_at),
            KEY source_job_id (source_job_id)
        ) {$charsetCollate};");

        update_option('kiln_cleanup_schema_version', self::VERSION, false);
    }
}
