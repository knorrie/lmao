<?php

#
# S.P.I.R.I.T.
#
# -------------------------------------------------------------------
# class.mailalias.php
# -------------------------------------------------------------------
#
# Historie:
# 23-05-2006
# . aangemaakt, ter vervanging van class.apmail.php
#

require_once("include.apmailutils.php");
require_once("class.apldap.php");

class MailAlias {
	
	#
	var $_apldap;
	var $_apuser;
	# welk domein zijn we in bezig?
	var $_dc;
	
	# foutmelding die in content-klasse wordt gebruikt
	var $_error = '';
	# hierin proppen we de waarden die in de invulvakken worden teruggezet
	# handig voor als er invoerfouten zijn en de gebruiker wordt terugverwezen
	# naar het invulscherm
	var $_terugzz = array('cn' => '', 'uid' => '', 'mail' => '');
	
	# mailalias die tijdens aliasedit bewerkt wordt
	var $_editalias;
	
	function MailAlias($apuser, $dc = '') {
		$this->_apuser =& $apuser;
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
	
	function getEditAlias() {
		return $this->_editalias;
	}
	
	# opvragen van een of meer aliassen
	# als we geen cn opgeven worden alle aliassen gezocht
	function getAlias($cn = '') {
		$list = $this->_apldap->getMailAlias($this->_dc, $cn);
		# nu hebben we de ldap-uitvoer
		# die gaan we omzetten naar een array die mailaliascontent kan laten zien
		# zinvolle array bakken met de info van deze alias zodat-ie
		# door de alias-edit bewerkt kan worden
		$result = array();
		for ($n=0; $n<$list['count']; $n++) {
			$alias = array();
			$alias['dn'] = $list[$n]['dn'];
			$alias['cn'] = $list[$n]['cn'][0];
			
			# Als er 'ontvangt voor' adressen zijn...
			if (isset($list[$n]['uid']))
			for ($i=0; $i<$list[$n]['uid']['count']; $i++) {
				list ($usr,$dom) = explode ('@', $list[$n]['uid'][$i]);
				# domein er af halen...
				$alias['uid'][$usr] = '';
			}
			
			# Als er aliassen zijn...
			if (isset($list[$n]['mail']))
			for ($i=0; $i<$list[$n]['mail']['count']; $i++) {
				# adres uitpluizen
				list ($usr,$dom) = explode ('@', $list[$n]['mail'][$i]);
				$error = '';
				if ($dom != "mailbox.{$this->_dc}") {
					# Een IP adres in [] heeft geen DNS...
					if (!isBracketIP($dom)) {
						# onderzoeken of het bestaat in DNS.
						$hasa  = checkdnsrr($dom, 'A');
						$hasmx = checkdnsrr($dom, 'MX');
						if (!$hasa and !$hasmx) $error = "Waarschuwing: Het domein van dit adres bestaat niet";
						elseif (!$hasmx) $error = "Waarschuwing: Het domein van dit adres is niet geconfigureerd om email te ontvangen (geen MX)";
					}
				}
				$alias['mail'][$list[$n]['mail'][$i]] = $error;
			}
			$result[] = $alias;
		}
		
		function cmp($s1, $s2) { return strnatcasecmp($s1['cn'], $s2['cn']); }
		usort($result, 'cmp');
		
		return $result;
	}
	
	# een nieuwe alias aanmaken
	function addAlias($cn = '') {
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
		# bestaat de alias al?
		if ($this->_apldap->isMailAlias($this->_dc, $cn) !== false) {
			$this->_error = "MailAlias bestaat al";
			return false;
		}
		# toevoegen, op dit punt moet de $cn goed zijn wat betreft geldige tekens enzo
		if ($this->_apldap->addAlias($this->_dc, $cn) === false) {
			$this_error = "Toevoegen is mislukt :-( (databaseproblemen?)";
			return false;
		}
		return true;
	}

	# een alias verwijderen
	function removeAlias($cn) {
		if (mb_strlen($cn) == 0 or mb_strlen($cn) > 100) {
			$this->_error = "Omschrijving is verplicht en mag max. 100 karakters zijn";
			return false;
		}
		# is het geldige utf8?
		if (!is_utf8($cn)) {
			$this->_error = "Omschrijving bevat ongeldige karakters";
			return false;
		}
		# bestaat de alias?
		if ($this->_apldap->isMailAlias($this->_dc, $cn) === false) {
			$this->_error = "MailAlias bestaat niet";
			return false;
		}
		# verwijderen, op dit punt moet de $cn goed zijn wat betreft geldige tekens enzo
		if ($this->_apldap->removeAlias($this->_dc, $cn) === false) {
			$this->_error = "Verwijderen is mislukt :-( (databaseproblemen?)";
			return false;
		}
		return true;
	}
	
	function isMailAlias($cn) {
		return $this->_apldap->isMailAlias($this->_dc, $cn);
	}
	
	#### functies om een mailalias zelf te bewerken ####
	
	function loadEditAlias ($cn) {
		if (mb_strlen($cn) == 0 or mb_strlen($cn) > 100) {
			$this->_error = "Omschrijving is verplicht en mag max. 100 karakters zijn";
			return false;
		}
		# is het geldige utf8?
		if (!is_utf8($cn)) {
			$this->_error = "Omschrijving bevat ongeldige karakters";
			return false;
		}
		# bestaat de alias?
		if ($this->_apldap->isMailAlias($this->_dc, $cn) === false) {
			$this->_error = "MailAlias bestaat niet";
			return false;
		}
		# ophalen alias
		$alias = $this->getAlias($cn);
		if (count($alias) != 1) {
			$this->_error = "MailAlias bestaat niet";
			return false;
		}
		$this->_editalias = $alias[0];
		# we zetten standaard de cn in het invulvak om makkelijk wijzigen mogelijk te maken
		$this->_terugzz['cn'] = $this->_editalias['cn'];
		return true;
	}
	

	# omschrijving van een alias veranderen
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
		# bestaat de nieuwe omschrijving al?
		if ($cn != $this->_editalias['cn'] and $this->_apldap->isMailAlias($this->_dc, $cn)) {
			$this->_error = "Er bestaat al een alias met deze omschrijving";
			return false;
		}
		$this->_editalias['cn'] = $cn;
		return true;
	}

	function adduid($uid) {
		$this->_terugzz['uid'] = $uid;
		$uid = strtolower($uid);
		$uids = explode(',',$uid);
		foreach ($uids as $uid) {
			$uid = trim($uid);
			if (strlen($uid) == 0) {
				$this->_error = "Er is geen adres ingevuld.";
				return false;
			}
			if (strlen($uid) > 127) {
				$this->_error = "Het adres mag maximaal 127 tekens lang zijn";
				return false;
			}
			if (!isValidLocalpart($uid)) {
				$this->_error = "Het adres bevat ongeldige tekens.";
				return false;
			}
			# bestaat het toevallig al?
			if (isset($this->_editalias['uid'][$uid])) {
				$this->_error = "Deze alias ontvangt al email op dit adres";
				return false;
			}
			# toevoegen
			$this->_editalias['uid'][$uid] = '';
		}
		return true;
	}

	function rmuid($uid) {
		# bestaat het toevallig wel?
		if (!isset($this->_editalias['uid'][$uid])) {
			$this->_error = "Ik kan geen adres verwijderen dat er niet in staat...";
			return false;
		}
		# verwijderen
		unset($this->_editalias['uid'][$uid]);
		return true;
	}

	function addmail($mail) {
		$this->_terugzz['mail'] = $mail;
		$mail = strtolower($mail);
		$mails = explode(',',$mail);
		foreach ($mails as $mail) {
			$mail = trim($mail);
			# zit er wel een @ in?
			if (mb_strpos($mail,'@') === false) {
				$this->_error = "Dit lijkt echt niet op een emailadres...";
				return false;
			}
			if (strlen($mail) > 127) {
				$this->_error = "Het adres mag maximaal 127 tekens lang zijn";
				return false;
			}
			# zo ja, ontleden
			list ($usr,$dom) = mb_split ('@', $mail);
			if (!isValidLocalpart($usr)) {
				$this->_error = "Het adres bevat ongeldige tekens.";
				return false;
			}
			if (!isValidDomain($dom) and !isBracketIP($dom)) {
				$this->_error = "Het domein van het adres is ongeldig.";
				return false;
			}
			# bestaat het toevallig al?
			if (isset($this->_editalias['mail'][$mail])) {
				$this->_error = "Email wordt al bezorgd naar dit adres via deze alias";
				return false;
			}	
			# alias opslaan
			$this->_editalias['mail'][$mail] = '';
		}
		return true;
	}

	function rmmail($mail) {
		# bestaat het wel?
		if (!isset($this->_editalias['mail'][$mail])) {
			$this->_error = "Ik kan geen adres verwijderen dat er niet in staat...";
			return false;
		}
		# verwijderen
		unset($this->_editalias['mail'][$mail]);
		return true;
	}

	function saveEditAlias () {
		# van de editalias array maken we een andere array die ldap in kan...
		$entry = array();
		$entry['cn'] = $this->_editalias['cn'];
		# local-parts waarop email ontvangen wordt...
		if (isset($this->_editalias['uid'])) {
			$entry['uid'] = array_keys($this->_editalias['uid']);
			# ...worden voorzien van de context
			foreach ($entry['uid'] as $i => $uid) $entry['uid'][$i] = $uid . '@' . $this->_dc;
		}
		# doeladressen
		if (isset($this->_editalias['mail'])) $entry['mail'] = array_keys($this->_editalias['mail']);
		# aanpassen
		if (!$this->_apldap->modifyAlias($this->_dc, $entry['cn'], $entry)) {
			$this->_error = "Het aanpassen van de omschrijving is mislukt... (databaseprobleem?)";
			return false;
		}
		return true;
	}

}

?>
