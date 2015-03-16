<?php
include('../includes/template.php');
include('../includes/upload.php');
require_login();
$page=array('page_id'=>'','name'=>'','type'=>'','link'=>'');
if(isset($_GET['page_slug'])) {
	$page_slug=$_GET['page_slug'];
	$query=Database::execute('SELECT page_id, name, type FROM pages WHERE slug=:slug AND user_id=:user_id',array(':slug'=>$page_slug,':user_id'=>user_id()));
	if($query->rowCount()==1) {
		$page=$query->fetch();
		$page['link']='/'.$page['type'].'/'.$page['slug'];
	}
}
if(!$page['page_id']) {
	redirect('/');
}
if(isset($_POST['name'])) {
	$event=array('page_id'=>$page['page_id'],'name'=>'','slug'=>'','start_time'=>'','end_time'=>'','location'=>'', 'description'=>'','flagged'=>0,'dow'=>0);
	$name=trim(stripslashes($_POST['name']));
	if(strlen($name)>=3) {
		$event['name']=$name;
	}
	$location=trim(stripslashes($_POST['location']));
	if(strlen($location)>=3) {
		$event['location']=$location;
	}
	if(isset($_POST['description'])) {
		$description=trim(stripslashes($_POST['description']));
		if(strlen($description)>10) {
			$event['description']=$description;
		}
	}
	if(isset($_POST['day_of_week'])) {
		$event['dow']=$_POST['day_of_week'];
	}
	if(isset($_POST['start_hour']) and isset($_POST['start_minute'])) {
		$start=@mktime($_POST['start_hour'],$_POST['start_minute'],0,1,1,2014);
		if($start) {
			$event['start_time']=$start;
		}
	}
	if(isset($_POST['end_hour']) and isset($_POST['end_minute'])) {
		$end=@mktime($_POST['end_hour'],$_POST['end_minute'],0,1,1,2014);
		if($end) {
			$event['end_time']=$end;
		}
	}
	if($event['page_id'] and $event['name']) {
		$event['flagged']=check_message($event['name']);
		if(!$event['flagged']) {
			$event['flagged']=check_message($event['description']);
		}
		$slug=trim(preg_replace('/\W/','_',strtolower($event['name'])),'_');
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
			$result=Database::execute('SELECT event_id FROM events WHERE slug=:slug',array(':slug'=>$slug));
		}
		while($result->rowCount());
		$event['slug']=$slug;
		$query=Database::execute('INSERT INTO events (page_id, slug, name, start_time, end_time, location, description, flagged, recurring_dow) VALUES (:page_id, :slug, :name, :start_time, :end_time, :location, :description, :flagged, :dow)',array(':page_id'=>$event['page_id'], ':slug'=>$event['slug'], ':name'=>$event['name'], ':start_time'=>date('Y-m-d H:i:s',$event['start_time']), ':end_time'=>date('Y-m-d H:i:s',$event['end_time']), ':location'=>$event['location'], ':description'=>$event['description'], ':flagged'=>$event['flagged'],':dow'=>$event['dow']));
		$id=Database::last_insert_id();
		if($id) {
			if($event['flagged']) {
				alert_admin("An event has been flagged. This needs reviewing.");
			}
			redirect('/events/'.$event['slug']);
		}
	}
}
$template->title("Create a Recurring Event");
$template->header();
?>
<div class="para">
	<h2>Create a Recurring Event for <? echo $page['name'];?></h2>
	<form method="post">
		<div class="form_item">
			<div class="form_label">
				<label for="form_name">Event Name</label>
			</div>
			<div class="form_element">
				<input type="text" name="name" id="form_name" autocomplete="off" />
			</div>
		</div>
		<div class="form_item">
			<div class="form_label">
				<label for="form_description">Description</label>
			</div>
			<div class="form_element">
				<textarea name="description" id="form_description"></textarea>
			</div>
		</div>
		<div class="form_item">
			<div class="form_label">
				<label for="form_location">Location</label>
			</div>
			<div class="form_element">
				<input type="text" name="location" id="form_location" autocomplete="off" />
			</div>
		</div>
		<div class="form_item">
			<div class="form_label">
				<label>Day of Week</label>
			</div>
			<div class="form_element">
				<select name="day_of_week">
					<option value="1">Monday</option>
					<option value="2">Tuesday</option>
					<option value="3">Wednesday</option>
					<option value="4">Thursday</option>
					<option value="5">Friday</option>
					<option value="6">Saturday</option>
					<option value="0">Sunday</option>
				</select>
			</div>
		</div>
		<div class="form_item">
			<div class="form_label">
				<label>Start</label>
			</div>
			<div class="form_element">
				<select name="start_hour">
					<? for($x=0;$x<=23;$x++) {?>
						<option value="<? echo $x;?>"><? echo sprintf("%02s", $x);?></option>
					<? }?>
				</select>:<select name="start_minute">
					<? for($x=0;$x<=60;$x+=5) {?>
						<option value="<? echo $x;?>"><? echo sprintf("%02s", $x);?></option>
					<? }?>
				</select>
			</div>
		</div>
		<div class="form_item">
			<div class="form_label">
				<label>End</label>
			</div>
			<div class="form_element">
				<select name="end_hour">
					<? for($x=0;$x<=23;$x++) {?>
						<option value="<? echo $x;?>"><? echo sprintf("%02s", $x);?></option>
					<? }?>
				</select>:<select name="end_minute">
					<? for($x=0;$x<60;$x+=5) {?>
						<option value="<? echo $x;?>"><? echo sprintf("%02s", $x);?></option>
					<? }?>
				</select>
			</div>
		</div>
		<div class="form_item paragraph">
			<div class="form_element">
				<input type="submit" value="Create Recurring Event" />
			</div>
		</div>
	</form>
</div>
<?
$template->footer();
?>