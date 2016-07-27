FROM php:7-cli

MAINTAINER pj.dietz@americanreading.com

RUN DEBIAN_FRONTEND=noninteractive && \
  apt-get update && \
  apt-get -y install \
    git-core imagemagick unzip zip

COPY ./docker/php.ini /usr/local/etc/php/php.ini

# Download Composer
RUN curl -sS https://getcomposer.org/installer | php -- --filename=composer --install-dir=/usr/local/bin

# Copy source code
COPY ./build.php /src/build.php
COPY ./src /src/src
RUN mkdir /src/build

# Install Composer dependencies and remove Composer
RUN composer install --working-dir /src/src \
    && rm /usr/local/bin/composer

# Build the Phar, move it into place, and remove the source code.
RUN cd /src \
    && php build.php \
    && mv /src/build/pdfextractor.phar /usr/local/bin/pdfextractor \
    && chmod +x /usr/local/bin/pdfextractor \
    && rm -R /src

VOLUME /data
WORKDIR /data

ENTRYPOINT ["pdfextractor"]
CMD ["--help"]
