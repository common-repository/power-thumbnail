<?php

if (!function_exists('file_get_contents')){
	function file_get_contents($filename){
		$fhandle = fopen($filename, "r");
		$fcontents = fread($fhandle, filesize($filename));
		fclose($fhandle);
		return $fcontents;
	}
}

if(!function_exists('file_put_contents')){
	function file_put_contents($filename, $content, $flags = null, $resource_context = null) {
		// If $content is an array, convert it to a string
		if (is_array($content)) {
			$content = implode('', $content);
		}
		
		// If we don't have a string, throw an error
		if (!is_scalar($content)) {
			user_error('file_put_contents() The 2nd parameter should be either a string or an array',E_USER_WARNING);
			return false;
		}
		
		// Get the length of data to write
		$length = strlen($content);
		
		// Check what mode we are using
		$mode = ($flags & FILE_APPEND)?'a':'wb';
		
		// Check if we're using the include path
		$use_inc_path = ($flags & FILE_USE_INCLUDE_PATH)?true:false;
		
		// Open the file for writing
		if (($fh = @fopen($filename, $mode, $use_inc_path)) === false) {
			user_error('file_put_contents() failed to open stream: Permission denied',E_USER_WARNING);
			return false;
		}
		
		// Attempt to get an exclusive lock
		$use_lock = ($flags & LOCK_EX) ? true : false ;
		if ($use_lock === true) {
			if (!flock($fh, LOCK_EX)) {
				return false;
			}
		}
		
		// Write to the file
		$bytes = 0;
		if (($bytes = @fwrite($fh, $content)) === false) {
			$errormsg = sprintf('file_put_contents() Failed to write %d bytes to %s',$length,$filename);
			user_error($errormsg, E_USER_WARNING);
			return false;
		}
		
		// Close the handle
		@fclose($fh);
		
		// Check all the data was written
		if ($bytes != $length) {
			$errormsg = sprintf('file_put_contents() Only %d of %d bytes written, possibly out of free disk space.',$bytes,$length);
			user_error($errormsg, E_USER_WARNING);
			return false;
		}
		
		// Return length
		return $bytes;
	}
}




?>