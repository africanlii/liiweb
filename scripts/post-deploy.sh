#!/usr/bin/env bash
set -xeo pipefail

drush=vendor/drush/drush/drush

# this is run by dokku once the deployment is complete
# changes made to the file system are NOT persisted
#
# ref: http://dokku.viewdocs.io/dokku/advanced-usage/deployment-tasks/#appjson-deployment-tasks

# 1. Set the site into maintenance mode, this lowers the chances of DB transaction deadlocks
$drush state-set system.maintenance_mode TRUE && \
# 2. Run updatedb #1 (some modules update before the config import)
$drush updatedb -y && \
# 3. Clear caches
$drush cr && \
# 4. Run config import
$drush cim sync -y && \
# 5. Run updatedb #2 (some modules update AFTER the config import)
$drush updatedb -y && \
# 6. Update module string translations
$drush locale:check && \
$drush locale:update && \
# 7. Clear caches one more time
$drush cr && \
# 8. Remove maintenance mode
$drush state-set system.maintenance_mode FALSE
