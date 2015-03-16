<?php
include('../includes/template.php');
$cdays=array("Sun","Mon","Tue","Wed","Thu","Fri","Sat");
if(isset($_GET['slug'])) {
	$slug=$_GET['slug'];
	$query=Database::execute('SELECT events.event_id, events.page_id, events.name, events.slug, start_time, end_time, location, events.description, flagged, pages.user_id, pages.slug, pages.user_id AS page_slug, pages.name AS page_name, type, picture, UNIX_TIMESTAMP(paid_until), approved, recurring_dow FROM events, pages WHERE events.slug=:slug AND events.page_id=pages.page_id',array(':slug'=>$slug));
	if($query->rowCount()==1) {
		$event=$query->fetch();
		$event['page_link']='/'.$event['type'].'/'.$event['page_slug'];
		if((($event['paid_until']>time() or $page['paid_until']==0) and $event['approved'] and $event['flagged']==0) or $event['user_id']==user_id()) {
			$template->title($event['name']);
			$template->description($event['description']);
			$template->header();
			//$sq_img = get_image_url($page['picture'],'q');
		?>
		<script>
		function cancel_event(id) {
			apprise('Are you sure you want to cancel this event? Once it has been cancelled, it can\'t be restored.', {'verify':true}, function(r) {
				if(r) {
					$.ajax({
						type: "POST",
						url: "/ajax/cancel_event.php",
						data: {id: id},
						dataType: 'json'
					}).done(function(data) {
						if(data.success!==undefined && data.success) {
							window.location.href = "https://www.devonportlive.com";
						}
					});
				}
			});
		}
		</script>
		<div class="para" itemscope itemtype="http://schema.org/Event">
			<h2 itemprop="name"><? echo $event['name'];?></h2>
			<? if(is_null($event['recurring_dow'])) {?>
			<meta itemprop="startDate" content="<? echo date('c',strtotime($event['start_time']));?>">
			<meta itemprop="endDate" content="<? echo date('c',strtotime($event['end_time']));?>">
			<p><strong><? echo date('jS M \a\t g:ia',strtotime($event['start_time'])).' until '.date('jS M \a\t g:ia',strtotime($event['end_time']));?> in <? echo $event['location'];?></strong></p>
			<? }else{ ?>
				<p><strong>Every <? echo $cdays[$event['recurring_dow']] .' at '.date('g:ia',strtotime($event['start_time'])).' until '.date('g:ia',strtotime($event['end_time']));?> in <? echo $event['location'];?></strong></p>
			<? }?>
			<p itemprop="description"><? echo nl2br(trim(htmlspecialchars($event['description'])));?></p>
			<p>This event was created by <a href="<? echo $event['page_link'];?>"><? echo $event['page_name'];?></a>.</p>
			<?php
			if($event['user_id']==user_id()) {?>
				<p><a href="javascript:;" onclick="cancel_event('<? echo $event['event_id'];?>');">Cancel this event</a></p>
			<? }else{
			?>
			<p><a href="javascript:;" onclick="report_content('event_<? echo $event['event_id'];?>');">Report as inappropriate</a></p>
			<? }?>
		</div>
		<? 
			$template->footer();
		}
	}
}
?>