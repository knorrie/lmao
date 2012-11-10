<?php

# logout.php

# instellingen & rommeltjes
require_once('../lib/include.config.php');
require_once('include.common.php');

# login-systeem
require_once('class.apuser.php');
session_start();
$apuser = new APUser();

$apuser->logout();

if (isset($_POST['url'])) $url = $_POST['url'];
else die ("Ho! Scheids! Hier gaat iets goed fout!");

# beetje checken, zodat er geen zooi in geinsert wordt.
if (preg_match("/[^ \"\n\r\t<]*?/", $url))
	header(sprintf("Location: %s%s", AP_SERVER, $url));


?>
