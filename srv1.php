<?php
require_once('server-conf.php');
$srv = new jsonRPCServer($AuthUsers);
$UserLogin = $srv->AuthUser();

class Books {
	private $title;

	public function __construct($title=""){
		$this->title = $title;
	}

    public function read($msg,$chap='') {
		return "$this->title: $msg, $chap";
		return $_SESSION;
    }
}


class Users {
	private $user;
	private $book;

	public function __construct($Login){
		$this->user = $Login;
		$this->book = new Books($this->user."'s  Book");
	}

    public function auth() {
		return $this->user!=''?'ok':'auth error';
    }

	//switch from root to user
    public function authas($user) {
		global $AuthUsers;
		if($this->user=='root' && isset($AuthUsers[$user])){
			$this->user = $user;
			return 'ok';
		}
		return 'auth error';
    }

    public function getBook() {
		return $this->book;
    }

}


$user = new Users($UserLogin);
$book = $user->getBook();

$srv->registerClass($user);
$srv->registerClass($book);
$srv->handle() or die("no request");

