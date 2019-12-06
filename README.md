# liiweb

[![Build Status](https://travis-ci.com/stefanbutura/liiweb.svg?branch=master)](https://travis-ci.org/drupal-composer/drupal-project)


## Install development environment from scratch

Make sure you have Drush 9 installed. This guide helps installing the 'default' site on the developer computer.

1. Create a database liiweb in MySQL
2. Clone this repository this project in `/home/user/work/liiweb/website` folder
3. Create a virtual host in Apache which should look like this. For consistency please use the same domain.
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
Then open the configuration file and set the missing variables: `$settings['hash_salt']`, `$databases['default']['default'` at minimum. If available, configure additional settings: Solr integration etc.

At this stage if you visit http://liiweb.test it should open the Drupal installation procedure. Proceed further.

5. Install the instance using Drush
```shell script
drush site:install --existing-config -y
drush cim sync -y
drush cr
```
6. Open the local instance

When you open the instance http://liiweb.test again you should be able to log in with the username and passwords set by Drush.


## Update local instance

If you already have a local instance installed and configured with code and database, use `git` to get the latest developments from the `master` branch or switch to another branch you wish to test and pull changes, then execute the following commands:
```shell script
drush updatedb -y
drush cim sync -y
drush updatedb -y
drush locale:check -y
drush locale:update -y
drush cr
```

Which imports the new configuration, applies all the pending updates and imports new translations - if available.

## How to commit local changes

Step 1. Export any configuration chances done to your Drupal instance (i.e. add new fields, change settings).

```shell script
drush cex -y
```

Step 2. Stage for commit code and configuration changes (YML files). We recommend using `git add -p` to commit only relevant changes. Sometimes a configuration export might export other changes not necessarily related to current functionality.

```shell script
git add -p /path/to/modified/file
git add /path/to/new/file
git commit -m "refs #123 Added new fields"
```

TODO: Add multi-site configuration details here.

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

## Production deployment using Dokku and Docker

It's easy to deploy liiweb using [Dokku](http://dokku.viewdocs.io/) and [Docker](https://www.docker.com/). Dokku is an open-source version of [Heroku](https://www.heroku.com/) that makes consistent, repeatable deployments easy.

### First time setup

Throughout this example, we will assume you're creating a website called `countrylii` at `countrylii.org`. You should replace that with the name of your LII, such as `namiblii`.

#### 1. MySQL Database

On a separate host, install MySQL and setup a new database. Make note of the hostname, username and password. We suggest you use `countrylii` as both the username and database name.

#### 2. Dokku and Docker

Install [Dokku](http://dokku.viewdocs.io/dokku/) and Docker using the Dokku installation instructions. We suggest installing it on Ubuntu 18.04.

#### 3. Dokku app

Create a new dokku application:

```
dokku apps:create countrylii
dokku domains:add countrylii countrylii.org
```

Set a secret hash salt. This should be at least 20 random characters and numbers.

```
dokku config:set countrylii HASH_SALT=your-hash-salt
```

Tell Dokku about your mysql database by setting a database URL. You will need to change the username, password, hostname and database name in the example below.

```
dokku config:set countrylii DATABASE_URL=mysql://username:password@hostname:3306/databasename
```

#### 4. On your local development machine

Add a git remote to tell git where to push to when you deploy your website:

```
git remote add dokku dokku@yourserver.com:countrylii
```

Deploy your website. This will take a little while.

```
git push dokku
```

#### 5. On the dokku server, setup the website

**Back on the dokku server**, tell Drupal to setup a blank website. This will take a little while.

```
dokku run countrylii vendor/drush/drush/drush site:install --existing-config -y
dokku run countrylii vendor/drush/drush/drush cim sync -y
dokku run countrylii vendor/drush/drush/drush cr
```

#### 5. SSL (optional)

We strongly recommend you include an SSL certificate. You can do this easily, and for free, with Dokku's [letsencrypt plugin](https://github.com/dokku/dokku-letsencrypt).

```
sudo dokku plugin:install https://github.com/dokku/dokku-letsencrypt.git
```

Configure letsencrypt with your email address, so you get reminders about renewing certificates:

```
dokku config:set --no-restart countrylii DOKKU_LETSENCRYPT_EMAIL=your@email.tld
```

**Before you install the certificate, your website's domain name must be setup and pointing at this server, so that you can prove that you own the domain.**

Install the certificate:

```
dokku letsencrypt countrylii
```

### Subsequent deployments

Dokku will only deploy files committed to git. **ONLY DEPLOY FROM THE MASTER BRANCH**.

To deploy, simply push to the dokku remote: `git push dokku`

This will push your changes to the server, Dokku will build a new Docker container with Drupal and all necessary dependencies.
Dokku will only swap the new container in place if the deployment succeeds, otherwise the old container is left running
and nothing changes.


## FAQ


### How can I install contrib modules?

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
