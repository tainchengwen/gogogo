<?php
class IndexAction extends Action {
    public function index(){
		if (!$_SESSION['admin_name']){
			$this->redirect("login/index");
		}
		$this->display();
    }
}

?>