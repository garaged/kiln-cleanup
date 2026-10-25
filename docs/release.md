# Release verification

Run:

    make release-package
    make release-verify
    make container-preflight
    make container-test
    make container-down

The package command creates a ZIP containing production Composer dependencies and a SHA-256 checksum. The container smoke test installs and activates that plugin source, then verifies schema, review, quarantine, deletion, and prevention invariants. Record the package checksum, WordPress/PHP versions, and Rancher Desktop ARM64 result in the PR before merge.

For a supported production host, also record the bounded review-job performance observation described in [operations](operations.md). Do not replace the package checksum or lifecycle acceptance with a performance observation.
