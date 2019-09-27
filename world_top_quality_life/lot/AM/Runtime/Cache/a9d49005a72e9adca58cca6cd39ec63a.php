<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta content="width=device-width, minimum-scale=1,initial-scale=1, maximum-scale=1, user-scalable=1;" id="viewport" name="viewport" />
<link href="__TMPL__Skin/Css/css.css" rel="stylesheet" type="text/css"/>
<script src="__PUBLIC__/js/jquery-1.7.2.min.js"></script>
<style>
.pic-box{ border:1px solid #ddd; height:auto; min-height:100px; width:150px; background:#fff; position:relative; cursor:pointer; overflow:hidden}
.pic-box img{ width:100%; display:block;}
.pic-box span{ display:block; text-align:center; width:80%; height:30px; line-height:30px; background:rgba(0,0,0,0.5); color:#fff; position:absolute; left:10%;top:50%; margin-top:-15px; border-radius:3px;}
</style>
<title>无标题文档</title>
</head>
<body>
<form action="<?php echo U('save');?>" method="post" name="form1" id="form1" enctype="multipart/form-data">
<table width="98%" border="0" align="center" cellpadding="0" cellspacing="0" id="tbl0">
    <tr>
      <td width="20%" height="30" align="right">活动主题：</td>
      <td width="80%"><input name="web_name" type="text" id="web_name" value="<?php echo ($config["web_name"]); ?>" size="50" maxlength="50" style=" width:90%; max-width:500px"/></td>
    </tr>
    <tr>
      <td width="20%" height="30" align="right">初始抽奖人：</td>
      <td width="80%"><input name="initNum" type="text" id="initNum" value="<?php echo ($config["initNum"]); ?>" size="10" maxlength="10"/></td>
    </tr>
    <tr>
      <td width="20%" height="30" align="right">活动时间：</td>
      <td width="80%"><input name="startTime" type="text" id="startTime" value="<?php echo ($config["startTime"]); ?>" size="20" maxlength="20"/> 至 <input name="endTime" type="text" id="startTime" value="<?php echo ($config["endTime"]); ?>" size="20" maxlength="20"/>
      <br>时间格式：<?php echo date('Y-m-d H:i:s');?></td>
    </tr>

    <tr>
      <td width="20%" height="30" align="right">兑奖密码：</td>
      <td width="80%"><input name="dj_pswd" type="text" id="dj_pswd" value="<?php echo ($config["dj_pswd"]); ?>" size="20" maxlength="20"/></td>
    </tr>
    <tr>
      <td height="100" align="right">活动规则：</td>
      <td align="left"><textarea name="txt2" cols="100" rows="6" id="txt2" style=" width:90%; max-width:500px"><?php echo ($config["txt2"]); ?></textarea></td>
    </tr>
    <tr>
      <td height="30" align="right">背景图：</td>
      <td bgcolor="#FFFFFF">
          <div class="pic-box">
            <span>点击上传图片</span>
            <p>
            <?php if(!empty($config['bg_url'])): ?><img src="__PUBLIC__/<?php echo ($config["bg_url"]); ?>"><?php endif; ?>
            </p>
          </div>
          <input name="bg_url" type="text" id="bg_url" value="<?php echo ($config["bg_url"]); ?>" size="50" maxlength="100"style="display:none"/>
          　
          <input type="file" name="fl_pic2" id="fl_pic2" style="display:none"><br>提示：不上传，则默认背景图</td>
    </tr>
    <tr>
      <td height="30" align="right">每个微信号：</td>
      <td>
	  <select name="nLotType">
        <option value="0" <?php if($config['nLotType'] == 0): ?>selected<?php endif; ?>>总共</option>
        <option value="1" <?php if($config['nLotType'] == 1): ?>selected<?php endif; ?>>每天</option>
      </select>
      可以抽 <input name="nLotTimes" type="text" id="nLotTimes" value="<?php echo ($config["nLotTimes"]); ?>" size="2" maxlength="2" /> 次
      </td>
    </tr>
    <tr>
      <td height="30" align="right">每次分享增加次数：</td>
      <td align="left"><input name="sharetime" type="text" id="sharetime" value="<?php echo ($config["sharetime"]); ?>" size="2" maxlength="2"/> 次</td>
    </tr>
    <tr>
      <td height="30" align="right">每天分享次数上限：</td>
      <td align="left"><input name="sharenum" type="text" id="sharenum" value="<?php echo ($config["sharenum"]); ?>" size="2" maxlength="2"/> 次</td>
    </tr>
   <tr>
      <td height="30" align="right">强制关注：</td>
      <td align="left"><input name="nNeedGZ" type="radio" value="0" <?php if($config['nNeedGZ'] == 0): ?>checked<?php endif; ?>>不需要关注 &nbsp;&nbsp;&nbsp;&nbsp; <input name="nNeedGZ" type="radio" value="1" <?php if($config['nNeedGZ'] == 1): ?>checked<?php endif; ?>> 需要关注</td>
    </tr>
    <tr>
      <td height="30" align="right">二维码图片：</td>
      <td bgcolor="#FFFFFF">
          <div class="pic-box">
            <span>点击上传图片</span>
            <p>
            <?php if(!empty($config['ewm_pic'])): ?><img src="__PUBLIC__/<?php echo ($config["ewm_pic"]); ?>"><?php endif; ?>
            </p>
          </div>
          <input name="ewm_pic" type="text" id="ewm_pic" value="<?php echo ($config["ewm_pic"]); ?>" size="50" maxlength="100"style="display:none"/>
          　
          <input type="file" name="fl_pic3" id="fl_pic3" style="display:none"></td>
    </tr>
    <tr>
      <td height="30" align="right">背景音乐：</td>
      <td align="left"><input name="nMusicTag" type="radio" value="0" <?php if($config['nMusicTag'] == 0): ?>checked<?php endif; ?>>关闭 &nbsp;&nbsp;&nbsp;&nbsp; <input name="nMusicTag" type="radio" value="1" <?php if($config['nMusicTag'] == 1): ?>checked<?php endif; ?>> 开启</td>
    </tr>
    <tr>
      <td width="20%" height="30" align="right">背景音乐文件：</td>
      <td width="80%"><input name="music_url" type="text" id="music_url" value="<?php echo ($config["music_url"]); ?>" size="50" maxlength="200" style=" width:90%; max-width:500px"/>
     <br>网络上音乐文件播放地址,比如http://www.xxxx.com/1.mp3</td>
    </tr>
    <tr>
      <td height="30" align="right">分享图片：</td>
      <td bgcolor="#FFFFFF">
          <div class="pic-box">
            <span>点击上传图片</span>
            <p>
            <?php if(!empty($config['logo_path'])): ?><img src="__PUBLIC__/<?php echo ($config["logo_path"]); ?>"><?php endif; ?>
            </p>
          </div>
          <input name="logo_path" type="text" id="logo_path" value="<?php echo ($config["logo_path"]); ?>" size="50" maxlength="100"style="display:none"/>
          　
          <input type="file" name="fl_pic" id="fl_pic" style="display:none"></td>
    </tr>
    <tr>
      <td height="120" align="right">分享文案：</td>
      <td align="left"><textarea name="txt1" cols="100" rows="6" id="txt1" style=" width:90%; max-width:500px"><?php echo ($config["txt1"]); ?></textarea><br>微信分享好友或朋友圈时显示的文字介绍</td>
    </tr>
    <tr>
      <td height="50" align="center">&nbsp;</td>
      <td height="50" align="left"><input type="submit" name="Submit" value="保 存" class="btn"/>　
          <input type="reset" name="Submit2" value="重 置" class="btn"/>
          <input name="id" type="hidden" id="id" value="<?php echo ($config["id"]); ?>" /></td>
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
		$(this).parent().find('input[type="file"]').click();
	});
	$('input[type="file"]').change(function(){
		 var s = getObjectURL($(this)[0].files[0]); 
		 $(this).parent().find('.pic-box p').html('<img src="' + s + '">');
	});

});
</script>
</body>
</html>