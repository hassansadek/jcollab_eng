<?php
class MenuController extends AppController {
	public $idModule = 129;
	
	public function index() {
		$user_id = $this->Session->read('Auth.User.id');
	    $role_id = $this->Session->read('Auth.User.role_id');
		$admins = $this->Session->read('admins');
		$depots = $this->Session->read('depots');
		$this->getPath($this->idModule);
	}
}