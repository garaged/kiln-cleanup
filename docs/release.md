# Release verification

Run:

    make release-package
    make release-verify
    make container-preflight
    make container-test
    make container-down

The package command creates a ZIP containing production Composer dependencies and a SHA-256 checksum. The container smoke test installs and activates that plugin source, then verifies the schema tables. Record the package checksum and Rancher Desktop ARM64 result in the PR before merge.
