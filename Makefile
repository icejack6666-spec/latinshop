PHPUNIT = vendor/bin/phpunit
ifeq (,$(wildcard $(PHPUNIT)))
    PHPUNIT = phpunit
endif

.PHONY: test test-unit test-integration test-coverage install clean

install:
	composer install --no-interaction --prefer-dist

test:
	$(PHPUNIT) --configuration phpunit.xml --colors=always

test-unit:
	$(PHPUNIT) --configuration phpunit.xml --testsuite Unit --colors=always

test-integration:
	$(PHPUNIT) --configuration phpunit.xml --testsuite Integration --colors=always

test-coverage:
	$(PHPUNIT) --configuration phpunit.xml --coverage-html coverage/ --colors=always

clean:
	rm -rf .phpunit.cache coverage/

list:
	$(PHPUNIT) --configuration phpunit.xml --list-tests
