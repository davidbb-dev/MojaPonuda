#!/bin/sh

echo "Setting up upload + storage directories..."

# Create upload directories if missing.
mkdir -p /var/www/public/uploads/images/listings
mkdir -p /var/www/public/uploads/images/avatars

# Application storage (mail log, app logs).
mkdir -p /var/www/storage/logs

# Development-only permissions.
chmod -R 777 /var/www/public/uploads
chmod -R 777 /var/www/storage

# Production permissions.
# chown -R www-data:www-data /var/www/public/uploads
# chmod -R 775 /var/www/public/uploads

echo "Permissions set."

# ---------------------------------------------------------
# Initialize the database and wait for MySQL to become ready.
# The schema uses IF NOT EXISTS, so existing tables are preserved.
# ---------------------------------------------------------
echo "Waiting for database & applying schema..."
i=0
until php /var/www/database/init-db.php 2>/tmp/initdb.err; do
    i=$((i + 1))

    if [ "$i" -ge 30 ]; then
        echo "Schema could not be applied after 30 tries; continuing anyway."
        cat /tmp/initdb.err 2>/dev/null || true
        break
    fi

    echo "  DB not ready yet (attempt $i)... retrying in 2s"
    sleep 2
done

exec php-fpm