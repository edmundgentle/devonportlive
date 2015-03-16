<?php
include('../includes/template.php');
if(isset($_GET['slug'])) {
	$slug=$_GET['slug'];
	$query=Database::execute('SELECT page_id, user_id, slug, name, type, website, twitter, address, lat, lon, phone, description, picture, UNIX_TIMESTAMP(paid_until) AS paid_until, approved FROM pages WHERE slug=:slug',array(':slug'=>$slug));
	if($query->rowCount()==1) {
		$page=$query->fetch();
		$page['link']='/'.$page['type'].'/'.$page['slug'];
		if((($page['paid_until']>time() or $page['paid_until']==0) and $page['approved']) or $page['user_id']==user_id()) {
			$template->title($page['name']);
			$template->description($page['description']);
			$template->image('https://www.devonportlive.com'.get_image_url($page['picture'],'m'));
			$template->header();
			$image_url=get_image_url($page['picture'],'m');
			$sq_img = get_image_url($page['picture'],'q');
		?>
		<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=<? echo $google_maps;?>&sensor=false"></script>
		<script type="text/javascript" src="/uploader.js"></script>
		<script type="text/javascript">
			var page_id=<? echo $page['page_id'];?>;
			var page_name=<? echo json_encode($page['name']);?>;
			var page_pic=<? echo json_encode($sq_img);?>;
			var page_link=<? echo json_encode($page['link']);?>;
			var page_mine=<? if(user_id()==$page['user_id']) {echo'true';}else{echo'false';}?>;
			var news_count=10;
			$(function() {
				<? if($page['lat']!=0 and $page['lon']!=0) {?>
				var myLatlng=new google.maps.LatLng(<? echo $page['lat'];?>, <? echo $page['lon'];?>);
				var mapOptions = {
		          center: myLatlng,
		          zoom: 17
		        };
		        var map = new google.maps.Map(document.getElementById("map-canvas"), mapOptions);
				var marker = new google.maps.Marker({
				    position: myLatlng,
				    map: map,
					title: <? echo json_encode($page['name']);?>
				});
				var infowindow = new google.maps.InfoWindow({
				      content: <? echo json_encode("<p><strong>{$page['name']}</strong></p><p>".nl2br(htmlspecialchars($page['address']))."</p>");?>
				});
				google.maps.event.addListener(marker, 'click', function() {
					infowindow.open(map,marker);
				});
				<? }?>
				$('#post_update').bind('submit',function(e) {
					e.preventDefault();
					var update=$('#post_update textarea').val();
					if(update.length>0 || post_pics.length>0) {
						var pics='';
						if(post_pics.length>0) {
							pics='<div><em>There are pictures attached to this message. These will be visible when you refresh the page.</em></div>';
						}
						$.ajax({
							type: "POST",
							url: "/ajax/post_update.php",
							dataType:'json',
							data:{id:page_id,update:update,pics:post_pics.join(',')}
						}).done(function(r) {
							if(r.success) {
								$('.compose').after('<li id="'+r.post_id+'"><div class="pic"><a href="<? echo $page['link'];?>"><img src="<? echo $sq_img;?>" /></a></div><div class="name"><a href="<? echo $page['link'];?>">'+page_name+'</a></div><div class="body">'+(update.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;"))+'</div>'+pics+'<div class="stamp">Just now&nbsp;&nbsp;&nbsp;<a href="javascript:;" onclick="report_content(\''+r.post_id+'\');">Report</a>&nbsp;&nbsp;&nbsp;<a href="javascript:;" onclick="remove_post(\''+r.post_id+'\');">Delete</a></div></li>');
							}
						});
					}
					$('#post_update textarea').val('');
					$('#post_update .attachments').hide();
					$('#post_update .attachments').html('');
					post_pics=[];
				});
				uploader('att_pics',['jpg','jpeg','png','gif','bmp'],true,function(data) {
					if(data.success) {
						post_pics.push(data.id);
						$('.attachments').append('<div class="attachment"><img src="'+data.url+'" /></div>');
						$('.attachments .loading')[0].remove();
					}
				},function() {
					$('.attachments').show();
					$('.attachments').append('<div class="attachment loading"></div>');
				});
				$('.morelink').bind('click',function(e) {
					e.preventDefault();
					$.ajax({
						type: "GET",
						url: "/ajax/get_home_feed.php",
						dataType:'json',
						data:{start:news_count}
					}).done(function(r) {
						if(r.success) {
							$.each(r.data, function( index, post ) {
								news_count++;
								var html='<li id="'+post.id+'"><div class="pic"><a href="'+page_link+'"><img src="'+page_pic+'" /></a></div><div class="name"><a href="'+page_link+'">'+page_name+'</a></div><div class="body">'+post.body+'</div>';
								if(post.pictures!==undefined) {
									html+='<ul class="gallery">';
									$.each(post.pictures, function( i, pic ) {
										html+='<li><a href="'+pic.l+'" rel="facebox"><img src="'+pic.m+'" /></a></li>';
									});
								}
								if(post.embed!==undefined) {
									html+='<div class="oembed"><a href="'+post.embed.url+'" target="_blank">';
									if(post.embed.pic!==undefined) {
										html+='<div class="media"><img src="'+post.embed.pic+'" /></div>';
									}
									if(post.embed.title!==undefined) {
										html+='<div class="title">'+post.embed.title+'</div>';
									}
									if(post.embed.description!==undefined) {
										html+='<div class="desc">'+post.embed.description+'</div>';
									}
									if(post.embed.provider!==undefined) {
										html+='<div class="url">'+post.embed.provider+'</div>';
									}
									html+='</a></div>';
								}
								html+='<div class="stamp">'+post.datehtml+'&nbsp;&nbsp;&nbsp;<a href="javascript:;" onclick="report_content(\''+post.id+'\');">Report</a>';
								if(page_mine) {
									html+='&nbsp;&nbsp;&nbsp;<a href="javascript:;" onclick="remove_post(\''+post.id+'\');">Delete</a>';
								}
								html+='</div></li>';
								$('#main_feed').append(html);
							});
							if(news_count%10!=0) {
								$('.morelink').remove();
							}
							$( "a[rel*=facebox]").unbind("click");
							$('a[rel*=facebox]').facebox();
						}
					});
				});
			});
			var post_pics=[];
		    </script>
		<? if(!(($page['paid_until']>time() or $page['paid_until']==0) and $page['approved'])) {?>
			<div class="alert error">This page is not visible to users of Devonport Live, either because it hasn't yet been approved or its subscription has expired and could not be renewed.</div>
		<? }?>
		<div class="page" itemscope itemtype="http://schema.org/LocalBusiness">
			<div class="page_header">
				<div class="image"><img src="<? echo $image_url;?>" /><meta itemprop="logo" content="https://www.devonportlive.com<? echo $image_url;?>" /></div>
				<h2 itemprop="name"><? echo htmlspecialchars($page['name']);?></h2>
				<p><strong><? echo ucfirst($page['type']);?> Page</strong></p>
				<? if($page['address']) {?>
				<p itemprop="address"><? 
				$addr_part=explode("\n",htmlspecialchars($page['address']));
				foreach($addr_part as $k=>$p) {
					$addr_part[$k]=trim($p,", \t\n\r\0\x0B");
				}
				
				echo implode(", ",$addr_part);?></p>
				<span itemprop="geo" itemscope itemtype="http://schema.org/GeoCoordinates">
					<meta itemprop="latitude" content="<? echo $page['lat'];?>" />
					<meta itemprop="longitude" content="<? echo $page['lon'];?>" />
				</span>
				<? }?>
				<? if($page['phone']) {?>
				<div class="notmobile">
					<p><strong>Tel: </strong><span itemprop="telephone"><? echo $page['phone'];?></span></p>
				</div>
				<? }?>
				<ul class="buttons">
					<? if($page['user_id']==user_id()) {?>
						<li><a href="<? echo $page['link'];?>/manage">Manage Page</a></li>
					<? }?>
					<? if($page['website']) {?>
						<li><a href="<? echo $page['website'];?>" target="_blank">Website</a></li>
					<? }?>
					<? if($page['phone']) {?>
						<li class="mobileinlineb"><a href="tel:<? echo str_replace(' ','',$page['phone']);?>">Call</a></li>
					<? }?>
					<? if($page['twitter']) {?>
						<li><a href="https://www.twitter.com/<? echo $page['twitter'];?>" target="_blank">Twitter</a></li>
					<? }?>
				</ul>
			</div>
			<div class="page_info<? if($page['lat']!=0 and $page['lon']!=0) {?> w_map<? }?>">
				<? if($page['lat']!=0 and $page['lon']!=0) {?>
					<div id="map-canvas"></div>
				<? }?>
				<div class="description" itemprop="description">
					<p><? echo nl2br(htmlspecialchars($page['description']));?></p>
				</div>
			</div>
			<ul class="newsfeed" id="main_feed">
				<? if($page['user_id']==user_id()) {?>
				<li class="compose">
					<div class="pic"><a href="<? echo $page['link'];?>"><img src="<? echo $sq_img;?>" /></a></div>
					<form method="post" id="post_update">
						<textarea name="body"></textarea>
						<ul class="buttons">
							<li><input type="submit" value="Post" /></li>
							<li id="att_pics"></li>
							<li><a href="<? echo $page['link'];?>/addevent">Create Event</a></li>
						</ul>
						<div class="attachments" style="display:none;"></div>
					</form>
				</li>
				<? }
				$months_to_display=3;
				$em=date("n")+$months_to_display;
				$ey=date("Y");
				if($em>12) {
					$ey++;
					$em-=12;
				}
				$end=mktime(0,0,0,$em,1,$ey);
				$q2=Database::execute('SELECT event_id, page_id, name, slug, start_time, end_time, location, description FROM events WHERE flagged=0 AND start_time>=NOW() AND page_id=:page_id ORDER BY start_time ASC LIMIT 3',array(':page_id'=>$page['page_id']));
				$events=$q2->fetchAll();
				$event_days=array();
				foreach($events as $ev) {?>
					<li>
						<div class="pic"><div class="calendar"><div class="month"><? echo date("M",strtotime($ev['start_time']));?></div><div class="day"><? echo date("j",strtotime($ev['start_time']));?></div></div></div>
						<div class="name"><a href="/events/<? echo $ev['slug'];?>"><? echo $ev['name']?></a></div>
						<div class="tagline"><?
						if(date("Y-m-d",strtotime($ev['start_time']))==date("Y-m-d",strtotime($ev['end_time']))) {
							echo date("g:ia",strtotime($ev['start_time'])).'-'.date("g:ia",strtotime($ev['end_time']));
						}else{
							echo date('jS M \a\t g:ia',strtotime($ev['start_time'])).' - '.date('jS M \a\t g:ia',strtotime($ev['end_time']));
						}?> at <? echo $ev['location'];?></div>
						<div class="body"><? echo trim(htmlspecialchars($ev['description']));?></div>
					</li>
				<? }
				$query=Database::execute('SELECT update_id, message, UNIX_TIMESTAMP(date) AS d, (SELECT COUNT(picture_id) FROM pictures WHERE pictures.update_id=updates.update_id) AS num_pics FROM updates WHERE page_id=:page_id AND flagged=0 ORDER BY date DESC LIMIT 10',array(':page_id'=>$page['page_id']));
				$results=$query->fetchAll();
				$c=0;
				foreach($results as $post) {
					$c++;
					$embed=false;
					if(preg_match($url_regex,htmlspecialchars($post['message']),$m)) {
						$url=$m[0];
						$embed=embedly_request($url);
					}
					?>
				<li id="update_<? echo $post['update_id'];?>">
					<div class="pic"><a href="<? echo $page['link'];?>"><img src="<? echo $sq_img;?>" /></a></div>
					<div class="name"><a href="<? echo $page['link'];?>"><? echo $page['name'];?></a></div>
					<div class="body"><? echo preg_replace($url_regex,'<a href="$0" target="_blank">$0</a>',nl2br(htmlspecialchars($post['message'])));?></div>
					<?
					if($post['num_pics']) {?>
						<ul class="gallery">
					<? 
						$q2=Database::execute('SELECT upload_id FROM pictures WHERE pictures.update_id=:update_id',array(':update_id'=>$post['update_id']));
						$pics=$q2->fetchAll();
						foreach($pics as $pic) {?>
							<li><a href="<? echo get_image_url($pic['upload_id'],'l');?>" rel="facebox"><img src="<? echo get_image_url($pic['upload_id'],'m');?>" /></a></li>
						<? }?>
						</ul>
					<? }?>
					<? if($embed) {?>
						<div class="oembed">
							<a href="<? echo $url;?>" target="_blank">
								<? if(isset($embed['thumbnail_url'])) {?><div class="media"><img src="<? echo $embed['thumbnail_url'];?>" /></div><? }?>
								<? if(isset($embed['title'])) {?><div class="title"><? echo $embed['title'];?></div><? }?>
								<? if(isset($embed['description'])) {?><div class="desc"><? echo $embed['description'];?></div><? }?>
								<? if(isset($embed['provider_url'])) {?><div class="url"><? echo $embed['provider_url'];?></div><? }?>
							</a>
						</div>
					<? }?>
					<div class="stamp"><? echo format_date($post['d']);?>&nbsp;&nbsp;&nbsp;<a href="javascript:;" onclick="report_content('update_<? echo $post['update_id'];?>');">Report</a><? if($page['user_id']==user_id()) {?>&nbsp;&nbsp;&nbsp;<a href="javascript:;" onclick="remove_post('update_<? echo $post['update_id'];?>');">Delete</a><? }?></div>
				</li>
				<? }?>
			</ul>
			<? if($c==10) {?>
			<a href="" class="morelink">See More</a>
			<? }?>
		</div>
		<? 
			$template->footer();
		}
	}
}
?>