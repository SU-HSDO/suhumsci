FROM gitpod/workspace-mysql

USER root

RUN curl -sS https://getcomposer.org/installer -o composer-setup.php
RUN sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer

RUN apt-get update
RUN apt-get install rsync php-bz2 php-imagick -y
ENV APACHE_DOCROOT_IN_REPO=docroot

USER gitpod

RUN echo '. $GITPOD_REPO_ROOT/.gitpod/.bash_aliases' >> ~/.bashrc

RUN composer global config minimum-stability dev
RUN composer global require drush/drush:^8 acquia/blt-launcher --no-update
RUN composer global update
RUN echo 'export PATH="$HOME/.config/composer/vendor/bin:$PATH"' >> ~/.bashrc

RUN git config --global core.fileMode false
