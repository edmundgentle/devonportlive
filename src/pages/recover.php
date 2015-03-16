<?php
include('../includes/template.php');
if(is_logged_in()) {
	redirect('/');
}
if(isset($_GET['code'])) {
	$info=explode('*',mcrypt_decrypt(MCRYPT_RIJNDAEL_128, DL_SALT, base64_decode($_GET['code']), 'ecb'));
	$probs=false;
	if(count($info)==2) {
		$user_id=$info[1];
		$email=$info[0];
		if(isset($_POST['password'])) {
			$password=stripslashes($_POST['password']);
			if(strlen($password)>=8) {
				$password = crypt($password, "$2y$14$".DL_SALT);
				$query=Database::execute('UPDATE users SET password=:password WHERE user_id=:user_id',array(':user_id'=>$user_id,':password'=>$password));
				if($query->rowCount()==1) {
					$template->title('Reset Password');
					$template->header();?>
					<div class="modal">
						<h2>Reset Password</h2>
						<div class="body">
							<p>Your password has been reset successfully! <a href="/login">Log in</a>.</p>
						</div>
					</div>
					<? 
					$template->footer();
					exit();
				}
			}else{
				$probs=true;
			}
		}
		$template->title('Reset Password');
		$template->header();?>
		<div class="modal">
			<h2>Reset Password</h2>
			<div class="body">
				<?
				if($probs) {?>
					<div class="alert error temperamental">Your password should be at least 8 characters long.</div>
				<? }
				?>
				<p>To reset your password, enter a new password below. It must be at least 8 characters long.</p>
				<form method="post">
					<div class="form_item">
						<div class="form_label">
							<label for="form_password">New Password</label>
						</div>
						<div class="form_element">
							<input type="password" name="password" id="form_password" />
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
		<? 
		$template->footer();
	}else{
		redirect('/');
	}
}else{
	redirect('/');
}
?>