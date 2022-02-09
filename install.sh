#!/bin/bash

# Setup
PHP="php8.0"
DEPENDENCIES="git imagemagick tesseract-ocr"
ROK_MONSTER_MAIN="rok-monster-ocr"
ROK_MONSTER_GIT="https://github.com/carmelosantana/$ROK_MONSTER_MAIN"
ROK_MONSTER_SAMPLES="https://github.com/carmelosantana/rok-monster-samples"
TESSDATA="https://github.com/tesseract-ocr/tessdata"
ROK_BIN="rok.php"

# Welcome
echo
echo " +----------------------------------+"
echo " | Welcome to RoK Monster installer |"
echo " +----------------------------------+"
echo

# Continue?
function ask_to_continue {
    if [ ! -n "$1" ]; then
        echo "$1"
        echo
    fi

    if [ "$AUTOMATE" != true ]; then
        echo -n "Do you want to continue? (yes/no) "
        read answer
        if ! echo "$answer" | grep -iq "^y"; then
            exit 0
        fi
    fi
}

# Launch demo
function demo {
    php rok.php --job=governor-more-info-kills \
        --input_path="rok-monster-samples/media/governor-more-info-kills/" \
        --tessdata="tessdata/"
}

function install_rok_monster {
    echo "This will install RoK Monster + sample media in: $PWD"
    echo
    echo "Supported OS:"
    echo "  - Ubuntu 20.04.3 LTS"
    echo
    echo "The following packages will be installed:"
    echo "  - git"
    echo "  - imagemagick"
    echo "  - tesseract-ocr"

    ask_to_continue

    # Setup PPA
    echo | sudo add-apt-repository ppa:ondrej/php

    # Update PPA
    sudo apt update

    # Install dependencies
    sudo apt -y install $DEPENDENCIES

    # Install php
    sudo apt -y install $PHP $PHP-cli $PHP-common $PHP-gd $PHP-mbstring $PHP-snmp $PHP-xml $PHP-zip $PHP-imagick

    # Install rok-monster-ocr
    if [ ! -f "$CHECK_FILE" ]; then
        git clone --depth 1 $ROK_MONSTER_GIT
        cd $ROK_MONSTER_MAIN
    fi

<<<<<<< HEAD
    # Install sample data
    git clone --depth 1 $ROK_MONSTER_SAMPLES
=======
# Install php
sudo apt -y install $PHP $PHP-cli $PHP-common $PHP-gd $PHP-mbstring $PHP-snmp $PHP-xml $PHP-zip $PHP-imagick
>>>>>>> main

    # Install Tesseract models from git
    git clone --depth 1 $TESSDATA$TESSDATA_VER

    # Get composer
    EXPECTED_CHECKSUM="$(wget -q -O - https://composer.github.io/installer.sig)"
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"

    # Validate checksum
    if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]; then
        echo >&2 'ERROR: Invalid installer checksum'
        rm composer-setup.php
        exit 1
    fi

    # Install composer local
    php composer-setup.php --quiet
    RESULT=$?
    rm composer-setup.php
    echo "Composer: $RESULT"

    # Run composer and build rok-monster-ocr
    php composer.phar install --no-plugins --no-scripts --quiet

    # We made it this far, install is done!
    echo
    echo " +------------------+"
    echo " | Install complete |"
    echo " +------------------+"
    echo

    demo
}

function uninstall_rok_monster {
    ask_to_continue "This will uninstall RoK Monster, dependencies and all sample media."
    sudo apt remove $PHP $DEPENDENCIES
}

function upgrade_rok_monster {
    ask_to_continue "This will upgrade RoK Monster, dependencies and sample media."
    sudo apt update
    sudo apt -y upgrade
    demo
}

# What tessdata are we using?
if echo "$2" | grep -iq "best|fast"; then
    TESSDATA_VER="_${2,,}"
else
    TESSDATA_VER=''
fi

# Automated
if echo "$1" | grep -iq "^y"; then
    AUTOMATE=true
    install_rok_monster TESSDATA_VER
elif echo "$1" | grep -iq "^update"; then
    upgrade_rok_monster
elif echo "$1" | grep -iq "^uninstall"; then
    uninstall_rok_monster
else
    install_rok_monster TESSDATA_VER
fi

# we're done
exit 0
