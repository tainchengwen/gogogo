<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE HTML>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta content="width=device-width, minimum-scale=1,initial-scale=1, maximum-scale=1, user-scalable=1;" id="viewport" name="viewport" />
<title>抽奖记录</title>
<link href="__TMPL__Skin/Css/css.css" rel="stylesheet" type="text/css"/>
<script src="__PUBLIC__/js/jquery-1.7.2.min.js"></script>
<script src="__PUBLIC__/js/admin.js"></script>
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
    <td width="15%">用户名</td>
    <td width="15%">头像</td>
    <td>中奖时间</td>
    <td>奖品</td>
    <td>信息</td>
    <td>操作</td>
  </tr>
<?php if(is_array($lottery)): $i = 0; $__LIST__ = $lottery;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><tr>
    <td align="center" height="30" bgcolor="#FFFFFF"><?php echo ($vo["username"]); ?></td>
    <td align="center" bgcolor="#FFFFFF"><img src="<?php echo ($vo["icon_path"]); ?>" width="50"></td>
    <td align="center" bgcolor="#FFFFFF"><?php echo ($vo["subtime"]); ?></td>
    <td align="center" bgcolor="#FFFFFF"><?php echo ($vo["jiangPin"]); if($vo["nStatus"] == 1): ?><br><span style="color:#FF0000">已兑奖</span><?php endif; ?></td>
    <td align="center" bgcolor="#FFFFFF"><?php echo ($vo["sname"]); ?>,<?php echo ($vo["mobile"]); ?>,<?php echo ($vo["cjm"]); ?>,<?php echo ($vo["address"]); ?>,<?php echo ($vo["remarks"]); ?></td>
    <td align="center" bgcolor="#FFFFFF">
	<a href="<?php echo U('edit?id='.$vo['id'].'');?>" class="btn2 edit"><span>兑</span></a> 
	<a href="<?php echo U('del?id='.$vo['id'].'');?>" class="btn2 del" onClick="return confirmFuc()"><span>删</span></a>
	</td>
  </tr><?php endforeach; endif; else: echo "" ;endif; ?>
</table>
<table width="98%" align="center" cellspacing="0">
  <tr>
    <td width="8%" height="31" align="left"></td>
    <td width="92%" align="right"><?php echo ($PageTurn); ?></td>
  </tr>
</table>
</body>
</html>