<?php

# instellingen & rommeltjes
require_once ("../lib/include.config.php");
require_once('include.common.php');

# login-systeem
session_start();
require_once ("class.apuser.php");
$apuser = new APUser();

# Moeten er acties uitgevoerd worden?
$validaction = array('none', 'setcn', 'setpw', 'deletebox');
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

# voor alle acties mbt het wijzigen van mailboxen moet er een domein zijn
# waarbinnen we de acties uitvoeren. Als het niet is meegegeven in POST of GET,
# dan volgt er een foutmelding, omdat het dan niet duidelijk is in welk domein
# we iets moeten veranderen. hetzelfde geldt voor de naam van de mailbox
if (isset($_POST['dc'])) $dc = $_POST['dc'];
elseif (isset($_GET['dc'])) $dc = $_GET['dc'];
else {
	$dc = '';
	$error = 1;
}
if (isset($_POST['uid'])) $uid = $_POST['uid'];
elseif (isset($_GET['uid'])) $uid = $_GET['uid'];
else {
	$uid = '';
	$error = 1;
}

# we hebben nu een action, een dc en een uid (die beide nog niet geverifieerd zijn)
# of error != 0

# controleren of de
# gebruiker wel rechten heeft om hier iets mee te doen...
if (!$apuser->hasMailBox($dc)) {
	$error = 1;
} else {
	require_once("class.mailbox.php");
	$mailbox = new MailBox($dc);
}

if ($error == 0) switch ($action) {
	case 'none':
		# controleren of de gevraagde mailbox bestaat en of we het
		# kunnen inladen
		$mailbox->loadEditBox($uid) or print($mailbox->getError());//$error = 1;
		break;
	case 'setcn':
		# de inhoud van $_POST['setcn'] wordt in $mailbox uitvoerig
		# gecontroleerd
		if (isset($_POST['cn']) and
		    $mailbox->loadEditBox($uid) and
		    $mailbox->setcn($_POST['cn']) and
		    $mailbox->saveEditBox() ) {
			header(sprintf("Location: %seditbox.php?dc=%s&uid=%s", AP_URL, $dc, rawurlencode($uid)));
			exit;
		} else {
			$error = 2;
		}
		break;
	case 'setpw':
		if (isset($_POST['nwpass']) and
		    isset($_POST['nwpass2']) and
		    $mailbox->loadEditBox($uid) and
		    $mailbox->setpw($_POST['nwpass'], $_POST['nwpass2']) and
		    $mailbox->saveEditBox() ) {
			header(sprintf("Location: %seditbox.php?dc=%s&uid=%s", AP_URL, $dc, rawurlencode($uid)));
			exit;
		} else {
			$error = 2;
		}
		break;
	case 'deletebox':
		# proberen de mailbox te verwijderen
		$error = ($mailbox->removeBox($uid)) ? 0 : 2;
		# als dat lukt gaan we terug naar de overzichtslijst met mailboxen
		if ($error == 0) {
			header(sprintf("Location: %smailbox.php?dc=%s", AP_URL, $dc));
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
	require_once('class.boxeditcontent.php');
	$content = new BoxEditContent($apuser, $mailbox);
} else {
	# geen rechten
	require_once('class.includer.php');
	$content = new Includer('', 'geentoegang.html');
}

# Maak pagina
$page = new Page ($menu, $content);
$page->appendTitle(" - MailBox");

$page->view();

?>
