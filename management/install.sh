#!/bin/bash
# This script sets up the Management application and configures correct filesystem permissions for it to run.
if ! command -v composer &> /dev/null
then
    echo "Composer was not be found and is required to install Management."
    echo "See https://getcomposer.org/download for information on how to install it."
    exit
fi

if [ -e .env ]
then
    echo
    echo "ERROR: You already have a .env file, so this script is aborting."
    echo "If you really want to run this installation again, rename your existing .env file to something else first."
    exit
fi

echo "Installing dependencies (production only)..."
composer install --no-dev
echo
echo
echo "Creating .env file. NOTICE: This still needs to be manually configured with your server's settings."
mv .env.example .env
php artisan key:generate
echo
echo "Setting filesystem permissions. If this fails, run this command again with sudo."
chgrp -R www-data storage
chgrp -R www-data bootstrap/cache
chmod -R ug+rwx storage/framework/cache
chmod -R ug+rwx storage/framework/sessions
chmod -R ug+rwx storage/framework/views
chmod -R ug+rwx storage/logs/
chmod -R ug+rwx bootstrap/cache