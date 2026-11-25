FROM php:8.2-apache

# Install mysqli extension
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Enable Apache mod_rewrite if needed
RUN a2enmod rewrite

# Copy the project into the apache web root under /blockchain-sentinel to preserve URL structure
# Note: In production you might want to map this to /var/www/html/ directly depending on your domain setup
COPY . /var/www/html/blockchain-sentinel

# Set correct permissions
RUN chown -R www-data:www-data /var/www/html/blockchain-sentinel

EXPOSE 80
