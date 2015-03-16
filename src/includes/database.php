<?php
class Database {
	private static $info='';
	private static $username='';
	private static $password='';
	private static $db=null;
	
	public static function connect($info, $username, $password) {
		self::$info=$info;
		self::$username=$username;
		self::$password=$password;
	}
	private static function dbconn() {
		if(is_null(self::$db)) {
			self::$db = new PDO(self::$info, self::$username, self::$password);
		}
	}
	public static function execute($query,$vals=null) {
		self::dbconn();
		$query = self::$db->prepare($query);
		if($query->execute($vals)) {
			return $query;
		}else{
			return false;
		}
	}
	public static function last_insert_id() {
		self::dbconn();
		return self::$db->lastInsertId();
	}
}
?>