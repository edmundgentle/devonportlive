<?php
include('../includes/template.php');
$responses=array();
require_login();
if(is_logged_in()) {
	$query=Database::execute('SELECT name, email, password FROM users WHERE user_id=:user_id',array(':user_id'=>user_id()));
	if($query->rowCount()==1) {
		$user=$query->fetch();
		$template->title("Manage Your Account");
		$template->header();
		$changed=false;
		$name=trim(stripslashes($_POST['name']));
		if(strlen($name)>=3) {
			$user['name']=$name;
			$changed=true;
		}
		$email=trim(stripslashes($_POST['email']));
		if(preg_match('/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,4}$/',$email)) {
			if($email!=$userinfo['email']) {
				$query=Database::execute('SELECT user_id FROM users WHERE user_id!=:userid AND email=:email',array(':userid'=>user_id(),':email'=>$email));
				if($query->rowCount()==0) {
					$user['email']=$email;
					$changed=true;
				}
			}
		}
		$password=stripslashes($_POST['password']);
		if(strlen($password)>8) {
			$changed=true;
			$user['password']=crypt($password, "$2y$14$".DL_SALT);
		}
		if($changed) {
			$query=Database::execute('UPDATE users SET name=:name, email=:email, password=:password WHERE user_id=:user_id',array(':user_id'=>user_id(),':name'=>$user['name'],':email'=>$user['email'],':password'=>$user['password']));
			if($query->rowCount()!=1) {
				$changed=false;
			}
		}?>
		<h2>Manage Your Account</h2>
		<?
		if($changed) {?>
			<div class="alert success">Your changes were saved successfully.</div>
		<? }?>
		<form method="post" enctype="multipart/form-data">
			<div class="form_item">
				<div class="form_label">
					<label for="form_name">Name</label>
				</div>
				<div class="form_element">
					<input type="text" name="name" id="form_name" autocomplete="off" value="<? echo $user['name'];?>" />
				</div>
			</div>
			<div class="form_item">
				<div class="form_label">
					<label for="form_email">Email Address</label>
				</div>
				<div class="form_element">
					<input type="email" name="email" id="form_email" autocomplete="off" value="<? echo $user['email'];?>" />
				</div>
			</div>
			<div class="form_item">
				<div class="form_label">
					<label for="form_password">New password</label>
				</div>
				<div class="form_element">
					<input type="password" name="password" id="form_password" autocomplete="off" />
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
	}
}
?>