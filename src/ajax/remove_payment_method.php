<?php
include('../includes/template.php');
$template->ajax_start();
if(is_logged_in() and isset($_POST['id']) and isset($_POST['page_id'])) {
	$id=stripslashes($_POST['id']);
	$page_id=stripslashes($_POST['page_id']);
	$query=Database::execute('SELECT stripe_id FROM pages WHERE page_id=:page_id',array(':page_id'=>$page_id));
	if($query->rowCount()==1) {
		list($stripe_id)=$query->fetch();
		if($stripe_id) {
			require_once('../lib/Stripe.php');
			Stripe::setApiKey($stripe['secret_key']);
			$cu = Stripe_Customer::retrieve($stripe_id);
			if($cu) {
				$response=$cu->cards->retrieve($id)->delete();
				if($response->deleted) {
					$output['success']=true;
				}
			}
		}
	}
}
$template->ajax_end();
?>