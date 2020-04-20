FROM php:7.2-alpine

RUN apk add bind-tools

CMD php -S 0.0.0.0:80 -t /var/www/

