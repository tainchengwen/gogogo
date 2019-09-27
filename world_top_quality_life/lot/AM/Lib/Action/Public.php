<?php

session_start();
if (!$_SESSION['admin_name']){
	$path = explode('/',__URL__,-1);
	$path = join('/',$path);
	echo '<script>alert("操作延时或者没有登录！");parent.window.location.href="'.$path.'/login";</script>';
}

function getClassDeep($v_pid){
	$v_nDeep=1;
	$c=M("class".C('DB_TAILFIX'));
	$cl=$c->where('id='.$v_pid)->find();
	if(!empty($cl)){
		if($cl['nParentId']>0){
			$v_nDeep=$v_nDeep + getClassDeep($cl['nParentId']);
		}
	}
	return $v_nDeep;
}

function getParentOption($v_pid,$v_n,$v_pid2){
	
	if ($v_pid==0){
		$html='<select name="nParentId" id="nParentId"><option value="0">网站根点</option></select>';
		return $html;
	}
	
	$c=M('class'.C('DB_TAILFIX'));
	if ($v_n==1){
		$cl=$c->where('id='.$v_pid)->find();
		$selected='';
		if ($v_pid2==$cl['id']){
			$selected=' selected';
		}
		$html='<select name="nParentId" id="nParentId"><option value="' . $v_pid . '"' . $selected . '>' . $cl['className'] . '</option>';	
	}
	
	if ($v_n < getClassDeep($v_pid2)){
		$cl=$c->where('nParentId='.$v_pid)->order('nOrder,id')->select();
		if(!empty($cl)){
			for ($i=2; $i<=$v_n;$i++){
				$v_str=$v_str . '&nbsp;&nbsp;&nbsp;&nbsp;';
			}
			$v_str=$v_str . '├─';
			foreach($cl as $key=>$val){
				$selected='';
				if ($v_pid2==$val['id']){
					$selected=' selected';
				}
				$html=$html .'<option value="' . $val['id'] . '"' . $selected . '>' . $v_str . $val['className'] . '</option>';
				$html=$html . getParentOption($val['id'],$v_n+1,$v_pid2);
			}	
		}
	}
	
	if ($v_n==1){
		$html=$html . '</select>';
	}
	return $html;
}
?>

