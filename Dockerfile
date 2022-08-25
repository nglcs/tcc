FROM php:7.4-apache
RUN apt-get update && apt-get install -y \
        libfreetype6-dev \
        apache2 \
        libjpeg62-turbo-dev \
        libpng-dev \
        libpq-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd  pdo pdo_pgsql mysqli pdo_mysql 
RUN apt-get install -y unzip
RUN apt-get install -y libaio1

ADD install/config-apache-docker/desnewpront.conf /etc/apache2/sites-available/desnewpront.conf

# Oracle instantclient
ADD install/instantclient-basic-linux.x64-19.6.0.0.0dbru.zip /tmp/
ADD install/instantclient-sdk-linux.x64-19.6.0.0.0dbru.zip /tmp/
RUN unzip /tmp/instantclient-basic-linux.x64-19.6.0.0.0dbru.zip -d /usr/local/
RUN unzip /tmp/instantclient-sdk-linux.x64-19.6.0.0.0dbru.zip -d /usr/local/
RUN mv /usr/local/instantclient_19_6 /usr/local/instantclient
#RUN ln -s /usr/local/instantclient/libclntsh.so.19.1 /usr/local/instantclient/libclntsh.so
#RUN ln -s /usr/local/instantclient/libocci.so.19.1 /usr/local/instantclient/libocci.so

ENV LD_LIBRARY_PATH=/usr/local/instantclient
RUN echo 'instantclient,/usr/local/instantclient' | pecl install oci8-2.2.0

RUN docker-php-ext-configure pdo_oci --with-pdo-oci=instantclient,/usr/local/instantclient
RUN docker-php-ext-install pdo_oci
RUN docker-php-ext-enable oci8

RUN pecl install xdebug
RUN echo "zend_extension=/usr/local/lib/php/extensions/no-debug-non-zts-20190902/xdebug.so" >> /usr/local/etc/php/php.ini

RUN apt install -y curl

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer -V
RUN sed -i 's/\/var\/www\/html/\/var\/www\/html\/public/g' /etc/apache2/sites-available/000-default.conf
#RUN rm /etc/apache2/sites-available/000-default.conf

RUN a2ensite desnewpront.conf
RUN a2enmod proxy_fcgi
RUN a2enmod rewrite
RUN { \
                echo '<FilesMatch \.php$>'; \
                echo '\tSetHandler application/x-httpd-php'; \
                echo '</FilesMatch>'; \
                echo; \
                echo 'DirectoryIndex disabled'; \
                echo 'DirectoryIndex index.php index.html'; \
                echo; \
                echo '<Directory /var/www/html>'; \
                echo '\tOptions -Indexes'; \
                echo '\tAllowOverride All'; \
                echo '</Directory>'; \
        } | tee "$APACHE_CONFDIR/conf-available/docker-php.conf" \
        && a2enconf docker-php


COPY . /var/www/html
WORKDIR /var/www/html
RUN sed -i "s/80/8080/g" /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf
RUN composer install && composer dump-autoload -o

VOLUME "/var/www/html"

EXPOSE 8080

CMD /usr/sbin/apache2ctl -D FOREGROUND