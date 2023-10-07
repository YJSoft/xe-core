#!/bin/bash

# remove previous files folder
rm -rf $PWD/files

# set config
cp -f $PWD/.devcontainer/config.php $PWD/config/config.user.inc.php
cp -f $PWD/.devcontainer/install-xe.php $PWD/config/install.config.php

# link repo to /var/www/ to access from web
sudo ln -s $PWD /var/www/xe

# create db (only first time)
sudo service mysql start
sudo mysql -uroot -proot -e "CREATE DATABASE xpressengine CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci"
sudo mysql -uroot -proot -e "CREATE USER 'xpressengine'@localhost IDENTIFIED BY 'xpressengine'"
sudo mysql -uroot -proot -e "GRANT ALL ON xpressengine.* to xpressengine@localhost; FLUSH PRIVILEGES"