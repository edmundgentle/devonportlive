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
	apprise('Are you sure you want to delete this page?', {'verify':true}, function(r) {
		if(r) {
			$.ajax({
				type: "POST",
				url: "/ajax/admin_delete_page.php",
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
	apprise('Are you sure you want to approve this page?', {'verify':true}, function(r) {
		if(r) {
			$.ajax({
				type: "POST",
				url: "/ajax/admin_approve_page.php",
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
		<th>Page</th>
		<th>Paid Until</th>
		<th>Approved</th>
		<th>Actions</th>
	</tr>
<?php
$query=Database::execute('SELECT page_id, slug, name, type, UNIX_TIMESTAMP(paid_until) AS paid_until, approved FROM pages ORDER BY page_id DESC');
$results=$query->fetchAll();
foreach($results as $row) {?>
	<tr id="obj_<? echo $row['page_id'];?>">
		<td><a href="/<? echo $row['type'];?>/<? echo $row['slug'];?>" target="_blank"><? echo $row['name'];?></a></td>
		<td><? if($row['type']=='business') {echo date("jS M Y",$row['paid_until']);}else{echo "N/A";}?></td>
		<td><? if($row['approved']) {echo "Yes";}else{echo"No";}?></td>
		<td><? if(!$row['approved']) {echo"<a href=\"javascript:;\" onclick=\"approve_obj({$row['page_id']});\">Approve</a><br />";}?><a href="javascript:;" onclick="delete_obj(<? echo $row['page_id'];?>);">Delete</a></td>
	</tr>
<? }
?>
</table>
<?
$template->footer();
?>