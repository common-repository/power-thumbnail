<?php
/*
Plugin Name: Power Thumbnail
Plugin URI: http://blog.codexpress.cn/php/wordpress-plugin-power-thumbnail/
Description: Power Thumbnail enables you to generate thumbnails with the existing attachment system. It offers two kind of thumbnails. Basic Resizer -- make thumbnails with given width or height. Advanced Zoomer -- split large images into small pieces and load them like Google Maps (you can drag, move and zoom the picture). The loading speed is faster when you apply Advanced Zoomer to large pictures
Author: Jiang Kuan
Version: 1.1
Author URI: http://blog.codexpress.cn
*/

add_option('power_thumbnail_enable_rewrite', 0, 'Power Thumbnail Enable Rewrite');

define('POWER_THUMBNAIL_HOME', dirname(__FILE__));

include_once(POWER_THUMBNAIL_HOME.'/google.api.key.inc');

require_once(POWER_THUMBNAIL_HOME.'/functions.php');


define('POWER_THUMBNAIL_UPLOAD_PATH', get_settings('upload_path') . '/zoomer.files/');
define('POWER_THUMBNAIL_BASE_URL', get_bloginfo('wpurl'));
define('POWER_THUMBNAIL_PATH', ABSPATH . POWER_THUMBNAIL_UPLOAD_PATH);
define('POWER_THUMBNAIL_URL',  POWER_THUMBNAIL_BASE_URL .'/'. POWER_THUMBNAIL_UPLOAD_PATH);

// check version
define('POWER_THUMBNAIL_VERSION', '1.1');

if(!file_exists(POWER_THUMBNAIL_PATH.'VERSION')) define('POWER_THUMBNAIL_OLD_VERSION', '1.0b1');
else define('POWER_THUMBNAIL_OLD_VERSION', @file_get_contents(POWER_THUMBNAIL_PATH.'VERSION'));

require_once(POWER_THUMBNAIL_HOME.'/upgrade.php');
@file_put_contents(POWER_THUMBNAIL_PATH.'VERSION', POWER_THUMBNAIL_VERSION);


// main
if(!is_dir(POWER_THUMBNAIL_PATH)) mkdir(POWER_THUMBNAIL_PATH, 0777);


add_action('admin_print_scripts', 'pt_insert_admin_script');

add_action('wp_head', 'pt_insert_zoomer_script');

add_action('simple_edit_form', 'pt_insert_controls');
add_action('edit_form_advanced', 'pt_insert_controls');
add_action('edit_page_form', 'pt_insert_controls');

add_action('delete_attachment', 'pt_delete_attachment');

add_filter('the_content', 'pt_add_content_filter');

add_filter('content_save_pre', 'pt_content_save_pre');

function pt_content_save_pre($content){
	if(!function_exists('pt_clean_content')){
		function pt_clean_content($matches){
			list($id, $width, $height, $alt, $enabled, $emptyCache, $maxZoom, $zoom) = explode('|', $matches[1]);
			
			if(!is_numeric($width . $height . $enabled . $emptyCache . $maxZoom . $zoom)) return '';
			
			if($emptyCache) pt_delete_attachment($id);
			if($maxZoom!='') return "\[zoomer\]$id|$width|$height|$alt|$enabled|0|$maxZoom|$zoom\[\/zoomer\]";
			else return "\[zoomer\]$id|$width|$height|$alt|$enabled|0\[\/zoomer\]";
		}
	}
	return preg_replace_callback('/\[zoomer\](.*?)\[\/zoomer\]/is', 'pt_clean_content', $content);
}


function pt_delete_attachment($postid){
	if(!function_exists('pt_remove_dir')){
		function pt_remove_dir($dir_name){
			// dangerous, should be limited
			if(strpos($dir_name.'/', POWER_THUMBNAIL_PATH) !=0) return false;
			if(! is_dir($dir_name)){
				return false;
				// trigger_error('Wrong directory name', E_USER_ERROR);
			}
			$result = false;
			$handle = opendir($dir_name); 
			while(($file = readdir($handle)) !== false){ 
				if($file != '.' && $file != '..'){ 
					$dir = $dir_name . DIRECTORY_SEPARATOR . $file;
					is_dir($dir) ? pt_remove_dir($dir) : unlink($dir);
				}
			}
			closedir($handle);
			$result = rmdir($dir_name);
			return $result;
		}
	}
	
	$dir = POWER_THUMBNAIL_PATH.$postid;
	pt_remove_dir($dir);
	
	$src = get_attached_file( $postid );
	if(is_file($src) && $info = @getimagesize($src)){
		if(!is_dir($dir)) mkdir($dir, 0777);
		
		if(is_dir($dir) && !is_file($dir.'/fileinfo'))
			file_put_contents($dir.'/fileinfo', $src);
	}
}

function pt_add_content_filter($content){
	if(!function_exists('pt_make_zoomer_div')){
		function pt_make_zoomer_div($matches){
			list($id, $width, $height, $alt, $enabled, $cache, $maxZoom, $zoom) = explode('|', $matches[1]);
			$src = get_attached_file( $id );
			if(is_file($src)){
				if($info = @getimagesize($src)){
					$dir = POWER_THUMBNAIL_PATH.$id;
					
					if(!is_dir($dir)) mkdir($dir, 0777);
					
					if(is_dir($dir) && !is_file($dir.'/fileinfo'))
						file_put_contents($dir.'/fileinfo', $src);
					
					list($srcWidth, $srcHeight) = array_values($info);
				}
				if($width) $height = round($srcHeight/$srcWidth*$width);
				else if($height) $width = round($srcWidth/$srcHeight*$height);
				else return '';
				
				$ratio = (double)($height / $srcHeight);
				
				if(is_feed() || !$enabled){
					return "<img src='".POWER_THUMBNAIL_BASE_URL."/wp-content/plugins/power-thumbnail/show-image.php?type=resize&amp;id=$id&amp;r=$ratio&amp;w=$width&amp;h=$height' alt='$alt' width='$width' height='$height' />";
				}
				
				$maxZoom = ceil(log10(max($srcWidth, $srcHeight)/256)/log10(2));
				$zoom = round(log10(max($width, $height)/256)/log10(2));
				
				$mapSize = 256 * pow(2, $maxZoom);
				$offsetX = ($mapSize - $srcWidth) / $mapSize * 180;
				$offsetY = ($mapSize - $srcHeight) / $mapSize * 90;
				
				$uri = get_option('power_thumbnail_enable_rewrite') && file_exists(POWER_THUMBNAIL_PATH.'.htaccess') ? POWER_THUMBNAIL_URL . "$id/tile{Z}-{X}-{Y}.jpg" : POWER_THUMBNAIL_BASE_URL."/wp-content/plugins/power-thumbnail/show-image.php?type=tile&id=$id&m=$maxZoom&x={X}&y={Y}&z={Z}";
				return "<span id=\"power_thumbnail_$id\"><strong>$alt</strong><br /> You need to enable javascript to view the content.<br /> <a href='http://blog.codexpress.cn/php/wordpress-plugin-power-thumbnail/' title='Power Thumbnail'><strong>Power Thumbnail</strong></a> Powered By <a href='http://blog.codexpress.cn' title='CodeXpress.CN'><strong>CodeXpress.CN</strong></a></span><script type=\"text/javascript\">ImageZoomer(\"power_thumbnail_$id\", $zoom, \"$uri\", $maxZoom, $width, $height);</script>";
			}else if($enabled){
				$folderUrl = POWER_THUMBNAIL_URL . $id;
				if(is_feed()){
					return "<img src='$folderUrl/tile0-0-0.jpg' alt='$alt' />";
				}
				return "<span id=\"power_thumbnail_$id\"><strong>$alt</strong><br /> You need to enable javascript to view the content.<br /> <a href='http://blog.codexpress.cn/php/wordpress-plugin-power-thumbnail/' title='Power Thumbnail'><strong>Power Thumbnail</strong></a> Powered By <a href='http://blog.codexpress.cn' title='CodeXpress.CN'><strong>CodeXpress.CN</strong></a></span></span><script type=\"text/javascript\">ImageZoomer(\"power_thumbnail_$id\", $zoom, \"$folderUrl/tile{Z}-{X}-{Y}.jpg\", $maxZoom, $width, $height);</script>";
			}else{
				return '';
			}
		}
	}
	return preg_replace_callback('/\[zoomer\](.*?)\[\/zoomer\]/is', 'pt_make_zoomer_div', $content);
}



function pt_insert_zoomer_script(){
	if(defined('GOOGLE_MAPS_API_KEY')) $key = GOOGLE_MAPS_API_KEY;
	else return;
?>
<script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key=<?php echo $key; ?>" type="text/javascript"></script>
<script type='text/javascript'>
/* <![CDATA[ */
function ImageZoomer(id, zoom, url, max, w, h){
if (GBrowserIsCompatible()){
var copyright = new GCopyright(100, new GLatLngBounds(new GLatLng(-90, -180), new GLatLng(90, 180)), 0, "&copy;2007 ");
var copyrightCollection = new GCopyrightCollection("<a href='http://blog.codexpress.cn' rel='external'>CodeXpress.CN</a>");
copyrightCollection.addCopyright(copyright);
var zoomerTile = new GTileLayer(copyrightCollection, 0, max, {isPng:false, tileUrlTemplate: url});
var tilelayers = [zoomerTile];
var zoomerType = new GMapType(tilelayers, G_NORMAL_MAP.getProjection(), "Power Thumbnail", {errorMessage:"N/A"});
var anchor = document.getElementById(id);
anchor.innerHTML = "";
var style = "";
if(anchor.parentNode.tagName.toLowerCase()=='p'){
	var align = anchor.parentNode.align.toLowerCase();
	style = anchor.parentNode.style.cssText;
	anchor.parentNode.style.cssText = 'display:none;';
	anchor = anchor.parentNode;
}
var canvas = document.createElement('div');
canvas.style.cssText = style;
canvas.style.width = w+"px";
canvas.style.height = h+"px";
switch(align){
case 'center':canvas.style.marginLeft = "auto";
canvas.style.marginRight = "auto";
break;
case 'right':canvas.style.marginLeft = "auto";
canvas.style.marginRight = "0px";
break;
case 'left':canvas.style.marginLeft = "0px";
canvas.style.marginRight = "auto";
break;
}
canvas.style.textAlign = align;
anchor.parentNode.insertBefore(canvas, anchor);
var map = new GMap2(canvas, {mapTypes: [zoomerType]});
map.setCenter(new GLatLng(0, 0), zoom);
map.addControl(new GSmallZoomControl());
GEvent.addDomListener(window, 'unload', function(){GUnload()});
}
}
/* ]]> */
</script>
<?php
}


function pt_insert_admin_script(){
	global $wp_version;
	
	if (strpos($_SERVER['SCRIPT_NAME'], 'upload.php')){
		if(version_compare($wp_version, '2.3', '>=')) {
			$prototypeJS = POWER_THUMBNAIL_BASE_URL. '/' .WPINC . '/js/prototype.js';
			echo "<script src=\"$prototypeJS\" type=\"text/javascript\"></script>";
		}
?>
<script type='text/javascript'>
/* <![CDATA[ */

// I hate this but they removes Prototype from upload.php since WP2.3

var win = window.opener ? window.opener : window.dialogArguments;
if ( !win ) win = top;

addLoadEvent(function(){
	if(!String.prototype.toQueryParams) return;
	$$('a.file-link').each(function(i){
		var id = String(i.id).split('-').pop();
		i.observe('click', function(){
			var el = $('attachment-is-image-' + id);
			if(!!id && !!el && el.value!='0'){
				win.$('power_thumbnail_id').value = id;
			}else{
				win.$('power_thumbnail_id').value = '';
			}
		})
	});
	
	var urlData  = document.location.href.split('?');
	var params = urlData[1].toQueryParams();
	var id = params['ID'];
	var el = $('attachment-is-image-' + id);
	if(!!id && !!el && el.value!='0'){
		win.$('power_thumbnail_id').value = id;
	}else{
		win.$('power_thumbnail_id').value = '';
	}

});
/* ]]> */
</script>
<?php
	}else{
?>
<script type="text/javascript">
/* <![CDATA[ */
function pt_SendToEditor(p){
	if(!p[0]){
		alert("Image ID must not be empty!\nPlease upload or choose an image below!");
		return;
	}
	p[1] = Math.abs(parseInt(p[1])|| 0);
	p[2] = Math.abs(parseInt(p[2])|| 0);
	if(!p[1] && !p[2]){
		alert("At least one of Width and Height should be a positive integer");
		return;
	}
	
	
	var h = "[zoomer]"+p.join("|")+"[/zoomer]";
	if ( typeof tinyMCE != 'undefined' && tinyMCE.getInstanceById('content') ) {
		tinyMCE.selectedInstance.getWin().focus();
		tinyMCE.execCommand('mceInsertContent', false, h);
	} else {
		edInsertContent(edCanvas, "\n\n"+h+"\n\n");
	}
}
/* ]]> */
</script>
<?php
	}
}

function pt_insert_controls(){
	if(!function_exists('gd_info')){
		echo '<h3 class="dbx-handle">GD Library is not properly installed or version does not meet the minimum requirement!</h3>';
		return;
	}
?>
<fieldset id="power_thumbnail_div" class="dbx-box">
<div class="dbx-h-andle-wrapper">
<h3 class="dbx-handle">Power Thumbnail</h3>
</div>
<div class="dbx-content" style="margin: 10px">
<div>
	<label for="power_thumbnail_id">ID</label> <input style="margin: 5px 10px 0px 0px" type="text" name="power_thumbnail_id" size="3" readonly="readonly" id="power_thumbnail_id" value="" />
	<label for="power_thumbnail_width">Width</label> <input style="margin: 5px 10px 0px 0px" type="text" name="power_thumbnail_width" size="3" id="power_thumbnail_width" value="400" />
	<label for="power_thumbnail_height">Height</label> <input style="margin: 5px 10px 0px 0px" type="text" name="power_thumbnail_height" size="3" id="power_thumbnail_height" value="0" />
	<label for="power_thumbnail_alt">Alt Text</label> <input style="margin: 5px 10px 0px 0px" type="text" name="power_thumbnail_alt" size="15" id="power_thumbnail_alt" value="Power Thumbnail" />
	<input style="margin: 5px 0px 0px 0px" type="checkbox" name="power_thumbnail_enable_zoomer" id="power_thumbnail_enable_zoomer" value="5" /> <label for="power_thumbnail_enable_zoomer">Enable Advanced Zoomer</label> 
	<input style="margin: 5px 0px 0px 0px" type="checkbox" name="power_thumbnail_empty_cache" id="power_thumbnail_empty_cache" value="5" /> <label for="power_thumbnail_empty_cache">Empty Cache</label> 
	<input style="margin: 5px 10px 0px 20px"  type="button" name="power_thumbnail_insert" size="4" id="power_thumbnail_insert" value="Send to editor &raquo;" onclick="pt_SendToEditor([power_thumbnail_id.value, power_thumbnail_width.value, power_thumbnail_height.value, power_thumbnail_alt.value, new Number(!!power_thumbnail_enable_zoomer.checked), new Number(!!power_thumbnail_empty_cache.checked)])" />
	(<a href="javascript:void(0)" onclick="var isShowing=this.innerHTML=='Show Help';this.innerHTML=isShowing?'Hide Help':'Show Help';$('power_thumbnail_help').style.display=isShowing?'block':'none'">Show Help</a>)
<ul id="power_thumbnail_help" style="display:none">
	<li><strong>ID</strong> is a readonly property. It will automatically be filled when you upload a new image or choose an existing one from below.</li>
	<li><strong>Width/Height</strong> must be an positive integer. If one of the them is left empty or zero, it will be calculated proportionally according to the other; so you should at least fill one of them.</li>
	<li><strong>Advanced Zoomer</strong> requires Google Maps API key (sign up <a href="http://www.google.com/apis/maps/signup.html" target="_blank">here</a>). Open <em>google.api.key.inc</em> in the plugin folder, and follow the instructions there.</li>
	<li>If <strong>Advanced Zoomer</strong> is enabled, you can drag, move and zoom your pictures as you do with Google Maps. The loading speed is slow at the first time, because the cache is empty. The author is recommended to be the first one to generate cache.</li>
	<li>If <strong>Empty Cache</strong> is checked, cached files for the given attachment will be deleted and regenerated.</li>
	<li>If your host support custom <strong>.htaccess</strong>, click <a href="<?php echo POWER_THUMBNAIL_BASE_URL.'/wp-content/plugins/power-thumbnail/enable-rewrite.php'; ?>" target="_blank"><strong>HERE</strong></a> to enable <strong>Rewrite</strong> module. If "Success" is returned, <strong>Rewrite</strong> is enabled.</li>
	<li>To get more detailed instruction or report bugs, please go to <a href="http://blog.codexpress.cn/wordpress/wordpress-plugin-power-thumbnail/" target="_blank">http://blog.codexpress.cn/wordpress/wordpress-plugin-power-thumbnail/</li>
</ul>
</div>
</div>
	
</fieldset>
<?php
}
?>