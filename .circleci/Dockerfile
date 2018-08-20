FROM juampynr/drupal8ci:latest

RUN docker-php-ext-install bz2

# Change docroot since we use Composer Drupal project.
RUN sed -ri -e 's!/var/www/html/web!/var/www/html/docroot!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html/web!/var/www/html/docroot!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

CMD /usr/sbin/apache2ctl -D FOREGROUND
