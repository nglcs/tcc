Instalação locaweb:

/usr/bin/php71 -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"

/usr/bin/php71 composer-setup.php --install-dir=/home/storage/4/d4/aa/quali-a1/composer --filename=composer

#Pode ser preciso rodar um composer upgrade
/usr/bin/php71 /home/storage/4/d4/aa/quali-a1/composer/composer install

/usr/bin/php71 /home/storage/4/d4/aa/quali-a1/composer/composer dump-autoload


