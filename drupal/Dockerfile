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
WORKDIR /opt/drupal
RUN composer require 'drupal/sqlsrv:^4.4'
RUN composer require 'drupal/restui:^1.21'

# Fix Drupal driver for SQL Server issues: https://www.drupal.org/project/sqlsrv/issues/3456815
COPY build/changes/Schema.php /opt/drupal/web/modules/contrib/sqlsrv/src/Driver/Database/sqlsrv/Schema.php
#COPY changes/Connection.php /opt/drupal/web/modules/contrib/sqlsrv/src/Driver/Database/sqlsrv/Connection.php
COPY build/changes/Select.php /opt/drupal/web/modules/contrib/sqlsrv/src/Driver/Database/sqlsrv/Select.php
COPY build/changes/Install/Tasks.php /opt/drupal/web/modules/contrib/sqlsrv/src/Driver/Database/sqlsrv/Install/Tasks.php

# Add drupal configuration for mssql via env vars
COPY build/config/settings-mssql.php /var/www/html/sites/default/settings.php
RUN chmod 777 /var/www/html/sites/default/settings.php
EXPOSE 80