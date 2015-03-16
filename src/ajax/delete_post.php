<?php
include('../includes/template.php');
$template->ajax_start();
if(is_logged_in() and isset($_POST['id'])) {
	list($type, $id)=explode('_',$_POST['id']);
	if($type and $id) {
		if($type=='update') {
			$query=Database::execute('SELECT updates.update_id FROM updates, pages WHERE update_id=:update_id AND updates.page_id=pages.page_id AND pages.user_id=:user_id',array(':update_id'=>$id,':user_id'=>user_id()));
			if($query->rowCount()==1) {
				$query=Database::execute('DELETE FROM updates WHERE update_id=:update_id',array(':update_id'=>$id));
				if($query->rowCount()==1) {
					$output['success']=true;
				}
			}
		}
	}
}
$template->ajax_end();
?>