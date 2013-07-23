<?php
require_once 'client-conf.php';
$rpc_sess = restore_rpc_session();
if(!$rpc_sess) die("Please login!");

$Books = new jsonRPCClient($ServerURL, 'Books', $rpc_sess);

print_r($Books->read(array('t1'=>"Привет!")));

