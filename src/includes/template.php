<?php
if(!isset($_SERVER['SSL']) or !$_SERVER['SSL']) {
	header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
	exit();
}
define('DL_SALT','K4XKG9gMgkLUSO0UUMDBSx');
date_default_timezone_set('Europe/London');
if (substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) {
	ob_start("ob_gzhandler");
}else{
	ob_start();
}
session_start();
include('database.php');
Database::connect('mysql:host=[DBHOST];dbname=[DBNAME]', '[DBUSERNAME]', '[DBPASSWORD]');
$stripe = array(
  "secret_key"      => "[STRIPE SECRET KEY]",
  "publishable_key" => "[STRIPE PUBLIC KEY]"
);
$google_maps='[GOOGLE MAPS KEY]';
$embedly='[EMBEDLY KEY]';
$url_regex='`(https?:\/\/)([\da-zA-Z\.-]+)\.([a-z\.]{2,6})([\/\w?=&\.-]*)*\/?`';
function alert_admin($message='Something on Devonport Live requires your attention') {
	$email=@file_get_contents('../admins/admin_email.txt');
	if($email) {
		@mail($email,"Alert from Devonport Live",$message."\n\nVisit https://www.devonportlive.com/admin to address this issue","From: Devonport Live <info@devonportlive.com>");
	}
}
function is_admin() {
	if(is_logged_in()) {
		$query=Database::execute('SELECT admin FROM users WHERE user_id=:user_id',array(':user_id'=>user_id()));
		$user=$query->fetch();
		if($user['admin']) {
			return true;
		}
	}
	return false;
}
function embedly_request($url) {
	global $embedly;
	$cache=base64_encode($url);
	if(!file_exists('../cache/'.$cache) or filemtime('../cache/'.$cache)<strtotime("-30 days")) {
		$response=@file_get_contents('https://api.embed.ly/1/oembed?key='.$embedly.'&url='.urlencode($url));
		file_put_contents('../cache/'.$cache,$response);
	}else{
		$response=file_get_contents('../cache/'.$cache);
	}
	if($response) {
		return json_decode($response,true);
	}
}
function substr_words($text,$maxchar,$end='...') {
	if(strlen($text)>$maxchar) {
		$words=explode(" ",$text);
		$output = '';
		$i=0;
		while(1){
			$length = (strlen($output)+strlen($words[$i]));
			if($length>$maxchar){
				break;
			}else{
				$output=$output." ".$words[$i];
				++$i;
			};
		};
	}else{
		$output=$text;
		$end="";
	}
	return $output.$end;
}
function is_logged_in() {
	if(isset($_SESSION['user_id'])) {
		return true;
	}
	return false;
}
function user_id() {
	if(is_logged_in()) {
		return $_SESSION['user_id'];
	}else{
		return null;
	}
}
function check_referrer($domain) {
	if(isset($_SERVER['HTTP_REFERER'])) {
		if(strpos($_SERVER['HTTP_REFERER'],$domain)) {
			return true;
		}
	}
	return false;
}
function redirect($url='/') {
	ob_end_clean();
	header("Location: $url");
	exit();
}
//this is the spam filter
function check_message($message) {
	global $badwords;
	if(!isset($badwords)) {
		include('offensive.php');
	}
	$regex='('.implode('|',$badwords).')';
	if(preg_match('/\b'.$regex.'\b/i',$message)) {
		return 1;
	}
	return 0;
}
function require_login() {
	if(!is_logged_in()) {
		redirect('/login?r='.$_SERVER["REQUEST_URI"]);
	}
}
function format_date($date,$html=true) {
	if($html) {
		return '<abbr class="lat_date" title="'.date('j F Y \a\t H:i',$date).'" data-timestamp="'.$date.'">'.format_date($date,false).'</abbr>';
	}else{
		$old=$date;
		$now=time();
		$diff=$now-$old;
		if($diff<60) {
			return 'A few seconds ago';
		}elseif($diff<3600) {
			if(floor($diff/60)==1) {
				return 'About a minute ago';
			}else{
				return floor($diff/60).' minutes ago';
			}
		}elseif($diff<86400) {
			if(floor($diff/3600)==1) {
				return '1 hour ago';
			}else{
				if(date('d/m/Y',$old)!=date('d/m/Y',$now)) {
					return 'Yesterday at '.date('H:i',$old);
				}else{
					return floor($diff/3600).' hours ago';
				}
			}
		}elseif($diff<604800) {
			if(date('d/m/Y',$old)==date('d/m/Y',strtotime("-1 day",$now))) {
				return 'Yesterday at '.date('H:i',$old);
			}else{
				return date('l \a\t H:i',$old);
			}
		}
		if(date('Y',$old)!=date('Y',$now)) {
			return date('j F Y \a\t H:i',$old);
		}else{
			return date('j F \a\t H:i',$old);
		}
	}
}
function generate_string($length=32) {
	$chars='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890';
	$a=$chars{rand(0, 61)};
	for($i=1;$i<$length;$i=strlen($a)){
		$r=$chars{rand(0, 61)};
		if($r!=$a{$i - 1}) $a.=$r;
	}
	return $a;
}
function get_image_url($id,$size='q') {
	$base=substr(sha1($id),0,14).$id;
	if($size!='o') {
		$base.='_'.$size.'.png';
		if(file_exists('../uploads/'.substr($base,0,2).'/'.substr($base,2))) {
			return '/u/'.$base;
		}
	}
	//find the original image :S
	$i=(substr(sha1($id),2,12).$id);
	if ($handle = @opendir('../uploads/'.substr($base,0,2))) {
		while (false !== ($entry = readdir($handle))) {
			if(substr($entry,0,strpos($entry,'.'))==$i) {
				closedir($handle);
				return '/u/'.substr($base,0,2).$entry;
			}
	    }
	    closedir($handle);
	}
	return '/images/noimg.png';
}
class Template {
	private $site_name="Devonport Live";
	private $app_id="";
	private $title='';
	private $description='';
	private $keywords='';
	private $image_url='/images/logo.png';
	private $url='';
	private $type='';
	function __construct() {
	}
	function ajax_start() {
		global $output;
		$output=array('success'=>false);
		if(!check_referrer('devonportlive.com')) {
			$this->ajax_end();
		}
	}
	function ajax_end() {
		global $output;
		echo json_encode($output);
		exit();
	}
	function title($title) {
		$this->title=$title;
		return true;
	}
	function description($description) {
		$this->description=$description;
		return true;
	}
	function keywords($keywords) {
		$this->keywords=$keywords;
		return true;
	}
	function image($image_url) {
		$this->image_url=$image_url;
		return true;
	}
	function url($url) {
		$this->url=$url;
		return true;
	}
	function type($type) {
		$this->type=$type;
		return true;
	}
	function header($echo=true) {
		if(strlen($this->title)>0) {
			$pagetitle="{$this->title} | {$this->site_name}";
		}else{
			$pagetitle=$this->site_name;
		}
		$output='';
		$output='<!DOCTYPE html>
		<html lang="en">
			<head>
				<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
			if(strlen($this->description)>0) {
				$output.='<meta name="Description" content="'.$this->description.'" />
				<meta property="og:description" content="'.$this->description.'" />
				<meta name="twitter:card" content="summary">
				<meta name="twitter:description" content="'.$this->description.'">';
			}
			if(strlen($this->keywords)>0) {
				$output.='<meta name="Keywords" content="'.$this->keywords.'" />';
			}
			if(strlen($this->title)>0) {
				$output.='<meta property="og:title" content="'.$this->title.'" />
				<meta name="twitter:title" content="'.$this->title.'">';
			}
			if(strlen($this->type)>0) {
				$output.='<meta property="og:type" content="'.$this->type.'" />';
			}
			if(strlen($this->image_url)>0) {
				$output.='<meta property="og:image" content="'.$this->image_url.'" />
				<meta name="twitter:image" content="'.$this->image_url.'">
				<meta itemprop="image" content="'.$this->image_url.'">';
			}
			if(strlen($this->url)>0) {
				$output.='<meta property="og:url" content="'.$this->url.'" />
				<meta name="twitter:url" content="'.$this->url.'">';
			}
			$output.='<title>'.$pagetitle.'</title>
				<meta property="og:site_name" content="'.$this->site_name.'" />
				<link rel="stylesheet" type="text/css" media="all" href="/style.css" />
				<script type="text/javascript" src="/jquery.js"></script>
				<script type="text/javascript" src="/apprise.js"></script>
				<script type="text/javascript" src="/facebox.js"></script>
			</head>
			<body>
				<div class="header">
					<div class="inner">
						<h1><a href="/">Devonport Live</a></h1>
						<ul class="main_menu">
							<li><a href="/">Home</a></li>
							<li><a href="/business">Businesses</a></li>
							<li><a href="/service">Services</a></li>
							<li><a href="/community">Community</a></li>
							<li class="mobileinlineb"><a href="/events">Events</a></li>';
							if(is_logged_in()) {
								$output.='<li><a href="/logout">Logout</a></li>';
							}else{
								$output.='<li><a href="/login">Login</a></li>';
							}
						$output.='</ul>
					</div>
				</div>
				<div class="main inner">
					<div class="sidebar">
						<div class="pad">
							<form class="search" method="get" action="/search">
								<div class="search_box">
									<input type="text" placeholder="Search Devonport Live..." name="q" value="';
									if(isset($_GET['q'])) {
										$output.=$_GET['q'];
									}
									$output.='" />
									<input type="submit" value="Search" />
								</div>
							</form>
							<div class="sidebar_content">';
							global $isadmin;
							$isadmin=false;
							if(is_logged_in()) {
								$query=Database::execute('SELECT name, admin FROM users WHERE user_id=:user_id',array(':user_id'=>user_id()));
								$user=$query->fetch();
								$output.='<div class="para">
										<p><strong>Welcome '.$user['name'].'!</strong></p>
										<p><a href="/account">Manage Account</a> | <a href="/logout">Logout</a></p>';
										if($user['admin']) {
											$isadmin=true;
											$output.='<p><a href="/admin">Admin Panel</a></p>';
										}
								$output.='
									</div>';
							}else{
								$output.='<div class="para">
										<p><strong>What is Devonport Live?</strong></p>
										<p>Devonport Live is a digital hub for Devonport, Plymouth. Residents can find out information about events going on in the local community, information about businesses and services and engage in community-led groups.</p>
									</div>';
							}
							$output.='
								<div class="para">
									<p><strong>Upcoming Events</strong></p>
								</div>';
								$query=Database::execute('SELECT name, slug, start_time, end_time, location FROM events WHERE flagged=0 AND start_time>=NOW() AND recurring_dow IS NULL ORDER BY start_time ASC LIMIT 3');
								$events=$query->fetchAll();
								$output.='<ul class="newsfeed">';
								foreach($events as $ev) {
										$output.='<li>
											<div class="pic"><div class="calendar"><div class="month">'.date("M",strtotime($ev['start_time'])).'</div><div class="day">'.date("j",strtotime($ev['start_time'])).'</div></div></div>
											<div class="name"><a href="/events/'.$ev['slug'].'">'.$ev['name'].'</a></div>
											<div class="tagline">';
											if(date("Y-m-d",strtotime($ev['start_time']))==date("Y-m-d",strtotime($ev['end_time']))) {
												$output.=date("g:ia",strtotime($ev['start_time'])).'-'.date("g:ia",strtotime($ev['end_time']));
											}else{
												$output.=date('jS M \a\t g:ia',strtotime($ev['start_time'])).' - '.date('jS M \a\t g:ia',strtotime($ev['end_time']));
											}
											$output.=' at '.$ev['location'].'</div>
										</li>';
								}
								$output.='</ul>
									<div class="block_button"><a href="/events">See all Events</a></div>';
									if(is_logged_in()) {
										$output.='<div class="para">
										<p><strong>Got a business or local organisation?</strong></p>
										<div class="block_button"><a href="/add">Create a Page</a></div>
										<div class="block_button"><a href="/mypages">See my Pages</a></div>
										</div>';
									}
									$output.='
							</div>
						</div>
					</div>
					<div class="maincontent">
						<div class="pad">';
		if($echo) {
			echo $output;
		}else{
			return $output;
		}
	}
	function footer($echo=true) {
		$output='</div>
	</div>
</div>
<div class="footer">
	<div class="inner">
		<ul>
			<li>&copy; Devonport Live '.date('Y').'. All Rights Reserved.</li>
			<li><a href="/about">About</a></li>
			<li><a href="/about/terms">Terms of Service</a></li>
			<li><a href="/about/contact">Contact Us</a></li>
	</div>
</div>
</body>
</html>';
		if($echo) {
			echo $output;
		}else{
			return $output;
		}
	}
}
$template=new Template();
?>