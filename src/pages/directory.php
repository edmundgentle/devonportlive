<?php
include('../includes/template.php');
$atq='';
$wq=array();
$title='Directory';
if(isset($_GET['t']) and strtolower($_GET['t'])!='search') {
	$type=strtolower($_GET['t']);
	$atq=' AND type=:type';
	$wq[':type']=$type;
	if($type=='business') {
		$title="Business Directory";
	}
	if($type=='service') {
		$title="Services Directory";
	}
	if($type=='community') {
		$title="Community Group Directory";
	}
	if($type=='my' and is_logged_in()) {
		$title="My Pages";
		$query=Database::execute('SELECT page_id, slug, name, type, website, twitter, address, lat, lon, phone, description, picture FROM pages WHERE user_id=:user_id',array(':user_id'=>user_id()));
	}
}
if(isset($_GET['q'])) {
	$term=$_GET['q'];
	if(!isset($type)) {
		$title="Search Results for $term";
	}
	$atq=' AND (name LIKE :term OR description LIKE :term)';
	$wq[':term']='%'.$term.'%';
}
$template->title($title);
$template->header();?>
<h2><? echo $title;?></h2>
<ul class="directory">
<? 
if(!isset($query)) {
	$query=Database::execute('SELECT page_id, slug, name, type, website, twitter, address, lat, lon, phone, description, picture FROM pages WHERE ((type=\'business\' AND paid_until>NOW()) OR type!=\'business\') AND approved=1'.$atq,$wq);
}
$results=$query->fetchAll();
foreach($results as $row) {
	$row['link']='/'.$row['type'].'/'.$row['slug'];?>
	<li>
		<div class="image"><a href="<? echo $row['link'];?>"><img src="<? echo get_image_url($row['picture'],'m');?>" /></a></div>
		<div class="name"><a href="<? echo $row['link'];?>"><? echo htmlspecialchars($row['name']);?></a></div>
		<div class="description"><? echo htmlspecialchars($row['description']);?></div>
		<? if($row['address']) {?>
		<div class="address"><? 
		$addr_part=explode("\n",htmlspecialchars($row['address']));
		foreach($addr_part as $k=>$p) {
			$addr_part[$k]=trim($p);
		}
		
		echo implode(", ",$addr_part);?></div>
		<? }?>
	</li>
<? }
?>
</ul>
<? 
$template->footer();
?>