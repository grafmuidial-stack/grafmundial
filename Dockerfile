# Dockerfile para Web Service com PHP servindo frontend e /admin
FROM php:8.2-apache

# Habilita mod_rewrite para roteamento
RUN a2enmod rewrite

# Define docroot e copia frontend
ENV APACHE_DOCUMENT_ROOT=/var/www/html
WORKDIR /var/www/html
COPY ./frontend ./
RUN mkdir -p /var/www/html/uploads

# Copia backend
COPY ./backend /var/www/backend

# Copia admin moderno para o docroot
COPY ./admin /var/www/html/admin

# Configurações de Apache para permitir rewrite
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf \
    && { \
      echo '<Directory "/var/www/html">'; \
      echo '  AllowOverride All'; \
      echo '  Require all granted'; \
      echo '</Directory>'; \
    } >> /etc/apache2/apache2.conf

# Reescreve .htaccess: serve /admin direto e fallback para index.html
RUN set -e; \
  cat > /var/www/html/.htaccess <<'EOF'
<IfModule mod_rewrite.c>
  RewriteEngine On
  # Admin moderno: somente a raiz /admin vai para o index.php do admin
  RewriteRule ^admin/?$ /admin/index.php [L]

  # Se for arquivo ou diretório existente (inclui /admin/assets, /admin/pages), serve direto
  RewriteCond %{REQUEST_FILENAME} -f [OR]
  RewriteCond %{REQUEST_FILENAME} -d
  RewriteRule ^ - [L]

  # Fallback para o frontend SPA
  RewriteRule ^ /index.html [L]
</IfModule>
EOF

# Copia router.php para docroot (não é mais usado para /admin)
COPY ./backend/router.php /var/www/html/router.php
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 775 {} \; \
    && find /var/www/html -type f -exec chmod 664 {} \;

# Entrada para ajustar porta do Apache
COPY ./docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Render usa PORT; expõe 10000 por compatibilidade local
ENV PORT=10000
EXPOSE 10000

# Usa entrypoint que ajusta a porta
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["apache2-foreground"]