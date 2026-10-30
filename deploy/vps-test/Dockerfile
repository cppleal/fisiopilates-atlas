# ================================================================
# Archivo: Dockerfile
# Ruta: /deploy/vps-test/Dockerfile → /opt/fisiopilates-test/Dockerfile (VPS)
# Descripción: Imagen del entorno TEST (test.fisiopilatesatlas.es) detrás de Traefik
# Última modificación: 2026-10-30
#
# Mismo modelo que Asociación Aérea y La Revolución del Arte. Ver specs/traslado-test-vps.md
# El código se monta como volumen (./site -> /var/www/html); no se copia a la imagen.
# ================================================================
FROM php:8.4-apache

# PDO MySQL: formulario de contacto, cookies y panel /admin/
RUN docker-php-ext-install -j"$(nproc)" pdo_mysql opcache

# Apache: módulos que usa el .htaccess, IP real del visitante y HTTPS detrás de Traefik
RUN a2enmod rewrite headers remoteip expires deflate \
 && sed -ri 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf
COPY apache-proxy.conf /etc/apache2/conf-enabled/zz-proxy.conf

# PHP: producción (sin mostrar errores) y zona horaria
RUN cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini
COPY php-test.ini /usr/local/etc/php/conf.d/zz-test.ini

ENV TZ=Europe/Madrid
