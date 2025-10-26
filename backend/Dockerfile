FROM php:8.2-cli
# ------------------------------------------------------------
# Instalacja pakietów systemowych i rozszerzeń PHP potrzebnych
# dla Laravel i typowych operacji:
#
# apt-get update                   -> aktualizacja listy pakietów
# apt-get install -y ...           -> instalacja pakietów systemowych:
#    git       -> potrzebny do instalowania paczek przez Composer
#    unzip     -> rozpakowywanie paczek (Composer zip)
#    libpng-dev, libjpeg-dev, libfreetype6-dev -> biblioteki do GD (obróbka obrazów)
#    libonig-dev -> dla mbstring (UTF-8, wielobajtowe stringi)
#    libxml2-dev -> parsowanie XML
#    zip, curl   -> tworzenie archiwów i żądania HTTP
#
# docker-php-ext-install ...      -> instalacja wbudowanych rozszerzeń PHP:
#    pdo_mysql -> połączenie z MySQL (PDO driver)
#    mbstring  -> obsługa wielobajtowych stringów
#    exif      -> odczyt metadanych obrazów
#    pcntl     -> obsługa procesów (queue worker)
#    bcmath    -> precyzyjne obliczenia matematyczne
#    gd        -> obróbka obrazów (resize, crop, miniatury)
# ------------------------------------------------------------
# Install system dependencies
RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libpng-dev libicu-dev libjpeg-dev libfreetype6-dev libonig-dev libxml2-dev zip curl \
    && docker-php-ext-configure zip \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd intl zip

RUN curl -o /usr/local/bin/wait-for-it.sh https://raw.githubusercontent.com/vishnubob/wait-for-it/master/wait-for-it.sh \
    && chmod +x /usr/local/bin/wait-for-it.sh

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy existing app files
COPY . .

RUN composer install

# Set permissions for storage and cache
RUN chmod -R 777 storage bootstrap/cache

EXPOSE 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
