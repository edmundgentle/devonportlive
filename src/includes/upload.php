<?php
function upload_image($file_ref, $user_id) {
	if($info=getimagesize($file_ref['tmp_name'])) {
		if(substr($info['mime'],0,6)=='image/') {
			$query=Database::execute('INSERT INTO uploads (user_id) VALUES (:user_id) ',array(':user_id'=>$user_id));
			$id=Database::last_insert_id();
			if($id) {
				$response=upload($file_ref['tmp_name'],$file_ref['name'],$id);
				if(isset($response['name'])) {
					return $id;
				}
			}
		}
	}
	return false;
}
function upload($file,$name=false, $int) {
	if(!$name) {
		$name=$file;
	}
	$xt=explode('.',$name);
	$ext=strtolower(end($xt));
	$image=false;
	do {
		$fn=substr(sha1($int),0,14).$int;
		$filename=$fn.'.'.$ext;
		$path=file_path($filename);
	}while(file_exists($path));
	create_folder(substr($path,0,strrpos($path,'/')));
	if(copy($file,$path)) {
		if($info=getimagesize($path)) {
			if(substr($info['mime'],0,6)=='image/') {
				$short_type=substr($info['mime'],6);
				$typemaps=array(
					'jpeg'=>'ImageCreateFromJPEG',
					'pjpeg'=>'ImageCreateFromJPEG',
					'png'=>'ImageCreateFromPNG',
					'bmp'=>'ImageCreateFromBMP',
					'x-windows-bmp'=>'ImageCreateFromBMP',
					'vnd.wap.wbmp'=>'ImageCreateFromWBMP',
					'gif'=>'ImageCreateFromGIF',
					'x-xbitmap'=>'ImageCreateFromXBM',
					'x-xbm'=>'ImageCreateFromXBM',
					'xbm'=>'ImageCreateFromXBM',
				);
				if(isset($typemaps[$short_type])) {
					$image=true;
					$func=$typemaps[$short_type];
					if($info[0]>800 or $info[1]>800) {
						if($info[0]>$info[1]) {
							$dest_imagex=800;
							$dest_imagey=($info[1]/$info[0])*800;
						}else{
							$dest_imagey=800;
							$dest_imagex=($info[0]/$info[1])*800;
						}
						$source_image = $func($path);
						$dest_image = imagecreatetruecolor($dest_imagex, $dest_imagey);
						$white = imagecolorallocate($dest_image, 255, 255, 255);
						imagefill($dest_image, 0, 0, $white);
						imagecopyresampled($dest_image, $source_image, 0, 0, 0, 0, $dest_imagex, $dest_imagey, imagesx($source_image), imagesy($source_image));
						imagepng($dest_image,file_path($fn.'_l.png'),9);
					}
					if($info[0]>300 or $info[1]>300) {
						if($info[0]>$info[1]) {
							$dest_imagex=300;
							$dest_imagey=($info[1]/$info[0])*300;
						}else{
							$dest_imagey=300;
							$dest_imagex=($info[0]/$info[1])*300;
						}
						$source_image = $func($path);
						$dest_image = imagecreatetruecolor($dest_imagex, $dest_imagey);
						$white = imagecolorallocate($dest_image, 255, 255, 255);
						imagefill($dest_image, 0, 0, $white);
						imagecopyresampled($dest_image, $source_image, 0, 0, 0, 0, $dest_imagex, $dest_imagey, imagesx($source_image), imagesy($source_image));
						imagepng($dest_image,file_path($fn.'_m.png'),9);
					}
					make_thumbnail($path,100,100,file_path($fn.'_q.png'));
				}
			}
		}
		return array('name'=>$filename,'path'=>$path,'image'=>$image);
	}else{
		return false;
	}
}
function file_path($filename) {
	return '../uploads/'.substr($filename,0,2).'/'.substr($filename,2);
}
function make_thumbnail($img_src,$target_width,$target_height,$loc=false) {
	$info = getimagesize($img_src);
	$factor = $target_width / $info[0];
	if($target_height<($factor * $info[1])) {
		$targetheight=$factor*$info[1];
		$targetwidth=$target_width;
		$yoff=($targetheight-$target_height)/2;
		$xoff=0;
	}else{
		$factor = $target_height / $info[1];
		$targetheight=$target_height;
		$targetwidth=$factor*$info[0];
		$xoff=($targetwidth-$target_width)/2;
		$yoff=0;
	}
	$mime = $info['mime'];
	$type = substr(strrchr($mime, '/'), 1);
	$typemaps=array(
		'jpeg'=>'ImageCreateFromJPEG',
		'pjpeg'=>'ImageCreateFromJPEG',
		'png'=>'ImageCreateFromPNG',
		'bmp'=>'ImageCreateFromBMP',
		'x-windows-bmp'=>'ImageCreateFromBMP',
		'vnd.wap.wbmp'=>'ImageCreateFromWBMP',
		'gif'=>'ImageCreateFromGIF',
		'x-xbitmap'=>'ImageCreateFromXBM',
		'x-xbm'=>'ImageCreateFromXBM',
		'xbm'=>'ImageCreateFromXBM',
	);
	$func=$typemaps['jpeg'];
	if(isset($typemaps[$type])) $func=$typemaps[$type];
	$thumb=imagecreatetruecolor($targetwidth,$targetheight);
	$white = imagecolorallocate($thumb, 255, 255, 255);
	imagefill($thumb, 0, 0, $white);
	$source = $func($img_src);
    imagecopyresampled($thumb,$source,0,0,0,0,$targetwidth,$targetheight,$info[0],$info[1]);
    $dest = imagecreatetruecolor($target_width,$target_height);
	imagecopy($dest,$thumb, 0, 0, $xoff,$yoff, $target_width, $target_height);
	if($loc) {
		imagepng($dest,$loc,9);
	}else{
		imagepng($dest);
	}
}
function create_folder($str) {
	$folders=explode('/',rtrim($str,'/'));
	$loc='';
	foreach($folders as $fol) {
		$loc.=$fol.'/';
		if(!file_exists($loc) and $loc!='..') {
			mkdir($loc);
			@chmod($loc,0777);
		}
	}
	return true;
}
?>