# Production deployment using Dokku and Docker

It's easy to deploy liiweb using [Dokku](http://dokku.viewdocs.io/) and [Docker](https://www.docker.com/). Dokku is an open-source version of [Heroku](https://www.heroku.com/) that makes consistent, repeatable deployments easy.

## First time setup

Throughout this example, we will assume you're creating a website called `countrylii` at `countrylii.org`. You should replace that with the name of your LII, such as `namiblii`.

### 1. MySQL Database

On a separate host, install MySQL or you can use an RDS database instance. Create a new database, and make note of the hostname, username and password. We suggest you use `countrylii` as both the username and database name.

Create a new database:

    CREATE DATABASE countrylii

Create a new user (**change XXX to a random password!***):

    GRANT ALL PRIVILEGES ON countrylii.* TO 'countrylii'@'%' IDENTIFIED BY 'XXX'

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

## Subsequent deployments

Dokku will only deploy files committed to git. **ONLY DEPLOY FROM THE MASTER BRANCH**.

To deploy, simply push to the dokku remote: `git push dokku`

This will push your changes to the server, Dokku will build a new Docker container with Drupal and all necessary dependencies.
Dokku will only swap the new container in place if the deployment succeeds, otherwise the old container is left running
and nothing changes.

Dokku will automatically run database updates and migrations after each deployment.

## PDFs and other media, S3 and CDNs

The site is configured to store uploaded files, such as PDFs and images, in AWS S3. This is persistent cloud storage which
is separate from the database and webserver. The user then loads those files and images from S3, rather than from Drupal, using
an AWS Cloudfront distribution (ie. a CDN).

For example, a user would load the file `gazette.pdf` from `https://media.namiblii.org/files/public/gazette.pdf`.

All of these settings are specific to each individual LIIWeb instillation.

                                                                     
                                +--------------+        +-----------+
          Admin uploads         |              |        |           |
          a PDF or image -----> |    Drupal    | -----> |           |
                                |              |        |           |
                                +--------------+        |           |
                                                        |  Amazon   |
                                                        |    S3     |
                                                        |           |
                                +--------------+        |           |
          User views PDF -----> | CDN          | -----> |           |
          or image       <----- | (Cloudfront) | <----- |           |
                                |              |        |           |
                                +--------------+        +-----------+



### Setting up S3 and the CDN

The steps below describe how to setup:

1. a certificate to use with the CDN to allow HTTPS (manual)
2. an S3 Bucket (automated)
3. the Cloudfront CDN (automated)
4. a user, with a corresponding access key and secret access key, that Drupal uses to write to S3 (automated)
5. a user-friendly domain name for the CDN (manual)

#### Create a certificate

* Log into the [AWS Console](https://aws.amazon.com/console) and go to [Certificate Manager](https://console.aws.amazon.com/acm/home?region=us-east-1).
* You **must** be in the "N. Virginia" region (us-east-1), be sure to choose that from the dropdown in the top-right corner.
* Click **Request a certificate** and then choose **Request a public certificate**.
* For the domain name, enter `media.<lii-website-domain>`, such as `media.namiblii.org`. Do not change the `media.` part of the domain.
* Click **Next**
* Certificate Manager will ask you to verify that you own the domain. Go through that process.
* Once the certificate is issued, make note of the certificate ARN, you will need it later. It looks something like `arn:aws:acm:us-east-1:11111:certificate/XXXX`

#### Create the S3 Bucket, CDN and user

This part uses a script to automate most of these steps.

* Still in the AWS Console, go to [Cloud Formation](https://console.aws.amazon.com/cloudformation/home?region=eu-west-1)
* You **must** be in the "Ireland" region (eu-west-1), be sure to choose that from the dropdown in the top-right corner.
* Click **Create stack** and choose **With new resources**
* Choose **Upload a template file** and click **Choose file**
* Upload [scripts/s3-cdn-user.yaml](scripts/s3-cdn-user.yaml) and click **Next**
* For **Stack name**, enter the short name of the LII, such as `namiblii`
* For **CertificateArn** enter the ARN of the certificate that you created above
* For **LiiName** enter the short name of the LII, such as `namiblii` - DO NOT include `.org` or spaces
* For **LiiDomain** entre the domain name of the LII, such as `namiblii.org` - DO NOT include `https` or `www`
* Click **Next** twice, then check the **I acknowledge that AWS CloudFormation might create IAM resources with custom names** checkbox and click **Create stack**

AWS will take a few moments to create all the resources it needs. It will take about 15 minutes for the final step of setting up the Cloudfront Distribution (CDN) to complete. 

Once it is done, it will show a number of important settings in the **Output** tab that you will need later.

* BucketName - this is the bucket it created
* CDNDomain - this is the domain of the cloudfront CDN
* UserAccessKey and UserSecretKey - these are the key and password that Drupal will use to talk to S3


#### Configure Drupal

Configure Drupal with these settings by running these commands **on the dokku host**, don't forget to **fill in the values from above**:

```
dokku config:set countrylii AWS_ACCESS_KEY_ID=your-access-key \
                            AWS_SECRET_ACCESS_KEY=your-secret-key \ 
                            S3_BUCKET=your-s3-bucket \
                            S3_CDN_DOMAIN=media.your-lii-domain
```

#### Setup DNS

Log into the DNS provider for your LII, and add a DNS entry:

* Type: CNAME
* Name: media.your-lii-domain (eg. `media.namiblii.org`)
* Value: the CDNDomain output value from above (eg. `xxxxx.cloudfront.net`)

## Cron job

Optionally, install a crontab to ensure that Drupal's cron job is run daily.

* Copy the file `scripts/crontab` to your dokku server's app directory, such as `/home/dokku/liiweb/crontab`.
* Edit the file and set the `APP` variable to the name of your dokku app
* Then link in the crontab file, changing `liiweb` where necessary:

```bash
APP=liiweb
sudo ln -s /home/dokku/$APP/crontab /etc/cron.d/$APP
sudo chown root:root /etc/cron.d/$APP
```

The cron job writes its logs to `/home/dokku/$APP/cron.log`.
