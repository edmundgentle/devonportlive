<?php
include('../includes/template.php');
$template->title("Admin Panel");
$template->header();
if(!$isadmin) {
	redirect('/');
}
?>
<h2>Admin Panel</h2>
<?php
if(isset($_POST['contact_email'])) {
	$file = fopen('admin_email.txt', "w");
	fwrite($file, stripslashes($_POST['contact_email']));
	fclose($file);?>
	<div class="alert success">The default contact email was updated successfully</div>
<? }
$allgood=true;
$query=Database::execute('SELECT message_id FROM contact WHERE responded=0');
if($query->rowCount()) {
	$allgood=false;?>
	<div class="alert info">There are <? echo $query->rowCount();?> messages that haven't been responded to.</div>
<? }
$query=Database::execute('SELECT page_id FROM pages WHERE approved=0');
if($query->rowCount()) {
	$allgood=false;?>
	<div class="alert info">There are <? echo $query->rowCount();?> unapproved pages.</div>
<? }
$query=Database::execute('SELECT update_id FROM updates WHERE flagged=1');
if($query->rowCount()) {
	$allgood=false;?>
	<div class="alert info">There are <? echo $query->rowCount();?> flagged updates which need reviewing.</div>
<? }
$query=Database::execute('SELECT event_id FROM events WHERE flagged=1');
if($query->rowCount()) {
	$allgood=false;?>
	<div class="alert info">There are <? echo $query->rowCount();?> flagged events which need reviewing.</div>
<? }
if($allgood) {?>
	<div class="alert success">Nothing needs reviewing!</div>
<? }
?>
<ul class="buttons">
	<li><a href="/admin/messages">Manage Messages</a></li>
	<li><a href="/admin/pages">Manage Pages</a></li>
	<li><a href="/admin/updates">Manage Updates</a></li>
	<li><a href="/admin/events">Manage Events</a></li>
	<li><a href="/admin/users">Manage Users</a></li>
</ul>
<h2>Default Contact Method</h2>
<form method="post">
	<div class="form_item">
		<div class="form_label">
			<label for="form_name">Contact Email</label>
		</div>
		<div class="form_element">
			<input type="text" name="contact_email" id="form_name" autocomplete="off" value="<? echo file_get_contents('admin_email.txt');?>" />
		</div>
	</div>
	<div class="form_item paragraph">
		<div class="form_element">
			<input type="submit" value="Save" />
		</div>
	</div>
</form>
<?
$template->footer();
?>