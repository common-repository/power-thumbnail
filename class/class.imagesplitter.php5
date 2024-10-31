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

/**
 * Split large images into small pieces
 *
 * Split large images into small equal-sized pieces that can speed up loading
 * It can be used to generating customed tile layers for Google Maps
 *
 * @author   Jiang Kuan <kuan.jiang@gmail.com>
 * @version  v1.1 2007/10/01
 * @package  ImageSplitter
 */
class ImageSplitter{
	/**
	 * Center mode, Default value: IMAGE_SPLITTER_CENTER_SQUARE
	 * @access public
	 */
	public $centerMode = IMAGE_SPLITTER_CENTER_SQUARE;
	
	/**
	 * Tile width. Default value: 256
	 * @var int
	 * @access public
	 */
	public $tileWidth = 256;
	
	/**
	 * Tile height. Default value: 256
	 * @var int
	 * @access public
	 */
	public $tileHeight = 256;
	
	/**
	 * Ratio, you can first resize the source image and then split it. Default value: 1
	 * @var float
	 * @access public
	 */
	public $ratio = 1;
	
	/**
	 * Output image type, available values are IMAGETYPE_PNG, IMAGETYPE_JPEG and IMAGETYPE_GIF. default value: IMAGETYPE_PNG
	 * @access public
	 */
	 public $outputType = IMAGETYPE_PNG;
	
	/**
	 * Source image filename
	 * @var string
	 * @access private
	 */
	private $src;
	
	/**
	 * Source image (resource)
	 * @var resource
	 * @access private
	 */
	private $srcImage;
	
	/**
	 * Source image width
	 * @var int
	 * @access private
	 */
	private $srcWidth;
	
	/**
	 * Source image height
	 * @var int
	 * @access private
	 */
	private $srcHeight;
	
	/**
	 * Source image type
	 * @var int
	 * @access private
	 */
	private $srcType;
	
	/**
	 * Source image mime type
	 * @var string
	 * @access private
	 */
	private $srcMimeType;
	
	/**
	 * Canvas width
	 * @var int
	 * @access private
	 */
	private $width;
	
	/**
	 * Canvas height
	 * @var int
	 * @access private
	 */
	private $height;
	
	private $realTileWidth;
	private $realTileHeight;
	
	private $offsetX = 0;
	private $offsetY = 0;
	
	private $countX = 0;
	private $countY = 0;
	
	private $startX = 0;
	private $startY = 0;
	
	
	/**
	 * Get class infomation
	 * @access public
	 * @param string|null $key
	 * @return string|array if $key is null, class information will be returned as an array
	 */
	public static function Info($key = null){
		$info = array(
			'ClassName' => 'ImageSpliter',
			'Version' => '1.1',
			'Author' => 'Jiang Kuan',
			'ReleaseData' => '2007-10-01',
		);
		if($key) return $info[$key];
		return $info;
	}
	
	/**
	 * Load a source image
	 * @access public
	 * @param string|resources $src If $src is a string, it will be treated as an filename; if $src is resource type, it will be load directly
	 * @return bool whether the source image is load successfully
	 */
	public function Load($src){
		if(is_null($src)) return false;
		
		
		if(is_file($src)){
			$this->src = $src;
			
			$info = getimagesize($src);
			if(!$info) return false;
			
			list($this->srcWidth, $this->srcHeight, $this->srcType, $tmp1, $tmp2, $this->srcMimeType) = array_values($info);
			
			$supported_format = array();
			$types = imagetypes();
			if($types & IMG_GIF) $supported_format[]=IMAGETYPE_GIF;
			if($types & IMG_JPG) $supported_format[]=IMAGETYPE_JPEG;
			if($types & IMG_PNG) $supported_format[]=IMAGETYPE_PNG;
			if($types & IMG_WBMP) $supported_format[]=IMAGETYPE_WBMP;
			if($types & IMG_XPM) $supported_format[]=IMAGETYPE_XBM;
			
			if(!in_array($this->srcType, $supported_format)) return false;
		}else if(is_resource($src)){
			$this->srcWidth = imagesx($src);
			$this->srcHeight = imagesy($src);
			if($this->srcWidth && $this->srcHeight && !$this->srcImage) $this->srcImage = $src;
			else return false;
		}
		
		$this->realTileWidth = round($this->tileWidth / $this->ratio);
		$this->realTileHeight = round($this->tileHeight / $this->ratio);
		
		switch($this->centerMode){
			case IMAGE_SPLITTER_CENTER_NONE:
				$this->width = round($this->ratio * $this->srcWidth);
				$this->height = round($this->ratio * $this->srcHeight);
				break;
			case IMAGE_SPLITTER_CENTER_NORMAL:
				$this->countX = ceil($this->srcWidth / $this->realTileWidth);
				$this->countY = ceil($this->srcHeight / $this->realTileHeight);
				$this->width = $this->countX * $this->tileWidth;
				$this->height = $this->countY * $this->tileHeight;
				$this->offsetX = round(($this->countX * $this->realTileWidth - $this->srcWidth) / 2);
				$this->offsetY = round(($this->countY * $this->realTileHeight - $this->srcHeight) / 2);
				break;
			case IMAGE_SPLITTER_CENTER_SQUARE:
				$this->countX = ceil($this->srcWidth / $this->realTileWidth / 2) * 2;
				$this->countY = ceil($this->srcHeight / $this->realTileHeight / 2) * 2;
				$this->width = $this->countX * $this->tileWidth;
				$this->height = $this->countY * $this->tileHeight;
				$this->offsetX = round(($this->countX * $this->realTileWidth - $this->srcWidth) / 2);
				$this->offsetY = round(($this->countY * $this->realTileHeight - $this->srcHeight) / 2);
				
				$diff = ($this->countX - $this->countY) / 2;
				
				
				if($diff>0){
					$this->startY = $diff;
				}else{
					$this->startX = - $diff;
				}
				break;
			default:
				return false;
		}
		
		return true;
	}
	
	/**
	 * Get a single tile
	 * @access public
	 * @param int $x If $src is a string, it will be treated as an filename; if $src is resource type, it will be load directly
	 * @param int $y If $src is a string, it will be treated as an filename; if $src is resource type, it will be load directly
	 * @param bool $output variable to set whether to output the tile to browser
	 * @param string $filename path to save the image, if it is false or null, the image is not saved on harddisk.
	 * @param bool $overwrite whether to overwrite the file.
	 * @return bool whether the tile is generated
	 */
	public function GetTile($x, $y, $output = false, $filename = false, $overwrite = false){
		if($filename && is_file($filename) && !$overwrite)
			return true;
			
		$x = (int) $x;
		$y = (int) $y;
		if($x<$this->startX || $y<$this->startY || ($this->startX + $this->countX)<=$x || ($this->startY + $this->countY)<=$y) return false;
		if(!$this->srcImage) $this->srcImage = imagecreatefromstring(file_get_contents($this->src));
		
		if(function_exists('imagecreatetruecolor')) $im = imagecreatetruecolor($this->tileWidth, $this->tileHeight);
		else $im = imagecreate($this->tileWidth, $this->tileHeight);
		if(!$im) return false;
		
		
		if($this->centerMode == IMAGE_SPLITTER_CENTER_NONE){
			$src_x = $x * $this->realTileWidth;
			$src_y = $y * $this->realTileHeight;
		}else{
			$src_x = ($x - $this->startX) * $this->realTileWidth - $this->offsetX;
			$src_y = ($y - $this->startY) * $this->realTileHeight - $this->offsetY;
		}
		
		if(!imagecopyresampled($im, $this->srcImage, 0, 0, $src_x, $src_y, $this->tileWidth, $this->tileHeight, $this->realTileWidth, $this->realTileHeight))
			return false;
		
		$ret1 = $ret2 = true;
		
		if($output){
			$ret1 = $this->__output($im);
		}
		
		if($filename){
			$ret2 = $this->__output($im, $filename);
		}
		
		$ret = $ret1 && $ret2 && imagedestroy($im);
		
		return $ret;
	}
	
	/**
	 * Get all tiles
	 * @access public
	 * @param string $path output path for the tiles
	 * @param string $prefix
	 * @param string $suffix
	 * @param string $splitter
	 */
	public function GetAllTiles($path, $prefix = 'tile', $suffix = '.png', $splitter = "-"){
		for($i=0; $i<$this->countX; $i++)
			for($j=0; $j<$this->countY; $j++){
				$m = $i + $this->startX;
				$n = $j + $this->startY;
				$this->GetTile($m, $n, false, "$path/$prefix$m$splitter$n$suffix");
			}
	}
	
	/**
	 * Release the source image's resource
	 * @access public
	 */
	public function Free(){
		if($this->srcImage) imagedestroy($this->srcImage);
	}
	
	/**
	 * Get all tiles
	 * @access private
	 * @param resource $res Image resource to be output
	 * @param string $dest Output filename, null to output to the browser directly
	 */
	private function __output($res, $dest = null){
		switch($this->outputType){
			case IMAGETYPE_GIF:
				return imagegif($res, $dest);
			case IMAGETYPE_JPEG:
				return imagejpeg($res, $dest);
			default:
				return imagepng($res, $dest);
		}
	}
	
	
	
}
?>