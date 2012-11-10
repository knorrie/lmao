<?php

# instellingen & rommeltjes
require_once("../../lib/include.config.php");
require_once('include.common.php');
require_once('include.apmailutils.php');

# login-systeem
session_start();
require_once ("class.apuser.php");
$apuser = new APUser();

$path = '/srv/www/stats.example.com/webalizer/fubar/';

if (!isset($_GET['vhost'])
    or !isValidDomain ($_GET['vhost'])
    or !$apuser->hasWWW($_GET['vhost'])
    or !is_dir($path . $_GET['vhost'])
    ) die ('Not Found');

if (isset($_GET['html'])
    and preg_match('/^(index|usage_\d{6})$/', $_GET['html'])
    and file_exists($path . $_GET['vhost'] . '/' . $_GET['html'] . '.html')
    ) {
	readfile($path . $_GET['vhost'] . '/' . $_GET['html'] . '.html');
	exit;
} elseif (isset($_GET['png'])
    and preg_match('/^((hourly_|daily_|ctry_)?usage(_\d{6})?)$/', $_GET['png'])
    and file_exists($path . $_GET['vhost'] . '/' . $_GET['png'] . '.png')
    ) {
	$fn = $path . $_GET['vhost'] . '/' . $_GET['png'] . '.png';
	header('Content-Length: '.filesize($fn));
	header('Content-Type: image/png');
	readfile($fn);
	exit;
}

die ('HTTP/1.0 404 Not Found');

?>
