<?php
	//check if we are running within the DokuWiki environment
	if (!defined("DOKU_INC")){
	    die();
	}
	
	if (isset($_GET['embedded']))
		include('layout_embedded.php');
	else
		include('layout_complete.php');
?>