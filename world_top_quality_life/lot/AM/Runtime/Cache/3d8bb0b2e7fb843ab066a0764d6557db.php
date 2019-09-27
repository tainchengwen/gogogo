<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE HTML>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta content="width=device-width, minimum-scale=1,initial-scale=1, maximum-scale=1, user-scalable=1;" id="viewport" name="viewport" />
<title>奖品设置</title>
<link href="__TMPL__Skin/Css/css.css" rel="stylesheet" type="text/css"/>
<script src="__PUBLIC__/js/jquery-1.7.2.min.js"></script>
<style>
.pic-box{ border:1px solid #ddd; height:auto; min-height:100px; width:150px; background:#fff; position:relative; cursor:pointer; overflow:hidden}
.pic-box img{ width:100%; display:block;}
.pic-box span{ display:block; text-align:center; width:80%; height:30px; line-height:30px; background:rgba(0,0,0,0.5); color:#fff; position:absolute; left:10%;top:50%; margin-top:-15px; border-radius:3px;}
</style>
</head>

<body>

<form action="<?php echo U('save');?>" method="post" enctype="multipart/form-data" name="form1">

<table width="98%"  border="0" align="center" cellpadding="0" cellspacing="0">
<tr>
  <td height="35" align="right" width="20%">等级名称：</td>
  <td bgcolor="#FFFFFF"><input name="id" type="hidden" value="<?php echo ($rs["id"]); ?>"><input name="title2" type="text" id="title2" size="30" maxlength="30" value="<?php echo ($rs["title2"]); ?>"></td>
</tr>

<tr>
  <td height="35" align="right">奖品名称：</td>
  <td bgcolor="#FFFFFF"><input name="title" type="text" id="title" size="30" maxlength="30" value="<?php echo ($rs["title"]); ?>"></td>
</tr>

<tr>
  <td height="35" align="right">奖品标志：</td>
  <td bgcolor="#FFFFFF"><select name="nTag" id="nTag">
    <option value="0">实物奖品</option>
    <option value="1" <?php if($rs["nTag"] == 1): ?>selected<?php endif; ?>>非奖品</option>
    <!--<option value="2" <?php if($rs["nTag"] == 1): ?>selected<?php endif; ?>>到账红包</option>-->
  </select>
  提示：非奖品例如谢谢参与
  </td>
</tr>
<!--<tr>
  <td height="35" align="right">对应金额：</td>
  <td bgcolor="#FFFFFF"><input name="nMoney" type="text" id="nMoney" size="6" maxlength="6" value="<?php echo ($rs["nMoney"]); ?>"> 元 提示：奖品为“到账红包”时需填写此项</td>
</tr>-->
<tr>
  <td height="35" align="right">奖品图片：</td>
  <td bgcolor="#FFFFFF">
      <div class="pic-box">
      	<span>点击上传图片</span>
        <p>
        <?php if(!empty($rs['pic'])): ?><img src="__PUBLIC__/<?php echo ($rs["pic"]); ?>"><?php endif; ?>
        </p>
      </div>
      <input name="pic" type="text" id="pic" value="<?php echo ($rs["pic"]); ?>" size="50" maxlength="100"style="display:none"/>
      　
      <input type="file" name="fl_pic" id="fl_pic" style="display:none"></td>
</tr>
<tr>
  <td height="35" align="right">奖品排序：</td>
  <td bgcolor="#FFFFFF"><input name="nOrder" type="text" id="nOrder" size="10" maxlength="10" value="<?php echo ($rs["nOrder"]); ?>"></td>
</tr>
<tr>
  <td height="35" align="right">奖品说明：</td>
  <td bgcolor="#FFFFFF"><input name="txt" type="text" id="txt" size="50" maxlength="200" value="<?php echo ($rs["txt"]); ?>"></td>
</tr>
<tr>
  <td height="50" align="left">&nbsp;</td>
  <td><input type="submit" name="Submit" value=" 保 存 " class="btn">　
	<input type="button" value=" 返 回 " onClick="window.location='<?php echo U('index');?>'" name="button" class="btn"></td>
</tr>
</table>
</form>
<script>
//建立一個可存取到該file的url
function getObjectURL(file) {
	var url = null ;
	if (window.createObjectURL!=undefined) { // basic
		url = window.createObjectURL(file) ;
	} else if (window.URL!=undefined) { // mozilla(firefox)
		url = window.URL.createObjectURL(file) ;
	} else if (window.webkitURL!=undefined) { // webkit or chrome
		url = window.webkitURL.createObjectURL(file) ;
	}
	return url ;
}
$(function(){
	$('.pic-box').click(function(){
		$('#fl_pic').click();
	});
	$('#fl_pic').change(function(){
		 var s = getObjectURL($('input[name="fl_pic"]')[0].files[0]); 
		 $('.pic-box p').html('<img src="' + s + '">');
	});
});
</script>

</body>
</html>