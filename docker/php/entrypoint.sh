#!/bin/sh
set -e

cd /var/www/html

if [ -f composer.json ] && [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist
fi

# Каталоги Laravel на bind-mount принадлежат пользователю хоста, Apache работает как www-data.
if [ -f artisan ]; then
    mkdir -p \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache
    chmod -R a+rwX storage bootstrap/cache || true
fi

if [ -f public/index.php ]; then
    DOCROOT=/var/www/html/public
else
    DOCROOT=/opt/voice-notes-placeholder
fi

cat > /etc/apache2/sites-available/000-default.conf <<EOF
<VirtualHost *:80>
    DocumentRoot ${DOCROOT}

    <Directory ${DOCROOT}>
        Options FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog /var/log/apache2/error.log
    CustomLog /var/log/apache2/access.log combined
</VirtualHost>
EOF

exec docker-php-entrypoint "$@"
