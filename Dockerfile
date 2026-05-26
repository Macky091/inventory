FROM dunglas/frankenphp:php8.4.21-bookworm

RUN install-php-extensions mysqli pdo_mysql mbstring curl

COPY . /app

CMD ["php", "-S", "0.0.0.0:8080", "-t", "/app"]