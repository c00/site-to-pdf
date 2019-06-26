FROM php:7.3-fpm-stretch

# Add composer
RUN php -r "readfile('http://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer

# Dependencues
RUN apt-get update && apt-get install -y xvfb xfonts-75dpi fontconfig libjpeg62-turbo libxrender1

# wkhtmltopdf
RUN curl -o /tmp/wkhtmltopdf.deb -L https://downloads.wkhtmltopdf.org/0.12/0.12.5/wkhtmltox_0.12.5-1.stretch_amd64.deb && \
    dpkg -i /tmp/wkhtmltopdf.deb

# Add app
COPY . /app
WORKDIR /app

RUN composer install

CMD ["php-fpm"]