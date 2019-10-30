# Apache configuration

* Hide Apache version in /etc/httpd/conf/httpd.conf: ServerTokens Prod
* Enable modules: `rewrite_module, proxy_module, proxy_connect_module, proxy_http_module, proxy_fcgi_module, proxy_wstunnel_module, ssl_module`
* Disable unused modules: `Disable unused module to conserve memory:  actions_module, allowmethods_module, auth_digest_module, authn_anon_module, authn_dbd_module, authn_dbm_module, authn_socache_module, authz_dbd_module, authz_dbm_module, authz_groupfile_module, authz_owner_module, cache_module, cache_disk_module, data_module, dbd_module, deflate_module, echo_module, info_module, socache_dbm_module, socache_memcache_module, socache_shmcb_module, substitute_module, userdir_module, version_module, buffer_module, watchdog_module, heartbeat_module, heartmonitor_module, usertrack_module, dialup_module, charset_lite_module, log_debug_module, ratelimit_module, reflector_module, request_module, sed_module, speling_module, lua_module, mpm_prefork_module, proxy_ajp_module, proxy_balancer_module, proxy_fdpass_module, proxy_ftp_module, proxy_scgi_module`
* Create a virtual host for the website

```
<VirtualHost *:80>
        ServerName ro.example.com
        Alias /.well-known /var/www/html/letsencrypt/.well-known
        <Directory "/var/www/html/letsencrypt/.well-known/">
                Options None
                AllowOverride None
                ForceType text/plain
                RedirectMatch 404 "^(?!/\.well-known/acme-challenge/[\w-]{43}$)"
        </Directory>
        RewriteEngine on
        RewriteRule ^/\.well\-known - [L,NC]
        RewriteRule ^ https://ro.example.com%{REQUEST_URI} [END,NE,R=permanent]
</VirtualHost>

<VirtualHost *:443>
        ServerName ro.example.com
        DocumentRoot /var/www/html/example/prod/web/
        <Directory /var/www/html/example/prod/web/>
                AllowOverride All
                Require all granted
        </Directory>
        <FilesMatch "\.ph(ar|p|tml)$">
                SetHandler "proxy:unix:/var/run/php73-fpm.sock|fcgi://localhost"
        </FilesMatch>

        Header Set Strict-Transport-Security "max-age=63072000; includeSubDomains"
        Header Set X-XSS-Protection "1; mode=block"
        Header Set Referrer-Policy "no-referrer-when-downgrade"

        SSLEngine On
        Include /etc/letsencrypt/options-ssl-apache.conf
        SSLCertificateFile /etc/letsencrypt/live/ro.example.com/cert.pem
        SSLCertificateKeyFile /etc/letsencrypt/live/ro.example.com/privkey.pem
        SSLCertificateChainFile /etc/letsencrypt/live/ro.example.com/fullchain.pem
</VirtualHost>
```
