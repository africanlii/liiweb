INTRODUCTION
------------

  * S3 File System (s3fs) provides an additional file system to your drupal
    site, alongside the public and private file systems, which stores files in
    Amazon's Simple Storage Service (S3) or any S3-compatible storage service.
    You can set your site to use S3 File System as the default, or use it only
    for individual fields. This functionality is designed for sites which are
    load-balanced across multiple servers, as the mechanism used by Drupal's
    default file systems is not viable under such a configuration.


REQUIREMENTS
------------

  * AWS SDK version-3. If module is installed via Composer it gets
    automatically installed.

  * Your PHP must be configured with "allow_url_fopen = On" in your php.ini
    file.
    Otherwise, PHP will be unable to open files that are in your S3 bucket.


INSTALLATION
------------

  * With the code installation complete, you must now configure s3fs to use
    your Amazon Web Services credentials. To do so, store them in the $config
    array in your site's settings.php file (sites/default/settings.php), like
    so:
    $settings['s3fs.access_key'] = 'YOUR ACCESS KEY';
    $settings['s3fs.secret_key'] = 'YOUR SECRET KEY';

  * Configure your settings for S3 File System (including your S3 bucket name)
    at /admin/config/media/s3fs. You can input your AWS credentials on this
    page as well, but using the $config array is recommended.

  * With the settings saved, go to /admin/config/media/s3fs/actions to refresh
    the file metadata cache. This will copy the filenames and attributes for
    every existing file in your S3 bucket into Drupal's database. This can take
    a significant amount of time for very large buckets (thousands of files).
    If this operation times out, you can also perform it using "drush
    s3fs-refresh-cache".

  * Please keep in mind that any time the contents of your S3 bucket change
    without Drupal knowing about it (like if you copy some files into it
    manually using another tool), you'll need to refresh the metadata cache
    again. S3FS assumes that its cache is a canonical listing of every file in
    the bucket. Thus, Drupal will not be able to access any files you copied
    into your bucket manually until S3FS's cache learns of them. This is true
    of folders as well; s3fs will not be able to copy files into folders that
    it doesn't know about.


CONFIGURATION
-------------

  * Visit the admin/config/media/file-system page and set the "Default download
    method" to "Amazon Simple Storage Service"
    -and/or-
    Add a field of type File, Image, etc. and set the "Upload destination" to
    "Amazon Simple Storage Service" in the "Field Settings" tab.

  * This will configure your site to store new uploaded files in S3. Files
    which your site creates automatically (such as aggregated CSS) will still
    be stored in the server's local filesystem, because Drupal is hard-coded
    to use the public:// filesystem for such files.

  * However, s3fs can be configured to handle these files as well. In
    settings.php you can enable the s3fs.use_s3_for_public and
    s3fs.use_s3_for_private settings to make s3fs take over the job of the
    public and/or private file systems. This will cause your site to store
    newly uploaded/generated files from the public/private file system in S3
    instead of the local file system. However, it will make any existing files
    in those file systems become invisible to Drupal.
    To remedy this, you'll need to copy those files into your S3 bucket.
    
    Example:
    $settings['s3fs.use_s3_for_public'] = TRUE;

  * If you use s3fs for public, you should change your php twig storage folder
    to a local directory, php twig files in S3 produce latency and security
    issues (these files would be public). Please change the php_storage
    settings in your setting.php and choose a directory, out of docroot
    recommended.
    
    Example:
    $settings['php_storage']['twig']['directory'] = '../storage/php';

    If you have a multiple backends you may use a NAS to store it or other
    shared storage system with your others backends.

  * If your S3 bucket is configured to store all files as private and only
    access files through CNAME, enable 'upload_as_private' feature. This
    feature is incompatible with private stream wrapper.

    Example:
    $settings['s3fs.upload_as_private'] = TRUE;

COPY LOCAL FILES TO S3
----------------------

  * You are strongly encouraged to use the drush command "drush
    s3fs-copy-local" to do this, as it will copy all the files into the correct
    subfolders in your bucket, according to your s3fs configuration, and will
    write them to the metadata cache. If you don't have drush, you can use the
    buttons provided on the S3FS Actions page (admin/config/media/s3fs/actions),
    though the copy operation may fail if you have a lot of files, or very
    large files. The drush command will cleanly handle any combination of
    files.

  * This feature change from 7.x version, now it's possible copy local files to
    s3 without activate in settings.php use_s3_for_public or use_s3_for_private.
    Activate this before migration, you will have unavailable all your images
    during the process.
    Activate this after migration, you shouldn't upload images during the
    process and when the process finished, replace with a simple query all
    s3:// by public:// if the files are public.
    This process is only useful if you have enabled public or private, after or
    before the migration, but it should be enabled in one of them cases.
    You can do your custom migrating process implements S3fsServiceInterface or
    extends S3fsService and use your custom service class in a ServiceProvider
    (see S3fsServiceProvider).


TROUBLESHOOTING
---------------

  * In the unlikely circumstance that the version of the SDK you downloaded
    causes errors with S3 File System, you can download this version instead,
    which is known to work:
    https://github.com/aws/aws-sdk-php/releases/download/3.22.7/aws.zip

  * IN CASE OF TROUBLE DETECTING THE AWS SDK LIBRARY:
    Ensure that the aws folder itself, and all the files within it, can be read
    by your webserver. Usually this means that the user "apache" (or "_www" on
    OSX) must have read permissions for the files, and read+execute permissions
    for all the folders in the path leading to the aws files.


AGGREGATED CSS AND JS IN S3
---------------------------

  * Because of the way browsers interpret relative URLs used in CSS files, and
    how they restrict requests made from external javascript files, if you want
    your site's aggregated CSS and JS to be placed in S3, you'll need to set up
    your webserver as a proxy for those files. S3 File System will present all
    public:// css files with the url prefix /s3fs-css/, and all public://
    javascript files with /s3fs-js/. So you need to set up your webserver to
    proxy all URLs with those prefixes into your S3 bucket.

  * For Apache, add this code to the right location* in your server's config:
    ProxyRequests Off
    SSLProxyEngine on
    <Proxy *>
        Order deny,allow
        Allow from all
    </Proxy>
    ProxyPass /s3fs-css/ https://YOUR-BUCKET.s3.amazonaws.com/s3fs-public/
    ProxyPassReverse /s3fs-css/ https://YOUR-BUCKET.s3.amazonaws.com/s3fs-public/
    ProxyPass /s3fs-js/ https://YOUR-BUCKET.s3.amazonaws.com/s3fs-public/
    ProxyPassReverse /s3fs-js/ https://YOUR-BUCKET.s3.amazonaws.com/s3fs-public/

  * For nginx, add this to your server config:
    location ~* ^/(s3fs-css|s3fs-js)/(.*) {
      set $s3_base_path 'YOUR-BUCKET.s3.amazonaws.com/s3fs-public';
      set $file_path $2;

      resolver         172.16.0.23 valid=300s;
      resolver_timeout 10s;

      proxy_pass http://$s3_base_path/$file_path;
    }

  * Again, be sure to take the S3FS Root Folder setting into account, here.
    The /s3fs-public/ subfolder is where s3fs stores the files from the
    public:// filesystem, to avoid name conflicts with files from the s3://
    filesystem.

  * If you're using the "Use a Custom Host" option to store your files in a
    non-Amazon file service, you'll need to change the proxy target to the
    appropriate URL for your service.

  * Under some domain name setups, you may be able to avoid the need for
    proxying by having the same domain name as your site also point to your S3
    bucket. If that is the case with your site, enable the "Don't rewrite
    CSS/JS file paths" option to prevent s3fs from prefixing the URLs for
    CSS/JS files.


IMAGE STYLES
------------

  * S3FS display image style from Amazon trough dynamic routes /s3/files/styles/
    to fix the issues around style generated images being stored in S3.
    (read more at https://www.drupal.org/node/2861975)
  * If you are using Nginx as webserver, it is neccessary to add additional
    block to your's website Nginx site configuration:
    location ~ ^/s3/files/styles/ {
            try_files $uri @rewrite;
    }


UPGRADING FROM S3 FILE SYSTEM 7.x-2.x or 7.x-3.x
------------------------------------------------

  * Drupal 8 version has the most of 7 params, you must use the new $config
    and $settings arrays, please read INSTALLATION and CONFIGURATION sections.

  * The database schema is the same than 7. Export and import, it could be
    enough. Other options could be refresh metadata cache when it'll be
    implemented.

  * If you use some functions or methods from .module or other files in your
    custom code you must find the equivalent function or method.


KNOWN ISSUES
------------

  * These problems are from Drupal 7, now we don't know if they happen in 8.
    If you tried that options or know new issues, please create a new issue
    in https://www.drupal.org/project/issues/s3fs?version=8.x

      * Some curl libraries, such as the one bundled with MAMP, do not come
        with authoritative certificate files. See the following page for
        details:
        http://dev.soup.io/post/56438473/If-youre-using-MAMP-and-doing-something

      * Because of a bizarre limitation regarding MySQL's maximum index length
        for InnoDB tables, the maximum uri length that S3FS supports is 250
        characters.
        That includes the full path to the file in your bucket, as the full
        folder path is part of the uri.

      * eAccelerator, a deprecated opcode cache plugin for PHP, is incompatible
        with AWS SDK for PHP. eAccelerator will corrupt the configuration
        settings for the SDK's s3 client object, causing a variety of different
        exceptions to be thrown. If your server uses eAccelerator, it is highly
        recommended that you replace it with a different opcode cache plugin,
        as its development was abandoned several years ago.


ACKNOWLEDGEMENT
---------------

  * Special recognition goes to justafish, author of the AmazonS3 module:
    http://drupal.org/project/amazons3

  * S3 File System started as a fork of her great module, but has evolved
    dramatically since then, becoming a very different beast. The main benefit
    of using S3 File System over AmazonS3 is performance, especially for image-
    related operations, due to the metadata cache that is central to S3 File
    System's operation.


MAINTAINERS
-----------

Current maintainers:

  * webankit (https://www.drupal.org/u/webankit)

  * coredumperror (https://www.drupal.org/u/coredumperror)

  * zach.bimson (https://www.drupal.org/u/zachbimson)

  * neerajskydiver (https://www.drupal.org/u/neerajskydiver)

  * Abhishek Anand (https://www.drupal.org/u/abhishek-anand)

  * jansete (https://www.drupal.org/u/jansete)
