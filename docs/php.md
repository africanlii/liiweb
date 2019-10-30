# PHP Configuration

## Packages

Install Remi repository or something similar. http://rpms.remirepo.net/enterprise/remi-release-7.rpm

Make sure the following packages are available:

* php73-php
* php73-php-cli
* php73-php-common
* php73-php-devel
* php73-php-fpm
* php73-php-gd
* php73-php-imap
* php73-php-intl
* php73-php-ldap
* php73-php-mbstring
* php73-php-mysqlnd
* php73-php-opcache
* php73-php-pdo
* php73-php-pear
* php73-php-xml
* php73-php-zip


## PHP.INI

For Remi-style repositories located in `/etc/opt/remi/php73/php.ini`.

- `post_max_size`, `max_file_uploads` and `upload_max_filesize` controls the file upload and you can adapt to your own needs.
- `date.timezone` is to UTC as the OS to have proper time reflected in Drupal, logs etc.
- remove `mail.add_x_header` to not appear in sent emails as header.

```
error_reporting: E_ALL & ~E_DEPRECATED & ~E_STRICT
display_errors: Off
error_log: syslog
date.timezone: UTC
expose_php: Off
allow_url_fopen: On
assert.active: Off
session.use_strict_mode: On
max_execution_time: 60
memory_limit: 1024M
post_max_size: 64M
upload_max_filesize: 32M
max_file_uploads: 10
max_input_vars: 10000
realpath_cache_size: 32k
```

## FPM Pool configuration

For Remi-style repositories located in `/etc/opt/remi/php73/php-fpm.d/www.conf`.

```
listen = /var/run/php73-fpm.sock
listen.owner = apache
listen.group = apache

pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
```
