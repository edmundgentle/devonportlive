<?php
include('../includes/template.php');
$responses=array();
if(isset($_POST['name']) and isset($_POST['email']) and isset($_POST['message'])) {
	$name=trim(stripslashes($_POST['name']));
	$email=trim(stripslashes($_POST['email']));
	if(!preg_match('/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,4}$/',$email)) {
		$responses[]='EMAIL_INVALID';
		$email=null;
	}
	$message=stripslashes($_POST['message']);
	if($name and $email and $message) {
		$query=Database::execute('INSERT INTO contact (name, email, message) VALUES (:name, :email, :message) ',array(':name'=>$name, ':email'=>$email, ':message'=>$message));
		$id=Database::last_insert_id();
		if($id) {
			alert_admin("Someone has contacted Devonport Live. This needs responding to.");
			$responses[]='SUCCESS';
		}
	}
}
$template->title('Contact Us');
$template->header();
?>
<div class="modal">
	<h2>Contact Us</h2>
	<div class="body">
		<?php
		if(in_array('EMAIL_INVALID',$responses)) {?>
			<div class="alert error">The email address you entered was invalid. Please enter a valid email address.</div>
		<? }
		if(in_array('SUCCESS',$responses)) {?>
			<div class="alert success"><strong>Thanks for contacting us.</strong> We will respond to your message as soon as we can.</div>
		<? }
		?>
		<p>Want to contact Devonport Live? Just fill in this form and we'll get back to you as soon as possible.</p>
		<form method="post" action="/about/contact">
			<div class="form_item">
				<div class="form_label">
					<label for="form_name">Your name</label>
				</div>
				<div class="form_element">
					<input type="text" name="name" id="form_name" />
				</div>
			</div>
			<div class="form_item">
				<div class="form_label">
					<label for="form_email">Email address</label>
				</div>
				<div class="form_element">
					<input type="email" name="email" id="form_email" />
				</div>
			</div>
			<div class="form_item">
				<div class="form_label">
					<label for="form_message">Message</label>
				</div>
				<div class="form_element">
					<textarea name="message" id="form_message"></textarea>
				</div>
			</div>
			<div class="form_item paragraph">
				<div class="form_element">
					<input type="submit" value="Send" />
				</div>
			</div>
		</form>
	</div>
</div>
<?php
$template->footer();
?>