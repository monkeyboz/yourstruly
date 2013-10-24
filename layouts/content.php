<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Untitled Document</title>
<script src="js/jquery-1.js" type="text/javascript"></script>
</head>

<body>
	<style>
		#nav a{
			background: #000;
			color: #fff;
			padding: 10px;
			margin-right: 5px;
			text-decoration: none;	
		}
		#nav{
			margin-bottom: 10px;	
		}
	</style>
	<div id="nav">
        <a href="">Home</a>
        <a href="">CMS</a>
    </div>
	<div>
    	<?php echo $content->content; ?>
    </div>
</body>
</html>
