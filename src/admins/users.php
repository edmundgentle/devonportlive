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
	apprise('Are you sure you want to delete this user?', {'verify':true}, function(r) {
		if(r) {
			$.ajax({
				type: "POST",
				url: "/ajax/admin_delete_user.php",
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
function admin_obj(id) {
	apprise('Are you sure you want to make this user an admin?', {'verify':true}, function(r) {
		if(r) {
			$.ajax({
				type: "POST",
				url: "/ajax/admin_user_admin.php",
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
<h2>Manage Pages</h2>
<table class="table">
	<tr>
		<th>Name</th>
		<th>Email address</th>
		<th>Admin</th>
		<th>Actions</th>
	</tr>
<?php
$query=Database::execute('SELECT user_id, name, email, admin FROM users ORDER BY user_id DESC');
$results=$query->fetchAll();
foreach($results as $row) {?>
	<tr id="obj_<? echo $row['user_id'];?>">
		<td><? echo $row['name'];?></td>
		<td><? echo $row['email'];?></td>
		<td><? if($row['admin']) {echo "Yes";}else{echo"No";}?></td>
		<td><? if(!$row['admin']) {echo"<a href=\"javascript:;\" onclick=\"admin_obj({$row['user_id']});\">Make Admin</a><br />";}?><a href="javascript:;" onclick="delete_obj(<? echo $row['user_id'];?>);">Delete</a></td>
	</tr>
<? }
?>
</table>
<?
$template->footer();
?>