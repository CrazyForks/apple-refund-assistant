FROM serversideup/php:8.2-fpm-nginx-alpine

USER root
RUN install-php-extensions bcmath intl gd mbstring xml curl exif fileinfo iconv
USER www-data

# 切换到工作目录
WORKDIR /var/www/html

COPY --chown=www-data:www-data composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader


COPY --chown=www-data:www-data . .
RUN composer install --no-dev --optimize-autoloader


RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

ENV PHP_OPCACHE_ENABLE=1
ENV LOG_OUTPUT_LEVEL=error

# 暴露端口（NGINX 使用 8080 和 8443）
EXPOSE 8080 8443
