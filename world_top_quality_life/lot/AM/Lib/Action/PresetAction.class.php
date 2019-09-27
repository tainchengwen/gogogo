<?php
class PresetAction extends Action{
	public function index(){
		$list =M('lottery_prize')->field('*,(select count(1) from xz_stock where nPrizeId=xz_lottery_prize.id and nStatus=1) as num1,(select count(1) from xz_stock where nPrizeId=xz_lottery_prize.id) as num2')->order('nOrder,id')->select();
		$this->assign(array(
			'list' => $list
		));	
		$this->display();
	}

	public function edit(){
		$id=$_GET['id'];
		if(is_numeric($id)){
			$a=M('lottery_prize');
			$rs=$a->where('id='.$id)->find();
			$this->assign(array(
				'rs'=>$rs
			));
		}
		$this->display();
	}
	public function stock(){
		$id=$_GET['id'];
		$list=M('lottery_prize')->order('nOrder,id')->select();
		$this->assign(array(
			'list'=>$list
		));
		$this->display();
	}
	public function stock_save(){
		$num=$_POST['num'];
		if(!is_numeric($num)) 
			$this->error('请输入正确的数字！');
		else{
			for($i=0;$i<$num;$i++){
				M('stock')->add(array('nPrizeId'=>$_POST['nPrizeId']));
			}
			$this->success('添加成功！',U('index'));
		}	
	}
	public function stock_del(){
		$this->stock();
	}
	public function stock_del_do(){
		$pid=intval($_POST['nPrizeId']);
		if($pid==0)
			$sql='1=1';
		else
			$sql='nPrizeId=' . $pid;
			
		M('stock')->where($sql)->delete();
		$this->success('删除成功！');
	}
	
	
	public function save(){
		header('Content-Type:text/html;charset=utf-8');
		$id=$_POST['id'];
		$c=M('lottery_prize');

		if(!empty($_FILES['fl_pic']['tmp_name'])){
			$arr=$this->upload();
			if($arr['status'])
				$_POST['pic']=$arr['path'];
			else{
				$this->error($arr['info']);
				return;
			}
		}
		
		$c->create();
		if(!is_numeric($id)) 
			$c->add();
		else
			$c->save();
			
		$this->success('保存成功！');
	}
	public function del(){
		$id=$_GET['id'];
		$d=M('lottery_prize');
		$n=$d->where('id =' . $id)->delete();
		M('stock')->where('nPrizeId=' . $id)->delete();
		$this->success('删除成功！');
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
				$arr['info']= '图片格式限制为gif、jpg、png,文件大小限制10M以内';
			}
		}
		return $arr;
	}
}
?>