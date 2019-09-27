<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE HTML>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta content="width=device-width, minimum-scale=1,initial-scale=1, maximum-scale=1, user-scalable=1;" id="viewport" name="viewport" />
<title>兑奖操作</title>
<link href="__TMPL__Skin/Css/css.css" rel="stylesheet" type="text/css"/>
</head>
<body>

<form name="form1" method="post" action="<?php echo U('save');?>">

<table width="98%"  border="0" align="center" cellpadding="0" cellspacing="0">
<tr>
  <td height="35" align="right" width="20%">用户名：</td>
  <td bgcolor="#FFFFFF"><?php echo ($lottery["username"]); ?><input name="id" type="hidden" value="<?php echo ($lottery["id"]); ?>"></td>
</tr>
<tr>
  <td height="35" align="right" width="20%">是否兑奖：</td>
  <td bgcolor="#FFFFFF"><input name="nStatus" type="radio" value="0" <?php if($lottery["nStatus"] == 0): ?>checked<?php endif; ?>>   未兑奖 <input name="nStatus" type="radio" value="1" <?php if($lottery["nStatus"] == 1): ?>checked<?php endif; ?>>已兑奖</td>
</tr>
<tr>
  <td height="85" align="right">备注：</td>
  <td bgcolor="#FFFFFF"><textarea name="remarks" cols="60" rows="5" id="remarks" style="width:80%"><?php echo ($lottery["remarks"]); ?></textarea></td>
</tr>

<tr>
  <td height="50" align="left">&nbsp;</td>
  <td><input type="submit" name="Submit" value=" 保 存 " class="btn">　
	<input type="button" value=" 返 回 " onClick="window.location='__URL__'" name="button" class="btn"></td>
</tr>
</table>
</form>


</body>
</html>