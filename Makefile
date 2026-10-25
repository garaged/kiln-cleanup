.DEFAULT_GOAL := help

.PHONY: help ci quality container-preflight container-test container-manual-up container-manual-m8-fixture container-down release-package release-verify

help:
	@printf '%s\n' \
	  'make quality             Run Composer validation, unit tests, and syntax checks' \
	  'make ci                  Run all available local quality gates' \
	  'make container-preflight Validate the WordPress container stack configuration' \
	  'make container-test      Run the WordPress container smoke test (M1)' \
	  'make container-manual-up Provision WordPress for browser-based manual checks (default: http://localhost:8080)' \
	  'make container-manual-m8-fixture Create a disposable quarantined account for M8 browser acceptance' \
	  'make container-down      Stop the WordPress container stack and remove volumes'

quality:
	composer validate --strict
	composer install --no-interaction --prefer-dist --no-progress
	composer test
	composer lint

ci: quality container-preflight

container-preflight:
	docker compose -f docker-compose.test.yml config --quiet

container-test:
	docker compose -f docker-compose.test.yml run --rm smoke

container-manual-up: container-test
	@printf '%s\n' 'Manual WordPress is ready at http://localhost:$${KILN_MCP_TEST_PORT:-8080}/wp-admin (admin / not-a-real-secret).'

container-manual-m8-fixture:
	docker compose -f docker-compose.test.yml run --rm --entrypoint=wp smoke eval-file /workspace/kiln-cleanup/scripts/m8-manual-fixture.php --allow-root

release-package:
	sh scripts/package-release.sh

release-verify:
	sh scripts/verify-release.sh

container-down:
	docker compose -f docker-compose.test.yml down --volumes --remove-orphans
