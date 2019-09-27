<?php
class IndexAction extends Action {
    public function index(){
		//$_SESSION['USER']['ID']=26;
		//unset($_SESSION['USER']['ID']);
		//return;
     	if(!isset($_SESSION['USER']['ID'])){
			$this->wxLoginApi();
			return;
		}
		$user=M('user')->where('id=' . $_SESSION['USER']['ID'])->find();
     	if(empty($user)){
			$this->wxLoginApi();
			return;
		}
		
		$webconfig = M('webconfig')->find();
		if($user['subscribe']==0 && $webconfig['nNeedGZ'] == 1){
			unset($_SESSION['USER']['ID']);
		}

/*		$uname=$_SESSION['USER']['NAME'];

		$user=M('user')->where('username=\'' . $uname . '\'')->find();
		if(empty($user)){
			$user['id']=M('user')->add(array('username'=>$uname));
		}
		$_SESSION['USER']['ID']=$user['id'];*/
		
		$webconfig = M('webconfig')->find();
		$lotNum =M('lottery')->count() + $webconfig['initNum'];
		$prize =M('lottery_prize')->limit(6)->order('nOrder,id')->select();
		$userList=M('lottery')->order('id desc')->limit(20)->select();
		foreach($userList as $n=>$val){
			$uname=$val['username'];
			$userList[$n]['username']=$uname;
		}
		$myprize=M('lottery')->join('left join xz_lottery_prize a on a.id=xz_lottery.nPrizeId')->field('xz_lottery.*,a.pic,a.title2')->where('xz_lottery.nUserId=' . $_SESSION['USER']['ID'] . ' and xz_lottery.nJPTag=0')->order('xz_lottery.id desc')->select();
		
		$now=date('Y-m-d H:i:s');
		$webconfig['diff_start']=strtotime($webconfig['startTime']) - strtotime($now);
		$webconfig['diff_end']=strtotime($now) - strtotime($webconfig['endTime']);
		
		require_once "jssdk.php";
		$jssdk = new JSSDK(C('wx_appId'),C('wx_appSecret'));
		$signPackage = $jssdk->GetSignPackage();
		$this->assign(array(
			'myprize' => $myprize,
			'user' => $user,
			'config' => $webconfig,
			'signPackage' => $signPackage,
			'prize' => $prize,
			'userList' => $userList,
			'lotNum' => $lotNum
		));	
		$this->display();
    }

	public function search(){
		$mobile=$_GET['mobile'];
		$list=M('lottery')->where('mobile=\'' . $mobile . '\'')->order('id')->select();
		if(!empty($list)){
			foreach($list as $n=>$val){
				echo ("奖品：" . $val['jiangPin'] . "<br>中奖时间：" . $val['subtime'] . "<br>");
			}
		}else{
			echo("对不起，没有查询到记录，请检查输入是否有误！");
		}
	}
	

    public function lottery(){
		$fireNum=0;
		$isHasChance=4;
		$rotate=0;
		$num=0;
		
		if(!isset($_SESSION['USER']['ID'])){
			$isHasChance=4;
			$results="操作延时，请重新登录！";
		}else{
			//$user=M('user')->where('id=' . $_SESSION['USER']['ID'])->find();
			$config = M('webconfig')->find();
			
			$now=date('Y-m-d H:i:s');
			$config['diff_start']=strtotime($config['startTime']) - strtotime($now);
			$config['diff_end']=strtotime($now) - strtotime($config['endTime']);
			if($config['diff_start']>0){
				$isHasChance=5;
				$results="活动还没开始！";
			}else if($config['diff_end']>0){
				$isHasChance=5;
				$results="活动已经到期！";
			}else{
				if($config['nLotType']==1) $sql=' and subtime >\'' . date('Y-m-d') . '\'';
				$rnum=M('lottery')->where('nUserId=' . $_SESSION['USER']['ID'] . $sql)->count();
				$user=M('user')->where('id=' . $_SESSION['USER']['ID'])->find();
				
				$url = 'http://hqyp.fenith.com?openid=' . $user['wxopenId'];
				$res= json_decode(file_get_contents($url),true);
				//if($res['vip']==1) $config['nLotTimes']=$config['nLotTimes']+1;
                $config['nLotTimes'] = $config['nLotTimes'] + $res['market_class'];
				
				$bChecked=false;
				$bChecked=$rnum<$config['nLotTimes'];
				if($config['sharetime']>0 && $bChecked==false){
					
					$fireNum=$user['fireNum'];
					if($fireNum>0) $bChecked=true;
				}
				if($bChecked){
					$stock =M('stock')->where('nStatus=0')->order('RAND()')->limit(1)->find();
					if(empty($stock)){
						$isHasChance=4;
						$results="奖品已经抽完";
					}else{
						M('stock')->where('id=' . $stock['id'])->setField('nStatus',1);
						$curPrizeId=$stock['nPrizeId'];
						$prize =M('lottery_prize')->order('nOrder,id')->select();
					
						$nMoney=0;
						foreach($prize as $n=>$val){
							if ($curPrizeId==$val['id']){
								$results=$val['title2'] . $val['title'];
								$nPrizeId=$val['id'];
								$pic=$val['pic'];
								$nJPTag=$val['nTag'];
								$nMoney=$val['nTag'];
								$num=$n;
								$rotate=$n * (360 / count($prize));
								break;
							}
						}
						
						$data['username']=$_SESSION['USER']['NAME'].'';
						$data['nUserId']=$_SESSION['USER']['ID'];
						$data['ip']=$_SERVER["REMOTE_ADDR"];
						$data['jiangPin']=$results;
						$data['nJPTag']=$nJPTag;
						$data['sname']=$_POST['sname'].'';
						$data['mobile']=$_POST['mobile'].'';
						$data['cjm']=$_POST['cjm'].'';
						$data['address']=$_POST['address'].'';
						$data['nPrizeId']=$nPrizeId;
						
						$lotId=M('lottery')->add($data);
						

						//$results= M('lottery')->getDbError();
						$isHasChance=3;
						if($fireNum>0) M('user')->where('id=' . $_SESSION['USER']['ID'])->setDec('fireNum');
					}
				}else{
					$isHasChance=5;
					$results="抽奖次数用完";
				}
			}
		}
		echo '{"isHasChance":' . $isHasChance . ',"rotate":' . $rotate . ',"num":' . $num . ',"results":"' . $results . '","pic":"' . $pic . '","lotId":"' . $lotId . '","time":"' . date('Y-m-d H:i:s') . '"}';
    }
	
	public function save(){
		$tbl=M('lottery');
		$tbl->create();
		$tbl->save();
	}
	
	public function wxLoginApi(){
	    $redirectUrl = 'http://' . $_SERVER['HTTP_HOST'] . U('wxLogin');
		$scope = 'snsapi_userinfo';
        $oAuthUrl = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".C('wx_appId')."&redirect_uri=".urlencode($redirectUrl)."&response_type=code&scope=".$scope."&state=state#wechat_redirect";
        $this -> gheader($oAuthUrl);
	}


    function gheader($url)
    {
        echo '<html><head><meta http-equiv="Content-Language" content="zh-CN"><meta HTTP-EQUIV="Content-Type" CONTENT="text/html;charset=gb2312"><meta http-equiv="refresh"
content="0;url='.$url.'"><title>loading ... </title></head><body><div style="display:none">
<script type="text/javascript">var cnzz_protocol = (("https:" == document.location.protocol) ? " https://" : " http://");document.write(unescape("%3Cspan id=\'cnzz_stat_icon_5696423\'%3E%3C/span%3E%3Cscript src=\'" + cnzz_protocol + "s9.cnzz.com/stat.php%3Fid%3D5696423%26show%3Dpic1\' type=\'text/javascript\'%3E%3C/script%3E"));</script></div>
<script>window.location="'.$url.'";</script></body></html>';
        exit();
    }

	public function wxLogin(){
		$url='https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . C('wx_appId') . '&secret=' . C('wx_appSecret') . '&code=' . $_GET['code'] . '&grant_type=authorization_code';
		$result=json_decode(file_get_contents($url),true);
		$accessToken = $result['access_token'];
		$openId = $result['openid'];
		$url = 'https://api.weixin.qq.com/sns/userinfo?access_token=' . $accessToken . '&openid=' . $openId . '&lang=zh_CN';
		$userinfo= json_decode(file_get_contents($url),true);
        //dump($userinfo);exit;
		$url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . C('wx_appId') . '&secret=' . C('wx_appSecret');
		$result=json_decode(file_get_contents($url),true);
		//dump($result);
		$accessToken = $result['access_token'];
		
		$url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token=' . $accessToken . '&openid=' . $openId . '&lang=zh_CN';
		$subinfo= json_decode(file_get_contents($url),true);
		//dump($subinfo);
		//return;
		if(isset($userinfo['openid'])){
			//$user=M('user')->where('wxopenId=\'' . $userinfo['openid'] . '\'')->find();
			$user=M('user')->where([
			    'wxopenId' => $userinfo['openid']
            ])->find();
			if(empty($user)){
				$rs['wxopenId']=$userinfo['openid'];
				$rs['username']=$userinfo['nickname'].'';
				$rs['nSex']=$userinfo['sex'];
				$rs['province']=$userinfo['province'];
				$rs['city']=$userinfo['city'];
				$rs['fireNum']=0;
				$rs['subscribe']=$subinfo['subscribe'];
				$rs['icon_path']=$userinfo['headimgurl'];
				$rs['pswd']='';
				$rs['mobile']='';
				$rs['email']='';
				$rs['qq']='';
				$rs['profile']='';
				$rs['addr']='';
				$rs['weixin']='';
				$rs['realName']='';
				$rs['sPicPath']='';
				$rs['zip']='';
				$rs['birthday']=date('Y-m-d H:i:s');
				$rs['preLogintime']=date('Y-m-d H:i:s');
				$rs['lastLogintime']=date('Y-m-d H:i:s');
				if($_SESSION['USER']['FID']!='') $rs['nFUserId']=$_SESSION['USER']['FID'];
				$id=M('user')->add($rs);
				//echo M() -> getLastSql();exit;
				$_SESSION['USER']['ID'] = $id;
				$_SESSION['USER']['NAME'] = $rs['username'];
			}else{
				$_SESSION['USER']['ID'] = $user['id'];
				$_SESSION['USER']['NAME'] = $user['username'];
				$rs['username']=$userinfo['nickname'];
				$rs['icon_path']=$userinfo['headimgurl'];
				$rs['subscribe']=$subinfo['subscribe'];
				$rs['preLogintime']=$user['lastLogintime'];
				$rs['lastLogintime']=date('Y-m-d H:i:s');
				M('user')->where('id=' . $user['id'])->save($rs);
			}
			//$this->his();
			//echo M('user')->getDbError();
            $this -> gheader(U('index'));
		}else{
			echo '获取用户信息失败';
		}
	}

	public function share(){
		$arr['status']=false;
		$config = M('webconfig')->find();
		if($config['sharetime']>0){
			$arr['status']=true;
			$arr['txt']='获取奖励失败，请重新登录！';
			if (isset($_SESSION['USER']['ID'])){
				$times=M('share')->where('nUserId=' . $_SESSION['USER']['ID'] . ' and subtime >\'' . date('Y-m-d') . '\'')->count();
				if($times<$config['sharenum']){  //判断是否超出每天分享限制次数
					M('user')->where('id=' . $_SESSION['USER']['ID'])->setInc('fireNum',$config['sharetime']); 
					$rs['nUserId']=$_SESSION['USER']['ID'];
					$rs['fireNum']=$config['sharetime'];
					$id=M('share')->add($rs);
					$arr['txt']='分享成功！增加' . $config['sharetime'] . '次机会！';
				}else{
					$arr['txt']='您今天已经分享超过' . $config['sharenum'] . '次,本次分享不能获取奖励！';
				}
			}
		}
		$this->ajaxReturn($arr);
	}
}

?>