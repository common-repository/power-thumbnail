<?php
###############################################################################
#
#   Class Name: ImageSplitter
#   Description: split large pictures into small pieces
#   Copyright (C) 2007 Jiang Kuan
#   
#   This program is free software: you can redistribute it and/or modify
#   it under the terms of the GNU General Public License as published by
#   the Free Software Foundation, either version 3 of the License, or
#   (at your option) any later version.
#   
#   This program is distributed in the hope that it will be useful,
#   but WITHOUT ANY WARRANTY; without even the implied warranty of
#   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#   GNU General Public License for more details.
#   
#   You should have received a copy of the GNU General Public License
#   along with this program.  If not, see <http://www.gnu.org/licenses/>.
#
###############################################################################


if (!function_exists('file_get_contents')){
	function file_get_contents($filename){
		$fhandle = fopen($filename, "r");
		$fcontents = fread($fhandle, filesize($filename));
		fclose($fhandle);
		return $fcontents;
	}
}

if (!extension_loaded('gd')) trigger_error('The class ImageSplitter requires GD library for PHP', E_USER_ERROR);

/**
 * Class Path
 * @const IMAGE_SPLITTER_CLASS_PATH
 */
if(!defined('IMAGE_SPLITTER_CLASS_PATH')) define('IMAGE_SPLITTER_CLASS_PATH', dirname(__FILE__));

/**
 * Center mode: none. Split directly without find the center
 * @const IMAGE_SPLITTER_CENTER_NONE
 */
if(!defined('IMAGE_SPLITTER_CENTER_NONE')) define('IMAGE_SPLITTER_CENTER_NONE', 0);

/**
 * Center mode: normal. Make a rectangular canvas which can be covered by integral number of the tiles, then put the source image in the center
 * @const IMAGE_SPLITTER_CENTER_NORMAL
 */
if(!defined('IMAGE_SPLITTER_CENTER_NORMAL')) define('IMAGE_SPLITTER_CENTER_NORMAL', 1);

/**
 * Center mode: square(default for the centerMode attribute). Make a square canvas which can be covered by integral number of the tiles, then put the source image in the center
 * @const IMAGE_SPLITTER_CENTER_SQUARE
 */
if(!defined('IMAGE_SPLITTER_CENTER_SQUARE')) define('IMAGE_SPLITTER_CENTER_SQUARE', 2);

if(version_compare(PHP_VERSION, '5.0.0', '>=')) include_once(IMAGE_SPLITTER_CLASS_PATH.'/class.imagesplitter.php5');
else if (version_compare(PHP_VERSION, '4.0.6', '>=')) include_once(IMAGE_SPLITTER_CLASS_PATH.'/class.imagesplitter.php4');
else trigger_error('The class ImageSplitter requires PHP 4.0.6 or above', E_USER_ERROR);



?>