FROM php:8.2-apache

RUN docker-php-ext-install pdo pdo_mysql \
    && a2enmod rewrite headers

WORKDIR /var/www/html

COPY . /var/www/html/

# Enable AllowOverride so uploads/.htaccess script-execution block is enforced.
RUN printf '<Directory /var/www/html>\n\
    AllowOverride All\n\
    Options -Indexes +FollowSymLinks\n\
    Require all granted\n\
</Directory>\n' \
    > /etc/apache2/conf-available/htaccess-enable.conf \
    && a2enconf htaccess-enable

RUN mkdir -p /var/www/html/uploads/photos \
    && chown -R www-data:www-data /var/www/html \
    && chmod 755 /var/www/html/uploads \
    && chmod 755 /var/www/html/uploads/photos

EXPOSE 80
