<?php
if(empty($_GET['url'])){
	exit('Cross domain url not defined.');
}
$url = urldecode($_GET['url']);
$ch = curl_init($url);
$result = curl_exec($ch);
echo $result;
