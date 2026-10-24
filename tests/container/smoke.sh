#!/bin/sh
set -eu

until wp db check --allow-root >/dev/null 2>&1; do sleep 2; done
wp core is-installed --allow-root >/dev/null 2>&1 || wp core install --url=http://localhost --title='Kiln Cleanup Smoke' --admin_user=admin --admin_password=not-a-real-secret --admin_email=admin@example.test --skip-email --allow-root
rm -rf wp-content/plugins/kiln-cleanup
cp -R /workspace/kiln-cleanup wp-content/plugins/kiln-cleanup
mkdir -p wp-content/plugins/kiln-cleanup/vendor
cat > wp-content/plugins/kiln-cleanup/vendor/autoload.php <<'PHP'
<?php
spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'KilnCleanup\\')) {
        require __DIR__ . '/../src/' . str_replace('\\', '/', substr($class, 12)) . '.php';
    }
});
PHP
wp plugin activate kiln-cleanup --allow-root
wp eval 'global $wpdb; foreach (["kiln_cleanup_jobs","kiln_cleanup_candidates","kiln_cleanup_audit_events"] as $table) { if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $wpdb->prefix . $table)) !== $wpdb->prefix . $table) { throw new RuntimeException("Missing table: " . $table); }}' --allow-root
wp plugin is-active kiln-cleanup --allow-root
echo "Container smoke checks passed."
