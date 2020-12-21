
# Git installation -- Slim requirement
apt-get -y update; apt-get -y install git libcurl4-openssl-dev pkg-config libssl-dev

# Move to work directory OR exit on fail
cd /var/www/ || exit

#Mongo PHP driver
/bin/bash -lc "pecl install mongodb"
docker-php-ext-enable mongodb
service apache2 restart
#echo 'extension=mongodb.so' >> usr/local/etc/php/conf.d/docker-php-ext-sodium.ini

# Composer instalation -- Slim requirement
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (hash_file('sha384', 'composer-setup.php') === '756890a4488ce9024fc62c56153228907f1545c228516cbf63f885e036d37e9a59d27d63f46af1d4d07ee0f76181c7d3') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
php composer-setup.php
php -r "unlink('composer-setup.php');"

# Slim microinterface instalation
php composer.phar require slim/slim:"4.*"
php composer.phar require slim/psr7


# Mongo integration module
php composer.phar require mongodb/mongodb

# Clean up
rm composer.*
