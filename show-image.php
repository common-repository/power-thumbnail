<?php
define('POWER_THUMBNAIL_HOME', dirname(__FILE__));

require_once(dirname(dirname(dirname(POWER_THUMBNAIL_HOME))).'/wp-config.php');
require_once(POWER_THUMBNAIL_HOME.'/functions.php');

if (empty($wp)) {
	wp();
}


define('POWER_THUMBNAIL_UPLOAD_PATH', get_settings('upload_path') . '/zoomer.files/');
define('POWER_THUMBNAIL_BASE_URL', get_bloginfo('wpurl'));
define('POWER_THUMBNAIL_PATH', ABSPATH . POWER_THUMBNAIL_UPLOAD_PATH);
define('POWER_THUMBNAIL_URL',  POWER_THUMBNAIL_BASE_URL .'/'. POWER_THUMBNAIL_UPLOAD_PATH);


if(isset($_GET['id'])){
	$path = POWER_THUMBNAIL_PATH.$_GET['id'];
	$url = POWER_THUMBNAIL_URL.$_GET['id'];
}
else exit();


if(isset($_GET['type']) && is_dir($path)){
	
	if($_GET['type']=='resize' && isset($_GET['w']) && isset($_GET['h']) && isset($_GET['r'])) {
		$cachename = 'resized-'.$_GET['w'].'x'.$_GET['h'].'.jpg';
		
		$cachefile = $path.'/'.$cachename;
		
		$ratio = (double)$_GET['r'];
		
		if(file_exists($cachefile)){	// cached
			header("Location: $url/$cachename");
		}else{	// not cached
			if(function_exists('imagecreatetruecolor')) $dst = @imagecreatetruecolor($_GET['w'], $_GET['h']);
			else $dst = @imagecreate($_GET['w'], $_GET['h']);
			if(!$dst) exit();
			
			set_time_limit(120);
			$source = @file_get_contents($path.'/fileinfo');
			if(!file_exists($source)) exit;
			$src = @imagecreatefromstring(@file_get_contents($source));
			if(!$src) exit();
			
			if(@imagecopyresampled($dst, $src, 0, 0, 0, 0, $_GET['w'], $_GET['h'], round($_GET['w'] / $ratio), round($_GET['h'] / $ratio))){
				header('content-type: image/jpeg');
				@imagejpeg($dst, $cachefile);
				@imagejpeg($dst);
				@imagedestroy($dst);
				@imagedestroy($src);
			}
		}
	}else if(isset($_GET['x']) && isset($_GET['y']) && isset($_GET['z'])){
		
		$cachename = 'tile'.$_GET['z'].'-'.$_GET['x'].'-'.$_GET['y'].'.jpg';
		$cachefile = $path.'/'.$cachename;
		if(file_exists($cachefile)){	// cached
			header("Location: $url/$cachename");
		}else if(file_exists($path.'/fileinfo')){	// not cached
			$source = @file_get_contents($path.'/fileinfo');
			if(!file_exists($source)) exit;
			
			set_time_limit(120);
			$lockname = $path.'/lock'.$_GET['z'];
			
			if(file_exists($lockname) && (time() - fileatime($lockname) < 120)){
				for($t=0; $t<60; $t++){
					sleep(1);
					if(!file_exists($lockname)){
						header("Location: $url/$cachename");
						exit;
					}
				}
			}else{
				touch($lockname);
				chmod($lockname,0666); //should let user to delete
			}
			
			if (isset($_GET['m']) ){
				$maxZoom = $_GET['m'];
			}else{
				list($srcWidth, $srcHeight) = array_values(@getimagesize($source));
				$maxZoom = ceil(log10(max($srcWidth, $srcHeight)/256)/log10(2));
			}
			
			require_once(dirname(__FILE__).'/class/class.imagesplitter.php');
			
			$splitter = new ImageSplitter;
			if($_GET['z']==0) $splitter->centerMode = IMAGE_SPLITTER_CENTER_NORMAL;
			$splitter->outputType = IMAGETYPE_JPEG;
			$splitter->ratio = pow(0.5,  $maxZoom-$_GET['z']);
			$splitter->Load($source);
			$splitter->GetAllTiles($path, 'tile'.$_GET['z'].'-', '.jpg');
			$splitter->Free();
			header("Location: $url/$cachename");
			@unlink($lockname);
		}
	}
}

/*require_once 'class.imagesplitter.php';
set_time_limit(120);
$ttt = new ImageSplitter;
$ttt->ratio = 0.5;
$ttt->Load('tiger_1.jpg');
$ttt->GetTile(1, 1, false);
file_put_contents('./log.txt', memory_get_peak_usage()/1024/1024);
$ttt->free;
*/
?>