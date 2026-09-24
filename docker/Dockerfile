FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    libfreetype6-dev \
 && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
 && docker-php-ext-install pdo pdo_mysql gd exif \
 && rm -rf /var/lib/apt/lists/*

# Custom PHP config (raises upload limits so large images don't fail)
COPY php/uploads.ini /usr/local/etc/php/conf.d/zz-uploads.ini

WORKDIR /var/www

COPY entrypoint.sh /entrypoint.sh

# Normalize line endings and make the entrypoint executable.
RUN sed -i 's/\r$//' /entrypoint.sh && chmod +x /entrypoint.sh

ENTRYPOINT [ "/entrypoint.sh" ]
