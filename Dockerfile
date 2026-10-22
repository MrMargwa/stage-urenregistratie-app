# syntax=docker/dockerfile:1
#
# Productie-image voor de stage-urenregistratie-app: nginx + PHP-FPM.
#
# Waarom een eigen Dockerfile in plaats van Nixpacks?
#   Nixpacks koos de PHP-versie uit zijn eigen nixpkgs-momentopname. Daardoor kon
#   je alleen "8.4" zeggen, niet "8.4.27". Bij een security-release (zoals
#   PHP2Shell, gedicht in 8.4.27) is dat precies wat je wél wilt kunnen.
#   Hier staat de versie letterlijk in de tag: pas de ARG hieronder aan om te
#   patchen, en je weet 100% zeker wat er in productie draait.
#
# Opbouw in drie fasen:
#   php-base -> PHP + extensies + nginx (gedeelde basis)
#   build    -> zelfde basis + Node, hier gebeuren composer install en npm build
#   runtime  -> php-base + alleen de kant-en-klare /app (geen Node, geen dev-tools)

ARG PHP_VERSION=8.4.27

# ---------------------------------------------------------------------------
# Fase 1: gedeelde basis
# ---------------------------------------------------------------------------
FROM php:${PHP_VERSION}-fpm AS php-base

# PHP-extensies die de app nodig heeft:
#   intl       -> Filament/Laravel (internationalisatie, o.a. datumnotatie)
#   zip        -> openspout (Excel-export/import)
#   pdo_pgsql  -> de productiedatabase is PostgreSQL
#   opcache    -> gecompileerde PHP-code cachen (flinke snelheidswinst)
#   redis      -> sessies/cache, als je op Railway de Redis-service gebruikt
# De phpize-deps zijn alleen nodig om redis te compileren; die gooien we daarna
# weer weg zodat ze niet in de image achterblijven.
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        $PHPIZE_DEPS \
        nginx \
        unzip \
        curl \
        libicu-dev \
        libzip-dev \
        libpq-dev \
    && docker-php-ext-install -j"$(nproc)" intl zip pdo_pgsql opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get purge -y --auto-remove $PHPIZE_DEPS \
    && rm -rf /var/lib/apt/lists/* /tmp/pear

# ---------------------------------------------------------------------------
# Fase 2: bouwfase
# Waarom Node hier en niet in de runtime-image? De Vite/Tailwind-build heeft
# twee dingen nodig: Node én de vendor-map (de Filament-theme-CSS wordt uit
# vendor/filament geïmporteerd). Dus PHP en Node moeten in dezelfde fase zitten.
# ---------------------------------------------------------------------------
FROM php-base AS build

# Node overnemen uit de officiële Node-image. Zo houd je één PHP-basis en hoef
# je geen tweede, afwijkende omgeving te onderhouden.
COPY --from=node:22-bookworm-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:22-bookworm-slim /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -sf ../lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm

# Composer hebben we alleen tijdens de build nodig, niet om te draaien.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Eerst alleen de composer-bestanden. Zolang die niet wijzigen blijft deze laag
# in de Docker-cache staan, dus hoef je na een codewijziging niet opnieuw alle
# packages te downloaden.
COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-interaction \
        --prefer-dist \
        --no-scripts \
        --no-autoloader

COPY . .

# Volgorde is belangrijk: eerst composer (vult vendor/), daarna npm build
# (heeft vendor/ nodig voor de Filament-CSS), daarna node_modules opruimen
# zodat het niet mee de runtime-image in gaat.
RUN composer dump-autoload --no-dev --optimize --no-scripts \
    && php artisan package:discover --ansi \
    && npm ci --ignore-scripts \
    && npm run build \
    && rm -rf node_modules

# ---------------------------------------------------------------------------
# Fase 3: runtime
# Alleen wat de app nodig heeft om te draaien: PHP, nginx en /app.
# ---------------------------------------------------------------------------
FROM php-base

WORKDIR /app
COPY --from=build /app /app

EXPOSE 8000

# start.sh start php-fpm én nginx. We starten hem via 'sh' zodat het niet
# afhangt van het executable-bit (dat git op Windows niet altijd bewaart).
CMD ["sh", "/app/start.sh"]
