export ROOT_DIR=${PWD}

all : build configure

.PHONY: all

build:
	docker run -it --rm -v ${ROOT_DIR}:/src -v ~/.composer:/root/.composer bashilbers/composer update --ignore-platform-reqs

configure:
	docker run -it --rm -v ${ROOT_DIR}:/src php:7.0-cli cd /src; php console.php configure