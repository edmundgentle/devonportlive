<?php
include('../includes/template.php');
$template->ajax_start();
if(isset($_POST['id'])) {
	list($type, $id)=explode('_',$_POST['id']);
	if($type and $id) {
		if($type=='update') {
			$query=Database::execute('UPDATE updates SET flagged=1 WHERE update_id=:update_id',array(':update_id'=>$id));
			if($query->rowCount()==1) {
				alert_admin("An update has been flagged. This needs reviewing.");
				$output['success']=true;
			}
		}
		if($type=='event') {
			$query=Database::execute('UPDATE events SET flagged=1 WHERE event_id=:event_id',array(':event_id'=>$id));
			if($query->rowCount()==1) {
				alert_admin("An event has been flagged. This needs reviewing.");
				$output['success']=true;
			}
		}
	}
}
$template->ajax_end();
?>