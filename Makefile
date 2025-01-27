help:			## Display help information.
	@fgrep -h "##" $(MAKEFILE_LIST) | fgrep -v fgrep | sed -e 's/\\$$//' | sed -e 's/##//'

build:			## Build an image from a docker-compose file. Params: {{ v=8.1 }}. Default latest PHP 8.1
	@cp -nfr ./tests/.env.example ./tests/.env
	PHP_VERSION=$(filter-out $@,$(v)) docker-compose -f tests/docker/docker-compose.yml up -d --build

down:			## Stop and remove containers, networks
	docker-compose -f tests/docker/docker-compose.yml down

start:			## Start services
	docker-compose -f tests/docker/docker-compose.yml up -d

sh:			## Enter the container with the application
	docker exec -it yii2-default-controller sh

test:			## Run tests. Params: {{ v=8.1 }}. Default latest PHP 8.1
	PHP_VERSION=$(filter-out $@,$(v)) docker-compose -f tests/docker/docker-compose.yml build --pull yii2-default-controller
	make create-cluster
	PHP_VERSION=$(filter-out $@,$(v)) docker-compose -f tests/docker/docker-compose.yml run yii2-default-controller vendor/bin/codecept run
	make down
