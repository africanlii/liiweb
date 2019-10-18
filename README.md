# liiweb

[![Build Status](https://travis-ci.com/stefanbutura/liiweb.svg?branch=master)](https://travis-ci.org/drupal-composer/drupal-project)



## Setup development environment

1. Git checkout
1. Create a virtual host in Apache
1. Install the instance [TODO]


## Updating Drupal Core

Follow the steps below to update your core files.

1. Run `composer update drupal/core webflo/drupal-core-require-dev "symfony/*" --with-dependencies` to update Drupal Core and its dependencies.
1. Run `git diff` to determine if any of the scaffolding files have changed. Review the files for any changes and restore any customizations to `.htaccess` or `robots.txt`.
1. Commit everything all together in a single commit, so `web` will remain in sync with the `core` when checking out branches or running `git bisect`.
1. In the event that there are non-trivial conflicts in step 2, you may wish to perform these steps on a branch, and use `git merge` to combine the updated core files with your customized files. This facilitates the use of a [three-way merge tool such as kdiff3](http://www.gitshah.com/2010/12/how-to-setup-kdiff-as-diff-tool-for-git.html). This setup is not necessary if your changes are simple; keeping all of your modifications at the beginning or end of the file is a good strategy to keep merges easy.


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
