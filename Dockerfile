FROM php:7.4-fpm

# install docker
RUN apt-get update && apt-get install -y \
    ca-certificates \
    curl \
    gnupg \
    lsb-release

RUN mkdir -p /etc/apt/keyrings
RUN curl -fsSL https://download.docker.com/linux/debian/gpg | gpg --dearmor -o /etc/apt/keyrings/docker.gpg
RUN echo \
    "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/debian \
    $(lsb_release -cs) stable" | tee /etc/apt/sources.list.d/docker.list > /dev/null
RUN apt-get update && apt-get install -y \
    docker-ce \
    docker-ce-cli \
    containerd.io \
    docker-compose-plugin

# Install node.js
RUN curl -fsSL https://deb.nodesource.com/setup_16.x | bash -
RUN apt-get update && apt-get install nodejs -y

# Install dependencies
RUN apt-get install -y \
    default-mysql-client \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libonig-dev \
    libpq-dev \
    libzip-dev \
    libmcrypt-dev \
    libfreetype6-dev \
    locales \
    zip \
    jpegoptim optipng pngquant gifsicle \
    vim \
    unzip \
    git \
    libzip-dev \
    libxml2-dev \
    zlib1g-dev \
    libc-client-dev \
    libkrb5-dev \
    supervisor \
    libmagickwand-dev --no-install-recommends \
    lsof\
    cron

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install extensions


# Install imap
RUN docker-php-ext-configure imap --with-kerberos --with-imap-ssl && docker-php-ext-install imap

# Install ImageMagick
RUN printf "\n" | pecl install imagick
RUN docker-php-ext-enable imagick

RUN docker-php-ext-install \
    mysqli \
    fileinfo \
    intl \
    mbstring \
    pcntl \
    pdo \
    pdo_mysql \
    pdo_pgsql \
    pgsql \
    zip \
    opcache \
    soap \
    bcmath \
    gd \
    exif


RUN pecl install -o -f mcrypt-1.0.3 && pecl install -o -f xdebug-3.1.5 && pecl install -o -f redis
RUN rm -rf /tmp/pear
RUN docker-php-ext-enable mcrypt && docker-php-ext-enable xdebug && docker-php-ext-enable redis

RUN echo "memory_limit = 1024M\n" > /usr/local/etc/php/conf.d/memlimit.ini

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --version=2.2.9 --install-dir=/usr/bin/ --filename=composer

# cron
COPY ./build-files/laravel-cron /etc/cron.d/laravel-cron
RUN chmod 0644 /etc/cron.d/laravel-cron && crontab /etc/cron.d/laravel-cron

# supervisord
COPY ./build-files/services.conf /etc/supervisor/conf.d/services.conf

# # Copy existing application directory contents
# COPY . /var/www/html

# # Set working directory
# WORKDIR /var/www/html

# RUN composer install
# RUN npm install --unsafe-perm=true && npm run dev


# # Set application directory permissions
# RUN chown -R www-data:www-data /var/www/html && chmod 755 /var/www/html/storage

# USER root

# Expose port 9000 and 6001
EXPOSE 9000 6001

# CMD bash build-files/init.sh && service supervisor start
# CMD bash build-files/init.sh && supervisord --nodaemon

