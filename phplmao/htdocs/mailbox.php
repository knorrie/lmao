<?php

# instellingen & rommeltjes
require_once ("../lib/include.config.php");
require_once('include.common.php');

# login-systeem
session_start();
require_once ("class.apuser.php");
$apuser = new APUser();

# zijn we van dc aan het veranderen?
if (isset($_POST['selectdc']) and $apuser->hasMailBox($_POST['selectdc'])) {
	header(sprintf("Location: %s?dc=%s", $_SERVER['PHP_SELF'], $_POST['selectdc']));
	exit;
}

# Moeten er acties uitgevoerd worden?
$validaction = array('none', 'newbox', 'deletebox', 'setpw');
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

# voor alle acties mbt mailboxen moet er een domein zijn waarbinnen we
# de acties uitvoeren. Als het niet is meegegeven in POST of GET,
# dan nemen bij a=none de eerste uit de lijst van domeinen die een gebruiker
# mag administreren, en bij andere acties volgt er een foutmelding, omdat het
# dan niet duidelijk is in welk domein we iets moeten toevoegen of verwijderen
if (isset($_POST['dc'])) $dc = $_POST['dc'];
elseif (isset($_GET['dc'])) $dc = $_GET['dc'];
else $dc = '';

# controleren van het domein waarin we aan de slag gaen
switch ($action) {
	# alleen lijst afbeelden met aliassen
	case 'none':
		# als er geen domein is opgegeven proberen we die te achterhalen
		# vanuit apuser...
		if ($dc == '') {
			$mbdc = $apuser->getMailBox();
			if (count($mbdc) > 0) $dc = $mbdc[0];
			else $error = 1;
		# als er wel een domein is opgegeven gaan we controleren of de
		# gebruiker wel rechten heeft om hier iets mee te doen...
		} else {
			if (!$apuser->hasMailBox($dc)) $error = 1;
		}
		break;
	case 'newbox':
	case 'deletebox':
	case 'setpw':
		# als er geen domein is opgegeven is het foute boel
		if ($dc == '') {
			$error = 1;
		# als er wel een domein is opgegeven gaan we controleren of de
		# gebruiker wel rechten heeft om hier iets mee te doen...
		} else {
			if (!$apuser->hasMailBox($dc)) $error = 1;
		}
		break;
	default:
		$error = 1;
		break;
}

# we hebben nu een action en een dc, of error != 0
require_once("class.mailbox.php");
$mailbox = new MailBox($dc);

if ($error == 0) switch ($action) {
	case 'newbox':
		# kijk of er een uid is opgegeven
		if (isset($_POST['uid'])) $uid = $_POST['uid'];
		else $uid = '';
		# kijk of er een cn is opgegeven
		if (isset($_POST['cn'])) $cn = $_POST['cn'];
		else $cn = '';
		# proberen de nieuwe mailbox toe te voegen
		$error = ($mailbox->addBox($uid, $cn)) ? 0 : 2;
		# als dat lukt gaan we naar het alias-edit schermpje toe meteen
		if ($error == 0) {
			header(sprintf("Location: %seditbox.php?dc=%s&uid=%s", AP_URL, $dc, rawurlencode($uid)));
			exit;
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
	require_once('class.mailboxcontent.php');
	$content = new MailBoxContent($apuser, $mailbox);
} else {
	# geen rechten
	require_once('class.includer.php');
	$content = new Includer('', 'geentoegang.html');
}

# Maak pagina
$page = new Page ($menu, $content);
$page->appendTitle(" - Mailboxen");

$page->view();

?>
