<?php
require_once('server-conf.php');
$srv = new jsonRPCServer($AuthUsers);
$UserLogin = $srv->AuthUser();
if(!$UserLogin) die("no login!");

class Books {
	private $title;

	public function __construct($title=""){
		$this->title = $title;
	}

    public function read($msg) {
		return "$this->title: ". serialize($msg);
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

    public function getBook() {
		return $this->book;
    }

}


$user = new Users($UserLogin);
$book = $user->getBook();

$srv->registerClass($user);
$srv->registerClass($book);
$srv->handle() or die("no request");

