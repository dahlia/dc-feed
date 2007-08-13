<?php
require_once 'HTTP/Request.php';

$server = basename($_SERVER['PATH_INFO']);
$query  = substr($_SERVER['REQUEST_URI'], strpos($_SERVER['REQUEST_URI'], '?'));

$src = new HTTP_Request("http://$server.dcinside.com/viewimage.php$query");
$src->addHeader('Referer', 'http://gall.dcinside.com/');
$src->sendRequest();

foreach($src->getResponseHeader() as $name => $value)
	header("$name: $value");

die($src->getResponseBody());
