# Production deployment using Dokku and Docker

It's easy to deploy liiweb using [Dokku](http://dokku.viewdocs.io/) and [Docker](https://www.docker.com/). Dokku is an open-source version of [Heroku](https://www.heroku.com/) that makes consistent, repeatable deployments easy.

## First time setup

Throughout this example, we will assume you're creating a website called `countrylii` at `countrylii.org`. You should replace that with the name of your LII, such as `namiblii`.

### 1. MySQL Database

On a separate host, install MySQL and setup a new database. Make note of the hostname, username and password. We suggest you use `countrylii` as both the username and database name.

### 2. Dokku and Docker

Install [Dokku](http://dokku.viewdocs.io/dokku/) and Docker using the Dokku installation instructions. We suggest installing it on Ubuntu 18.04.

### 3. Dokku app

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

### 4. On your local development machine

Add a git remote to tell git where to push to when you deploy your website:

```
git remote add dokku dokku@yourserver.com:countrylii
```

Deploy your website. This will take a little while.

```
git push dokku
```

### 5. On the dokku server, setup the website

**Back on the dokku server**, tell Drupal to setup a blank website. This will take a little while.

```
dokku run countrylii vendor/drush/drush/drush site:install --existing-config -y
dokku run countrylii vendor/drush/drush/drush cim sync -y
dokku run countrylii vendor/drush/drush/drush cr
```

Copy the `nginx/liiweb.conf` Nginx config file from the repo into `~dokku/countrylii/nginx.conf.d/`.

### 5. SSL (optional)

We strongly recommend you include an SSL certificate. You can do this easily, and for free, with Dokku's [letsencrypt plugin](https://github.com/dokku/dokku-letsencrypt).

Install the letsencrypt plugin:

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

#### Renewing a certificate

Letsencrypt certificates expire every three months. Let's setup a cron job to renew certificates automatically:

```
dokku letsencrypt:cron-job --add
```

You can also manually renew a certificate when it's close to expiry:

```
dokku letsencrypt:auto-renew countrylii
```

### Subsequent deployments

Dokku will only deploy files committed to git. **ONLY DEPLOY FROM THE MASTER BRANCH**.

To deploy, simply push to the dokku remote: `git push dokku`

This will push your changes to the server, Dokku will build a new Docker container with Drupal and all necessary dependencies.
Dokku will only swap the new container in place if the deployment succeeds, otherwise the old container is left running
and nothing changes.
