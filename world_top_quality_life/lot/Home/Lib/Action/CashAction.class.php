<?php
class CashAction extends Action {
    public function index(){	
		if($_SESSION['CASH_USER']['OPENID']==''){
			$this->wxLoginApi($_GET['rid']);
			return;
		}

		C('DEFAULT_THEME','default');
		$rs=M('lottery')->join('left join xz_lottery_prize a on a.id=xz_lottery.nPrizeId')->where('xz_lottery.id=' . $_GET['rid'])->field('xz_lottery.*,a.pic,a.title2,a.title')->order('xz_lottery.id desc')->find();
		if($config['shop_list'].''!='') $list=explode(',',$config['shop_list']);
		$this->assign(array(
			'rs' => $rs,
			'list' => $list,
			'config' => $config
		));	
		$this->display();
    }
	 public function save(){
		 $config = M('webconfig')->find();
		 $dj_pswd=$_POST['dj_pswd'];
		 if($dj_pswd!=$config['dj_pswd']){
			 $this->error('兑奖密码错误！');
			 return;
		 }
		 $tbl=M('lottery');
		 $data['nStatus']=1;
		 $data['cash_time']=date('Y-m-d H:i:s');
		 $data['cash_openid']=$_SESSION['CASH_USER']['OPENID'];
		 $data['cash_name']=$_SESSION['CASH_USER']['NAME'];
		 $data['cash_info']=$_POST['shop'] . ',' . $_POST['remarks'];

		 $tbl->where('id=' . $_POST['rid'])->save($data);
		 echo'<meta name="viewport" content="width=device-width, minimum-scale=1,initial-scale=1, maximum-scale=1, user-scalable=1;" id="viewport" />';
		 $this->success('兑换成功！');
	 }
	 
	public function wxLoginApi($rid){
	    $redirectUrl = urlencode('http://' . $_SERVER['HTTP_HOST'] . U('wxLogin?rid=' . $rid));
		$scope = 'snsapi_userinfo';
		$oAuthUrl = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . C('wx_appId') . '&redirect_uri=' . $redirectUrl . '&response_type=code&scope=' . $scope . '&state=';
		Header("HTTP/1.1 303 See Other"); 
		Header("Location: $oAuthUrl"); 
	}

	public function wxLogin(){
		$url='https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . C('wx_appId') . '&secret=' . C('wx_appSecret') . '&code=' . $_GET['code'] . '&grant_type=authorization_code';
		$result=json_decode(file_get_contents($url),true);
		$accessToken = $result['access_token'];
		$openId = $result['openid'];
		$url = 'https://api.weixin.qq.com/sns/userinfo?access_token=' . $accessToken . '&openid=' . $openId . '&lang=zh_CN';
		$userinfo= json_decode(file_get_contents($url),true);

		$_SESSION['CASH_USER']['OPENID'] =$userinfo['openid'];
		$_SESSION['CASH_USER']['NAME'] = $userinfo['nickname'].'';
		Header('Location: ' . U('index?rid=' . $_GET['rid']));
	} 

}

?>