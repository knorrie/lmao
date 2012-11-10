<?php

#
# Jeugdkerken NL
#
# -------------------------------------------------------------------
# class.apuser.php
# -------------------------------------------------------------------
# Beheert gebruikersprofielen en checkt logingegevens
# -------------------------------------------------------------------
# Historie:
# 28-03-2005
# . gemaakt voor S.P.I.R.I.T.
# 01-05-2006
# . ap04, met dank aan C.S.R. Delft code!!
#

class APUser {
	### private ###
	
	# SimpleXML object met de userdb erin
	var $_userdb;
	# profiel van de huidige gebruiker, uit userdb gehaald
	var $_profiel;

	### public ###
	function APUser() {
		# XML inladen
		# openen xml bestand met accounts
		$this->_userdb = simplexml_load_file(ETC_PATH.'/userdb.xml');

		# we starten op aan het begin van een pagina
		# kijken in de sessie of er	een gebruiker in staat
		# en of dit een gebruiker is die een profiel heeft in de instellingen
		if (!isset($_SESSION['_apuserid']) or !$this->reloadProfile()) {
			# zo nee, dan nobody user er in gooien...
			# in dit geval is het de eerste keer dat we een pagina opvragen
			# of er is net uitgelogd waardoor de gegevens zijn leeggegooid
			$remoteip = $_SERVER['REMOTE_ADDR'];
			$remotename = gethostbyaddr($_SERVER['REMOTE_ADDR']);
			aplog("new session start from {$remotename}[{$remoteip}]");
			$this->login('nobody');
		}
	}

	# Deze functie wordt door het inlogscript gebruikt: checken of een
	# ingevulde user/password bestaat, en zo ja, dan wordt de gebruiker
	# in de sessie gerost.
	function login($apuserid, $pass = '', $checkip = true) {
		# remote host opzoeken
		$remoteip = $_SERVER['REMOTE_ADDR'];
		$remotename = gethostbyaddr($_SERVER['REMOTE_ADDR']);

		# controleer patroon gebruikersnaam
		if (!preg_match('/^\w{3,20}$/', $apuserid)) {
			aplog("FATAL login apuserid '{$apuserid}' doesn't match /^\w{3,20}$/");
			return false;
		}

		# zoek naar de gebruiker
		$apuser = $this->_userdb->xpath("/userdb/apuser[@apuserid='{$apuserid}']");
		# als het er niet precies 1 is, dan is het mis...
		if (count($apuser) > 1) {
			aplog("FATAL login duplicate user in userdb! {$apuserid}");
			return false;
		} elseif (count($apuser) < 1) {
			aplog("FAILED login unknown user in userdb: {$apuserid}");
			return false;
		}

		# als het er wel precies 1 is, dan is het een simplexml object
		# wat hebben we nodig? -> auth dingen onder deze gebruiker
		$auth = $this->_userdb->xpath("/userdb/apuser[@apuserid='{$apuserid}']/auth");
		if (count($auth) < 1) {
			aplog("FAILED login apuser {$apuserid} has no authentication methods");
			return false;
		}

		# nu gaan we de auth units proberen.
		require_once("class.authmech.php");
		$authenticated = false;
		foreach ($auth as $auth_id) {
			# we maken het goede type object dat voor ons kan praten met de database met logingegevens
			$authmechobj = AuthFactory::Create($auth_id['mech']);
			# kijken of pass klopt
			# zo ja, zet vlag, en kijk niet verder
			if ($authmechobj->authenticate(strval($auth_id),$pass)) {
				$authenticated = true;
				break;
			}
		}

		if ($authenticated === true) {
			aplog(sprintf ("OK: login apuserid=%s client=%s[%s]", $apuserid, $remotename, $remoteip));
			# \o het is gelukt
			
			# username in sessie zetten
			$_SESSION['_apuserid'] = $apuserid;
			
			# sessie koppelen aan ip?
			if ($checkip == true) $_SESSION['_ip'] = $remoteip . "/32";
			else $_SESSION['_ip'] = $remoteip . "/0";
			
			# profiel inladen
			$this->reloadProfile();		
			return true;
		} else {
			aplog(sprintf("FAILED login apuserid=%s client=%s[%s]", $apuserid, $remotename, $remoteip));
		}

		return false;
	}

	function logout() {
		session_unset();
		$this->login('nobody');
	}

	function reloadProfile() {
		$this->_profiel = array(
			'fullname' => array(),
			'mail' => array(),
			'www' => array(),
			'mailalias' => array(),
			'mailrelay' => array(),
			'mailbox' => array(),
			'mysqldb' => array()
		);
		
		# we doen een xpath query om de huidige gebruiker te vinden
		# en vullen dan zijn profiel
		foreach ($this->_profiel as $veld => $foo) {
			$sx = $this->_userdb->xpath(sprintf("/userdb/apuser[@apuserid='%s']/%s", $_SESSION['_apuserid'], $veld));
			foreach ($sx as $element) {
				$value = strval($element);
				if ($value == "*") {
					$this->_profiel[$veld] = array();
					$sx2 = $this->_userdb->xpath(sprintf("/userdb/apuser/%s", $veld));
					foreach ($sx2 as $element2) {
						if ($element2 != "*") $this->_profiel[$veld][] = strval($element2);
					}
					break;
				}
				$this->_profiel[$veld][] = strval($element);
			}
		}
		
		return true;
	}

	# allerlei info opvragen uit het profiel
	function isLoggedIn() { return (isset($_SESSION['_apuserid']) and $_SESSION['_apuserid'] != 'nobody');}	
	function getAPUserID() { return $_SESSION['_apuserid']; }

	function getWWW()       { return $this->_profiel['www']; }
	function getMailAlias() { return $this->_profiel['mailalias']; }
	function getMailRelay() { return $this->_profiel['mailrelay']; }
	function getMailbox()   { return $this->_profiel['mailbox']; }
	function getMySQLDB()   { return $this->_profiel['mysqldb']; }

	function hasWWW($dc)       { return in_array($dc, $this->_profiel['www']); }
	function hasMailAlias($dc) { return in_array($dc, $this->_profiel['mailalias']); }
	function hasMailRelay($dc) { return in_array($dc, $this->_profiel['mailrelay']); }
	function hasMailBox($dc)   { return in_array($dc, $this->_profiel['mailbox']); }
	function hasMySQLDB($dc)   { return in_array($dc, $this->_profiel['mysqldb']); }

}

?>
