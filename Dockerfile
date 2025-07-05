FROM php:8.3.7RC1-fpm-alpine3.19

ARG GID
ARG UUID

ENV GID=${GID:-1000}
ENV UUID=${UUID:-1000}

RUN apk update && apk add --no-cache git \
    curl \
    libpng-dev \
    wget \
    zip \
    unzip \
    libzip-dev \
    openssl \
    gcc \
    mariadb-connector-c-dev \
    php83-xml \
    php83-xmlwriter \
    php83-xmlreader \
    php83-tokenizer \
    php83-simplexml \
    php83-session \
    php83-pdo_mysql \
    php83-pdo_sqlite \
    php83-pdo_pgsql \
    php83-pdo_odbc \
    php83-gd \
    php83-dom \
    php83-ctype \
    php83-openssl \
    php83-json \
    php83-phar \
    php83-iconv \
    php83-mbstring \
    libxml2-dev \
    freetype \
    libjpeg-turbo \
    libpng \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    icu-dev \
    openssh

RUN docker-php-ext-configure gd
RUN docker-php-ext-install pdo pdo_mysql intl soap zip
RUN docker-php-ext-install  -j$(nproc) gd
RUN docker-php-ext-install bcmath
RUN docker-php-ext-enable gd

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

RUN addgroup -g ${GID} local && \
    adduser -D -u ${UUID} -G local local

RUN chown -R local:local /app

USER local
EXPOSE 9000
CMD [ "php", "-S", "0.0.0.0:9000" ]