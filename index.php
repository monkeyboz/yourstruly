<?php 
	error_reporting(E_ALL);
	ini_set('display_errors', '1');
	
	include('classes/cms.php');
	
	$content = new CMS();
	if(isset($_GET['step'])){
		$function = 'step'.$_GET['step'];
		$content->{$function}();	
	} else {
		$content->stephomelayout();
	}
	
	if(isset($_GET['ajax'])){
		echo $content->content;	
	} else {
		include('content.php');
	}
?>