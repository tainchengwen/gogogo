<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE HTML>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta content="width=device-width, minimum-scale=1,initial-scale=1, maximum-scale=1, user-scalable=1;" id="viewport" name="viewport" />
<title>奖品设置</title>
<link href="__TMPL__Skin/Css/css.css" rel="stylesheet" type="text/css"/>
</head>

<body>

<form action="<?php echo U('stock_save');?>" method="post" name="form1">

<table width="98%"  border="0" align="center" cellpadding="0" cellspacing="0">
<tr>
  <td height="35" align="right" width="20%">奖品名称：</td>
  <td bgcolor="#FFFFFF">
 <select name="nPrizeId">
 	<?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><option value="<?php echo ($vo["id"]); ?>"><?php echo ($vo["title2"]); echo ($vo["title"]); ?></option><?php endforeach; endif; else: echo "" ;endif; ?>
  </select>
  </td>
</tr>
<tr>
  <td height="35" align="right">奖品数量：</td>
  <td bgcolor="#FFFFFF"><input name="num" type="text" id="num" size="10" maxlength="10" value=""></td>
</tr>
<tr>
  <td height="50" align="left">&nbsp;</td>
  <td><input type="submit" name="Submit" value=" 添 加 " class="btn">　
	<input type="button" value=" 返 回 " onClick="window.location='<?php echo U('index');?>'" name="button" class="btn"></td>
</tr>
</table>
</form>
</body>
</html>