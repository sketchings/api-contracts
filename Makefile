SHELL := /bin/bash

ARAZZO_DIR := ./arazzo
LOG_DIR := ./logs
SYMFONY_DIR := ./symfony-app
SCHEMATHESIS_VERSION := 4.31.1
API_URL ?= https://localhost:8000

# Basic auth credentials for MuseumPlaceholderAuth, read by the Arazzo workflows as $inputs.env.*.
# Override from the environment or with `make arazzo-test MUSEUM_API_USER=... MUSEUM_API_PASSWORD=...`.
MUSEUM_API_USER ?= museum
MUSEUM_API_PASSWORD ?= museum
export MUSEUM_API_USER MUSEUM_API_PASSWORD

openapi-lint:
	npx @redocly/cli@latest lint spec/openapi.yaml

openapi-bundle: openapi-lint
	npx @redocly/cli@latest bundle spec/openapi.yaml --output openapi.yaml

openapi-preview:
	npx @redocly/cli@latest preview

arazzo-lint:
	npx @redocly/cli@latest lint arazzo/museum-api.arazzo.yaml

arazzo-test:
	@failed=0; \
	found=0; \
	while IFS= read -r -d '' file; do \
		found=1; \
		filename=$$(basename "$$file"); \
		name=$${filename%.arazzo.yaml}; \
		echo "Running Respect on: $$file"; \
		if npx @redocly/cli@latest respect "$$file" \
			--har-output="$(LOG_DIR)/$$name.har"; then \
			echo "PASS: $$file"; \
		else \
			echo "FAIL: $$file"; \
			failed=1; \
		fi; \
	done < <(find "$(ARAZZO_DIR)" -type f -name '*.arazzo.yaml' -print0); \
	if [ "$$found" -eq 0 ]; then \
		echo "No Arazzo files found in $(ARAZZO_DIR)"; \
		exit 1; \
	fi; \
	$(MAKE) --no-print-directory arazzo-db-reset || failed=1; \
	exit $$failed

# Run Respect on one Arazzo file, e.g. `make respect FILE=exercises/special-events.arazzo.yaml`.
respect:
	@test -n "$(FILE)" || { echo "Usage: make respect FILE=path/to/file.arazzo.yaml"; exit 2; }
	@npx @redocly/cli@latest respect "$(FILE)" --har-output="$(LOG_DIR)/$$(basename "$(FILE)" .arazzo.yaml).har"; \
	status=$$?; \
	$(MAKE) --no-print-directory arazzo-db-reset || status=1; \
	exit $$status

# Restore the Symfony dev database to its seeded state, removing the tickets and
# events created by the Arazzo workflows and Schemathesis.
arazzo-db-reset:
	@echo "Resetting the $(SYMFONY_DIR) dev database"
	@cd "$(SYMFONY_DIR)" && \
		php bin/console doctrine:schema:drop --force --full-database -q && \
		php bin/console doctrine:migrations:migrate -n -q && \
		php bin/console app:seed-events -q

# Property-based API tests generated from openapi.yaml, run against the Symfony app
# at $(API_URL). Settings and auth are in schemathesis.toml. Requires uv
# (https://docs.astral.sh/uv/). Pass extra options with SCHEMATHESIS_ARGS, e.g.
# `make schemathesis SCHEMATHESIS_ARGS="--max-examples 10 --report junit"`.
schemathesis:
	@uvx schemathesis==$(SCHEMATHESIS_VERSION) run openapi.yaml --url "$(API_URL)" $(SCHEMATHESIS_ARGS); \
	status=$$?; \
	$(MAKE) --no-print-directory arazzo-db-reset || status=1; \
	exit $$status

# Run the Symfony app's PHPUnit suite (functional + Gesso contract tests).
# Pass extra options with `make app-tests PHPUNIT_ARGS="--filter Tickets"`.
app-tests:
	cd "$(SYMFONY_DIR)" && php bin/phpunit $(PHPUNIT_ARGS)