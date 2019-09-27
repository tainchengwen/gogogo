<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta content="width=device-width, minimum-scale=1,initial-scale=1, maximum-scale=1, user-scalable=1;" id="viewport" name="viewport" />
<link href="__TMPL__Skin/Css/css.css" rel="stylesheet" type="text/css"/>
<title>密码修改</title>
</head>

<body>
<form method="post" action="<?php echo U('save');?>">
<table border="0" width="98%" cellspacing="0" cellpadding="0">
    <tr>
      <td height="35" width="20%" align="right">用户名：</td>
      <td width="80%" align="left"><input type="text" name="admin_name" size="20" readonly="readonly" value="<?php echo ($user["admin_name"]); ?>"/></td>
    </tr>
    <tr>
      <td height="35" align="right">密　码：</td>
      <td align="left"><input type="text" name="admin_pass" size="20"/></td>
    </tr>
    <tr>
      <td></td>
      <td height="29" align="left" valign="bottom"><input type="submit" value=" 修 改 " name="B1" class="btn"/>　
          <input type="reset" name="Submit" value=" 重 置 " class="btn"/>
      </td>
    </tr>
</table>
</form>
</body>
</html>