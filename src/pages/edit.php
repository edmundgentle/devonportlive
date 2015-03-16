<?php
include('../includes/template.php');
include('../includes/upload.php');
require_login();
require_once('../lib/Stripe.php');
Stripe::setApiKey($stripe['secret_key']);
$responses=array();
if(isset($_GET['slug'])) {
	$slug=$_GET['slug'];
	$query=Database::execute('SELECT page_id, user_id, slug, name, type, website, twitter, address, lat, lon, phone, description, picture, UNIX_TIMESTAMP(paid_until) AS paid_until, approved, stripe_id FROM pages WHERE slug=:slug',array(':slug'=>$slug));
	if($query->rowCount()==1) {
		$page=$query->fetch();
		$page['link']='/'.$page['type'].'/'.$page['slug'];
		if($page['user_id']==user_id()) {
			$template->title("Manage ".$page['name']);
			$template->header();
			
			$changed=false;
			$name=trim(stripslashes($_POST['name']));
			if(strlen($name)>=3) {
				$page['name']=$name;
				$changed=true;
			}
			if(isset($_POST['website'])) {
				$website=trim(stripslashes($_POST['website']));
				if($website=='http://') {
					$website='';
				}
				if(strlen($website)>10) {
					$page['website']=$website;
					$changed=true;
				}
			}
			if(isset($_POST['address'])) {
				$address=trim(stripslashes($_POST['address']));
				if(strlen($address)>30) {
					$page['address']=$address;
					$changed=true;
					$string = str_replace (" ", "+", urlencode($address));
					$details_url = "http://maps.googleapis.com/maps/api/geocode/json?address=".$string."&sensor=false";
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, $details_url);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					$response = json_decode(curl_exec($ch), true);
					if ($response['status'] == 'OK') {
						$geometry = $response['results'][0]['geometry'];
						$page['lat'] = $geometry['location']['lat'];
						$page['lon'] = $geometry['location']['lng'];
					}
				}
			}
			if(isset($_POST['phone'])) {
				$phone=trim(stripslashes($_POST['phone']));
				if(strlen($phone)>=11) {
					$page['phone']=$phone;
					$changed=true;
				}
			}
			if(isset($_POST['description'])) {
				$description=trim(stripslashes($_POST['description']));
				if(strlen($description)>10) {
					$page['description']=$description;
					$changed=true;
				}
			}
			if(isset($_FILES['logo']) and !$_FILES["logo"]["error"]) {
				$id=upload_image($_FILES['logo'],user_id());
				if($id) {
					$page['picture']=$id;
					$changed=true;
				}
			}
			if($changed) {
				$query=Database::execute('UPDATE pages SET name=:name, website=:website, address=:address, lat=:lat, lon=:lon, phone=:phone, description=:description, picture=:picture WHERE page_id=:page_id',array(':page_id'=>$page['page_id'],':name'=>$page['name'],':website'=>$page['website'],':address'=>$page['address'],':lat'=>$page['lat'],':lon'=>$page['lon'],':phone'=>$page['phone'],':description'=>$page['description'],':picture'=>$page['picture']));
				if($query->rowCount()!=1) {
					$changed=false;
				}
			}
			if(isset($_POST['stripeToken'])) {
				$token=$_POST['stripeToken'];
				if(is_null($page['stripe_id']) or strlen($page['stripe_id'])==0 or !$page['stripe_id']) {
					try {
						$cus=Stripe_Customer::create(array(
						  "card" => $token
						));
						$page['stripe_id']=$cus->id;
						if(Database::execute('UPDATE pages SET stripe_id=:stripeid WHERE page_id=:page_id',array(':page_id'=>$page['page_id'],':stripeid'=>$page['stripe_id']))) {
							$responses[]='PAYMENTMETHODS_ADD_SUCCESS';
						}
					}catch(Stripe_CardError $ex) {
						$responses[]='PAYMENTMETHODS_ERROR_CARDNOTSUPPORTED';
					}
				}else{
					$cu = Stripe_Customer::retrieve($page['stripe_id']);
					try {
						$cu->cards->create(array("card"=>$token));
						$responses[]='PAYMENTMETHODS_ADD_SUCCESS';
					}catch(Stripe_CardError $ex) {
						$responses[]='PAYMENTMETHODS_ERROR_CARDNOTSUPPORTED';
					}
				}
			}
			$payment_added=0;
			if(!is_null($page['stripe_id']) and strlen($page['stripe_id'])>0 and $page['stripe_id']) {
				$cust=Stripe_Customer::retrieve($page['stripe_id']);
				$payment_added=$cust->cards->count;
			}
		?>
		<script type="text/javascript" src="https://js.stripe.com/v2/"></script>
		<script type="text/javascript">
		Stripe.setPublishableKey('<? echo $stripe['publishable_key'];?>');
		var page_id=<? echo $page['page_id'];?>;
		$(function() {
			$('#payment-form').submit(function(e) {
				var form = $(this);

				// Disable the submit button to prevent repeated clicks
				form.find('button').prop('disabled', true);

				Stripe.createToken(form, function(status, response) {
					var form = $('#payment-form');
					if (response.error) {
						// Show the errors on the form
						form.find('.payment-errors').html('<div class="alert error temperamental">'+response.error.message+'</div>');
						form.find('button').prop('disabled', false);
					} else {
						// token contains id, last4, and card type
						var token = response.id;
						// Insert the token into the form so it gets submitted to the server
						form.append($('<input type="hidden" name="stripeToken" />').val(token));
						// and re-submit
						form.get(0).submit();
					}
				});
				// Prevent the form from submitting with the default action
				return false;
			});
			$('.pm_remove').click(function(e) {
				e.preventDefault();
				var id=$(this).attr('id').substring(3);
				apprise('<div align="center"><strong>Are you sure you want to remove this payment method?</strong><br />This can\'t be undone.</div>', {'verify':true}, function(r) {
						if(r) {
							$.ajax({
								type: "POST",
								url: "/ajax/remove_payment_method.php",
								data: {id: id, page_id:page_id },
								dataType: 'json'
							}).done(function(data) {
								if(data.success!==undefined && data.success) {
									$('#pm_'+id).remove();
									$('#page_paymentmethods').prepend('<div class="alert success temperamental">Your card was removed successfully</div>');
								}
							});
						}
					});
			});
			$('.pm_default').click(function(e) {
				change_default_card(e, $(this));
			});
			$('#form_number').bind('keyup change keypress keydown paste',function(e) {
				if($('#form_number').val()!=stored_form_car_number_field) {
					$('#form_number').val(ccard_num_format($('#form_number').val()));
					stored_form_car_number_field=$('#form_number').val();
				}
			});
		});
		function change_default_card(e, t) {
			e.preventDefault();
			var id=t.attr('id').substring(3);
			apprise('<div align="center">Are you sure you want to change your default payment method?</div>', {'verify':true}, function(r) {
				if(r) {
					$.ajax({
						type: "POST",
						url: "/ajax/set_default_payment_method.php",
						data: {id: id, page_id:page_id },
						dataType: 'json'
					}).done(function(data) {
						if(data.success!==undefined && data.success) {
							var old_id=$('.pm_ldefault').attr('id').substring(4);
							$('.pm_ldefault').remove();
							$('#pm_'+old_id+' .actions').prepend('<li><a class="pm_default" id="df_'+old_id+'" href="javascript:;">Set as Default</a></li>');
							$('#df_'+old_id).click(function(e) {
								change_default_card(e, $('#df_'+old_id));
							});
							$('#pm_'+id+' .method_name').append('<span id="def_'+id+'" class="pm_ldefault"> (Default)</span>');
							$('#df_'+id).parent().remove();
							$('#page_paymentmethods').prepend('<div class="alert success temperamental">Your default card was changed successfully</div>');
						}
					});
				}
			});
		}
		var stored_form_car_number_field='';
		function ccard_num_format(num) {
			var output='';
			if(num.length>1 && (num.substring(0,2)=='37' || num.substring(0,2)=='34')) {
				var c=0;
				for(x=0;x<num.length;x++) {
					if(num.substring(x,x+1).match(/[0-9]/g)) {
						c++;
						if(c<=15) {
							output+=num.substring(x,x+1);
							if(c==4 || c==10) {
								output+=' ';
							}
						}
					}
				}
			}else{
				var c=0;
				for(x=0;x<num.length;x++) {
					if(num.substring(x,x+1).match(/[0-9]/g)) {
						c++;
						if(c<=16) {
							output+=num.substring(x,x+1);
							if(c==4 || c==8 || c==12) {
								output+=' ';
							}
						}
					}
				}
			}
			return output;
		}
		</script>
		<div class="page">
			<h2>Manage <? echo ucfirst($page['type']);?> Page</h2>
			<ul class="buttons">
				<li><a href="<? echo $page['link'];?>">View Page</a></li>
			</ul>
			<?
			if(check_referrer('devonportlive.com/add/')) {?>
				<div class="alert success"><strong>Your page was created successfully!</strong> It won't yet be visible for all users of Devonport Live until it has been approved by our moderators. You can edit any page information here or click the "View Page" button above to see your page.<? if($page['type']=='business') {?> As this is a business page, you should probably add a payment method. You can do this at the bottom of this page.<? }?></div>
			<? }else{
				if(!$page['approved']) {?>
					<div class="alert error">This page is yet to be approved. When it has been approved, it will be visible to all visitors of Devonport Live.</div>
				<? }
				if($page['type']=='business') {
					if(!$payment_added) {?>
						<div class="alert error">You haven't added any payment methods yet. You should do this to ensure your business will remain listed on Devonport Live.</div>
					<? }
					if($page['paid_until']<=time()) {?>
						<div class="alert error">Your page is not visible because it has not been paid for since it expired on <? echo date("jS F Y",$page['paid_until']);?>. Please ensure you've added valid payment information below.</div>
					<? }else{?>
					<div class="alert info">Your subscription will end on <? echo date("jS F Y",$page['paid_until']);?>. Devonport Live will attempt to automatically renew this using your default payment method below.</div>
				<? 	}
				}
			}
			if($changed) {?>
				<div class="alert success">Your changes were saved successfully.</div>
			<? }
			if(in_array('PAYMENTMETHODS_ADD_SUCCESS',$responses)) {?>
				<div class="alert success temperamental">Your card was added successfully</div>
			<? }
			if(in_array('PAYMENTMETHODS_ERROR_CARDNOTSUPPORTED',$responses)) {?>
				<div class="alert error temperamental">Your card is not supported. Please use a Visa, MasterCard, or American Express card</div>
			<? }?>
			<form method="post" enctype="multipart/form-data">
				<div class="form_item">
					<div class="form_label">
						<label for="form_name">Page Name</label>
					</div>
					<div class="form_element">
						<input type="text" name="name" id="form_name" autocomplete="off" value="<? echo $page['name'];?>" />
					</div>
				</div>
				<div class="form_item">
					<div class="form_label">
						<label for="form_website">Website Address</label>
					</div>
					<div class="form_element">
						<input type="text" name="website" id="form_website" autocomplete="off" value="<? echo $page['website'];?>" />
					</div>
				</div>
				<div class="form_item">
					<div class="form_label">
						<label for="form_address">Address</label>
					</div>
					<div class="form_element">
						<textarea name="address" id="form_address"><? echo $page['address'];?></textarea>
					</div>
				</div>
				<div class="form_item">
					<div class="form_label">
						<label for="form_phone">Phone Number</label>
					</div>
					<div class="form_element">
						<input type="text" name="phone" id="form_phone" autocomplete="off" placeholder="&bull;&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;&bull;&bull;" value="<? echo $page['phone'];?>" />
					</div>
				</div>
				<div class="form_item">
					<div class="form_label">
						<label for="form_description">Description</label>
					</div>
					<div class="form_element">
						<textarea name="description" id="form_description"><? echo $page['description'];?></textarea>
					</div>
				</div>
				<div class="form_item">
					<div class="form_label">
						<label for="form_logo">Logo Image</label>
					</div>
					<div class="form_element">
						<img src="<? echo get_image_url($page['picture'],'m');?>" style="max-width:300px;max-height:300px;" /><br />
						<input type="file" name="logo" id="form_logo" />
					</div>
				</div>
				<div class="form_item paragraph">
					<div class="form_element">
						<input type="submit" value="Save" />
					</div>
				</div>
			</form>
			<? if($page['type']=='business') {?>
				<h2>Payment Methods</h2>
				<? 
				$months=array(1=>'Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec');
				if(!is_null($page['stripe_id']) and strlen($page['stripe_id'])>0 and $page['stripe_id']) {
					$default_card=$cust->default_card;
					$cards=$cust->cards->all(array('count'=>50));
					foreach($cards['data'] as $card) {?>
						<div id="pm_<? echo $card->id;?>" class="payment_method">
							<div class="method_type <? echo str_replace(' ','_',strtolower($card->type));?>"></div>
							<div class="method_name"><? echo $card->type;?><? if($card->id==$default_card) {?><span class="pm_ldefault" id="def_<? echo $card->id;?>"> (Default)</span><? }?></div>
							<div class="method_num"><?
							if($card->type=='American Express') {
								echo '&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;&bull;&bull; &bull;';
							}else{
								echo '&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; ';
							}
							?><? echo $card->last4;?><br /><strong>Expiry:</strong> <? echo $months[$card->exp_month].' '.$card->exp_year;?></div>
							<ul class="actions">
								<? if($card->id!=$default_card) {?>
									<li><a class="pm_default" id="df_<? echo $card->id;?>" href="javascript:;">Set as Default</a></li>
								<? }?>
								<li><a class="pm_remove" id="rm_<? echo $card->id;?>" href="javascript:;">Remove</a></li>
							</ul>
							<div class="clear"></div>
						</div>
					<? }
				}
				?>
				<h3>Add a Payment Method</h3>
				<form action="" method="POST" id="payment-form">
					<span class="payment-errors"></span>
					<div class="form_item">
						<div class="form_label">
							<label for="form_number">Card Number</label>
						</div>
						<div class="form_element">
							<input type="text" size="20" id="form_number" data-stripe="number" placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;" />
						</div>
					</div>
					<div class="form_item">
						<div class="form_label">
							<label for="form_cvc">CVC</label>
						</div>
						<div class="form_element">
							<input type="text" size="4" id="form_cvc" data-stripe="cvc" placeholder="CVC" style="width:50px;" />
						</div>
					</div>
					<div class="form_item">
						<div class="form_label">
							<label for="form_month">Expiration</label>
						</div>
						<div class="form_element">
							<select id="form_month" data-stripe="exp-month">
								<?
								foreach($months as $k=>$v) {?>
									<option value="<? echo $k;?>"><? echo $v;?></option>
								<? }?>
							</select>
							<select id="form_year" data-stripe="exp-year">
								<?
								for($x=date("Y");$x<=date("Y")+10;$x++) {?>
									<option value="<? echo $x;?>"><? echo $x;?></option>
								<? }?>
							</select>
						</div>
					</div>
					<div class="form_item">
						<div class="form_label">
							<label for="form_cardholdername">Cardholder's Name</label>
						</div>
						<div class="form_element">
							<input type="text" id="form_cardholdername" data-stripe="name" placeholder="Cardholder's Name" />
						</div>
					</div>
					<div class="form_item">
						<div class="form_label">
							<label for="form_cardholderaddress1">Cardholder's Address</label>
						</div>
						<div class="form_element">
							<input type="text" id="form_cardholderaddress1" data-stripe="address_line1" placeholder="Address Line 1" /><br />
							<input type="text" id="form_cardholderaddress2" data-stripe="address_line2" placeholder="Address Line 2" /><br />
							<input type="text" id="form_cardholdercity" data-stripe="address_city" placeholder="City" /><br />
							<input type="text" id="form_cardholderstate" data-stripe="address_state" placeholder="County" /><br />
							<input type="text" id="form_cardholderzip" data-stripe="address_zip" placeholder="Postcode" style="width:120px;" /><br />
							<input type="text" id="form_cardholdercountry" data-stripe="address_country" placeholder="Country" />
						</div>
					</div>
					<div class="form_item">
						<div class="form_element">
							<input type="submit" value="Add Card" />
						</div>
					</div>
				  </form>
			<? }?>
		</div>
		<? 
			$template->footer();
		}
	}
}
?>