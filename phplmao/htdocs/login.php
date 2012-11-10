<?php

# login.php

# instellingen & rommeltjes
require_once('../lib/include.config.php');
require_once('include.common.php');

# login-systeem
require_once('class.apuser.php');

session_start();
$apuser = new APUser();

# ok_url en user/pass invoer checken
if (isset($_POST['url']) /*and preg_match("/^[\w.\/]+$/", $_POST['url'])*/
	and isset($_POST['user']) and isset($_POST['pass'])
	/* and $_POST['user'] != '' and $_POST['pass'] != '' */) {
	
	$checkip = isset($_POST['checkip']) and $_POST['checkip'] == 'true';
	
	if ($apuser->login(strval($_POST['user']),strval($_POST['pass']), $checkip)) {
		header(sprintf("Location: %s%s", AP_SERVER, $_POST['url']));
	} else {
		$_SESSION['auth_error'] = "Ongeldige gebruiker of wachtwoord";
		header(sprintf("Location: %s%s", AP_SERVER, $_POST['url']));
	}
}

exit;

?>
