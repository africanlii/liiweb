# liiweb

[![Test](https://github.com/africanlii/liiweb/actions/workflows/test.yml/badge.svg)](https://github.com/africanlii/liiweb/actions/workflows/test.yml)


## Install development environment from scratch

Make sure you have Drush 9 installed. This guide helps installing the 'default' site on the developer computer.

1. [Install php](https://www.php.net/manual/en/install.general.php) version 7.3
2. [Install composer](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-macos)
3. Create a database liiweb in MySQL
4. Clone this repository this project in `/home/user/work/liiweb/website` folder
5. In the repository folder, run `composer install` to install dependencies (this will take a while the first time you do it)
6. Create a virtual host in Apache which should look like this. For consistency please use the same domain.
```apacheconfig
<VirtualHost *:80>
  ServerName liiweb.test
  DocumentRoot /home/user/work/liiweb/website/web/
  <Directory /home/user/work/liiweb/website/web/>
    AllowOverride All
    Require all granted
  </Directory>
  <FilesMatch ".+\.php$">
    SetHandler "proxy:unix:/run/php/php7.3-fpm.sock|fcgi://localhost"
  </FilesMatch>
</VirtualHost>
```
4. Create Drupal settings file
```bash
cp web/sites/example.settings.local.php web/sites/default/settings.local.php
```

Open `web/sites/default/settings.local.php` and configure the following variables:

- `$settings['hash_salt']` - Drupal hash_salt - For development can be any string
- `$databases['default']['default'` - Database connection with valid database, username and password, see example below
- Solr configuration - You need a working solr configuration (see FAQ below)

Example:

```
$settings['hash_salt'] = 'ggxaq3QNDyWVhlqeV0gz7YHJJm39P5JOU4HekxOYGMnpMU78LKdHJGm1cPprsGW2YdycSZ';

$databases['default']['default'] = [
  'database' => 'liiweb',
  'username' => 'root',
  'password' => 'secret',
  'host' => 'localhost',
  'port' => '3306',
  'driver' => 'mysql',
  'prefix' => '',
  'collation' => 'utf8mb4_general_ci',
];


/* SMTP module configuration - WARNING! Use MailHog or similar fake SMTP server to avoid sending REAL emails */
$config['smtp.settings']['smtp_on'] = '1';
$config['smtp.settings']['smtp_host'] = 'localhost';
$config['smtp.settings']['smtp_port'] = 25;
$config['smtp.settings']['smtp_protocol'] = 'standard';
$config['smtp.settings']['smtp_from'] = 'user@example.com';
$config['smtp.settings']['smtp_username'] = 'user@example.com';
$config['smtp.settings']['smtp_password'] = '';

/* Apache Solr configuration */
$config['search_api.server.solr']['backend_config']['connector_config']['scheme'] = 'http';
$config['search_api.server.solr']['backend_config']['connector_config']['host'] = '127.0.0.1';
$config['search_api.server.solr']['backend_config']['connector_config']['port'] = '8983';
$config['search_api.server.solr']['backend_config']['connector_config']['path'] = '/';
$config['search_api.server.solr']['backend_config']['connector_config']['core'] = 'liiweb';
$config['search_api.server.solr']['backend_config']['connector_config']['username'] = 'user';
$config['search_api.server.solr']['backend_config']['connector_config']['password'] = 'pass';
```

At this stage browsing http://liiweb.test opens the Drupal installation procedure. DO NOT FOLLOW THE INSTALLATION. Instead, follow the steps below.

5. Install the instance using Drush

```shell script
# Create a database in MySQL for the project (i.e. liiweb)
mysql -u root -p -e "DROP DATABASE IF EXISTS liiweb; CREATE DATABASE liiweb"
./vendor/bin/drush site:install --existing-config -y
```

At this step you should see the output of a success install:

```
$> ./vendor/bin/drush site:install --existing-config -y

 // You are about to DROP all tables in your 'liiweb' database. Do you want to continue?: yes.                          

 [notice] Starting Drupal installation. This takes a while.
 [success] Installation complete.  User name: admin  User password: etM5pZKU6P
```

Now install the project configuration:

```
./vendor/bin/drush cim sync -y
./vendor/bin/drush cr
```

6. Open the local instance

When you open the instance http://liiweb.test/user again you should be able to log in with the username and passwords set by Drush during `site:install` task.

## Update local instance

If you already have a local instance installed and configured with code and database, use `git` to get the latest developments from the `master` branch or switch to another branch you wish to test and use `git pull` to fetch changes, then execute the following commands:

```shell script
./vendor/bin/drush updatedb -y
./vendor/bin/drush cim sync -y
./vendor/bin/drush updatedb -y
./vendor/bin/drush locale:check -y
./vendor/bin/drush locale:update -y
./vendor/bin/drush cr
```

Which imports the new configuration, applies all the pending updates and imports new translations - if available.

If you experience any errors regarding missing modules during import make sure to run a `composer install` to install any newly added modules.

## How to commit local changes

Step 1. Export any configuration chances done to your Drupal instance (i.e. add new fields, change settings).

```shell script
./vendor/bin/drush cex -y
```

Step 2. Stage for commit code and configuration changes (YML files). We recommend using `git add -p` to commit only relevant changes. Sometimes a configuration export might export other changes not necessarily related to current functionality.

```shell script
git add -p /path/to/modified/file
git add /path/to/new/file
git commit -m "refs #123 Added new fields"
```

For field changes, you MUST also copy any new and changed files to `web/modules/custom/liiweb/modules/liiweb_features/config/install/` so that they are
installed during testing.

## Pushing  changes to live site
Whenever changes are pushed to a live site, always make sure to visit https://your-lii-site.org/admin/config/media/file-system/filefield-paths and save congifuration, this prevents issues with S3 file configuration preventing correct file uploads.

TODO: Add multi-site configuration details here.

## LIIBarrio theme development

The main theme for the site is `liibarrio2020`, which is a subtheme of [Bootstrap Barrio](https://www.drupal.org/project/bootstrap_barrio).
It uses SCSS from Bootstrap 4.

The code for the theme lives in [web/themes/custom/liibarrio2020](web/themes/custom/liibarrio2020).

### Setting up local theme development

1. Copy `web/sites/default/default.services.yml` to `web/sites/default/services.yml`
2. In `web/sites/default/services.yml`:
    * twig.config.debug: true
    * twig.config.cache: false
3. In `web/sites/default/settings.local.php`:
    * uncomment `$settings['cache']['bins']['render'] = 'cache.backend.null';`
    * uncomment `$settings['cache']['bins']['page'] = 'cache.backend.null';`
    * uncomment `$settings['cache']['bins']['dynamic_page_cache'] = 'cache.backend.null';`

### Changing CSS and JS in the liibarrio2020 theme

CSS is compiled from SCSS from various sources using Gulp. If you want to make changes:

1. `cd web/themes/custom/liibarrio2020`
2. Install node dependencies: `npm install`
3. Recompile CSS: `npx gulp styles`

Be sure to commit both the compiled CSS and the changed SCSS.


## How to run the tests locally

Step 1. Copy `example.phpunit.xml` to `phpunit.xml` and modify variables inside according to your environment: SIMPLETEST_BASE_URL, SIMPLETEST_DB, BROWSERTEST_OUTPUT_DIRECTORY, MINK_DRIVER_ARGS_WEBDRIVER (for FunctionalJavascript tests) etc.
Step 2. Run the whole `liiweb` suite
```bash
./vendor/bin/phpunit --colors=auto --group liiweb
```

## Updating Drupal Core

Follow the steps below to update your core files.

1. Run `composer update drupal/core webflo/drupal-core-require-dev "symfony/*" --with-dependencies` to update Drupal Core and its dependencies.
1. Run `git diff` to determine if any of the scaffolding files have changed. Review the files for any changes and restore any customizations to `.htaccess` or `robots.txt`.
1. Commit changes all together in a single commit, so `web` will remain in sync with the `core` when checking out branches or running `git bisect`. **Important**: Review indidivual changes because some might override customized files such as to `web/sites/example.settings.local.php`.
1. In the event that there are non-trivial conflicts in step 2, you may wish to perform these steps on a branch, and use `git merge` to combine the updated core files with your customized files. This facilitates the use of a [three-way merge tool such as kdiff3](http://www.gitshah.com/2010/12/how-to-setup-kdiff-as-diff-tool-for-git.html). This setup is not necessary if your changes are simple; keeping all of your modifications at the beginning or end of the file is a good strategy to keep merges easy.

## Production deployment

See [docs/production.md](docs/production.md) for details on how to install this website in a production environment.

## FAQ

### How do I configure Apache Solr

The easiest way is to use Docker

1. Create a separate folder on your server, i.e. `/opt/solr`
2. Inside, create a configuration file `docker-compose.yml` with the content below

```
solr.edw.ro solr # cat docker-compose.yml 
version: '3.3'

services:
  solr7:
    image: library/solr:7
    container_name: solr7
    restart: unless-stopped
    environment:
      SOLR_JAVA_MEM: "-Xms512m -Xmx2g"
    volumes:
      - solr7-cores:/opt/solr/server/solr/cores
    ports:
      - 8983:8983
    logging:
        options:
            max-size: "10m"
            max-file: "3"

volumes:
  solr8-cores:
```

3. Start the Docker stack:

```shell script
# First time
docker-compose pull
docker-compose up -d
```

Note: The stack is set to start everytime docker daemon starts (i.e. when the computer boots).


### How can I install contrib modules?

To install contrib modules, simply navigate to your repo via command line and enter the  `composer require 'drupal/example_module:^1.1'` line found on the contrib modules download page. When commiting new module changes make sure to include both the `composer.json` and `composer.lock` files in your push.

For a walkthrough on installing via composer follow:
https://www.drupal.org/docs/develop/using-composer/using-composer-to-install-drupal-and-manage-dependencies

### How can I apply patches to downloaded modules?

If you need to apply patches (depending on the project being modified, a pull
request is often a better solution), you can do so with the
[composer-patches](https://github.com/cweagans/composer-patches) plugin.

To add a patch to drupal module foobar insert the patches section in the extra
section of composer.json:
```json
"extra": {
    "patches": {
        "drupal/foobar": {
            "Patch description": "URL or local path to patch"
        }
    }
}
```

# License

Licensed under GNU LGPLv3. See LICENSE.

Copyright AfricanLII 2020-2021.
