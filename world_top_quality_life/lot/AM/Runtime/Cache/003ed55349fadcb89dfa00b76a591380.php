<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE HTML>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta content="width=device-width, minimum-scale=1,initial-scale=1, maximum-scale=1, user-scalable=1;" id="viewport" name="viewport" />
<title>奖品设置</title>
<link href="__TMPL__Skin/Css/css.css" rel="stylesheet" type="text/css"/>
</head>
<body>
<table width="98%" border="0" cellspacing="0" cellpadding="0" align="center">
  <tr>
    <td align="right" height="40">
    
    <input type="button" class="btn" value="添加奖项" onClick="window.location='<?php echo U('edit');?>'">
    <input type="button" class="btn" value="添加奖品数量" onClick="window.location='<?php echo U('stock');?>'">
    <input type="button" class="btn" value="删除奖品数量" onClick="window.location='<?php echo U('stock_del');?>'">
    </td>
  </tr>
</table>
<table width="98%" border="0" align="center" cellpadding="0" cellspacing="1" bgcolor="#dddddd" class="tbl">
  <tr class="tbl_tt">
    <td width="20%">等级</td>
    <td width="20%">奖品名称</td>
    <td width="20%">奖品数量</td>
    <td>操作</td>
  </tr>
<?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><tr>
    <td  height="30" align="center" bgcolor="#FFFFFF"><?php echo ($vo["title2"]); ?></td>
    <td align="center" bgcolor="#FFFFFF"><?php echo ($vo["title"]); ?></td>
    <td align="center" bgcolor="#FFFFFF"><?php echo ($vo["num1"]); ?>/<?php echo ($vo["num2"]); ?></td>
    <td align="center" bgcolor="#FFFFFF">
    <a href="<?php echo U('edit?id='.$vo['id'].'');?>" class="btn2 edit"><span>编辑</span></a>
	<a href="<?php echo U('del?id='.$vo['id'].'');?>" class="btn2 del" onClick="return confirmFuc()"><span>删除</span></a>
	</td>
  </tr><?php endforeach; endif; else: echo "" ;endif; ?>
</table>

</body>
</html>