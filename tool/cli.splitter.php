#!/usr/bin/php -q
<?php
###############################################################################
#
#   Script Name: cli.splitter.php
#   Description: split large pictures into small pieces
#   Copyright (C) 2007 Jiang Kuan
#   
#   Usage: php -q ./cli.splitter.php [source image] [target folder]
#       [source image] -- image to be splitted
#       [target folder] -- path where you wish to store the splitted files
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


require_once(dirname(dirname(__FILE__)).'/class/class.imagesplitter.php');
$usage = 'Usage: php -q ./cli.splitter.php [source image] [target folder]
[source image] -- image to be splitted
[target folder] -- path where you wish to store the splitted files
';
if (count($argv)==1) exit($usage);
if(!is_file($argv[1])) exit("Invalide source file!\n$usage");
if(!is_dir($argv[2])) exit("Invalide output directory does not exist!\n$usage");

$path = $argv[2];

$res = imagecreatefromstring(file_get_contents($argv[1]));
$w = imagesx($res);
$h = imagesy($res);
$max_zoom = ceil(log10(max($w, $h)/256)/log10(2));

echo "\nThe image size is $w x $h, and Maximum Zoom level should be set to $max_zoom\n\nWell, now let's start to generating cache for \"{$argv[1]}\". Please wait for a while";

for($i=$max_zoom; $i>=0; $i--){
	echo '.';
	$splitter = new ImageSplitter;
	if($i==0) $splitter->centerMode = IMAGE_SPLITTER_CENTER_NORMAL;
	$splitter->outputType = IMAGETYPE_JPEG;
	$splitter->ratio = pow(0.5,  $max_zoom-$i);
	$splitter->Load($res);
	$splitter->GetAllTiles($path, "tile$i-", '.jpg');
}

echo "\n\nAll image pieces are successfully generated!\n";
imagedestroy($res);
?>