#!/usr/bin/env bash -i
export HOME="/root"

# composer install
if [[ ! -f "/usr/local/bin/composer" ]]; then
    echo "Installing composer..."
    cd /tmp
    # https://getcomposer.org/doc/faqs/how-to-install-composer-programmatically.md
    EXPECTED_CHECKSUM="$(php -r 'copy("https://composer.github.io/installer.sig", "php://stdout");')"
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"

    if [ "$EXPECTED_CHECKSUM" == "$ACTUAL_CHECKSUM" ]; then
        php composer-setup.php --quiet
        RESULT=$?
        rm composer-setup.php
        # globally accessible as composer
        mv composer.phar /usr/local/bin/composer
        echo "Composer installed: $(composer --version)"
    else
        >&2 echo 'ERROR: Invalid composer installer checksum - aborting'
        rm composer-setup.php
        exit 1
    fi
else
    echo "Composer $(composer --version | cut -d ' ' -f 3) is already installed"
fi


# Check for NPM / node installation

# Not sure why but this script doesn't run as an interactive mode with the .bashrc loaded...
if [[ -f "$HOME/.bashrc" ]]; then
    source "$HOME/.bashrc"
fi

if command npm -v &> /dev/null; then
    echo "npm $(npm -v) is already installed"
else
    cd $HOME

    # npm install
    # https://nodejs.org/en/download
    curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.40.1/install.sh | bash

    # put the nvm command in the bashrc
    export NVM_DIR="$HOME/.nvm" >> $HOME/.bashrc

    # in lieu of restarting the shell
    \. "$HOME/.nvm/nvm.sh"

    # Download and install Node.js:
    nvm install 22

    # Verify the Node.js version:
    echo "Currend Dir: $(pwd)"
    echo "node version: $(node -v)"    # Should print "v22.14.0".
    echo "nvm version: $(nvm current)" # Should print "v22.14.0".
    echo "npm version: $(npm -v)"      # Should print "10.9.2".
fi
