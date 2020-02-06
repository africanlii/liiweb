#!/usr/bin/env bash
set -eo pipefail

drush=vendor/drush/drush/drush

# this is run by dokku once the deployment is complete
# changes made to the file system are NOT persisted
#
# ref: http://dokku.viewdocs.io/dokku/advanced-usage/deployment-tasks/#appjson-deployment-tasks

$drush state-set system.maintenance_mode TRUE && \
$drush updatedb -y && \
$drush cr && \
$drush cim sync -y && \
$drush updatedb -y && \
$drush locale:check && \
$drush locale:update && \
$drush cr && \
$drush state-set system.maintenance_mode FALSE
