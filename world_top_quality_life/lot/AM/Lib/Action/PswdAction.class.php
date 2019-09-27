<?php
require './AM/Lib/Action/Public.php';

class PswdAction extends Action{
	public function index(){
		$a = M('admin');
		session_start();
		$user = $a->where('id='.$_SESSION['admin_id'])->find();
		$this->assign('user',$user);
		$this->display();
	}
	public function save(){
		header('Content-Type:text/html;charset=utf-8');
		$a = M('admin');
		$data['admin_pass']=md5($_POST['admin_pass']);
		$user = $a->where('id='.$_SESSION['admin_id'])->save($data);
		$this->success('保存成功！');
	}
}
?>