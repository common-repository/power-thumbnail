<?php
define('POWER_THUMBNAIL_HOME', dirname(__FILE__));

require_once(dirname(dirname(dirname(POWER_THUMBNAIL_HOME))).'/wp-config.php');

if (empty($wp)) {
	wp();
}



if(isset($_GET['status'])){
	$enabled = $_GET['status']=='disabled' ? 0:1;
	update_option('power_thumbnail_enable_rewrite', $enabled);
	echo $_GET['status'];
	exit;
}

include_once(dirname(__FILE__).'/functions.php');
define('POWER_THUMBNAIL_UPLOAD_PATH', get_settings('upload_path') . '/zoomer.files/');
define('POWER_THUMBNAIL_BASE_URL', get_bloginfo('wpurl'));
define('POWER_THUMBNAIL_PATH', ABSPATH . POWER_THUMBNAIL_UPLOAD_PATH);
define('POWER_THUMBNAIL_URL',  POWER_THUMBNAIL_BASE_URL .'/'. POWER_THUMBNAIL_UPLOAD_PATH);

$rewrite_base = preg_replace ('/^http:\/\/[^\/]+/i', '', POWER_THUMBNAIL_BASE_URL);

@file_put_contents(POWER_THUMBNAIL_PATH . '.htaccess', <<<HTACCESS
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

header('Location: '.IMAGE_ZOOMER_URL.'rewrite-enabled');
?>