<?php
switch(POWER_THUMBNAIL_OLD_VERSION){
	case '1.0b1':
		require_once(dirname(__FILE__).'/upgrade.1.0b1-1.0.php');
		break;
	default:
		break;
}

?>