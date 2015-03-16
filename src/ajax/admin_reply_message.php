<?php
include('../includes/template.php');
$template->ajax_start();
if(is_admin() and isset($_POST['id']) and isset($_POST['re'])) {
	$id=(int)$_POST['id'];
	if($id) {
		$query=Database::execute('SELECT name, email, message FROM contact WHERE message_id=:update_id',array(':update_id'=>$id));
		if($query->rowCount()==1) {
			$msg=$query->fetch();
			$message=stripslashes($_POST['re'])."\n\n____________________________\nYour message:\n\n".$msg['message'];
			if(@mail("{$msg['name']} <{$msg['email']}>","Response from Devonport Live",$message,"From: Devonport Live <info@devonportlive.com>")) {
				$query=Database::execute('UPDATE contact SET responded=1 WHERE message_id=:page_id',array(':page_id'=>$id));
				if($query->rowCount()==1) {
					$output['success']=true;
				}
			}
		}
	}
}
$template->ajax_end();
?>