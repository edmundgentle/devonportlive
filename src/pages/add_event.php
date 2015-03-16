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
		$page['link']='/'.$page['type'].'/'.$page_slug;
	}
}
if(!$page['page_id']) {
	redirect('/');
}
if(isset($_POST['name'])) {
	$event=array('page_id'=>$page['page_id'],'name'=>'','slug'=>'','start_time'=>'','end_time'=>'','location'=>'', 'description'=>'','flagged'=>0);
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
	if(isset($_POST['start_day']) and isset($_POST['start_month']) and isset($_POST['start_year']) and isset($_POST['start_hour']) and isset($_POST['start_minute'])) {
		$start=@mktime($_POST['start_hour'],$_POST['start_minute'],0,$_POST['start_month'],$_POST['start_day'],$_POST['start_year']);
		if($start) {
			$event['start_time']=$start;
		}
	}
	if(isset($_POST['end_day']) and isset($_POST['end_month']) and isset($_POST['end_year']) and isset($_POST['end_hour']) and isset($_POST['end_minute'])) {
		$end=@mktime($_POST['end_hour'],$_POST['end_minute'],0,$_POST['end_month'],$_POST['end_day'],$_POST['end_year']);
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
		$query=Database::execute('INSERT INTO events (page_id, slug, name, start_time, end_time, location, description, flagged) VALUES (:page_id, :slug, :name, :start_time, :end_time, :location, :description, :flagged) ',array(':page_id'=>$event['page_id'], ':slug'=>$event['slug'], ':name'=>$event['name'], ':start_time'=>date('Y-m-d H:i:s',$event['start_time']), ':end_time'=>date('Y-m-d H:i:s',$event['end_time']), ':location'=>$event['location'], ':description'=>$event['description'], ':flagged'=>$event['flagged']));
		$id=Database::last_insert_id();
		if($id) {
			if($event['flagged']) {
				alert_admin("An event has been flagged. This needs reviewing.");
			}
			redirect('/events/'.$event['slug']);
		}
	}
}
$template->title("Create an Event");
$template->header();
?>
<div class="para">
	<h2>Create an Event for <? echo $page['name'];?></h2>
	<p>Want to create an event which happens every week? <a href="<? echo $page['link'];?>/addevent/recurring">Add a recurring event</a>!</p>
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
				<label>Start</label>
			</div>
			<div class="form_element">
				<select name="start_day">
					<? for($x=1;$x<=31;$x++) {?>
						<option value="<? echo $x;?>"><? echo $x;?></option>
					<? }?>
				</select>
				<select name="start_month">
					<? for($x=1;$x<=12;$x++) {?>
						<option value="<? echo $x;?>"><? echo date("F",mktime(0,0,0,$x));?></option>
					<? }?>
				</select>
				<select name="start_year">
					<? for($x=date("Y");$x<=(date("Y")+3);$x++) {?>
						<option value="<? echo $x;?>"><? echo $x;?></option>
					<? }?>
				</select>
				at
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
				<select name="end_day">
					<? for($x=1;$x<=31;$x++) {?>
						<option value="<? echo $x;?>"><? echo $x;?></option>
					<? }?>
				</select>
				<select name="end_month">
					<? for($x=1;$x<=12;$x++) {?>
						<option value="<? echo $x;?>"><? echo date("F",mktime(0,0,0,$x));?></option>
					<? }?>
				</select>
				<select name="end_year">
					<? for($x=date("Y");$x<=(date("Y")+3);$x++) {?>
						<option value="<? echo $x;?>"><? echo $x;?></option>
					<? }?>
				</select>
				 at 
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
				<input type="submit" value="Create Event" />
			</div>
		</div>
	</form>
</div>
<?
$template->footer();
?>