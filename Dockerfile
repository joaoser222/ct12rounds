FROM node:22-bookworm-slim AS node-deps

WORKDIR /app

ENV NODE_ENV=production

COPY package.json package-lock.json ./
RUN npm ci

# Compiles public/build and bootstrap/ssr. The image is the only source of
# truth for assets in production, where the repository is not bind-mounted.
# The build needs devDependencies, which the runtime stage deliberately drops.
FROM node:22-bookworm-slim AS node-build

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --include=dev

COPY . .
RUN npm run build:ssr

FROM php:8.3-fpm-bookworm

WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y --no-install-recommends ca-certificates curl git gnupg libicu-dev libonig-dev libpq-dev libzip-dev unzip \
    && install -d /usr/share/postgresql-common/pgdg \
    && curl -fsSL https://www.postgresql.org/media/keys/ACCC4CF8.asc -o /usr/share/postgresql-common/pgdg/apt.postgresql.org.asc \
    && echo "deb [signed-by=/usr/share/postgresql-common/pgdg/apt.postgresql.org.asc] https://apt.postgresql.org/pub/repos/apt bookworm-pgdg main" > /etc/apt/sources.list.d/pgdg.list \
    && apt-get update \
    && apt-get install -y --no-install-recommends postgresql-client-17 \
    && docker-php-ext-install -j"$(nproc)" bcmath intl mbstring pdo_mysql pdo_pgsql zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY --from=node:22-bookworm-slim /usr/local/bin/node /usr/local/bin/node

COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-scripts

COPY . .
RUN composer dump-autoload --optimize --no-scripts

COPY --from=node-deps /app/node_modules /var/www/html/node_modules
COPY --from=node-build /app/public/build ./public/build
COPY --from=node-build /app/bootstrap/ssr ./bootstrap/ssr

# Named volumes inherit ownership from these paths when first created, so the
# www-data worker can write them without depending on a runtime chown.
RUN chown -R www-data:www-data storage bootstrap/cache
