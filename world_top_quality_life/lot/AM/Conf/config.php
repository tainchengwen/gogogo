<?php
$db = include './config.inc.php';
$myConf=array(
	'TMPL_CACHE_ON' => false,
	'LANG_SWITCH_ON'=>true,  
	'DEFAULT_LANG'=>'zh-cn', 
	'LANG_LIST'=>'en-us,zh-cn',
	'VAR_LANGUAGE'=>'l',
	'URL_CASE_INSENSITIVE' =>true,
    'URL_MODEL' => 0
);

return array_merge($db,$myConf);
?>