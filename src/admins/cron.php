<?php
include('../includes/template.php');
require_once('../lib/Stripe.php');
Stripe::setApiKey($stripe['secret_key']);
$time=strtotime("+3 days");
$query=Database::execute('SELECT page_id, name, stripe_id, UNIX_TIMESTAMP(paid_until) AS until FROM pages WHERE type=\'business\' AND UNIX_TIMESTAMP(paid_until)<=:time',array(":time"=>$time));
$results=$query->fetchAll();
foreach($results as $row) {
	if($row['stripe_id']) {
		try {
			Stripe_Charge::create(array("amount" => 2000, "currency" => "gbp", "customer" => $row['stripe_id'], "description" => "Charge for ".$name));
			$row['until']=max(strtotime("+1 year",$row['until']),strtotime("+1 year"));
			$query=Database::execute('UPDATE pages SET paid_until=:paid_until WHERE page_id=:page_id',array(':paid_until'=>date("Y-m-d H:i:s",$row['until']),':page_id'=>$row['page_id']));
		}catch(Exception $ex) {
			
		}
	}
}
?>