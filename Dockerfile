FROM composer:2 AS composer

FROM php:8.3-fpm-alpine

WORKDIR /var/www

# System dependencies (minimal but complete for Laravel + MySQL)
RUN apk add --no-cache \
    bash \
    curl \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    zip \
    unzip \
    git \
    linux-headers \
    $PHPIZE_DEPS

# PHP extensions
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    bcmath \
    intl \
    zip

# Composer
COPY --from=composer /usr/bin/composer /usr/local/bin/composer

# Create a non-root user that matches common host UID/GID for dev
ARG APP_UID=1000
ARG APP_GID=1000
RUN addgroup -g ${APP_GID} app && adduser -D -G app -u ${APP_UID} app

# Entrypoint
COPY docker/app/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

USER app

EXPOSE 9000

ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]

