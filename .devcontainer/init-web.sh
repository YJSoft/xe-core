#!/bin/bash
# stop apache to update config
sudo service apache2 stop

# set apache2 config
sudo cp -f $PWD/.devcontainer/web.conf /etc/apache2/sites-enabled/000-default.conf

# enable rewrite
sudo a2enmod rewrite
