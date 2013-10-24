<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>CSS Layout</title>
<script src="js/jquery-1.js" type="text/javascript"></script>
<script src="js/jQueryRotateCompressed.2.2.js" type="text/javascript"></script>
<link href='http://fonts.googleapis.com/css?family=Puritan' rel='stylesheet' type='text/css'>
</head>

<body>
	<style>
		#navigation a{
			background: #000;
			color: #fff;
			padding: 10px;
			margin-right: 5px;
			text-decoration: none;	
		}
		#navigation{
			margin-bottom: 10px;	
		}
		input, textarea{
			padding: 5px;
			border: #ababab 1px solid;
			border-radius: 10px;
		}
	</style>
    <?php echo $content->content; ?>
	<?php echo $content->debug; ?>
</body>
</html>
