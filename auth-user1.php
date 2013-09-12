<?php
require_once 'client-conf.php';
$rpc_sess = array_merge($AuthUser, restore_rpc_session()); //reauth user

echo "<pre>";
//print_r($rpc_sess);
//echo "\n";

$Users = new jsonRPCClient($ServerURL, 'Users', $rpc_sess);
try{
	print_r($Users->auth("user1"));
} catch (Exception $e) {
	print_r($e->getMessage());
	die("\nAuth Failed");
}

echo "\nOK";

