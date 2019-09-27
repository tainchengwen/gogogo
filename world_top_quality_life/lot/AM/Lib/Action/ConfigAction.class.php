<?php
require './AM/Lib/Action/Public.php';

class ConfigAction extends Action{
	public function index(){
		$c=M('webconfig'.C('DB_TAILFIX'));
		$config=$c->find();
		$this->assign('config',$config);
		$this->display();
	}
	public function save(){
		header('Content-Type:text/html;charset=utf-8');
		$c=M('webconfig');
		
		$arr=$this->upload();
		if($arr['status']){
			if(!empty($arr['fl_pic'])) $_POST['logo_path']=$arr['fl_pic'];
			if(!empty($arr['fl_pic2'])) $_POST['bg_url']=$arr['fl_pic2'];
			if(!empty($arr['fl_pic3'])) $_POST['ewm_pic']=$arr['fl_pic3'];
		}else{
			$this->error($arr['info']);
			return;
		}
		
		$c->create();
		$c->save();
		$this->success('保存成功');
	}
	public function upload(){
		$arr['status']=true;
		foreach($_FILES as $key =>$val){
			$ftype=$val["type"];
			if($val['name']!=''){
				if ((( $ftype== "image/gif")
				|| ($ftype == "image/jpeg")
				|| ($ftype == "image/pjpeg") || ($ftype== "image/png"))
				&& ($val["size"] < 1024 * 1024 * 5))
				{
					if ($val["error"] > 0){
						$arr['status']=false;
						$arr['info']= $val["error"];
					}else{
						$path_info = pathinfo($val['name']);
						$newfilename=md5(uniqid()). '.' . $path_info["extension"];
						$savepath='Uploadfiles/' . $newfilename;
						$arr[$key]=$savepath;
						$savepath='Public/' . $savepath;
						move_uploaded_file($val["tmp_name"],$savepath);
					}
				}else{
					$arr['status']=false;
					$arr['info']= '图片格式限制为gif、jpg、png,文件大小限制5M以内' . $key;
					break;
				}
			}
		}
		return $arr;
	}
}
?>