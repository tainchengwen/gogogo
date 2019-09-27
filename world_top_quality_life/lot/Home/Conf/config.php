<?php
$db = include './config.inc.php';
$myConf=array(
	'TMPL_CACHE_ON' => false,
	'URL_CASE_INSENSITIVE' =>true,
	'DEFAULT_THEME'=>'dzp',
	'wx_appId'=> 'wx0a3486871f810ecf',
	'wx_appSecret'=> 'ad5c877872c0af177b7c2622fa765207',
	'wx_mchid'=> '1525999471',
	'wx_secret_key'=> ''
);
return array_merge($db,$myConf);
?>