#!/bin/sh
set -e

if [ -f /var/www/html/Kiosco/composer.json ] && [ ! -d /var/www/html/Kiosco/vendor ]; then
    echo "==> Instalando dependencias de Kiosco (composer install)..."
    composer install --working-dir=/var/www/html/Kiosco --no-interaction --optimize-autoloader
fi

exec apache2-foreground
