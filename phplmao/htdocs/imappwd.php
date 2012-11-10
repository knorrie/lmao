<?php

# instellingen & rommeltjes
require_once ("../lib/include.config.php");
require_once('include.common.php');

# login-systeem
session_start();
require_once ("class.apuser.php");
$apuser = new APUser();

# Moeten er acties uitgevoerd worden?
$validaction = array('setpw');
if (isset($_POST['a']) and in_array($_POST['a'], $validaction)) $action = $_POST['a'];
elseif (isset($_GET['a']) and in_array($_POST['a'], $validaction)) $action = $_GET['a'];
else $action = 'none';

# Een error-waarde houden we bij om zodadelijk evt. een foutmelding
# te kunnen laden in plaats van de profiel pagina omdat er geen
# toegang wordt verleend voor de actie die gevraagd wordt.
$error = 0;
# 0 -> gaat goed
# 1 -> mag niet, foutpagina afbeelden
# 2 -> er treden (vorm)fouten op in bijv de invoer.

require_once("class.mailbox.php");
$mailbox = new MailBox();

switch ($action) {
	case 'setpw':
		# kijk of er een uid is opgegeven (mailbox+domein)
		$uid = (isset($_POST['uid'])) ? $_POST['uid'] : '';
		# kijk of er een oud wachtwoord is opgegeven
		$oldpass = (isset($_POST['oldpass'])) ? $_POST['oldpass'] : '';
		# kijk of er een nieuw wachtwoord is opgegeven
		$nwpass = (isset($_POST['nwpass'])) ? $_POST['nwpass'] : '';
		# kijk of er een nieuw wachtwoord (bevestiging) is opgegeven
		$nwpass2 = (isset($_POST['nwpass2'])) ? $_POST['nwpass2'] : '';
		
		# bestaat de mailbox en klopt het oude password?
		$error = ($mailbox->pwToolCheckPass($uid,$oldpass)) ? 0 : 2;
		if ($error == 0) {
			# proberen wachtwoord te wijzigen
			list ($usr,$dom) = explode('@',$uid);
			$mailbox->loadEditBox ($usr);
			if ($mailbox->setpw($nwpass, $nwpass2)) {
				$mailbox->saveEditBox();
			} else {
				$error = 2;
			}
		}
		break;
}

### Pagina-onderdelen ###
require_once ("class.menu.php");
require_once ("class.page.php");

# Hopsa menu
$menu = new Menu($apuser);

# De pagina opbouwen, met aliastabel, of met foutmelding
if ($error == 0  or $error == 2) {
	require_once('class.pwtoolcontent.php');
	$content = new PWToolContent($apuser, $mailbox);
} else {
	# geen rechten
	require_once('class.includer.php');
	$content = new Includer('', 'geentoegang.html');
}

# Maak pagina
$page = new Page ($menu, $content);
$page->appendTitle(" - Mailbox Passwordtool");

$page->view();

?>
