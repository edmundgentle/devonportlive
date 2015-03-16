<?php
include('../includes/template.php');
if(is_logged_in()) {
	redirect('/');
}
$responses=array();
if(isset($_POST['name']) and isset($_POST['email']) and isset($_POST['password1']) and isset($_POST['password2'])) {
	$name=trim(stripslashes($_POST['name']));
	if(strpos($name,' ')) {
		list($fn,$ln)=explode(' ',$name,2);
		if(strlen($fn)>1 and strlen($ln)>1) {
			if($fn!=$userinfo['first_name'] or $ln!=$userinfo['last_name']) {
				$first_name=$fn;
				$last_name=$ln;
				$changes=true;
			}
		}else{
			$responses[]='REGISTER_NAME_INVALID';
		}
	}else{
		$responses[]='REGISTER_NAME_FULLNAME';
	}
	$email=trim(stripslashes($_POST['email']));
	if(preg_match('/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,4}$/',$email)) {
		if($email!=$userinfo['email']) {
			$query=Database::execute('SELECT user_id FROM users WHERE email=:email',array(':email'=>$email));
			if($query->rowCount()==0) {
				$userinfo['email']=$email;
				$changes=true;
			}else{
				$responses[]='REGISTER_EMAIL_TAKEN';
			}
		}
	}else{
		$responses[]='REGISTER_EMAIL_INVALID';
	}
	$password=stripslashes($_POST['password1']);
	if(strlen($password)<8) {
		$responses[]='REGISTER_PASSWORD_INVALID';
	}
	if($_POST['password1']!=$_POST['password2']) {
		$responses[]='REGISTER_PASSWORD_MISMATCH';
	}
	if(!isset($_POST['terms']) or $_POST['terms']!='TRUE') {
		$responses[]='REGISTER_TERMS_INVALID';
	}
	if($email and $password and $first_name and $last_name and count($responses)==0) {
		$password_hash = crypt($password, "$2y$14$".DL_SALT);
		$active=generate_string(32);//random string
		$query=Database::execute('INSERT INTO users (name, email, password, active) VALUES (:name, :email, :password, :active) ',array(':name'=>$first_name.' '.$last_name, ':email'=>$email, ':password'=>$password_hash, ':active'=>$active));
		$id=Database::last_insert_id();
		if($id) {
			$_SESSION['user_id']=$id;
			redirect('/');
		}
	}
}
$template->title('Register');
$template->header();
?>
<script src="/jquery.password.min.js" type="text/javascript"></script>
<script type="text/javascript">
$(function() {
	$('#form_password1').password({
	    minLength:8,
	    allowSpace:false,
	    strengthIndicator:$('#pw_feedback'),
	    personalInformation:[$('#form_name'),$('#form_email')]
	});
});
</script>
<div class="modal">
	<h2>Register</h2>
	<div class="body">
		<?
		if(in_array('REGISTER_NAME_INVALID',$responses)) {?>
			<div class="alert error temperamental">Your first name and surname must each be at least one character long</div>
		<? }
		if(in_array('REGISTER_NAME_FULLNAME',$responses)) {?>
			<div class="alert error temperamental">You must enter your first name and surname</div>
		<? }
		if(in_array('REGISTER_EMAIL_TAKEN',$responses)) {?>
			<div class="alert error temperamental">The email address you entered is currently being used by a different account</div>
		<? }
		if(in_array('REGISTER_EMAIL_INVALID',$responses)) {?>
			<div class="alert error temperamental">The email address you entered was invalid</div>
		<? }
		if(in_array('REGISTER_PASSWORD_INVALID',$responses)) {?>
			<div class="alert error temperamental">The password you entered wasn't long enough</div>
		<? }
		if(in_array('REGISTER_PASSWORD_MISMATCH',$responses)) {?>
			<div class="alert error temperamental">The passwords you entered didn't match</div>
		<? }
		if(in_array('REGISTER_TERMS_INVALID',$responses)) {?>
			<div class="alert error temperamental">You must agree to the terms of service</div>
		<? }
		?>
		<form method="post" action="/register">
			<div class="form_item">
				<div class="form_label">
					<label for="form_name">Your name</label>
				</div>
				<div class="form_element">
					<input type="text" name="name" id="form_name" autocomplete="off" value="<? if(isset($_POST['name'])) {echo stripslashes($_POST['name']);}?>" />
				</div>
			</div>
			<div class="form_item">
				<div class="form_label">
					<label for="form_email">Email address</label>
				</div>
				<div class="form_element">
					<input type="email" name="email" id="form_email" autocomplete="off" value="<? if(isset($_POST['email'])) {echo stripslashes($_POST['email']);}?>" />
				</div>
			</div>
			<div class="form_item">
				<div class="form_label">
					<label for="form_password1">Password</label>
				</div>
				<div class="form_element">
					<input type="password" name="password1" id="form_password1" autocomplete="off" value="<? if(isset($_POST['password1'])) {echo stripslashes($_POST['password1']);}?>" />
					<p>Your password must be at least 8 characters long.</p>
					<div id="pw_feedback"></div>
				</div>
			</div>
			<div class="form_item">
				<div class="form_label">
					<label for="form_password2">Confirm password</label>
				</div>
				<div class="form_element">
					<input type="password" name="password2" id="form_password2" autocomplete="off" value="<? if(isset($_POST['password2'])) {echo stripslashes($_POST['password2']);}?>" />
				</div>
			</div>
			<div class="form_item">
				<div class="form_label"></div>
				<div class="form_element">
					<input type="checkbox" name="terms" value="TRUE" id="form_terms" /> <label for="form_terms">I agree to the <a href="/about/terms" target="_blank">terms of service</a></label>
				</div>
			</div>
			<div class="form_item paragraph">
				<div class="form_element">
					<input type="submit" value="Sign Up" />
				</div>
			</div>
		</form>
	</div>
</div>
<?php
$template->footer();
?>