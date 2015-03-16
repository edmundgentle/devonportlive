<?php
include('../includes/template.php');
$template->ajax_start();
$start=0;
if(isset($_GET['start'])) {
	$start=(int)$_GET['start'];
}
$query=Database::execute('SELECT updates.update_id, message, UNIX_TIMESTAMP(date) AS d, (SELECT COUNT(picture_id) FROM pictures WHERE pictures.update_id=updates.update_id) AS num_pics, pages.page_id, name, slug, type, picture, user_id FROM updates, pages WHERE flagged=0 AND updates.page_id=pages.page_id ORDER BY date DESC LIMIT '.$start.', 10');
$results=$query->fetchAll();
$output['success']=true;
foreach($results as $post) {
	$op=array(
		'id'=>'update_'+$post['update_id'],
		'body'=>preg_replace($url_regex,'<a href="$0" target="_blank">$0</a>',nl2br(htmlspecialchars($post['message']))),
		'from'=>array('link'=>'/'.$post['type'].'/'.$post['slug'],'name'=>$post['name'],'picture'=>get_image_url($post['picture'],'q'))
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
	$op['can_edit']=($post['user_id']==user_id());
	$output['data'][]=$op;
}
$template->ajax_end();
?>