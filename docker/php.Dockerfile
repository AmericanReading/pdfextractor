FROM php:7-cli

MAINTAINER pj.dietz@americanreading.com

RUN DEBIAN_FRONTEND=noninteractive && \
  apt-get update && \
  apt-get -y install \
    git-core unzip zip

COPY ./docker/php.ini /usr/local/etc/php/php.ini

# Download Composer
RUN curl -sS https://getcomposer.org/installer | php -- --filename=composer --install-dir=/usr/local/bin

VOLUME /data
WORKDIR /data
