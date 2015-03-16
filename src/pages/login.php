<?php
include('../includes/template.php');
if(is_logged_in()) {
	if(isset($_GET['r'])) {
		redirect($_GET['r']);
	}
	redirect('/');
}
$error=0;
if(isset($_POST['email']) and isset($_POST['password'])) {
	$email=trim(stripslashes($_POST['email']));
	$password=stripslashes($_POST['password']);
	if($email and $password) {
		$password_hash = crypt($password, "$2y$14$".DL_SALT);
		$query=Database::execute('SELECT user_id FROM users WHERE email=:email AND password=:password',array(':email'=>$email,':password'=>$password_hash));
		if($query->rowCount()==1) {
			$row=$query->fetch();
			$_SESSION['user_id']=$row['user_id'];
			if(isset($_GET['r'])) {
				redirect($_GET['r']);
			}
			redirect('/');
		}else{
			$error=1;
		}
	}else{
		$error=2;
	}
}
$template->title('Login');
$template->header();
?>
<div class="modal">
	<h2>Login</h2>
	<div class="body">
		<?php
		if($error==1) {?>
			<div class="alert error">Your email address or password were incorrect.</div>
		<? }elseif($error==2) {?>
			<div class="alert error">Your email address or password were invalid.</div>
		<? }
		?>
		<form method="post">
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
					<label for="form_password">Password</label>
				</div>
				<div class="form_element">
					<input type="password" name="password" id="form_password" />
				</div>
			</div>
			<div class="form_item paragraph">
				<div class="form_element">
					<input type="submit" value="Login" />
				</div>
			</div>
		</form>
		<div class="para">
			<p><strong>Not got an account?</strong> <a href="/register">Register now</a>!</p>
			<p><strong>Forgotten your password?</strong> <a href="/login/forgot">Recover it</a>.</p>
		</div>
	</div>
</div>
<?php
$template->footer();
?>