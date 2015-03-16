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
	apprise('Are you sure you want to delete this update?', {'verify':true}, function(r) {
		if(r) {
			$.ajax({
				type: "POST",
				url: "/ajax/admin_delete_update.php",
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
	apprise('Are you sure you want to approve this update?', {'verify':true}, function(r) {
		if(r) {
			$.ajax({
				type: "POST",
				url: "/ajax/admin_approve_update.php",
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
<h2>Manage Updates</h2>
<table class="table">
	<tr>
		<th>Message</th>
		<th>Date Posted</th>
		<th>Flagged</th>
		<th>Actions</th>
	</tr>
<?php
$query=Database::execute('SELECT update_id, flagged, message, UNIX_TIMESTAMP(date) AS date FROM updates ORDER BY date DESC');
$results=$query->fetchAll();
foreach($results as $row) {?>
	<tr id="obj_<? echo $row['update_id'];?>">
		<td><? echo htmlspecialchars($row['message']);?></td>
		<td><? echo date("jS M Y H:i",$row['date']);?></td>
		<td><? if($row['flagged']) {echo "Yes";}else{echo"No";}?></td>
		<td><? if($row['flagged']) {echo"<a href=\"javascript:;\" onclick=\"approve_obj({$row['update_id']});\">Approve</a><br />";}?><a href="javascript:;" onclick="delete_obj(<? echo $row['update_id'];?>);">Delete</a></td>
	</tr>
<? }
?>
</table>
<?
$template->footer();
?>