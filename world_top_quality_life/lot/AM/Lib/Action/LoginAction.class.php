<?php
class LoginAction extends Action{
	public function index(){
		$this->display();
	}
	
	public function verify(){
		import('ORG.Util.Image');	
		Image::buildImageVerify();
	}
	
	public function check(){
		header('Content-Type:text/html;charset=utf-8');

		if ($_SESSION['verify'] != md5($_POST['verifycode'])){
			$url=$_SERVER['HTTP_REFERER'];
			echo '<script>alert("验证码错误！");location.href="'.$url.'"</script>';
			return;
		}	
			
		$a = M('admin');
		$admin_name = $_POST['admin_name'];
		$admin_pass = md5($_POST['admin_pass']);
		$user = $a->where('admin_name="'.$admin_name.'"')->find();	

		if ($user['admin_pass'] != $admin_pass){
			echo '<script>alert("用户名或密码错误！");window.history.go(-1);</script>';
			return;
		}else{
			$data['lastLoginTime']=date('Y-m-d H:i:s',time());
			$data['ip']=$_SERVER['REMOTE_ADDR'];
 			$a->where('id='.$user['id'])->data($data)->save();
			session_start();
			$_SESSION['admin_name'] = $user['admin_name'];
			$_SESSION['admin_type'] = $user['nType'];
			$_SESSION['admin_id'] = $user['id'];
			header('Location:'.__APP__);
		}			
		
	}
	
	public function out(){
		header('Content-Type:text/html;charset=utf-8');
		session_start();
		$_SESSION = array();
		session_destroy();
		header('Location:'.__URL__);
	}
}
?>