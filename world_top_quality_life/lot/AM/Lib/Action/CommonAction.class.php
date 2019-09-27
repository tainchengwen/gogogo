<?php
class CommonAction extends Action{
	public function _initialize(){
		session_start();
		if (!$_SESSION['admin_name']){
			$path = explode('/',__URL__,-1);
			$path = join('/',$path);
			echo '<script>alert("操作延时或者没有登录！");parent.window.location.href="'.$path.'/login";</script>';
			exit();
		}
	}
	public function random_code($length = 8,$chars = null){
	  if(empty($chars)){
		 $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
	  }
	  $count = strlen($chars) - 1;
	  $code = '';
	  while( strlen($code) < $length){
		 $code .= substr($chars,rand(0,$count),1);
	  }
	  return $code;
	}
	
	public function upload(){
		$arr['status']=true;
		foreach($_FILES as $key =>$val){
			$ftype=$val["type"];
			if ((( $ftype== "image/gif")
			|| ($ftype == "image/jpeg")
			|| ($ftype == "image/pjpeg") || ($ftype== "image/png"))
			&& ($val["size"] < 1024 * 1024 * 10))
			{
				if ($val["error"] > 0){
					$arr['status']=false;
					$arr['info']= $val["error"];
				}else{
					$path_info = pathinfo($val['name']);
					$newfilename=md5(uniqid()). '.' . $path_info["extension"];
					$savepath='Uploadfiles/' . $newfilename;
					$arr['path']=$savepath;
					$savepath='Public/' . $savepath;
					move_uploaded_file($val["tmp_name"],$savepath);
				}
			}else{
				$arr['status']=false;
				$arr['info']= '图片格式限制为gif、jpg、png,文件大小限制2M以内';
			}
		}
		return $arr;
	}
}
?>