###########
# PHP App #
###########
# References:	https://github.com/sprintcube/docker-compose-lamp/blob/master/bin/php74/Dockerfile
#				https://registry.hub.docker.com/r/fjudith/glpi/dockerfile

FROM php:7.2-apache

# Fix debconf warnings upon build
ARG DEBIAN_FRONTEND=noninteractive

# Packages
###########

# Update
RUN apt-get -y update --fix-missing && \
	apt-get upgrade -y && \
	apt-get --no-install-recommends install -y apt-utils && \
	rm -rf /var/lib/apt/lists/*

# Install useful tools and install important libaries
RUN apt-get -y update && \
    apt-get -y --no-install-recommends install nano wget \
	dialog \
	libsqlite3-dev \
	libsqlite3-0 && \
		apt-get -y --no-install-recommends install default-mysql-client \
	zlib1g-dev \
	libzip-dev \
	libicu-dev && \
		apt-get -y --no-install-recommends install --fix-missing apt-utils \
	build-essential \
	git \
	curl \
	libonig-dev && \ 
		apt-get -y --no-install-recommends install --fix-missing libcurl4 \
	libcurl4-openssl-dev \
	zip \
	unzip \
	openssl \
	libxml2-dev && \
	bzip2 && \
	rm -rf /var/lib/apt/lists/*

# Install xdebug
RUN pecl install -f xdebug-2.8.0 && \
	docker-php-ext-enable xdebug

# Install redis
RUN pecl install -f redis-5.1.1 && \
	docker-php-ext-enable redis

# Install imagick
RUN apt-get update && \
	apt-get -y --no-install-recommends install --fix-missing libmagickwand-dev && \
	rm -rf /var/lib/apt/lists/* && \
	pecl install -f imagick && \
	docker-php-ext-enable imagick

RUN docker-php-ext-install pdo_mysql && \
	docker-php-ext-install pdo_sqlite && \
	docker-php-ext-install mysqli && \
	docker-php-ext-install curl && \
	docker-php-ext-install tokenizer && \
	docker-php-ext-install json && \
	docker-php-ext-install zip && \
	docker-php-ext-install -j$(nproc) intl && \
	docker-php-ext-install pcntl && \
	docker-php-ext-install mbstring && \
	docker-php-ext-install gettext && \
	docker-php-ext-install exif && \
	docker-php-ext-install bcmath && \
	docker-php-ext-install bz2 && \
	docker-php-ext-install opcache && \
	docker-php-ext-install xml && \
	docker-php-ext-install soap

# Install Freetype
RUN apt-get -y update && \
	apt-get --no-install-recommends install -y libfreetype6-dev \
libjpeg62-turbo-dev \
libpng-dev && \
	rm -rf /var/lib/apt/lists/* && \
	docker-php-ext-configure gd --with-freetype-dir=/usr --with-jpeg-dir=/usr --with-png-dir=/usr && \
	docker-php-ext-install gd

# Set our public document root
WORKDIR /var/www

ENV APACHE_DOCUMENT_ROOT /var/www/public_html
ENV HOME /var/www
#RUN rm -d /var/www/html

# Enable mod_rewrite
RUN a2enmod rewrite

# Clear package lists
RUN apt-get clean; rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

# Personal configurations
######################################
COPY ./.Docker/php/php.ini-development /etc/php/7.2/apache2/php.ini
COPY ./.Docker/php/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY ./.Docker/php/httpd.conf /etc/apache2/httpd.conf

RUN echo "\nServerName localhost\n" >> /etc/apache2/apache2.conf

# Enable PHP extensions in php.ini; May not be needed with Docker
#RUN sed -i 's/;extension=intl/extension=intl/' /etc/php/7.2/apache2/php.ini
#RUN sed -i 's/;extension=mysqli/extension=mysqli/' /etc/php/7.2/apache2/php.ini
#RUN sed -i 's/;extension=pdo_mysql/extension=pdo_mysql/' /etc/php/7.2/apache2/php.ini

# PHP will log errors here
RUN sed -i 's:;error_log = php_errors.log:error_log = /var/www/logs/error_log:' /etc/php/7.2/apache2/php.ini

# Composer
###########
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
