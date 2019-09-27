<?php
class ContentAction extends Action{
	public function index(){
		$a = M('lottery');
		$search['username']=$_POST['username'].$_GET['username'];
		$sql='1=1';
		if($search['username']!='') $sql=$sql . ' and xz_lottery.username like \'%' . $search['username'] . '%\'';

		
		import("ORG.Util.Page");
		$n = $a->where($sql)->count();
		$pg = new Page($n,50);
		$pgsw = $pg->show();
		
		$lottery = $a->join('left join xz_user a on a.id=xz_lottery.nUserId')->field('xz_lottery.*,a.city,a.province,a.icon_path')->where($sql)->limit($pg->firstRow.','.$pg->listRows)->order('xz_lottery.id desc')->select();
		//dump($lottery);
		$this->assign(array(
			'search' => $search,
			'lottery' => $lottery,
			'PageTurn' => $pgsw
		));	
		$this->display();
	}
	public function lot_del_all(){
		M('lottery')->where('1=1')->delete();
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
		echo iconv("UTF-8", "GB2312","信息")."\t";
		echo iconv("UTF-8", "GB2312","奖品")."\t";;
		echo iconv("UTF-8", "GB2312","中奖时间")."\t";
		
		$sql='1=1';
		
		$list = M('lottery')->join('left join xz_user a on a.id=xz_lottery.nUserId')->field('xz_lottery.*,a.city,a.province,a.icon_path')->where($sql)->order('xz_lottery.id desc')->select();
		foreach($list as $n=>$val){
			echo   "\n"; 
			echo iconv("UTF-8", "GB2312",$val['username'])."\t";
			echo iconv("UTF-8", "GB2312",$val['sname'] . ',' . $val['mobile'] . ',' . $val['cjm']  . ',' . $val['address']  . ',' . $val['remarks'])."\t";		
			echo iconv("UTF-8", "GB2312",$val['jiangPin'])."\t";
			echo iconv("UTF-8", "GB2312",$val['subtime'])."\t";
		}
	}
	public function edit(){
		$id=$_GET['id'];
		if(is_numeric($id)){
			$a=M('lottery');
			$lottery=$a->where('id='.$id)->find();
		}
		$this->assign(array(
			'lottery'=>$lottery
		));
		$this->display();
	}
	public function save(){
		header('Content-Type:text/html;charset=utf-8');
		$id=$_POST['id'];
		$c=M('lottery');
		$c->create();
		if(!is_numeric($id)){
			$c->add();
		}
		$c->save();
		$this->success('保存成功！');
	}
	public function del(){
		$id=$_GET['id'];
		$d=M('lottery');
		$n=$d->where('id ='. $id)->delete();
		$this->success('删除成功！');
	}
}
?>