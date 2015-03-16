<?php
include('../includes/template.php');
$template->title("Admin Panel");
$template->header();
if(!$isadmin) {
	redirect('/');
}
?>
<script>
function delete_obj(id) {
	apprise('Are you sure you want to delete this event?', {'verify':true}, function(r) {
		if(r) {
			$.ajax({
				type: "POST",
				url: "/ajax/admin_delete_event.php",
				data: {id: id},
				dataType: 'json'
			}).done(function(data) {
				if(data.success!==undefined && data.success) {
					$('#obj_'+id).remove();
				}
			});
		}
	});
}
function approve_obj(id) {
	apprise('Are you sure you want to approve this event?', {'verify':true}, function(r) {
		if(r) {
			$.ajax({
				type: "POST",
				url: "/ajax/admin_approve_event.php",
				data: {id: id},
				dataType: 'json'
			}).done(function(data) {
				if(data.success!==undefined && data.success) {
					location.reload(true);
				}
			});
		}
	});
}
</script>
<h2>Manage Events</h2>
<table class="table">
	<tr>
		<th>Event Name</th>
		<th>Event Date</th>
		<th>Flagged</th>
		<th>Actions</th>
	</tr>
<?php
$query=Database::execute('SELECT event_id, name, slug, flagged, UNIX_TIMESTAMP(start_time) AS start_time FROM events WHERE start_time>=NOW() OR recurring_dow IS NOT NULL ORDER BY event_id DESC');
$results=$query->fetchAll();
foreach($results as $row) {?>
	<tr id="obj_<? echo $row['event_id'];?>">
		<td><a href="/events/<? echo $row['slug'];?>"><? echo $row['name'];?></a></td>
		<td><? echo date("jS M Y H:i",$row['start_time']);?></td>
		<td><? if($row['flagged']) {echo "Yes";}else{echo"No";}?></td>
		<td><? if($row['flagged']) {echo"<a href=\"javascript:;\" onclick=\"approve_obj({$row['event_id']});\">Approve</a><br />";}?><a href="javascript:;" onclick="delete_obj(<? echo $row['event_id'];?>);">Delete</a></td>
	</tr>
<? }
?>
</table>
<?
$template->footer();
?>