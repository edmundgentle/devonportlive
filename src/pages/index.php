<?php
include('../includes/template.php');
$template->header();
?>
<script>
var news_count=10;
$(function() {
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
					var html='<li id="'+post.id+'"><div class="pic"><a href="'+post.from.link+'"><img src="'+post.from.picture+'" /></a></div><div class="name"><a href="'+post.from.link+'">'+post.from.name+'</a></div><div class="body">'+post.body+'</div>';
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
					if(post.can_edit) {
						html+='&nbsp;&nbsp;&nbsp;<a href="javascript:;" onclick="remove_post(\''+post.id+'\');">Delete</a>';
					}
					html+='</div></li>';
					$('#mainfeed').append(html);
					$( "a[rel*=facebox]").unbind("click");
					$('a[rel*=facebox]').facebox();
				});
				if(news_count%10!=0) {
					$('.morelink').remove();
				}
			}
		});
	});
});
</script>
<ul class="newsfeed" id="mainfeed">
	<?php
	$query=Database::execute('SELECT updates.update_id, message, UNIX_TIMESTAMP(date) AS d, (SELECT COUNT(picture_id) FROM pictures WHERE pictures.update_id=updates.update_id) AS num_pics, pages.page_id, name, slug, type, picture, user_id FROM updates, pages WHERE flagged=0 AND updates.page_id=pages.page_id ORDER BY date DESC LIMIT 10');
	$results=$query->fetchAll();
	$c=0;
	foreach($results as $post) {
		$c++;
		if($c==3) {
			$months_to_display=3;
			$em=date("n")+$months_to_display;
			$ey=date("Y");
			if($em>12) {
				$ey++;
				$em-=12;
			}
			$end=mktime(0,0,0,$em,1,$ey);
			$q2=Database::execute('SELECT event_id, page_id, name, slug, start_time, end_time, location, description FROM events WHERE flagged=0 AND start_time>=NOW() AND end_time<=:end_time AND recurring_dow IS NULL ORDER BY rand() LIMIT 1',array(':end_time'=>date('Y-m-d H:i:s',$end)));
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
			<? }?>
		<? }
		if($c==7) {
			if(!file_exists('../cache/newsrss') or filemtime('../cache/newsrss')<strtotime("-1 hour")) {
				$feed = @file_get_contents('http://www.plymouthherald.co.uk/editor.rss');
				$feed = str_replace('<media:', '<', $feed);
				$feed = str_replace('</media:', '</', $feed);
				file_put_contents('../cache/newsrss',$feed);
			}else{
				$feed=file_get_contents('../cache/newsrss');
			}

			$rss = simplexml_load_string($feed);
			$count=0;
			foreach ($rss->channel->item as $item) {
				if(isset($item->thumbnail) and check_message($item->title)==0) {// and check_message($item->description)==0
					$count++;
					if($count==1) {?>
						<li class="news">
							<ul>
					<? }
					if($count<=3) {?>
						<li>
							<a href="<? echo $item->link;?>" target="_blank" class="article">
								<div class="title"><? echo $item->title;?></div>
								<div class="image"><img src="<? $attrs=$item->thumbnail->attributes();echo $attrs['url'];?>" /></div>
								<div class="desc"><? echo substr_words(trim(strip_tags($item->description)),150);?></div>
							</a>
						</li>
						<? 
					}
					if($count==3) {?>

						</ul>
					</li>
					<? break;
					}
				}
			}
			if($count==2 or $count==1) {?>

				</ul>
			</li>
			<?}
		}
		$post['link']='/'.$post['type'].'/'.$post['slug'];
		$embed=false;
		if(preg_match($url_regex,htmlspecialchars($post['message']),$m)) {
			$url=$m[0];
			$embed=embedly_request($url);
		}
		
		?>
	<li id="update_<? echo $post['update_id'];?>">
		<div class="pic"><a href="<? echo $post['link'];?>"><img src="<? echo get_image_url($post['picture'],'q');?>" /></a></div>
		<div class="name"><a href="<? echo $post['link'];?>"><? echo $post['name'];?></a></div>
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
		<div class="stamp"><? echo format_date($post['d']);?>&nbsp;&nbsp;&nbsp;<a href="javascript:;" onclick="report_content('update_<? echo $post['update_id'];?>');">Report</a><? if($post['user_id']==user_id()) {?>&nbsp;&nbsp;&nbsp;<a href="javascript:;" onclick="remove_post('update_<? echo $post['update_id'];?>');">Delete</a><? }?></div>
	</li>
	<? }?>
</ul>
<? if($c==10) {?>
<a href="" class="morelink">See More</a>
<? }?>
<?php
$template->footer();
?>