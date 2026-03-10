# ========= Base commune =========
FROM dunglas/frankenphp:1-php8.3-alpine AS base
WORKDIR /app

# Paquets système (intl, zip, gd, etc. si besoin)
RUN apk add --no-cache \
    icu-dev oniguruma-dev libzip-dev zlib-dev libpng libpng-dev git bash \
 && docker-php-ext-configure intl \
 && docker-php-ext-install -j$(nproc) intl pdo_mysql opcache \
 zip \
 gd \
 && apk del libpng-dev || true

# Composer (fourni par l'image frankenphp via /usr/local/bin/composer)
# Xdebug optionnel en dev
ARG INSTALL_XDEBUG=false
RUN if [ "$INSTALL_XDEBUG" = "true" ]; then \
      apk add --no-cache $PHPIZE_DEPS \
      && pecl install xdebug \
      && docker-php-ext-enable xdebug ; \
    fi

# Installer Node.js et npm (version Alpine)
RUN apk add --no-cache nodejs npm

# Caddyfile pour FrankenPHP
COPY ./Caddyfile /etc/caddy/Caddyfile

# >>> AJOUTER COMPOSER <
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# ========= Image Dev =========
FROM base AS dev
ENV APP_ENV=dev
# Rien à copier : on montera le code en volume
EXPOSE 8080
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]