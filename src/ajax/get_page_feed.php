<?php
include('../includes/template.php');
$template->ajax_start();
if(isset($_GET['id'])) {
	$page_id=(int)$_GET['id'];
	$start=0;
	if(isset($_GET['start'])) {
		$start=(int)$_GET['start'];
	}
	$query=Database::execute('SELECT update_id, message, UNIX_TIMESTAMP(date) AS d, (SELECT COUNT(picture_id) FROM pictures WHERE pictures.update_id=updates.update_id) AS num_pics FROM updates WHERE page_id=:page_id AND flagged=0 ORDER BY date DESC LIMIT '.$start.', 10',array(':page_id'=>$page_id));
	$results=$query->fetchAll();
	$output['success']=true;
	foreach($results as $post) {
		$op=array(
			'id'=>'update_'+$post['update_id'],
			'body'=>preg_replace($url_regex,'<a href="$0" target="_blank">$0</a>',nl2br(htmlspecialchars($post['message'])))
		);
		if(preg_match($url_regex,htmlspecialchars($post['message']),$m)) {
			$url=$m[0];
			$embed=embedly_request($url);
			$op['embed']=array(
				'url'=>$url
			);
			if(isset($embed['thumbnail_url'])) {
				$op['embed']['pic']=$embed['thumbnail_url'];
			}
			if(isset($embed['title'])) {
				$op['embed']['title']=htmlspecialchars($embed['title']);
			}
			if(isset($embed['description'])) {
				$op['embed']['description']=htmlspecialchars($embed['description']);
			}
			if(isset($embed['provider_url'])) {
				$op['embed']['provider']=$embed['provider_url'];
			}
		}
		if($post['num_pics']) {
			$q2=Database::execute('SELECT upload_id FROM pictures WHERE pictures.update_id=:update_id',array(':update_id'=>$post['update_id']));
			$pics=$q2->fetchAll();
			foreach($pics as $pic) {
				$op['pictures'][]=array('m'=>get_image_url($pic['upload_id'],'m'),'l'=>get_image_url($pic['upload_id'],'l'));
			}
		}
		$op['datehtml']=format_date($post['d']);
		$output['data'][]=$op;
	}
}
$template->ajax_end();
?>