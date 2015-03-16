<?php
include('../includes/template.php');
include('../includes/upload.php');
require_login();
if(isset($_GET['t'])) {
	$type=$_GET['t'];
	if($type=='business') {
		if(isset($_POST['name'])) {
			$business=array('name'=>'','website'=>'','address'=>'','phone'=>'','description'=>'','logo'=>'', 'lat'=>'','lon'=>'');
			$name=trim(stripslashes($_POST['name']));
			if(strlen($name)>=3) {
				$business['name']=$name;
			}
			if(isset($_POST['website'])) {
				$website=trim(stripslashes($_POST['website']));
				if($website=='http://') {
					$website='';
				}
				if(strlen($website)>10) {
					$business['website']=$website;
				}
			}
			if(isset($_POST['address'])) {
				$address=trim(stripslashes($_POST['address']));
				if(strlen($address)>30) {
					$business['address']=$address;
					$string = str_replace (" ", "+", urlencode($address));
					$details_url = "http://maps.googleapis.com/maps/api/geocode/json?address=".$string."&sensor=false";
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, $details_url);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					$response = json_decode(curl_exec($ch), true);
					if ($response['status'] == 'OK') {
						$geometry = $response['results'][0]['geometry'];
						$business['lat'] = $geometry['location']['lat'];
						$business['lon'] = $geometry['location']['lng'];
					}
				}
			}
			if(isset($_POST['phone'])) {
				$phone=trim(stripslashes($_POST['phone']));
				if(strlen($phone)>=11) {
					$business['phone']=$phone;
				}
			}
			if(isset($_POST['description'])) {
				$description=trim(stripslashes($_POST['description']));
				if(strlen($description)>10) {
					$business['description']=$description;
				}
			}
			if(isset($_FILES['logo'])) {
				$id=upload_image($_FILES['logo'],user_id());
				if($id) {
					$business['logo']=$id;
				}
			}
			if($business['name'] and $business['description']) {
				$slug=trim(preg_replace('/\W/','_',strtolower($business['name'])),'_');
				do {
					$slug=str_replace('__','_',$slug,$c);
				}
				while($c);
				$slug=substr($slug,0,40);
				$c=0;
				$slug=$slug;
				do {
					if($c>0) {
						$slug=substr($slug,0,39-strlen($c)).'_'.$c;
					}
					$c++;
					$result=Database::execute('SELECT page_id FROM pages WHERE slug=:slug',array(':slug'=>$slug));
				}
				while($result->rowCount());
				$business['slug']=$slug;
				$query=Database::execute('INSERT INTO pages (user_id, slug, name, type, website, address, lat, lon, phone, description, picture, paid_until, approved) VALUES (:user_id, :slug, :name, \'business\', :website, :address, :lat, :lon, :phone, :description, :picture, :paid_until, 0) ',array(':user_id'=>user_id(), ':slug'=>$business['slug'], ':name'=>$business['name'], ':website'=>$business['website'], ':address'=>$business['address'], ':lat'=>$business['lat'], ':lon'=>$business['lon'], ':phone'=>$business['phone'], ':description'=>$business['description'], ':picture'=>$business['logo'], ':paid_until'=>date('Y-m-d',strtotime("+12 weeks"))));
				$id=Database::last_insert_id();
				if($id) {
					alert_admin("A page has been added and needs approving.");
					redirect('/business/'.$business['slug'].'/manage');
				}
			}
		}
		$template->title("Create a Business Page");
		$template->header();
		?>
		<script>
		$(function() {
			$('#form_phone').bind('keyup change keypress keydown paste',function(e) {
				if($('#form_phone').val()!=stored_form_number_field) {
					$('#form_phone').val(card_num_format($('#form_phone').val()));
					stored_form_number_field=$('#form_phone').val();
				}
			});
		});
		var stored_form_number_field='';
		function card_num_format(num) {
			var output='';
			var c=0;
			for(x=0;x<num.length;x++) {
				if(num.substring(x,x+1).match(/[0-9]/g)) {
					c++;
					if(c<=11) {
						if(c==6) {
							output+=' ';
						}
						output+=num.substring(x,x+1);
					}
				}
			}
			return output;
		}
		</script>
		<div class="alert info">
			A business page costs &pound;20 a year, but you get the first three months free. Payment information can be completed once you've created your page.
		</div>
		<div class="para">
			<h2>Create a Business Page</h2>
			<form method="post" action="/add/business" enctype="multipart/form-data">
				<div class="form_item">
					<div class="form_label">
						<label for="form_name">Business Name</label>
					</div>
					<div class="form_element">
						<input type="text" name="name" id="form_name" autocomplete="off" />
					</div>
				</div>
				<div class="form_item">
					<div class="form_label">
						<label for="form_website">Website Address</label>
					</div>
					<div class="form_element">
						<input type="text" name="website" id="form_website" autocomplete="off" value="http://" />
					</div>
				</div>
				<div class="form_item">
					<div class="form_label">
						<label for="form_address">Address</label>
					</div>
					<div class="form_element">
						<textarea name="address" id="form_address"></textarea>
					</div>
				</div>
				<div class="form_item">
					<div class="form_label">
						<label for="form_phone">Phone Number</label>
					</div>
					<div class="form_element">
						<input type="text" name="phone" id="form_phone" autocomplete="off" placeholder="&bull;&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;&bull;&bull;" />
					</div>
				</div>
				<div class="form_item">
					<div class="form_label">
						<label for="form_description">Business Description</label>
					</div>
					<div class="form_element">
						<textarea name="description" id="form_description"></textarea>
					</div>
				</div>
				<div class="form_item">
					<div class="form_label">
						<label for="form_logo">Logo Image</label>
					</div>
					<div class="form_element">
						<input type="file" name="logo" id="form_logo" />
					</div>
				</div>
				<div class="form_item paragraph">
					<div class="form_element">
						<input type="submit" value="Add Business" />
					</div>
				</div>
			</form>
		</div>
		<?
		$template->footer();
	}
	if($type=='service') {
		if(isset($_POST['name'])) {
			$business=array('name'=>'','website'=>'','address'=>'','phone'=>'','description'=>'','logo'=>'', 'lat'=>'','lon'=>'');
			$name=trim(stripslashes($_POST['name']));
			if(strlen($name)>=3) {
				$business['name']=$name;
			}
			if(isset($_POST['website'])) {
				$website=trim(stripslashes($_POST['website']));
				if($website=='http://') {
					$website='';
				}
				if(strlen($website)>10) {
					$business['website']=$website;
				}
			}
			if(isset($_POST['address'])) {
				$address=trim(stripslashes($_POST['address']));
				if(strlen($address)>30) {
					$business['address']=$address;
					$string = str_replace (" ", "+", urlencode($address));
					$details_url = "http://maps.googleapis.com/maps/api/geocode/json?address=".$string."&sensor=false";
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, $details_url);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					$response = json_decode(curl_exec($ch), true);
					if ($response['status'] == 'OK') {
						$geometry = $response['results'][0]['geometry'];
						$business['lat'] = $geometry['location']['lat'];
						$business['lon'] = $geometry['location']['lng'];
					}
				}
			}
			if(isset($_POST['phone'])) {
				$phone=trim(stripslashes($_POST['phone']));
				if(strlen($phone)>=11) {
					$business['phone']=$phone;
				}
			}
			if(isset($_POST['description'])) {
				$description=trim(stripslashes($_POST['description']));
				if(strlen($description)>10) {
					$business['description']=$description;
				}
			}
			if(isset($_FILES['logo'])) {
				$id=upload_image($_FILES['logo'],user_id());
				if($id) {
					$business['logo']=$id;
				}
			}
			if($business['name'] and $business['description']) {
				$slug=trim(preg_replace('/\W/','_',strtolower($business['name'])),'_');
				do {
					$slug=str_replace('__','_',$slug,$c);
				}
				while($c);
				$slug=substr($slug,0,40);
				$c=0;
				$slug=$slug;
				do {
					if($c>0) {
						$slug=substr($slug,0,39-strlen($c)).'_'.$c;
					}
					$c++;
					$result=Database::execute('SELECT page_id FROM pages WHERE slug=:slug',array(':slug'=>$slug));
				}
				while($result->rowCount());
				$business['slug']=$slug;
				$query=Database::execute('INSERT INTO pages (user_id, slug, name, type, website, address, lat, lon, phone, description, picture, approved) VALUES (:user_id, :slug, :name, \'service\', :website, :address, :lat, :lon, :phone, :description, :picture, 0) ',array(':user_id'=>user_id(), ':slug'=>$business['slug'], ':name'=>$business['name'], ':website'=>$business['website'], ':address'=>$business['address'], ':lat'=>$business['lat'], ':lon'=>$business['lon'], ':phone'=>$business['phone'], ':description'=>$business['description'], ':picture'=>$business['logo']));
				$id=Database::last_insert_id();
				if($id) {
					alert_admin("A page has been added and needs approving.");
					redirect('/service/'.$business['slug'].'/manage');
				}
			}
		}
		$template->title("Create a Service Page");
		$template->header();
		?>
		<script>
		$(function() {
			$('#form_phone').bind('keyup change keypress keydown paste',function(e) {
				if($('#form_phone').val()!=stored_form_number_field) {
					$('#form_phone').val(card_num_format($('#form_phone').val()));
					stored_form_number_field=$('#form_phone').val();
				}
			});
		});
		var stored_form_number_field='';
		function card_num_format(num) {
			var output='';
			var c=0;
			for(x=0;x<num.length;x++) {
				if(num.substring(x,x+1).match(/[0-9]/g)) {
					c++;
					if(c<=11) {
						if(c==6) {
							output+=' ';
						}
						output+=num.substring(x,x+1);
					}
				}
			}
			return output;
		}
		</script>
		<div class="para">
			<h2>Create a Service Page</h2>
			<form method="post" action="/add/service" enctype="multipart/form-data">
				<div class="form_item">
					<div class="form_label">
						<label for="form_name">Service Name</label>
					</div>
					<div class="form_element">
						<input type="text" name="name" id="form_name" autocomplete="off" />
					</div>
				</div>
				<div class="form_item">
					<div class="form_label">
						<label for="form_website">Website Address</label>
					</div>
					<div class="form_element">
						<input type="text" name="website" id="form_website" autocomplete="off" value="http://" />
					</div>
				</div>
				<div class="form_item">
					<div class="form_label">
						<label for="form_address">Address</label>
					</div>
					<div class="form_element">
						<textarea name="address" id="form_address"></textarea>
					</div>
				</div>
				<div class="form_item">
					<div class="form_label">
						<label for="form_phone">Phone Number</label>
					</div>
					<div class="form_element">
						<input type="text" name="phone" id="form_phone" autocomplete="off" placeholder="&bull;&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;&bull;&bull;" />
					</div>
				</div>
				<div class="form_item">
					<div class="form_label">
						<label for="form_description">Service Description</label>
					</div>
					<div class="form_element">
						<textarea name="description" id="form_description"></textarea>
					</div>
				</div>
				<div class="form_item">
					<div class="form_label">
						<label for="form_logo">Logo Image</label>
					</div>
					<div class="form_element">
						<input type="file" name="logo" id="form_logo" />
					</div>
				</div>
				<div class="form_item paragraph">
					<div class="form_element">
						<input type="submit" value="Add Service" />
					</div>
				</div>
			</form>
		</div>
		<?
		$template->footer();
	}
	if($type=='community') {
		if(isset($_POST['name'])) {
			$business=array('name'=>'','website'=>'','address'=>'','phone'=>'','description'=>'','logo'=>'', 'lat'=>'','lon'=>'');
			$name=trim(stripslashes($_POST['name']));
			if(strlen($name)>=3) {
				$business['name']=$name;
			}
			if(isset($_POST['website'])) {
				$website=trim(stripslashes($_POST['website']));
				if($website=='http://') {
					$website='';
				}
				if(strlen($website)>10) {
					$business['website']=$website;
				}
			}
			if(isset($_POST['address'])) {
				$address=trim(stripslashes($_POST['address']));
				if(strlen($address)>30) {
					$business['address']=$address;
					$string = str_replace (" ", "+", urlencode($address));
					$details_url = "http://maps.googleapis.com/maps/api/geocode/json?address=".$string."&sensor=false";
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, $details_url);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					$response = json_decode(curl_exec($ch), true);
					if ($response['status'] == 'OK') {
						$geometry = $response['results'][0]['geometry'];
						$business['lat'] = $geometry['location']['lat'];
						$business['lon'] = $geometry['location']['lng'];
					}
				}
			}
			if(isset($_POST['phone'])) {
				$phone=trim(stripslashes($_POST['phone']));
				if(strlen($phone)>=11) {
					$business['phone']=$phone;
				}
			}
			if(isset($_POST['description'])) {
				$description=trim(stripslashes($_POST['description']));
				if(strlen($description)>10) {
					$business['description']=$description;
				}
			}
			if(isset($_FILES['logo'])) {
				$id=upload_image($_FILES['logo'],user_id());
				if($id) {
					$business['logo']=$id;
				}
			}
			if($business['name'] and $business['description']) {
				$slug=trim(preg_replace('/\W/','_',strtolower($business['name'])),'_');
				do {
					$slug=str_replace('__','_',$slug,$c);
				}
				while($c);
				$slug=substr($slug,0,40);
				$c=0;
				$slug=$slug;
				do {
					if($c>0) {
						$slug=substr($slug,0,39-strlen($c)).'_'.$c;
					}
					$c++;
					$result=Database::execute('SELECT page_id FROM pages WHERE slug=:slug',array(':slug'=>$slug));
				}
				while($result->rowCount());
				$business['slug']=$slug;
				$query=Database::execute('INSERT INTO pages (user_id, slug, name, type, website, address, lat, lon, phone, description, picture, approved) VALUES (:user_id, :slug, :name, \'community\', :website, :address, :lat, :lon, :phone, :description, :picture, 0) ',array(':user_id'=>user_id(), ':slug'=>$business['slug'], ':name'=>$business['name'], ':website'=>$business['website'], ':address'=>$business['address'], ':lat'=>$business['lat'], ':lon'=>$business['lon'], ':phone'=>$business['phone'], ':description'=>$business['description'], ':picture'=>$business['logo']));
				$id=Database::last_insert_id();
				if($id) {
					alert_admin("A page has been added and needs approving.");
					redirect('/community/'.$business['slug'].'/manage');
				}
			}
		}
		$template->title("Create a Community Group Page");
		$template->header();
		?>
		<script>
		$(function() {
			$('#form_phone').bind('keyup change keypress keydown paste',function(e) {
				if($('#form_phone').val()!=stored_form_number_field) {
					$('#form_phone').val(card_num_format($('#form_phone').val()));
					stored_form_number_field=$('#form_phone').val();
				}
			});
		});
		var stored_form_number_field='';
		function card_num_format(num) {
			var output='';
			var c=0;
			for(x=0;x<num.length;x++) {
				if(num.substring(x,x+1).match(/[0-9]/g)) {
					c++;
					if(c<=11) {
						if(c==6) {
							output+=' ';
						}
						output+=num.substring(x,x+1);
					}
				}
			}
			return output;
		}
		</script>
		<div class="para">
			<h2>Create a Community Group Page</h2>
			<form method="post" action="/add/community" enctype="multipart/form-data">
				<div class="form_item">
					<div class="form_label">
						<label for="form_name">Group Name</label>
					</div>
					<div class="form_element">
						<input type="text" name="name" id="form_name" autocomplete="off" />
					</div>
				</div>
				<div class="form_item">
					<div class="form_label">
						<label for="form_website">Website Address</label>
					</div>
					<div class="form_element">
						<input type="text" name="website" id="form_website" autocomplete="off" value="http://" />
					</div>
				</div>
				<div class="form_item">
					<div class="form_label">
						<label for="form_address">Address</label>
					</div>
					<div class="form_element">
						<textarea name="address" id="form_address"></textarea>
					</div>
				</div>
				<div class="form_item">
					<div class="form_label">
						<label for="form_phone">Phone Number</label>
					</div>
					<div class="form_element">
						<input type="text" name="phone" id="form_phone" autocomplete="off" placeholder="&bull;&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;&bull;&bull;" />
					</div>
				</div>
				<div class="form_item">
					<div class="form_label">
						<label for="form_description">Group Description</label>
					</div>
					<div class="form_element">
						<textarea name="description" id="form_description"></textarea>
					</div>
				</div>
				<div class="form_item">
					<div class="form_label">
						<label for="form_logo">Logo Image</label>
					</div>
					<div class="form_element">
						<input type="file" name="logo" id="form_logo" />
					</div>
				</div>
				<div class="form_item paragraph">
					<div class="form_element">
						<input type="submit" value="Add Community Group" />
					</div>
				</div>
			</form>
		</div>
		<?
		$template->footer();
	}
}else{
	$template->title("Create a Page");
	$template->header();
	?>
	<div class="para">
		<h2>Create a Page</h2>
		<p><strong>What kind of page would you like to create?</strong></p>
		<ul class="button_page">
			<li><a href="/add/business"><strong>A Business Page</strong>Used for commercial organisations such as a shop</a></li>
			<li><a href="/add/service"><strong>A Service Page</strong>Used for an organisation which provides a service to the community, such as the Police</a></li>
			<li><a href="/add/community"><strong>A Community Group Page</strong>Used for community-led organisations such as a religious organisation</a></li>
		</ul>
		<p>Not sure what type of page you need? <a href="/about/contact">Contact us</a>.</p>
	</div>
	<?
	$template->footer();
}
?>