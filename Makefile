.PHONY: ci test prerequisites

# Use any most recent PHP version
PHP=$(shell which php)

# Default parallelism
JOBS=$(shell nproc)

# Default silencer if installed
SILENT=$(shell which chronic)

# PHP CS Fixer
PHP_CS_FIXER=vendor/bin/php-cs-fixer
PHP_CS_FIXER_ARGS=--cache-file=build/cache/.php_cs.cache --verbose

# PHPUnit
PHPUNIT=vendor/bin/phpunit
PHPUNIT_ARGS=--coverage-xml=build/logs/coverage-xml --log-junit=build/logs/junit.xml --coverage-clover=build/logs/clover.xml
PHPUNIT_GROUP=default

# Phan
PHAN=vendor/bin/phan
PHAN_ARGS=-j $(JOBS)
export PHAN_DISABLE_XDEBUG_WARN=1

# PHPStan
PHPSTAN=vendor/bin/phpstan
PHPSTAN_ARGS=analyse src tests --level=2 -c .phpstan.neon

# Psalm
PSALM=vendor/bin/psalm
PSALM_ARGS=--show-info=false

# Composer
COMPOSER=composer

# Infection
INFECTION=vendor/bin/infection
MIN_MSI=90
MIN_COVERED_MSI=100
INFECTION_ARGS=--min-msi=$(MIN_MSI) --min-covered-msi=$(MIN_COVERED_MSI) --threads=$(JOBS) --coverage=build/logs

all: test

##############################################################
# Continuous Integration                                     #
##############################################################

ci-test: SILENT=
ci-test: prerequisites
	$(SILENT) $(PHPDBG) $(PHPUNIT) $(PHPUNIT_COVERAGE_CLOVER) --group=$(PHPUNIT_GROUP)

ci-analyze: SILENT=
ci-analyze: ci

ci: SILENT=
ci: prerequisites ci-phpunit ci-analyze
	$(SILENT) $(COMPOSER) validate --strict

ci-phpunit: ci-cs
	$(SILENT) $(PHP) $(PHPUNIT) $(PHPUNIT_ARGS)
	cp build/logs/junit.xml build/logs/phpunit.junit.xml
	$(SILENT) $(PHP) $(INFECTION) $(INFECTION_ARGS) --quiet

ci-analyze: ci-cs
	$(SILENT) $(PHP) $(PHAN) $(PHAN_ARGS)
	$(SILENT) $(PHP) $(PHPSTAN) $(PHPSTAN_ARGS) --no-progress
	$(SILENT) $(PHP) $(PSALM) $(PSALM_ARGS) --no-cache

ci-cs: prerequisites
	$(SILENT) $(PHP) $(PHP_CS_FIXER) $(PHP_CS_FIXER_ARGS) --dry-run --stop-on-violation fix

##############################################################
# Development Workflow                                       #
##############################################################

test: phpunit analyze
	$(SILENT) $(COMPOSER) validate --strict

test-prerequisites: prerequisites composer.lock

phpunit: cs
	$(SILENT) $(PHP) $(PHPUNIT) $(PHPUNIT_ARGS) --verbose
	cp build/logs/junit.xml build/logs/phpunit.junit.xml
	$(SILENT) $(PHP) $(INFECTION) $(INFECTION_ARGS) --show-mutations

analyze: cs
	$(SILENT) $(PHP) $(PHAN) $(PHAN_ARGS) --color
	$(SILENT) $(PHP) $(PHPSTAN) $(PHPSTAN_ARGS)
	$(SILENT) $(PHP) $(PSALM) $(PSALM_ARGS)

cs: test-prerequisites
	$(SILENT) $(PHP) $(PHP_CS_FIXER) $(PHP_CS_FIXER_ARGS) --diff fix

##############################################################
# Prerequisites Setup                                        #
##############################################################

# We need both vendor/autoload.php and composer.lock being up to date
.PHONY: prerequisites
prerequisites: build/cache vendor/autoload.php .phan composer.lock

# Do install if there's no 'vendor'
vendor/autoload.php:
	$(SILENT) $(COMPOSER) install --prefer-dist

# If composer.lock is older than `composer.json`, do update,
# and touch composer.lock because composer not always does that
composer.lock: composer.json
	$(SILENT) $(COMPOSER) update && touch composer.lock

.phan:
	$(PHP) $(PHAN) --init --init-level=1 --init-overwrite --target-php-version=native > /dev/null

build/cache:
	mkdir -p build/cache

