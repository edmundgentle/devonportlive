<?php
include('../includes/template.php');
$template->ajax_start();
if(is_admin() and isset($_POST['id'])) {
	$id=(int)$_POST['id'];
	if($id) {
		$query=Database::execute('UPDATE pages SET approved=1 WHERE page_id=:page_id',array(':page_id'=>$id));
		if($query->rowCount()==1) {
			$query=Database::execute('SELECT users.email, pages.name, slug, type FROM users, pages WHERE pages.page_id=:page_id AND pages.user_id=users.user_id',array(':page_id'=>$id));
			if($query->rowCount()==1) {
				$pg=$query->fetch();
				@mail($pg['email'],"Your page has been approved!","Your page {$pg['name']} has now been approved. You can visit it at https://www.devonportlive.com/{$pg['type']}/{$pg['slug']}\n\nThanks,\n\nDevonport Live","From: Devonport Live <info@devonportlive.com>");
			}
			$output['success']=true;
		}
	}
}
$template->ajax_end();
?>