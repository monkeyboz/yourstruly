<?php
	class CMS{
		var $layoutDir;
		var $uploadDir;
		var $content;
		var $something;
		var $postInfo;
		
		var $debug;
		
		var $host = 'localhost';
		var $username = 'testing';
		var $password = 'testing';
		var $database = 'yourstruly';
		var $connect;
		
		public function __construct(){
			$this->uploadDir = getcwd().'/uploads';
			$this->layoutDir = getcwd().'/layouts';
		}
			
		public function checkLogin(){
			if(!isset($_COOKIE['ytpp_username']) && !isset($_COOKIE['ytpp_email']) && !isset($_COOKIE['ytpp_user_id'])) header('Location: ?step=login');
			$query = $this->query('SELECT COUNT(*) FROM users WHERE username="'.$_COOKIE['ytpp_username'].'" AND email="'.$_COOKIE['ytpp_email'].'"');
			if(sizeof($query) < 1){
				header('Location: ?step=login');
			}
			return true;
		}
		
		public function conn(){
			$this->connect = mysql_connect($this->host, $this->username, $this->password);
			mysql_select_db($this->database);	
		}
		
		public function disc(){
			mysql_close($this->connect);
		}
		
		public function save($table, $array){
			$query = 'INSERT INTO '.$table;
			$columns = '';
			$values = '';
			
			foreach($array as $k=>$r){
				$columns .= $k.',';
				if(is_numeric($r)){
					$values .= $r.',';
				} else {
					$values .= '"'.mysql_real_escape_string($r).'",';
				}
			}
			
			$values = substr($values, 0,-1);
			$columns = substr($columns, 0,-1);
			$query .= '('.$columns.') VALUES('.$values.');';
			
			$this->conn();
			mysql_query($query);
			$this->debug = mysql_error();
			$this->disc();
		}
		
		public function update($table, $array, $whereArray){
			$query = 'UPDATE '.$table.' SET ';
			$columns = '';
			$values = '';
			
			foreach($array as $k=>$r){
				$values .= $k.'=';
				if(is_numeric($r)){
					$values .= $r.',';
				} else {
					$values .= '"'.mysql_real_escape_string($r).'",';
				}
			}
			
			$whereHolder = '';
			foreach($whereArray as $k=>$w){
				$whereHolder .= $k.'=';
				if(is_numeric($w)){
					$whereHolder .= $w.',';
				} else {
					$whereHolder .= '"'.mysql_real_escape_string($w).'",';
				}
				$whereHolder .= ' AND';
			}
			
			$whereHolder = substr($whereHolder, 0, -5);
			
			$where = 'WHERE '.$whereHolder;
			$values = substr($values, 0,-1);
			$columns = substr($columns, 0,-1);
			$query .= $values.' '.$where.';';
			
			$this->conn();
			mysql_query($query);
			$this->debug = mysql_error().$query;
			$this->disc();
		}
		
		public function query($string){
			$this->conn();
			$mysql = mysql_query($string);
			
			if(mysql_error()){
				$this->debug .= mysql_error().'</br>';
				$this->debug .= $string.'</br>';
			}
			
			$content = array();
			while($array = @mysql_fetch_assoc($mysql)){
				$content[] = $array;
			}
			
			$this->disc();
			return $content;
		}
		
		public function processPost(){
			if($_POST && sizeof($_POST) > 0){
				foreach($_POST as $k=>$p){
					if($p != '' && $k != 'submit'){	
						$this->postInfo[$k] = $p;
					} else {
						if($k != 'submit'){
							$this->postInfo['fail'][$k] = str_replace('_', ' ', ucfirst($k)).' empty';
							$this->postInfo[$k] = null;
						}
					}
				}
				foreach($_FILES as $k=>$f){
					$this->postInfo['files'][$k] = $f;
				}
				
				if(isset($this->postInfo['fail'])){
					return false;
				} else {
					return true;
				}
			}
			return false;
		}
		
		private function checkErrors(&$content, $postArray){
			foreach($postArray as $k=>$p){
				if($p == ''){
					$content = str_replace('[error_'.$k.']', 'Please Fill In '.$k, $content);
				}
			}
		}
		
		public function stepForgotPass(){
			$content = $this->getLayout('/main/container');
			if($this->processPost() && sizeof($this->postInfo['fail']) < 1){
				$info = $this->query('SELECT * FROM users WHERE username="'.$this->postInfo['username'].'"');
				if(sizeof($info) > 0){
					$headers = "From: forgot@".$_SERVER['HTTP_REFERER']."\r\n";
					$headers .= "Reply-To: forgot@".$_SERVER['HTTP_REFERER'] . "\r\n";
					$headers .= "MIME-Version: 1.0\r\n";
					$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
				
					$info = $info[0];
					$hash = md5($info['username'].date('Y-m-d').rand(100,1000000000));
					
					$saveArray = array('user_id'=>$info['user_id'], 'log_type'=>'resetPass', 'data'=>$hash);
					
					$this->save('logs', $saveArray);
					$message = $this->getLayout('/cms/emails/forgot');
					
					$message = str_replace('[email]', $info['email'], $message);
					$message = str_replace('[hash]', $hash, $message);
					$message = str_replace('[username]', $info['username'], $message);
					
					mail($info['email'], 'Forgot Password', $message, $headers);
					$content = str_replace('[contents]', 'Your login credentials have been sent.  Please check your email', $content);
					$this->content = $content;
				} else {
					$layout = $this->getLayout('/cms/forgotpass');
					$content = str_replace('[contents]', $layout, $content);
					$this->content = $content;
				}
			} else {
				$layout = $this->getLayout('/cms/forgotpass');
				$content = str_replace('[contents]', $layout, $content);
				$this->content = $content;
			}
		}
		
		public function stepresetPass(){
			$info = $this->query('SELECT * FROM logs WHERE data="'.$_GET['id'].'"');
			$info = $info[0];
			$user = $this->query('SELECT * FROM users WHERE user_id='.$info['user_id']);
			
			if($this->processPost()){
				if($this->postInfo['new_password'] != $this->postInfo['re_new_password']){
					$content = $this->getLayout('/main/container');
					$layout = $this->getLayout('/cms/resetPass');
					$layout = str_replace('[error_passwords]', 'passwords do not match', $layout);
					$layout = preg_replace('/\[error_(\w+)\]/is', '', $layout);
					$this->content = str_replace('[contents]', $layout, $content);
				} else {
					$resetArray = array('password'=>md5($this->postInfo['new_password']));
					$this->update('users', $resetArray, array('user_id'=>$info['user_id']));
					
					$content = $this->getLayout('/main/contaner');
					$layout = $this->getLayout('/cms/resetSuccessful');
					$this->checkErrors($layout, $this->postInfo);
					$this->content = str_replace('[contents]', $layout, $content);
				}
			} elseif(sizeof($info)) {
				$content = $this->getLayout('/main/container');
				$layout = $this->getLayout('/cms/resetPass');
				$layout = preg_replace('/\[error_(\w+)\]/is', '', $layout);
				$this->content = str_replace('[contents]', $layout, $content);
			} else {
				header('Location: ?step=signup');
			}
		}
		
		public function stephomeLayout(){
			$this->content = $this->getLayout('/cms/main');
		}
		
		public function stepsignup(){
			$content = '';
			if($this->processPost()){
				foreach($this->postInfo as $p){
					$this->save($this->postInfo['step1']);
				}
			} else {
				$content = $this->getLayout('/main/container');
				$signup = $this->getLayout('/cms/signup');
				$content = str_replace('[contents]', $signup, $content);
			}
			$this->content = $content;	
		}
		
		public function steplogin(){
			$content = $this->getLayout('/main/container');
			if($this->processPost() && !isset($this->postInfo['fail'])){
				$this->info = $this->query('SELECT * FROM users WHERE username="'.$this->postInfo['username'].'" AND password="'.md5($this->postInfo['password']).'"');
				if(sizeof($this->info)){
					setcookie('ytpp_username', $this->info[0]['username']);
					setcookie('ytpp_email', $this->info[0]['email']);
					setcookie('ytpp_user_id', $this->info[0]['user_id']);
					setcookie('ytpp_hash', md5($this->info[0].$this->info[0]['email'].date('Y-m-d')));
					
					header('Location: ?step=1');
				} else {
					$layout = $this->getLayout('/cms/login');
					$this->info = $this->postInfo;
					
					foreach($this->info as $k=>$i){
						$this->putValues($k, $layout, $i, 'value');
					}
					
					$layout = preg_replace('/\[error_(\w+)\]/is', '', $layout);
					if(isset($this->postInfo['fail'])){
						foreach($this->postInfo['fail'] as $k=>$p){
							$this->putValues($k, $layout, $p, 'error');
						}
					}
					$this->error = 'Username and Password do not match our records. <a href="?step=signup">Sign-up</a> or did you <a href="?step=ForgotPass">Forget Password</a>';
					$layout = str_replace('[errors]', $this->error, $layout);
					$this->content = str_replace('[contents]', $layout, $content);
				}
			} else {
				$layout = $this->getLayout('/cms/login');
				
				if(isset($this->postInfo['fail'])){
					foreach($this->postInfo as $k=>$p){
						if($k == 'fail'){
							foreach($p as $e=>$g){
								$this->putValues($e, $layout, $g, 'error');
							}
						} else {
							$this->putValues($k, $layout, $p, 'value');
						}
					}
				} else {
					$layout = preg_replace('/\[error_(\w+)\]/is', '', $layout);
				}
				$layout = str_replace('[errors]', '', $layout);
				$layout = preg_replace('/\[value_(\w+)\]/is', '', $layout);
				$layout = preg_replace('/\[error(\w+)\]/is', '', $layout);
				$this->content = str_replace('[contents]', $layout, $content);
			}
		}
		
		public function putValues($key, &$content, $value, $type){
			if(!is_array($value)){
				$content = str_replace('['.$type.'_'.$key.']', $value, $content);
			}
		}
		
		public function step1(){
			$h = opendir(getcwd().'/layouts');
			$layouts = array();
			
			$this->checkLogin();
			
			while($content = readdir($h)){
				 if(preg_match('/layout/is', $content)){
					 $layouts[] = substr($content, 0, -5);
				 }
			}
			
			$content = $this->getLayout('/cms/step1');
			
			$testing = '';
			foreach($layouts as $l){
				$testing .= '<a href="?step=2&layout='.$l.'" ref="'.$l.'" style="background: url(images/'.$l.'_thumbnail.jpg);"></a>';
			}
			$content = str_replace('[layouts]', $testing, $content);
			
			$this->content = $content;
		}
		
		public function stepcheckout(){
			setcookie('ytpp_username', $_POST['step1']['username']);
			setcookie('ytpp_user_id', $_POST['step1']['username']);
			setcookie('ytpp_password', $_POST['step1']['password']);
			setcookie('ytpp_email', $_POST['step1']['email']);
			include(getcwd().'/classes/checkout.php');
		}
		
		public function stepthankyou(){
			$this->conn();
			mysql_query('INSERT INTO users (username, password, pp_id) VALUES("'.$_COOKIE['ytpp_username'].'", "'.$_COOKIE['ytpp_password'].'", "'.$_REQUEST['PayerID'].'")');
			$this->disc();
			$this->content = 'Thank you for your order, your confirmation email will be sent shortly along with your login information';
			$this->content .= '<script>window.opener.location.href = "?step=1"; window.close();</script>';
			return $this->content;
		}
		
		public function stepmobileView(){
			$url = $_GET['url'];
			$this->content = '<script src="js/iphone.js" type="text/javascript"></script>';
			$this->content .= '<link rel="stylesheet" href="css/step1.css" type="text/css" media="screen">';
			$this->content .= '<div id="iphoneNav"><a href="" id="rotate">Rotate</a></div><div class="iframeHolder">' .
							'<iframe id="iphoneIframe" src="'.$url.'"></iframe></div>' .
							'<div style="text-align: center;"><div class="iphone"></div><div>';
		}
		
		public function step2(){
			$content = '';
			$layoutId = str_replace('layout', '', $_GET['layout']);
			
			$layout = $this->getLayout('/cms/sections');
			
			$navigation = $this->getLayoutMenu($layoutId);
			
			$layout = str_replace('[layouts]', $layoutId, $layout);
			$layout = str_replace('[layout]', $layoutId, $layout);
			$layout = str_replace('[sections]', $navigation, $layout);
			$layout = str_replace('[edit_sections]', '', $layout);
			$layout = str_replace('[section_selector]', '', $layout);
			$this->content = $layout;
		}
		
		private function getLayoutMenu($layoutId){
			$layoutId = (isset($_GET['layout']))?$_GET['layout']:$layoutId;
			$layoutId = str_replace('layout', '', $layoutId);
			
			$layout = $this->getLayout('/cms/sections');
			
			$h = opendir($this->layoutDir.'/parts/layout'.$layoutId);
			$navigation = '';
			$navLayout = array();
			while($content = readdir($h)){
				if(preg_match('/(.+?).html/is', $content)){
					$navLayout[] = $content;
				}
			}
			sort($navLayout);
			foreach($navLayout as $content){
				$content = substr($content, 0, -5);
				$number = substr($content, 0, -1);
				$navigation .= '<a href="?step=sections&section='.$content.'" ref="'.$content.'" style="background: url(layouts/parts/layout'.$layoutId.'/'.$content.'_thumbnail.png) no-repeat center; font-size: 20px; text-align: center; line-height: 112px;"></a>';
			}
			
			return $navigation;
		}
		
		public function stepeditLayout($id=''){
			$id = (isset($_GET['id']))?$_GET['id']:$id;
			$layoutDb = $this->query('SELECT * FROM layouts WHERE layout_id='.$id);
			$json = json_decode($layoutDb[0]['data']);
			
			$layout = $this->getLayout('/cms/sections');
			$content = '';
			
			$navigation = $this->getLayoutMenu($json->{'layout'});
			
			$sectionLayout = '';
			foreach($json->{'title'} as $b=>$j){
				$sectionLayout .= '<script>$(document).ready(function(){$("#sectionSelector a").each(function(){ if($(this).attr("ref") == "'.$b.'"){ $(this).data("deselect", "true"); } }) });</script>';
				$content .= '<div class="sectionHolder" id="'.$b.'">'.$this->stepsections($json->{'layout'}, $b).'</div>';
				foreach($json->{'section'} as $k=>$p){
					foreach($p as $a=>$v){
						$content = preg_replace('/name=\"section\['.$b.'\]\['.$a.'\]\"/is', 'name="section['.$b.']['.$a.']" value="'.$v.'"', $content);
					}
					$content = preg_replace('/name=\"title\['.$k.'\]\"/is', 'name="title['.$k.']" value="'.$j.'"', $content);
				}
			}
			$layout = str_replace('[edit_sections]', $content, $layout);
			$layout = str_replace('[layouts]', $json->{'layout'}, $layout);
			$layout = str_replace('[layout]', $json->{'layout'}, $layout);
			$layout = str_replace('[sections]', $navigation, $layout);
			$layout = str_replace('[section_selector]', $sectionLayout, $layout);
			$this->content = $layout;
		}
		
		private function rrmdir($dir) {
			foreach(glob($dir . '/*') as $file) {
				if(is_dir($file))
					$this->rrmdir($file);
				else
					unlink($file);
			}
			rmdir($dir);
		}
		
		public function stepdeleteLayout($id=null){
			$id = (isset($_GET['id']))?$_GET['id']:$id;
			$layout = $this->query('DELETE FROM layouts WHERE layout_id='.$id);
			
			$checkUserDir = $this->uploadDir.'/'.$_COOKIE['ytpp_username'].'/'.$id;
			
			$this->rrmdir($checkUserDir);
			
			header('Location: ?step=home');
		}
		
		public function stephome(){
			$home = $this->getLayout('/main/home');
			
			$this->checkLogin();
			
			$user = $this->query('SELECT * FROM users WHERE username="'.$_COOKIE['ytpp_username'].'"');
			$revisiondb = $this->query('SELECT * FROM layouts WHERE user_id='.$user[0]['user_id'].' ORDER BY date DESC');
			
			$revisions = '<table style="width: 100%" cellpadding="5">';
			$revisions .= '<tr style="background: #000; color: #fff;"><td>Layout Id</td><td>Date</td><td>Action</td></tr>';
			foreach($revisiondb as $k=>$r){
				$odd = '';
				if($k%2 == 0){
					$odd = 'class="odd"';
				}
				$revisions .= '<tr '.$odd.'><td>'.$r['layout_id'].'</td><td>'.$r['date'].'</td><td class="actions"><a href="?step=editLayout&id='.$r['layout_id'].'">Edit</a><a href="?step=deleteLayout&id='.$r['layout_id'].'" class="delete">Delete</a></td></tr>';
			}
			$revisions .= '</table>';
			
			$h = opendir($this->layoutDir.'/samples');
			$layout = '';
			while($content = readdir($h)){
				if(preg_match('/(\w+).html/is', $content)){
					$content = preg_replace('/(\w+).html/is', '$1', $content);
					$layout .= '<div class="content"><a href="?step=2&layout='.$content.'">'.$content.'</a></div>';
				}
			}
			$newLayouts = '';
			
			$home = str_replace('[revisions]', $revisions, $home);
			$home = str_replace('[new_layouts]', $layout, $home);
			
			$content = $this->getLayout('/main/container');
			$this->content = str_replace('[contents]', $content, $home);
		}
		
		public function stepsections($layout = '', $section=''){
			$section = (isset($_GET['section']))?$_GET['section']:$section;
			$layout = (isset($_GET['layout']))?$_GET['layout']:$layout;
			$content = $this->getLayout('/parts/layout'.$layout.'/'.$section);
			preg_match_all('/\[nav_(.+?)\]/is', $content, $getTemplate);
				
			$content = '';
			foreach($getTemplate as $k=>$g){
				$content = $this->getForm($section, $g);
			}
			
			$this->content = $content;
			return $this->content;
		}
		
		public function stepgetSlider(){
			$form = $this->getLayout('/cms/sliderContent');
			preg_match_all('/\[nav_(.+?)\]/is', $form, $getTemplate);
			$_GET['layout'] = 'slider';
			
			$formHolder = '';
			foreach($getTemplate as $k=>$g){
				$formHolder = $this->getForm('slider', $g);
			}
			$this->content = $formHolder;
		}
		
		public function stepgetGallery(){
			$form = $this->getLayout('/cms/galleryContent');
			preg_match_all('/\[nav_(.+?)\]/is', $form, $getTemplate);
			$_GET['layout'] = 'gallery';
			
			$formHolder = '';
			foreach($getTemplate as $k=>$g){
				$formHolder = $this->getForm($_GET['gallery'], $g);
			}
			
			$this->content = $formHolder;
			return $this->content;
		}
		
		public function getFormLayout($l, $section){
			$content = '';
			if(preg_match('/header/is', $l) || preg_match('/head/is', $l)){
				$content .= '<div style="margin-bottom: 10px;"><label>'.$l.'</label><input name="section['.$section.']['.$l.']" type="text" style="font-size: 20px;"/></div>';
			} else if(preg_match('/sub/is', $l)) {
				$content .= '<div style="margin-bottom: 10px;"><label>'.$l.'</label><input name="section['.$section.']['.$l.']" type="text" style="font-size: 11px;"/></div>';
			} else if(preg_match('/description/is', $l)) {
				if($section != 'gallery' && $section != 'slider'){
					$content .= '<div style="margin-bottom: 10px;"><label>'.$l.'</label><textarea name="section['.$section.']['.$l.']"></textarea></div>';
				} else {
					$content .= '<div style="margin-bottom: 10px;"><label>'.$l.'</label><textarea name="section['.$section.']['.$l.']"></textarea></div>';
				}
			} else if(preg_match('/multi-image/', $l)) {
				$content .= '<div style="margin-bottom: 10px;"><label>'.$l.'</label><a href="" class="'.$l.'" ref="'.$l.'">+ Add Gallery</a><script>$(".'.$l.'").addGallery(); </script></div>';	
			} else if(preg_match('/image/is', $l)) {
				if($section != 'gallery' && $section != 'slider' && $section != 'gallery_multi-image'){
					$content .= '<div style="margin-bottom: 10px;"><label>'.$l.'</label><input name="section['.$section.']['.$l.'][]" type="file"/></div>';
				} else {
					$content .= '<div style="margin-bottom: 10px;"><label>'.$l.'</label><input name="section['.$section.']['.$l.'][]" type="file"/></div>';
				}
			} else if(preg_match('/youtube/is', $l)) {
				$content .= '<div style="margin-bottom: 10px;"><label>'.$l.'</label><input name="section['.$section.']['.$l.']" type="text"/></div>';	
			} else if(preg_match('/slider/is', $l)) {
				$content .= '<div style="margin-bottom: 10px;"><label>'.$l.'</label><a href="" class="addSlider">+ Add Slider</a></div>';
				$content .= '<script>$(".addSlider").addSlider(); testing</script>';
			} else {
				if($section != 'gallery' && $section != 'slider' && !preg_match('/multi-image/is', $section)){
					$content .= '<div style="margin-bottom: 10px;"><label>'.$l.'</label><input type="text" name="section['.$section.']['.$l.']"/></div>';
				} else {
					$content .= '<div style="margin-bottom: 10px;"><label>'.$l.'</label><input type="text" name="section['.$section.']['.$l.'][]"/></div>';
					$content .= '<a href="" class="removeGallery" ref="'.$l.'">+ Remove Gallery</a><script>$(".removeGallery").removeGallery(); </script>';
				}
			}
			return $content;
		}
		
		public function getForm($section, $layout){
			$content = '';
			if($section != 'slider' && $section != 'gallery' && !preg_match('/multi-image/is', $section)){
				$content = '<div style="margin-bottom: 10px;"><label>'.$section.' Nav Title</label><input type="text" name="title['.$section.']" style="font-size: 20px;"/></div>';
				foreach($layout as $l){
					$content .= $this->getFormLayout($l, $section);
				}
			} else {
				foreach($layout as $l){
					$content .= $this->getFormLayout($l, $section);
				}
			}
			$contentHolder = '<div class="formHolder" style="width: 500px; float: left;">'.$content.'</div><div class="sectionImageHolder" style="width: 100px; float: left;"><img src="layouts/parts/layout'.$_GET['layout'].'/'.$section.'.jpg" /></div><div style="clear: both;"></div>';
			
			return $contentHolder;
		}
		
		public function stepdownload($url = '', $id = ''){
			$this->checkLogin();
			$id = (isset($_GET['id']))?$_GET['id']:$id;
			$directory = '';
			
			if($url != ''){
				$file = $this->uploadDir.'/'.$_COOKIE['ytpp_username'].'/'.$id.'/upload.zip';
				$directory = getcwd().'/'.$url;
			} else {
				$file = $this->uploadDir.'/'.$_COOKIE['ytpp_username'].'/'.$id.'/upload.zip';
				$directory = $this->uploadDir.'/'.$_COOKIE['ytpp_username'].'/'.$id.'/';
				$url = $directory;
			}
			
			if(!is_file($file)){
				$h = fopen($file, 'w');
				fclose($h);
			}
			
			$zip = new ZipArchive();
			if($zip->open($file, ZIPARCHIVE::CHECKCONS) == TRUE){
				$sourceHandle = opendir($url);
				
				$zip->addEmptyDir('css');
				$zip->addEmptyDir('images');
				$zip->addEmptyDir('js');
				
				$files = array();
				while($res = readdir($sourceHandle)){
					if($res == '.' || $res == '..')
						continue;
					
					if(is_dir($url.$res)){
						$users = str_replace($this->uploadDir.'/'.$_COOKIE['ytpp_username'].'/'.$id.'/', '', $url);
						$this->stepdownload($url.$res, $id);
					} else {
						if($res != 'upload.zip'){
							$users = str_replace($this->uploadDir.'/'.$_COOKIE['ytpp_username'].'/'.$id.'/', '', $url);
							$sourceDir = $url.$res;
							if($users != ''){
								$users = $users.'/';
								$sourceDir = $url.'/'.$res;
							}
							//echo str_replace('/var/www/yourstruly', '', $sourceDir) .' -- <br/>';
							//echo $res .' ++ </br>';
							$files[$sourceDir] = $users.$res;
						}
					}
				}
				
				foreach($files as $k=>$f){
					$zip = new ZipArchive();
					$zip->open($file);
				
					$zip->addFile($k, $f);
					$zip->close();
				}
				
				$this->downloadFile($file);
			}
		}
		
		public function downloadFile($file){
			if (file_exists($file)) {
				header('Content-Description: File Transfer');
				header('Content-Type: application/octet-stream');
				header('Content-Disposition: attachment; filename='.basename($file));
				header('Content-Transfer-Encoding: binary');
				header('Expires: 0');
				header('Cache-Control: must-revalidate');
				header('Pragma: public');
				header('Content-Length: ' . filesize($file));
				ob_clean();
				flush();
				readfile($file);
				exit;
			}
		}
		
		public function step3(){
			$content = '';
			$checkUserUploadDir = '';
			$userImageDir = '';
			
			if($this->processPost()){
				$this->conn();
				$json = json_encode($this->postInfo);
				mysql_query('INSERT INTO layouts (data, user_id) VALUES ("'.mysql_real_escape_string($json).'", '.$_COOKIE['ytpp_user_id'].')') or die(mysql_error());
				$id = mysql_insert_id();
			
				$layout = $this->getLayout('/layout'.$this->postInfo['layout']);
				
				$checkUserUploadDir = $this->uploadDir.'/'.$_COOKIE['ytpp_username'].'/'.$id;
				
				if(!is_dir($checkUserUploadDir)){
					mkdir($checkUserUploadDir, 0777, true);
					mkdir($checkUserUploadDir.'/images', 0777, true);
					mkdir($checkUserUploadDir.'/css', 0777, true);
					mkdir($checkUserUploadDir.'/js', 0777, true);
					
					$cssDir = getcwd().'/css/layout'.$this->postInfo['layout'];
					$jsDir = getcwd().'/js/layout'.$this->postInfo['layout'];
					
					$this->recursiveCopy($cssDir, $checkUserUploadDir.'/css');
					$this->recursiveCopy($jsDir, $checkUserUploadDir.'/js');
				}
				
				$userImageDir = $this->uploadDir.'/'.$_COOKIE['ytpp_username'].'/'.$id.'/images/';
				
				$navigation = '';
				$nav = array();
				if(isset($this->postInfo['title'])){
					foreach($this->postInfo['title'] as $k=>$p){
						$navigation .= '<a href="#'.$k.'" ref="'.$k.'">'.$p.'</a>';
						$nav[] = $k;
					}
					$layout = str_replace('[layout_navigation]', $navigation, $layout);
				}
				
				$slider = '<div class="slider" style="height: 300px;">';
				$sectionLayout = '';
				if(isset($this->postInfo['section']['slider'])){
					foreach($this->postInfo['section']['slider']['descript'] as $k=>$p){
						$fileName = $_FILES['section']['name']['slider']['image'][$k];
						
						if(!@mkdir($userImageDir, 0777, true)){  }
						
						move_uploaded_file($_FILES['section']['tmp_name']['slider']['image'][$k], $userImageDir.$fileName);
						
						$sliderLayout = $this->getLayout('/cms/sliderContent');
						$sliderLayout = str_replace('[nav_descript]', $p, $sliderLayout);
						
						$sliderLayout = str_replace('[nav_image]', $userImageDir.$fileName, $sliderLayout);
						$slider .= $sliderLayout;
					}
					$slider .= '</div>';
					$sectionLayout .= $slider;
				}
				
				$count = 0;
				if(isset($this->postInfo['section'])){
					foreach($this->postInfo['section'] as $k=>$p){
						$section = $this->getLayout('/parts/layout'.$this->postInfo['layout'].'/'.$k);
						
						foreach($p as $j=>$a){
							if(!is_array($a)){
								$section = str_replace('[nav_'.$j.']', $a, $section);
							} else {
								$string = '<ul>';
								foreach($a as $b){
									$string .= '<li>'.$b.'</li>';
								}
								$string .= '</ul>';
								$section = str_replace('[nav_'.$j.']', $string, $section);
							}
						}
						if(isset($nav[$count])){
							$sectionLayout .= str_replace('[anchor_link]', '<a name="'.$nav[$count].'"/></a>', $section);
						}
						++$count;
					}
					$layout = str_replace('[layout_sections]', $sectionLayout, $layout);
				}
				
				if(isset($_FILES['section'])){
					foreach($_FILES['section']['tmp_name'] as $k=>$f){
						$images = '';
						foreach($f as $a=>$b){
							if(!is_array($_FILES['section']['name'][$k][$a])){
								$name = $_FILES['section']['name'][$k][$a];
								$upload = 'uploads/';
								
								if(!@mkdir($userImageDir, 0777, true)){  }
								
								move_uploaded_file($_FILES['section']['tmp_name'][$k][$a], $userImageDir.$name);
								
								$layout = str_replace('[nav_'.$k.']', '<img src="images/'.$name.'" class="img" />', $layout);
							} else{
								foreach($_FILES['section']['name'][$k][$a] as $j=>$b){
									$name = $_FILES['section']['name'][$k][$a][$j];
									$upload = 'uploads/';
									
									if(!@mkdir($userImageDir, 0777, true)){  }
									
									move_uploaded_file($_FILES['section']['tmp_name'][$k][$a][$j], $userImageDir.$name);
									
									$images .= '<img src="images/'.$name.'" class="img" />';
								}
								$layout = str_replace('[nav_'.$k.']', $images, $layout);
							}
						}
						$layout = str_replace('[nav_'.$a.']', $images, $layout);
					}
				}
				$content = $layout;
				
				$h = fopen($checkUserUploadDir.'/index.html', 'w+');
				fwrite($h, $content);
				fclose($h);
				
				$this->disc();
			}
			
			$layoutHolder = $this->getLayout('/cms/step3');
			
			$userNavigation = $this->getLayout('/cms/user_navigation');
			$layoutHolder = str_replace('[user_navigation]', $userNavigation, $layoutHolder);
			$layoutHolder = str_replace('[layout]', str_replace(getcwd().'/', '', $checkUserUploadDir), $layoutHolder);
			
			$layoutHolder = str_replace('[layout_id]', $id, $layoutHolder);
			
			$this->content = $layoutHolder;
		}
		
		public function recursiveCopy($source, $dest){
			$sourceHandle = opendir($source);
		   
			while($res = readdir($sourceHandle)){
				if($res == '.' || $res == '..')
					continue;
			   
				if(is_dir($source . '/' . $res)){
					RecursiveCopy($source . '/' . $res, $dest);
				} else {
					copy($source . '/' . $res, $dest . '/' .$res);
				   
				}
			}
		} 
		
		public function step4(){
			$this->content;
		}
		
		public function getLayout($layout){
			$content = '';
			if(is_file($this->layoutDir.'/'.$layout.'.html')){
				$h = fopen($this->layoutDir.'/'.$layout.'.html', 'r');
				$content = fread($h, filesize($this->layoutDir.$layout.'.html'));
				fclose($h);
			}
			return $content;
		}
	}
?>
