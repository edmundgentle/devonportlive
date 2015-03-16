<?php
include('../includes/template.php');
$template->ajax_start();
$pics=array();
if(is_logged_in() and isset($_POST['id']) and isset($_POST['update'])) {
	$page_id=(int)$_POST['id'];
	$update=trim(stripslashes($_POST['update']));
	if(isset($_POST['pics']) and strlen($_POST['pics'])) {
		$pics=explode(',',$_POST['pics']);
		foreach($pics as $k=>$v) {
			$pics[$k]=(int)$v;
		}
	}
	if($page_id) {
		$query=Database::execute('SELECT page_id FROM pages WHERE page_id=:page_id AND user_id=:user_id',array(':page_id'=>$page_id,':user_id'=>user_id()));
		if($query->rowCount()==1) {
			$flagged=check_message($update);
			$query=Database::execute('INSERT INTO updates (page_id, flagged, message) VALUES (:page_id, :flagged, :message)',array(':page_id'=>$page_id,':flagged'=>$flagged,':message'=>$update));
			$id=Database::last_insert_id();
			if($id) {
				if($flagged) {
					alert_admin("An update has been flagged. This needs reviewing.");
				}
				$output['success']=true;
				$output['post_id']='update_'.$id;
				foreach($pics as $p) {
					Database::execute('INSERT INTO pictures (update_id, upload_id) VALUES (:update_id, :upload_id)',array(':update_id'=>$id,':upload_id'=>$p));
				}
			}
		}
	}
}
$template->ajax_end();
?>