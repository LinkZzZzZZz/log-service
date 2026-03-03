FROM php:8.4-cli

RUN apt-get update && apt-get install -y \
    librabbitmq-dev \
    libicu-dev \
    unzip \
    git \
    && pecl install amqp-2.1.2 \
    && docker-php-ext-enable amqp \
    && docker-php-ext-install sockets bcmath intl \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-scripts

COPY . .
RUN composer dump-autoload --optimize

RUN useradd -m -u 1000 app && chown -R app:app /app
USER app

EXPOSE 8080

CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]
