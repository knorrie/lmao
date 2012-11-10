<?php

# instellingen & rommeltjes
require_once ("../lib/include.config.php");
require_once('include.common.php');

# login-systeem
session_start();
require_once ("class.apuser.php");
$apuser = new APUser();

# Moeten er acties uitgevoerd worden?
$validaction = array('none', 'setcn', 'adduid', 'rmuid');
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

# voor alle acties mbt het wijzigen van mailrelays moet er een domein zijn
# waarbinnen we de acties uitvoeren. Als het niet is meegegeven in POST of GET,
# dan volgt er een foutmelding, omdat het dan niet duidelijk is in welk domein
# we iets moeten veranderen. hetzelfde geldt voor de naam van de relay
if (isset($_POST['dc'])) $dc = $_POST['dc'];
elseif (isset($_GET['dc'])) $dc = $_GET['dc'];
else {
	$dc = '';
	$error = 1;
}
if (isset($_POST['cn'])) $cn = $_POST['cn'];
elseif (isset($_GET['cn'])) $cn = $_GET['cn'];
else {
	$cn = '';
	$error = 1;
}

# we hebben nu een action, een dc en een cn (die beide nog niet geverifieerd zijn)
# of error != 0

# controleren of de
# gebruiker wel rechten heeft om hier iets mee te doen...
if (!$apuser->hasMailRelay($dc)) {
	$error = 1;
} else {
	require_once("class.mailrelay.php");
	$mailrelay = new MailRelay($apuser, $dc);
}

if ($error == 0) switch ($action) {
	case 'none':
		# controleren of de gevraagde relay bestaat en of we het
		# kunnen inladen
		$mailrelay->loadEditRelay($cn) or $error = 1;
		break;
	case 'setcn':
		# om een omschrijving te veranderen moeten we helemaal een relay
		# weggooien en een nieuwe aanmaken... :|
		# de inhoud van $_POST['setcn'] wordt in $mailrelay uitvoerig
		# gecontroleerd
		if (isset($_POST['setcn']) and
		    $mailrelay->loadEditRelay($cn) and
		    $mailrelay->setcn($_POST['setcn']) and
		    $mailrelay->removeRelay($cn) and
		    $mailrelay->addRelay($_POST['setcn']) and
		    $mailrelay->saveEditRelay() ) {
			header(sprintf("Location: %seditrelay.php?dc=%s&cn=%s", AP_URL, $dc, rawurlencode($_POST['setcn'])));
			exit;
		} else {
			$error = 2;
		}
		break;
	case 'adduid':
		# kijk of er een uid is opgegeven
		if (isset($_POST['uid']) and
		    $mailrelay->loadEditRelay($cn) and
		    $mailrelay->adduid($_POST['uid']) and
		    $mailrelay->saveEditRelay() ) {
			header(sprintf("Location: %seditrelay.php?dc=%s&cn=%s", AP_URL, $dc, rawurlencode($cn)));
			exit;
		} else {
			$error = 2;
		}
		break;
	case 'rmuid':
		# kijk of er een uid is opgegeven
		if (isset($_POST['uid']) and
		    $mailrelay->loadEditRelay($cn) and
		    $mailrelay->rmuid($_POST['uid']) and
		    $mailrelay->saveEditRelay() ) {
			header(sprintf("Location: %seditrelay.php?dc=%s&cn=%s", AP_URL, $dc, rawurlencode($cn)));
			exit;
		} else {
			$error = 2;
		}
		break;
}

### Pagina-onderdelen ###
require_once ("class.menu.php");
require_once ("class.page.php");

# Hopsa menu
$menu = new Menu($apuser);

# De pagina opbouwen, met relaytabel, of met foutmelding
if ($error == 0  or $error == 2) {
	require_once('class.relayeditcontent.php');
	$content = new RelayEditContent($apuser, $mailrelay);
} else {
	# geen rechten
	require_once('class.includer.php');
	$content = new Includer('', 'geentoegang.html');
}

# Maak pagina
$page = new Page ($menu, $content);
$page->appendTitle(" - Mail Relay");

$page->view();

?>
