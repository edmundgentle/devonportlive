<?php
include('../includes/template.php');
include('../includes/upload.php');
class qqUploadedFileXhr {
	private $t;
    function save() {    
        $input = fopen("php://input", "r");
		$this->t='../temp/'.generate_string(32);
		$target = fopen($this->t, "w");
        stream_copy_to_stream($input, $target);
		fclose($target);
        fclose($input);
        return $this->t;
    }
	function dispose() {
		unlink($this->t);
	}
    function getName() {
        return $_GET['qqfile'];
    }
    function getSize() {
        if (isset($_SERVER["CONTENT_LENGTH"])){
            return (int)$_SERVER["CONTENT_LENGTH"];            
        } else {
            throw new Exception('Getting content length is not supported.');
        }      
    }   
}
class qqUploadedFileForm {
    function save($path) {
        if(!move_uploaded_file($_FILES['qqfile']['tmp_name'], $path)){
            return false;
        }
        return true;
    }
    function getName() {
        return $_FILES['qqfile']['name'];
    }
    function getSize() {
        return $_FILES['qqfile']['size'];
    }
}

class qqFileUploader {
    private $file;
    function __construct() {        
        $this->checkServerSettings();
        if (isset($_GET['qqfile'])) {
            $this->file = new qqUploadedFileXhr();
        } elseif (isset($_FILES['qqfile'])) {
            $this->file = new qqUploadedFileForm();
        } else {
            $this->file = false; 
        }
    }
    private function checkServerSettings(){        
    }
    private function toBytes($str){
        $val = trim($str);
        $last = strtolower($str[strlen($str)-1]);
        switch($last) {
            case 'g': $val *= 1024;
            case 'm': $val *= 1024;
            case 'k': $val *= 1024;        
        }
        return $val;
    }
    function handleUpload() {
		if (!$this->file){
            return array('error' => 'No files were uploaded.');
        }
        $size = $this->file->getSize();
        if ($size == 0) {
            return array('error' => 'File is empty');
        }
        $pathinfo = pathinfo($this->file->getName());
        $filename = $pathinfo['basename'];
		$op=upload_image(array('tmp_name'=>$this->file->save(),'name'=>$filename,'mime'=>'image/jpeg'), user_id());
		$this->file->dispose();
		return $op;
    }    
}
if(is_logged_in()) {
	$uploader = new qqFileUploader();
	$result = $uploader->handleUpload();
	echo htmlspecialchars(json_encode(array('success'=>true,'id'=>$result,'url'=>'/u/'.substr(sha1($result),0,14).$result.'_q.png')), ENT_NOQUOTES);
}else{
	echo htmlspecialchars(json_encode(array('error'=>'Not logged in')), ENT_NOQUOTES);
}
?>