<?php
include('../includes/template.php');
if(is_logged_in()) {
	$_SESSION = array();
	session_destroy();
	setcookie(session_name(),'',time()-300,'/','',0);
}
redirect('/');
?>