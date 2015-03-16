<?php
include('../includes/template.php');
if(is_logged_in()) {
	redirect('/');
}
$responses=array();
if(isset($_POST['email'])) {
	$email=trim(stripslashes($_POST['email']));
	if(preg_match('/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,4}$/',$email)) {
		if($email!=$userinfo['email']) {
			$query=Database::execute('SELECT user_id FROM users WHERE email=:email',array(':email'=>$email));
			if($query->rowCount()==1) {
				$row=$query->fetch();
				
				$data=$email.'*'.$row['user_id'];
				
				$encrypted_data=base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, DL_SALT, $data, 'ecb'));
				
				$url='https://www.devonportlive.com/recover?code='.urlencode($encrypted_data);
				
				@mail($email,"Recover password","To recover your password, click this link:\n\n$url","From: Devonport Live <info@devonportlive.com>");
				$template->title('Recovering Password');
				$template->header();?>
				<div class="modal">
					<h2>Recovering Password</h2>
					<div class="body">
						<p>An email has now been sent to you. Click the link in this email in order to reset your password.</p>
					</div>
				</div>
				<? 
				$template->footer();
				exit();
			}else{
				$responses[]='FORGOT_NOT_FOUND';
			}
		}
	}else{
		$responses[]='FORGOT_INVALID_EMAIL';
	}
}
$template->title('Forgotten Password');
$template->header();
?>
<div class="modal">
	<h2>Forgotten Password</h2>
	<div class="body">
		<?
		if(in_array('FORGOT_INVALID_EMAIL',$responses)) {?>
			<div class="alert error temperamental">The email address you entered wasn't valid</div>
		<? }
		if(in_array('FORGOT_NOT_FOUND',$responses)) {?>
			<div class="alert error temperamental">Your account was not found. Please check you entered your email address correctly.</div>
		<? }
		?>
		<p>Enter your email address to reset your password.</p>
		<form method="post">
			<div class="form_item">
				<div class="form_label">
					<label for="form_email">Email address</label>
				</div>
				<div class="form_element">
					<input type="email" name="email" id="form_email" autocomplete="off" value="<? if(isset($_POST['email'])) {echo stripslashes($_POST['email']);}?>" />
				</div>
			</div>
			<div class="form_item paragraph">
				<div class="form_element">
					<input type="submit" value="Reset Password" />
				</div>
			</div>
		</form>
	</div>
</div>
<?php
$template->footer();
?>