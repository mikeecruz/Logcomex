FROM php:8.4-fpm

ARG UID=1000
ARG GID=1000

# libs do sistema para extensões do PHP
RUN apt-get update && apt-get install -y \
    git unzip zip curl \
    libicu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
  && rm -rf /var/lib/apt/lists/*

# extensões PHP (Laravel)
RUN docker-php-ext-configure intl \
  && docker-php-ext-install -j$(nproc) \
    pdo_mysql intl zip mbstring opcache bcmath exif pcntl xml

# GD (opcional mas comum)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install -j$(nproc) gd

# composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# usuário app com UID/GID do host (WSL) pra não gerar root:root
RUN groupadd -g ${GID} app \
  && useradd -u ${UID} -g app -m -s /bin/bash app

WORKDIR /var/www/html

COPY ./local.ini /usr/local/etc/php/conf.d/local.ini

USER app
