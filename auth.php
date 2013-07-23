<?php
require_once 'client-conf.php';
$rpc_sess = array_merge($AuthUser, restore_rpc_session()); //reauth user

echo "<pre>";
print_r($rpc_sess);
echo "\n";

$Users = new jsonRPCClient($ServerURL, 'Users', $rpc_sess);
print_r($Users->auth());
