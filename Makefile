build:
	@cp -nfr .env.example .env
	@cp -nfr ./tests/.env.example ./tests/.env
	PHP_VERSION=$(filter-out $@,$(v)) docker-compose -f tests/docker-compose.yml up -d --build

test:
	PHP_VERSION=$(filter-out $@,$(v)) docker-compose -f tests/docker-compose.yml build --pull yii2-default-controller
	PHP_VERSION=$(filter-out $@,$(v)) docker-compose-f tests/docker-compose.yml run yii2-default-controller vendor/bin/codecept run -v --debug
	docker-compose -f tests/docker-compose.yml down

clean:
	docker-compose down
	rm -rf tests/runtime/*
	rm -rf composer.lock
	rm -rf vendor/

clean-all: clean
	rm -rf tests/runtime/.composer*
