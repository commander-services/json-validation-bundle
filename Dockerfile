FROM php:7.4-cli-alpine

RUN set -eux; \
	apk add --no-cache \
		git \
        unzip

# Composer
COPY --from=composer/composer:latest-bin /composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV PATH="/root/.composer/vendor/bin:${PATH}"

WORKDIR /app
