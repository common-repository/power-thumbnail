<?php


$rewrite_base = preg_replace ('/^http:\/\/[^\/]+/i', '', POWER_THUMBNAIL_BASE_URL);

if(get_option('power_thumbnail_enable_rewrite')) @file_put_contents(POWER_THUMBNAIL_PATH . '.htaccess', <<<HTACCESS
<IfModule mod_expires.c>
 # enable expirations
ExpiresActive On
ExpiresByType image/jpeg "access plus 3 day"
</IfModule>

<IfModule mod_rewrite.c> 
RewriteEngine On
RewriteBase $rewrite_base/wp-content/plugins/power-thumbnail/
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^(\d+)\/tile(\d+)\-(\d+)\-(\d+)\.jpg\$  show-image.php?type=tile&id=\$1&z=\$2&x=\$3&y=\$4
RewriteRule ^rewrite-enabled\$ enable-rewrite.php?status=Success
</IfModule>
HTACCESS
);
?>