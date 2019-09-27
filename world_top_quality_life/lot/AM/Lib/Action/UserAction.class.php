<?php
class UserAction extends CommonAction{
	public function index(){
		$u=M('user');
		//if($_SESSION['admin_type']>0) $sql='nAdminId=' . $_SESSION['admin_id'];
		$sql='';
		$search['username']=$_POST['username'].$_GET['username'];
		if($search['username']!='') $sql=$sql . ' and username like \'%' . $search['username'] . '%\'';
		import("ORG.Util.Page");
		$n = $u->where($sql)->count();
		$pg = new Page($n,20);
		$pgsw = $pg->show();
		
		$user=$u->where($sql)->limit($pg->firstRow.','.$pg->listRows)->order('id desc')->select();
		$this->assign(array(
			'user' => $user,
			'PageTurn' => $pgsw,
			'search' => $search
		));	
		$this->display();
	}

	public function lot_del_all(){
		M('user')->where('1=1')->delete();
		$this->success('清空成功！');
	}
	 
	public function toExcel(){
		header('Content-Type:application/force-download');
		header('Content-Type:application/octet-stream');
		header('Content-Type:application/download');
		header('Content-Transfer-Encoding:utf-8');
		header('Content-Type:application/vnd.ms-excel');
		header("Content-Disposition:attachment;filename=" . date('Y-m-d') . ".xls");
		header('Pragma:no-cache');
		header('Cache-Control:max-age=0');
		
		echo iconv("UTF-8", "GB2312","用户名")."\t";
		echo iconv("UTF-8", "GB2312","性别")."\t";
		echo iconv("UTF-8", "GB2312","省市")."\t";;
		echo iconv("UTF-8", "GB2312","注册时间")."\t";
		
		$sql='1=1';
		if($_SESSION['admin_type']>0) $sql=$sql .' and nAdminId=' . $_SESSION['admin_id'];
		$list = M('user')->where($sql)->order('id desc')->select();
		foreach($list as $n=>$val){
			echo   "\n"; 
			echo iconv("UTF-8", "GB2312",$val['username'])."\t";
			$sex='女';
			if($val['nSex'] == 1) $sex='男';
			echo iconv("UTF-8", "GB2312",$sex)."\t";
			echo iconv("UTF-8", "GB2312",$val['province'] . $val['city'])."\t";
			echo iconv("UTF-8", "GB2312",$val['subtime'])."\t";
		}
	}
	
	public function user_prize(){
		$u=M('user_prize');
		import("ORG.Util.Page");
		$n = $u->count();
		$pg = new Page($n,20);
		$pgsw = $pg->show();
		
		$list=$u->join('left join xz_user a on a.id=xz_user_prize.nUserId')->field('a.username,a.realName,a.mobile,xz_user_prize.*')->limit($pg->firstRow.','.$pg->listRows)->order('xz_user_prize.nGrade,xz_user_prize.id')->select();
		///echo $u->getDbError();
		//dump($list);
		$this->assign(array(
			'list' => $list,
			'PageTurn' => $pgsw,
		));	
		$this->display();
	}
	public function edit(){
		$id=$_GET['id'];
		if(is_numeric($id)){
			$a=M('user');
			$user=$a->where('id='.$id)->find();
		}
		$this->assign(array(
			'user'=>$user,
			'city'=>$city
		));
		$this->display();
	}
	public function save(){
		header('Content-Type:text/html;charset=utf-8');
		$id=$_POST['id'];
		$u=M('user');
		if($_POST['pswd2']!='')$_POST['pswd']=md5($_POST['pswd2']);
		$u->create();
		if(!is_numeric($id)){
			$u->add();
		}
		$u->save();
		$this->success('保存成功！');
	}
	public function del(){
		$id=$_GET['id'];
		$d=M('user');
		$n=$d->where('id ='. $id)->delete();
		$this->success('删除成功！');
	}

}
?>