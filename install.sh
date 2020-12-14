#!/bin/bash
# Installs rok-monster-cli + all dependancies.

# Setup
DEMO_VER="1.0.2"
PHP="php7.4"
ROK_MONSTER_CLI="https://github.com/carmelosantana/rok-monster-cli"
ROK_MONSTER_SAMPLES="https://github.com/carmelosantana/rok-monster-samples"
TESSDATA="https://github.com/tesseract-ocr/tessdata"
ROK_PHP="rok.php"

# Welcome
echo
echo " +------------------------------------+"
echo " |Welcome to rok-monster-cli installer|"
echo " +------------------------------------+"
echo

# What are we doing?
echo "This will install rok-monster-cli + sample media in: $PWD"
echo
echo "Supported OS:"
echo "  - Ubuntu 20.04 LTS"
echo
echo "The following packages will be installed:"
echo "  - git"
echo "  - imagemagick"
echo "  - ffmpeg"
echo "  - tesseract-ocr"
echo

# Automated
if echo "$1" | grep -iq "^y"; then
	AUTOMATE=true
fi

# Continue?
if [ "$AUTOMATE" != true ]; then
    echo -n "Do you want to continue? (yes/no) "
    read answer
    if ! echo "$answer" | grep -iq "^y"; then
        exit 0
    fi
fi 

# Update system
sudo apt update
# https://bugs.launchpad.net/ubuntu/+source/ansible/+bug/1833013 - 1/2/2020
UCF_FORCE_CONFOLD=1 DEBIAN_FRONTEND=noninteractive apt -o Dpkg::Options::="--force-confdef" -o Dpkg::Options::="--force-confold" -qq -y upgrade

# Install depedancies
sudo apt -y install git imagemagick ffmpeg tesseract-ocr

# Install php
echo | sudo add-apt-repository ppa:ondrej/php
sudo apt -y install $PHP $PHP-cli $PHP-common $PHP-gd $PHP-mbstring $PHP-snmp $PHP-xml $PHP-zip php-imagick

# Install rok-monster-cli + sample data
if [ ! -f "$FILE" ]; then
    git clone $ROK_MONSTER_CLI
    cd rok-monster-cli
fi
git clone $ROK_MONSTER_SAMPLES

# Install Tesseract models from git
git clone $TESSDATA

# Get composer
EXPECTED_CHECKSUM="$(wget -q -O - https://composer.github.io/installer.sig)"
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"

# Validate checksum
if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]; then
    >&2 echo 'ERROR: Invalid installer checksum'
    rm composer-setup.php
    exit 1
fi

# Install composer local
php composer-setup.php --quiet
RESULT=$?
rm composer-setup.php
echo "Composer: $RESULT"

# Run composer and build rok-monster-cli
php composer.phar install --no-plugins --no-scripts --quiet

# We made it this far, install is done!
echo
echo " +----------------+"
echo " |Install complete|"
echo " +----------------+"
echo

# Launch demo
php rok.php --job=governor_more_info_kills \
    --input_path="rok-monster-samples/media/governor-more-info-kills/" \
    --tessdata="tessdata/"