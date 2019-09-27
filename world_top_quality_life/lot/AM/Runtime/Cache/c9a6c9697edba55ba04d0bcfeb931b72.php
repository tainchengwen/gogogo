<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta content="width=device-width, minimum-scale=1,initial-scale=1, maximum-scale=1, user-scalable=1;" id="viewport" name="viewport" />
<script src="__PUBLIC__/js/jquery-1.7.2.min.js" language="javascript"></script> 
<script src="__PUBLIC__/js/admin.js" language="javascript"></script> 
<title></title>
<link href="__TMPL__Skin/Css/css.css" rel="stylesheet" type="text/css"/>
</head>
<body>
<table width="98%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td align="center" height="40">
    <form action="<?php echo U('index');?>" method="post">
    用户名：<input name="username" type="text" size="20" value="<?php echo ($search['username']); ?>"> 
    <input type="submit" value="查询" class="btn"> 
    <input type="button" class="btn" value="导出Excel" onClick="window.location='<?php echo U("toExcel");?>'">
    <input type="button" value="一键清空" class="btn" style=" float:right;" onClick="if(confirmFuc()){window.location='<?php echo U("lot_del_all"); ?>'}"> 
    
    </form></td>
  </tr>
</table>
<table width="98%" border="0" align="center" cellpadding="0" cellspacing="1" bgcolor="#dddddd" class="tbl">
  <tr class="tbl_tt">
    <td>ID</td>
    <td>头像</td>
    <td>用户名</td>
    <td>性别</td>
    <td>省市</td>
    <td>剩余次数</td>
    <td>注册时间</td> 
    <td>操作</td>
  </tr>
<?php if(is_array($user)): $i = 0; $__LIST__ = $user;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><tr>
    <td  height="30" align="center" bgcolor="#FFFFFF"><?php echo ($vo["id"]); ?></td>
    <td align="center" bgcolor="#FFFFFF"><img src="<?php echo ($vo["icon_path"]); ?>" width="50"></td>
    <td align="center" bgcolor="#FFFFFF"><?php echo ($vo["username"]); ?></td>
    <td align="center" bgcolor="#FFFFFF"><?php if($vo['nSex'] == 1): ?>男<?php else: ?>女<?php endif; ?></td>
    <td align="center" bgcolor="#FFFFFF"><?php echo ($vo["province"]); echo ($vo["city"]); ?></td>
    <td align="center" bgcolor="#FFFFFF"><?php echo ($vo["fireNum"]); ?></td>
     <td align="center" bgcolor="#FFFFFF"><?php echo ($vo["subtime"]); ?></td>
    <td align="center" bgcolor="#FFFFFF">
    <a href="<?php echo U('User/edit?id='.$vo['id']);?>" class="btn2 edit"><span>编辑</span></a>
	<a href="<?php echo U('User/del?id='.$vo['id']);?>" class="btn2 del" onClick="return confirmFuc()"><span>删除</span></a>
	</td>
  </tr><?php endforeach; endif; else: echo "" ;endif; ?>
</table>
<table width="98%" align="center" cellspacing="0">
  <tr>
    <td align="right" class="pageturn"><?php echo ($PageTurn); ?></td>
  </tr>
</table>
</body>
</html>