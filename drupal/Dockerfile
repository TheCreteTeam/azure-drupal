FROM drupal:10-apache AS drupal-base
#FROM drupal:php8.3-fpm-bookworm AS drupal-base
WORKDIR /app

# For dev purposes, TODO: remove for prod
COPY info.php /var/www/html/

# https://learn.microsoft.com/en-us/sql/connect/php/installation-tutorial-linux-mac?view=sql-server-ver16#installing-on-debian
# Set microsoft repos
RUN echo "os version:$(lsb_release -sc)"
RUN apt-get -y update
RUN apt-get install -y curl apt-transport-https wget lsb-release unixodbc-dev vim gpg
RUN wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
RUN echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list
RUN apt-get -y update

# install odbc driver
RUN curl -fsSL https://packages.microsoft.com/keys/microsoft.asc | gpg --dearmor -o /usr/share/keyrings/microsoft-prod.gpg
RUN curl https://packages.microsoft.com/config/debian/12/prod.list | tee /etc/apt/sources.list.d/mssql-release.list
RUN apt-get -y update
RUN ACCEPT_EULA=Y apt-get install -y msodbcsql18

# Install SQL Server PDO driver
RUN pecl install sqlsrv
RUN pecl install pdo_sqlsrv
RUN docker-php-ext-enable sqlsrv pdo_sqlsrv


# Install Drupal driver for SQL Server
#WORKDIR /opt/drupal
##RUN composer require 'drupal/sqlsrv:^4.4'


# Fix Drupal driver for SQL Server issues: https://www.drupal.org/project/sqlsrv/issues/3456815
COPY build/changes/Schema.php /opt/drupal/web/modules/contrib/sqlsrv/src/Driver/Database/sqlsrv/Schema.php
#COPY changes/Connection.php /opt/drupal/web/modules/contrib/sqlsrv/src/Driver/Database/sqlsrv/Connection.php
COPY build/changes/Select.php /opt/drupal/web/modules/contrib/sqlsrv/src/Driver/Database/sqlsrv/Select.php
COPY build/changes/Install/Tasks.php /opt/drupal/web/modules/contrib/sqlsrv/src/Driver/Database/sqlsrv/Install/Tasks.php


#ssh
COPY build/entrypoint.sh ./
RUN chmod +x ./entrypoint.sh
RUN apt-get update \
    && apt-get install -y --no-install-recommends supervisor\
    && apt-get install -y --no-install-recommends dialog \
    && apt-get install -y --no-install-recommends openssh-server \
    && echo "root:Docker!" | chpasswd \
    && chmod u+x ./entrypoint.sh
COPY build/sshd_config /etc/ssh/




# /usr/local/bin/docker-php-entrypoint: 9: exec: docker: not found
RUN apt-get install -y --no-install-recommends docker.io

COPY build/config/DigiCertGlobalRootG2.crt.pem /var/www/html/DigitalOcean-CA3.crt


# Supervisord configuration file
COPY build/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
RUN mkdir -p /var/run/sshd
#CMD ["/usr/sbin/sshd", "-D"]


# Install Drupal driver for SQL Server
WORKDIR /opt/drupal
RUN composer require --dev drush/drush


# Add drupal configuration for mssql via env vars
COPY build/config/settings-mysql.php /var/www/html/sites/default/settings.php
#RUN chmod 777 /var/www/html/sites/default/settings.php
#make non writable
RUN chmod 444 /var/www/html/sites/default/settings.php

# Admin status report fixes
RUN mkdir -p /var/www/html/sites/default/files/config_WxI8vmxYs9PqmeenmJT9W4FOaGlM4B-6Ytl0htct0-INmEDl-Q0YE9KAPzJNiJHd1m09Tsxi7A/sync
RUN mkdir -p /var/www/html/sites/default/files
# make writable
RUN chmod -R 777 /var/www/html/sites/default/files



# Run supervisord
#CMD ["/usr/bin/supervisord"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

# WITHOUT SUPERVISORD
#CMD ["./entrypoint.sh"]
# CMD remains the same to start Apache
#CMD ["apache2-foreground"]
#ENTRYPOINT [ "./entrypoint.sh" ]

EXPOSE 80 2222