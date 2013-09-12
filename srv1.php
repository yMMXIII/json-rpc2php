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
    }
}


class Users {
	private $user;
	private $book;

	public function __construct($Login){
		if(isset($_SESSION["authasuser"])){
			$this->user = $_SESSION["authasuser"];
		}else{
			$this->user = $Login;
		}

		$this->book = new Books($this->user."'s  Book");
	}

    public function auth($AsUser='') {
		global $AuthUsers;

		if($AsUser!=''){
			if($this->user=='root' && isset($AuthUsers[$AsUser])){
				$this->user = $AsUser;
				$_SESSION["authasuser"] = $AsUser;
			}
			return "ok, welcome $this->user";
		}

		if(isset($_SESSION["authasuser"])) unset($_SESSION["authasuser"]);
		if($this->user!=''){
			return "ok, welcome $this->user";
		}else{
			return 'auth error';
		}
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

