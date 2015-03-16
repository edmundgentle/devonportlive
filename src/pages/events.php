<?php
include('../includes/template.php');
$template->title('Events');
$template->header();?>
<h2>Events</h2>
<? 
$query=Database::execute('SELECT event_id, page_id, name, slug, start_time, end_time, location, description, recurring_dow FROM events WHERE flagged=0 AND recurring_dow IS NOT NULL ORDER BY start_time');
$events=$query->fetchAll();
$recurring_events=array();
foreach($events as $ev) {
	$recurring_events[$ev['recurring_dow']][]=$ev;
}
$months=array(1=>"January","February","March","April","May","June","July","August","September","October","November","December");
$days=array("Mon","Tue","Wed","Thu","Fri","Sat","Sun");
$cdays=array("Sun","Mon","Tue","Wed","Thu","Fri","Sat");
$months_to_display=3;
$em=date("n")+$months_to_display;
$ey=date("Y");
if($em>12) {
	$ey++;
	$em-=12;
}
$end=mktime(0,0,0,$em,1,$ey);
$query=Database::execute('SELECT event_id, page_id, name, slug, start_time, end_time, location, description FROM events WHERE flagged=0 AND start_time>=NOW() AND end_time<=:end_time AND recurring_dow IS NULL ORDER BY start_time',array(':end_time'=>date('Y-m-d H:i:s',$end)));
$events=$query->fetchAll();
$event_days=array();
foreach($events as $ev) {
	$event_days[date("Y-m-d",strtotime($ev['start_time']))]=true;
}
/*foreach($results as $row) {
	$row['link']='/'.$row['type'].'/'.$row['slug'];?>
	
<? }*/?>
<div class="fs0">
<div class="calendars_left notmobile">
<? for($x=0;$x<$months_to_display;$x++) {
	$tm=date("n")+$x;
	$ty=date("Y");
	if($tm>12) {
		$ty++;
		$tm-=12;
	}?>
	<div class="big_calendar">
		<div class="cal_head"><? echo $months[$tm];?> <? echo $ty;?></div>
		<?
		foreach($days as $d) {?>
			<div class="cell head"><? echo $d;?></div>
		<? }
		$dow=date("w",mktime(0,0,0,$tm,1,$ty))-1;
		if($dow<0) {$dow=6;}
		for($y=0;$y<$dow;$y++) {?>
			<div class="cell blank"></div>
		<? }
		$dim=date("t",mktime(0,0,0,$tm,1,$ty));
		for($d=1;$d<=$dim;$d++) {
			if(isset($event_days[date("Y-m-d",mktime(0,0,0,$tm,$d,$ty))]) or isset($recurring_events[date("w",mktime(0,0,0,$tm,$d,$ty))])) {?>
				<a href="/events?d=<? echo date("Y-m-d",mktime(0,0,0,$tm,$d,$ty));?>" class="cell<? if(date("Y-m-d",mktime(0,0,0,$tm,$d,$ty))==date("Y-m-d")) {echo ' today';}?>"><? echo $d;?></a>
			<? }else{?>
				<div class="cell<? if(date("Y-m-d",mktime(0,0,0,$tm,$d,$ty))==date("Y-m-d")) {echo ' today';}?>"><? echo $d;?></div>
		<? 	}
		}
		?>
	</div>
<? }
?>
</div>
<div class="list_right">
	<? if(!isset($_GET['d'])) {?>
		<h3>Upcoming Events</h3>
	<? }else{
		$date=@strtotime($_GET['d']);
		if($date) {
			$query=Database::execute('SELECT event_id, page_id, name, slug, start_time, end_time, location, description FROM events WHERE flagged=0 AND start_time>=:start_time AND end_time<=:end_time AND recurring_dow IS NULL ORDER BY start_time ASC',array(':start_time'=>date('Y-m-d 00:00:00',$date),':end_time'=>date('Y-m-d 23:59:59',$date)));
			$events=$query->fetchAll();?>
			<h3>Events on <? echo date("jS M Y",$date);?></h3>
		<? }
	}?>
		<ul class="newsfeed">
			<?
			if($date) {
				foreach($recurring_events[date("w",$date)] as $ev) {?>
					<li>
						<div class="pic"><div class="calendar"><div class="month"><? echo date("M",$date);?></div><div class="day"><? echo date("j",$date);?></div></div></div>
						<div class="name"><a href="/events/<? echo $ev['slug'];?>"><? echo $ev['name'];?></a></div>
						<div class="tagline">Every <? echo $cdays[date("w",$date)];?> at <?
						echo date("g:ia",strtotime($ev['start_time'])).'-'.date("g:ia",strtotime($ev['end_time']));
						?> in <? echo $ev['location'];?></div>
					</li>
				<? }
			}else{
				for($d=0;$d<=6;$d++) {
					foreach($recurring_events[$d] as $ev) {?>
						<li>
							<div class="pic"><div class="calendar"><div class="month">Every</div><div class="day"><? echo $cdays[$d];?></div></div></div>
							<div class="name"><a href="/events/<? echo $ev['slug'];?>"><? echo $ev['name'];?></a></div>
							<div class="tagline">Every <? echo $cdays[$d];?> at <?
							echo date("g:ia",strtotime($ev['start_time'])).'-'.date("g:ia",strtotime($ev['end_time']));
							?> in <? echo $ev['location'];?></div>
						</li>
					<? }
				}
			}
			foreach($events as $ev) {?>
				<li>
					<div class="pic"><div class="calendar"><div class="month"><? echo date("M",strtotime($ev['start_time']));?></div><div class="day"><? echo date("j",strtotime($ev['start_time']));?></div></div></div>
					<div class="name"><a href="/events/<? echo $ev['slug'];?>"><? echo $ev['name'];?></a></div>
					<div class="tagline"><?
					if(date("Y-m-d",strtotime($ev['start_time']))==date("Y-m-d",strtotime($ev['end_time']))) {
						echo date("g:ia",strtotime($ev['start_time'])).'-'.date("g:ia",strtotime($ev['end_time']));
					}else{
						echo date('jS M \a\t g:ia',strtotime($ev['start_time'])).' - '.date('jS M \a\t g:ia',strtotime($ev['end_time']));
					}?> in <? echo $ev['location'];?></div>
				</li>
			<? }?>
		</ul>
</div>
</div>
<? 
$template->footer();
?>