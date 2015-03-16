<?php
include('../includes/template.php');
$template->ajax_start();
if(isset($_POST['id'])) {
	$id=$_POST['id'];
	$query=Database::execute('SELECT events.event_id, events.page_id, events.name, events.slug, start_time, end_time, location, events.description, flagged, pages.user_id, pages.slug, pages.user_id AS page_slug, pages.name AS page_name, type, picture, UNIX_TIMESTAMP(paid_until), approved, recurring_dow FROM events, pages WHERE events.event_id=:id AND events.page_id=pages.page_id',array(':id'=>$id));
	if($query->rowCount()==1) {
		$event=$query->fetch();
		if($event['user_id']==user_id()) {
			$query=Database::execute('DELETE FROM events WHERE event_id=:page_id',array(':page_id'=>$id));
			if($query->rowCount()==1) {
				$output['success']=true;
			}
		}
	}
}
$template->ajax_end();
?>