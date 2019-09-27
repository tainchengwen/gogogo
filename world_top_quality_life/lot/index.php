<?php
/*$uname=$_COOKIE['CK_USER_NAME'];
if(empty($uname)){
	$uname=uniqid();
	setcookie("CK_USER_NAME", $uname, time()+3600*24*365,'/');
}
session_start();
$_SESSION['USER']['NAME']=$uname;*/
define('APP_NAME','Home');
define('APP_PATH','./Home/');
define('APP_DEBUG',true);
require 'ThinkPHP/ThinkPHP.php';
?>