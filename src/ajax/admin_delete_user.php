<?php
include('../includes/template.php');
$template->ajax_start();
if(is_admin() and isset($_POST['id'])) {
	$id=(int)$_POST['id'];
	if($id) {
		$query=Database::execute('DELETE FROM users WHERE user_id=:page_id',array(':page_id'=>$id));
		if($query->rowCount()==1) {
			$output['success']=true;
		}
	}
}
$template->ajax_end();
?>