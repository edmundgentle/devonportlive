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
	apprise('Are you sure you want to delete this message?', {'verify':true}, function(r) {
		if(r) {
			$.ajax({
				type: "POST",
				url: "/ajax/admin_delete_message.php",
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
function reply_obj(id) {
	apprise('<textarea id="myMessage"></textarea>', {'verify':true,'textYes':'Send','textNo':'Cancel'}, function(r) {
		if(r) {
			var resp=$('#myMessage').val();
			$.ajax({
				type: "POST",
				url: "/ajax/admin_reply_message.php",
				data: {id: id, re:resp},
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
<h2>Manage Messages</h2>
<table class="table">
	<tr>
		<th>Name</th>
		<th>Message</th>
		<th>Date</th>
		<th>Actions</th>
	</tr>
<?php
$query=Database::execute('SELECT message_id, name, message, UNIX_TIMESTAMP(date) AS date, responded FROM contact ORDER BY responded ASC');
$results=$query->fetchAll();
foreach($results as $row) {?>
	<tr id="obj_<? echo $row['message_id'];?>">
		<td><? echo $row['name'];?></td>
		<td><? echo nl2br(htmlspecialchars($row['message']));?></td>
		<td><? echo date("jS M Y H:i",$row['date']);?></td>
		<td><? if(!$row['responded']) {echo"<a href=\"javascript:;\" onclick=\"reply_obj({$row['message_id']});\">Reply</a><br />";}?><a href="javascript:;" onclick="delete_obj(<? echo $row['message_id'];?>);">Delete</a></td>
	</tr>
<? }
?>
</table>
<?
$template->footer();
?>