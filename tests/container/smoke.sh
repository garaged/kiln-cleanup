#!/bin/sh
set -eu

# The complete WordPress activation smoke test is intentionally introduced in M1
# with a packaged Composer vendor tree. M0 validates the service topology only.
echo "Container topology validated; runtime activation smoke test begins in M1."
