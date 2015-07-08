apt-get install git
apt-get install php5
cat .ssh/id_rsa.pub 
cd /opt/
git clone https://github.com/igwtech/reactphp-servicebus
cd reactphp-servicebus/
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/bin/composer
composer update
apt-get install mysql-server mysql-client
apt-get install php5-mysql
apt-get install php5-fpm
apt-get install php5-cli
cd /var/www
mkdir test
cd test/
vi poster.php
cat /tmp/input/
apt-get install php5-dev
apt-get install php-pear
apt-get install libzmq-dev
apt-get install pkg-config
apt-get install make
pecl install channel://pecl.php.net/zmq-1.1.2
