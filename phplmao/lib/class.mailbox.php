<?php

#
# S.P.I.R.I.T.
#
# -------------------------------------------------------------------
# class.mailbox.php
# -------------------------------------------------------------------
#
# Historie:
# 26-05-2006
# . aangemaakt
# 19-08-2010
# . max tekens mailboxnaam naar 100 gezet
#

require_once("class.apldap.php");

class MailBox {
	
	#
	var $_apldap;
	# welk domein zijn we in bezig?
	var $_dc;
	
	# foutmelding die in content-klasse wordt gebruikt
	var $_error = '';
	# hierin proppen we de waarden die in de invulvakken worden teruggezet
	# handig voor als er invoerfouten zijn en de gebruiker wordt terugverwezen
	# naar het invulscherm
	var $_terugzz = array('uid' => '', 'cn' => '');
	
	# mailbox die tijdens boxedit bewerkt wordt
	var $_editbox;
	
	function MailBox($dc = '') {
		$this->_apldap = new APLDAP('example');
		$this->setDC($dc);
	}
	
	function setDC($dc = '') {
		$this->_dc = $dc;
	}
	
	function getDC() {
		return $this->_dc;
	}
	
	function getError() {
		return $this->_error;
	}
	
	function getTerugZZ() {
		return $this->_terugzz;
	}
	
	function getEditBox() {
		return $this->_editbox;
	}
	

	# opvragen van een of meer mailboxen
	# als we geen uid opgeven worden alle mailboxen gezocht
	function getBox($uid = '') {
		$list = $this->_apldap->getMailBox($this->_dc, $uid);
		# nu hebben we de ldap-uitvoer
		# die gaan we omzetten naar een array die mailboxcontent kan laten zien
		$result = array();
		for ($n=0; $n<$list['count']; $n++) {
			$box = array();
			$box['dn'] = $list[$n]['dn'];
			$box['cn'] = $list[$n]['cn'][0];
			$box['sn'] = $list[$n]['sn'][0];
			$box['userpassword'] = $list[$n]['userpassword'][0];
			list ($usr,$dom) = explode ('@', $list[$n]['uid'][0]);
			# domein er af halen...
			$box['uid'] = $usr;
			$result[] = $box;
		}
			
		function cmp($s1, $s2) { return strnatcasecmp($s1['uid'], $s2['uid']); }
		usort($result, 'cmp');
		
		return $result;
	}
	
	# een nieuwe mailbox aanmaken
	function addBox($uid = '', $cn = '') {
		$this->_terugzz['uid'] = $uid;
		$this->_terugzz['cn'] = $cn;
		if (mb_strlen($uid) > 100) {
			$this->_error = "De mailboxnaam mag maximaal 100 karakters zijn";
			return false;
		}
		# beperkingen op tekens die gebruikt mogen worden
		if (!preg_match('/^[a-z0-9_.-]+$/', $uid)) {
			$this->_error = "Gebruik alleen a-z 0-9, _ - en . in de mailboxnaam.";
			return false;
		}
		# bestaat de mailbox al?
		if ($this->_apldap->isMailBox($this->_dc, $uid) !== false) {
			$this->_error = "MailBox bestaat al";
			return false;
		}
		# max 100 tekens omschrijving
		if (mb_strlen($cn) > 100) {
			$this->_error = "Omschrijving mag max. 100 karakters zijn";
			return false;
		}
		# is het geldige utf8?
		if (!is_utf8($cn)) {
			$this->_error = "Omschrijving bevat ongeldige karakters";
			return false;
		}
		# prima, dan genereren we nog even een wachtwoordje...
		$passwd = genpasswd(20);
		
		# toevoegen, op dit punt moet de $cn goed zijn wat betreft geldige tekens enzo
		if ($this->_apldap->addBox($this->_dc, $uid, $cn, $passwd) === false) {
			$this_error = "Toevoegen is mislukt :-( (databaseproblemen?)";
			return false;
		}
	
		/*
		# nu gaan we de mailbox in Cyrus IMAP nog aanmaken, anders kan er nog geen mail
		# bezorgd worden... hopelijk gaat dit een beetje stabiel zijn....
		
		require_once('Net/Cyrus.php');
		$conn = new Net_cyrus(sprintf("%s@%s",$uid,$this->_dc), $passwd, 'localhost', 143, 5);
		$conn->connect();

		# als de mailbox nog niet bestaat gaan we m aanmaken
		$folderlist = $conn->getFolderList('*');
		if (count($folderlist) == 0 ) {
			$mkbox = array ("INBOX", "INBOX.Drafts", "INBOX.Sent", "INBOX.Trash", "INBOX.Junk");
			foreach ($mkbox as $box) $conn->createMailbox($box);
		}
		$conn->disconnect();
		*/
		
		return true;
	}

	# een mailbox verwijderen
	function removeBox($uid) {
		if (mb_strlen($uid) > 100) {
			$this->_error = "De mailboxnaam mag maximaal 100 karakters zijn";
			return false;
		}
		# beperkingen op tekens die gebruikt mogen worden
		if (!preg_match('/^[a-z0-9_.-]+$/', $uid)) {
			$this->_error = "Gebruik alleen a-z 0-9, _ - en . in de mailboxnaam.";
			return false;
		}
		# bestaat de mailbox?
		if ($this->_apldap->isMailBox($this->_dc, $uid) !== true) {
			$this->_error = "MailBox bestaat niet";
			return false;
		}
		# verwijderen, op dit punt moet de $cn goed zijn wat betreft geldige tekens enzo
		if ($this->_apldap->removeBox($this->_dc, $uid) === false) {
			$this->_error = "Verwijderen is mislukt :-( (databaseproblemen?)";
			return false;
		}
		
		# vervolgens moeten we de mailbox zelf weggooien...
		# dit gaat gebeuren door een cronjob die 's nachts draait. we maken een
		# tmpfile in de map deletebox onder data
		$tmpfname = tempnam(DATA_PATH . '/deletebox', sprintf("%s%%%s-", $uid, $this->_dc));
		
		$handle = fopen($tmpfname, "w");
		fwrite($handle, sprintf("%s@%s\n", $uid, $this->_dc));
		fclose($handle);
		return true;
	}
	
	function isMailBox($uid) {
		if (mb_strlen($uid) > 100) {
			$this->_error = "De mailboxnaam mag maximaal 100 karakters zijn";
			return false;
		}
		# beperkingen op tekens die gebruikt mogen worden
		if (!preg_match('/^[a-z0-9_.-]+$/', $uid)) {
			$this->_error = "Gebruik alleen a-z 0-9, _ - en . in de mailboxnaam.";
			return false;
		}
		# kijken of de mailbox bestaat
		return $this->_apldap->isMailBox($this->_dc, $uid);
	}
		
	#### functies om een mailbox zelf te bewerken ####
	
	function loadEditBox ($uid) {
		if (mb_strlen($uid) > 100) {
			$this->_error = "De mailboxnaam mag maximaal 100 karakters zijn";
			return false;
		}
		# beperkingen op tekens die gebruikt mogen worden
		if (!preg_match('/^[a-z0-9_.-]+$/', $uid)) {
			$this->_error = "Gebruik alleen a-z 0-9, _ - en . in de mailboxnaam.";
			return false;
		}
		# bestaat de mailbox?
		if ($this->_apldap->isMailBox($this->_dc, $uid) !== true) {
			$this->_error = "MailBox bestaat niet";
			return false;
		}
		# ophalen mailboxinfo
		$box = $this->getBox($uid);
		if (count($box) != 1) {
			$this->_error = "MailBox bestaat niet";
			return false;
		}
		$this->_editbox = $box[0];
		# we zetten standaard de cn in het invulvak om makkelijk wijzigen mogelijk te maken
		$this->_terugzz['cn'] = $this->_editbox['cn'];
		return true;
	}	

	# omschrijving van een mailbox veranderen
	function setcn($cn) {
		$this->_terugzz['cn'] = $cn;
		if (mb_strlen($cn) == 0 or mb_strlen($cn) > 100) {
			$this->_error = "Omschrijving is verplicht en mag max. 100 karakters zijn";
			return false;
		}
		# is het geldige utf8?
    	if (!is_utf8($cn)) {
			$this->_error = "Omschrijving bevat ongeldige karakters";
			return false;
		}
		$this->_editbox['cn'] = $cn;
		return true;
	}	
	
	function setpw($nwpass, $nwpass2) {
		# is er wat nieuws ingevuld...?
		if ($nwpass == "" or $nwpass2 == "") {
			$this->_error = "Vul het nieuwe wachtwoord 2x in.";
			return false;
		}
		# daarna of de twee nieuwe overeenkomen
		if($nwpass != $nwpass2) {
			$this->_error = "De nieuwe wachtwoorden komen niet overeen.";
			return false;
		}
		# daarna of het nieuwe wel aan de veiligheidscriteria voldoet
		$tmperror = '';
		if(!isSecurePW($this->_editbox['uid'], $nwpass, $tmperror)) {
			$this->_error = $tmperror;
			return false;
		}
		# anders is het wel ok... en gaan we het password instellen
		$this->_editbox['userpassword'] = makepasswd($nwpass);
		$this->_error = "Het wachtwoord is met succes gewijzigd.";
		return true;
	}
	
	function saveEditBox () {
		# van de editbox array maken we een andere array die ldap in kan...
		$entry = array();
		$entry['uid'] = $this->_editbox['uid'];
		$entry['cn'] = $this->_editbox['cn'];
		$entry['userpassword'] = $this->_editbox['userpassword'];
		# aanpassen
		if (!$this->_apldap->modifyBox($this->_dc, $entry['uid'], $entry)) {
			$this->_error = "Het aanpassen van de omschrijving is mislukt... (databaseprobleem?)";
			return false;
		}
		return true;
	}
	
	# onderstaande functies worden gebruikt door de password-tool
	
	# is de opgegeven uid een geldige mailboxnaam?
	# $uid is een volledige mailboxnaam
	function pwToolCheckPass($uid,$oldpass) {
		# kijken of er een @ in zit
		if (strpos($uid, '@') === false) {
			$this->_error = "Typ een volledige mailboxnaam (user@domein)";
			return false;
		}
		# opsplitsen en kijken of de mailbox bestaat
		list ($usr,$dom) = explode('@',$uid);
		if (!preg_match("/^[a-z0-9]+([\\.-][a-z0-9]+)*\\.[a-z]{2,4}$/i", $dom)) {
			$this->_error = "Het domein van het adres is ongeldig.";
			return false;
		}
		# domein instellen
		$this->setDC($dom);
		# controleren of de mailbox bestaat
		if (!$this->isMailBox($usr)) {
			$this->_error = "De mailbox bestaat niet, of het oude wachtwoord is ongeldig.";
			return false;
		}
		# Kijken of we op de mailbox kunnen inloggen met oude wachtwoord
		require_once("class.authmech.php");
		$authmechobj = AuthFactory::Create('ldap_mailbox');
		if (!$authmechobj->authenticate($uid,$oldpass)) {
			$this->_error = "De mailbox bestaat niet, of het oude wachtwoord is ongeldig.";
			return false;
		}
		# in dat geval... klopt alles!
		$this->_terugzz['uid'] = $uid;
		return true;
	}

}

?>
