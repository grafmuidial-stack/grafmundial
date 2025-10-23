#!/bin/sh
set -e

# Ajusta Apache para escutar na porta definida pelo Render
PORT="${PORT:-10000}"
sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf || true
sed -i "s/:80>/:${PORT}>/" /etc/apache2/sites-available/000-default.conf || true

# Garantir permissões de escrita do Apache/PHP (www-data) em todo o docroot
chown -R www-data:www-data /var/www/html || true
find /var/www/html -type d -exec chmod 775 {} \; || true
find /var/www/html -type f -exec chmod 664 {} \; || true

# Inicia Apache em foreground
exec apache2-foreground