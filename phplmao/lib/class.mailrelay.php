<?php

#
# S.P.I.R.I.T.
#
# -------------------------------------------------------------------
# class.mailrelay.php
# -------------------------------------------------------------------
#
#

require_once("include.apmailutils.php");
require_once("class.apldap.php");

class MailRelay {
	
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
	var $_terugzz = array('cn' => '', 'uid' => '');
	
	# mailrelay object dat bewerkt wordt
	var $_editrelay;
	
	function MailRelay($apuser, $dc = '') {
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
	
	function getEditRelay() {
		return $this->_editrelay;
	}
	
	# opvragen van een of meer relay recipients
	# als we geen cn opgeven wordt alles gezocht
	function getRelay($cn = '') {
		$list = $this->_apldap->getMailRelay($this->_dc, $cn);
		# nu hebben we de ldap-uitvoer
		# die gaan we omzetten naar een array die mailrelaycontent kan laten zien
		# zinvolle array bakken met de info van deze relay recipient zodat-ie
		# bewerkt kan worden
		$result = array();
		for ($n=0; $n<$list['count']; $n++) {
			$alias = array();
			$alias['dn'] = $list[$n]['dn'];
			$alias['cn'] = $list[$n]['cn'][0];
			
			# valid relay recipient addresses...
			if (isset($list[$n]['uid']))
			for ($i=0; $i<$list[$n]['uid']['count']; $i++) {
				list ($usr,$dom) = explode ('@', $list[$n]['uid'][$i]);
				# strip the domain part...
				$alias['uid'][$usr] = '';
			}
			$result[] = $alias;
		}
		
		function cmp($s1, $s2) { return strnatcasecmp($s1['cn'], $s2['cn']); }
		usort($result, 'cmp');
		
		return $result;
	}
	

	function addRelay($cn = '') {
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
		# bestaat de omschrijving al?
		if ($this->_apldap->isMailRelay($this->_dc, $cn) !== false) {
			$this->_error = "Omschrijving bestaat al";
			return false;
		}
		# toevoegen, op dit punt moet de $cn goed zijn wat betreft geldige tekens enzo
		if ($this->_apldap->addRelay($this->_dc, $cn) === false) {
			$this_error = "Toevoegen is mislukt :-( (databaseproblemen?)";
			return false;
		}
		return true;
	}

	# Remove a relay recipient group
	function removerelay($cn) {
		if (mb_strlen($cn) == 0 or mb_strlen($cn) > 100) {
			$this->_error = "Omschrijving is verplicht en mag max. 100 karakters zijn";
			return false;
		}
		# is het geldige utf8?
		if (!is_utf8($cn)) {
			$this->_error = "Omschrijving bevat ongeldige karakters";
			return false;
		}
		# bestaat de omschrijving?
		if ($this->_apldap->isMailRelay($this->_dc, $cn) === false) {
			$this->_error = "Deze omschrijving komt niet voor";
			return false;
		}
		# verwijderen, op dit punt moet de $cn goed zijn wat betreft geldige tekens enzo
		if ($this->_apldap->removeRelay($this->_dc, $cn) === false) {
			$this->_error = "Verwijderen is mislukt :-( (databaseproblemen?)";
			return false;
		}
		return true;
	}
	
	function isMailRelay($cn) {
		return $this->_apldap->isMailRelay($this->_dc, $cn);
	}
	
	#### functies om een relay recipient group zelf te bewerken ####
	
	function loadEditRelay ($cn) {
		if (mb_strlen($cn) == 0 or mb_strlen($cn) > 100) {
			$this->_error = "Omschrijving is verplicht en mag max. 100 karakters zijn";
			return false;
		}
		# is het geldige utf8?
		if (!is_utf8($cn)) {
			$this->_error = "Omschrijving bevat ongeldige karakters";
			return false;
		}
		# bestaat de omschrijving?
		if ($this->_apldap->isMailRelay($this->_dc, $cn) === false) {
			$this->_error = "Deze omschrijving komt niet voor";
			return false;
		}
		# ophalen relay info
		$relay = $this->getRelay($cn);
		if (count($relay) != 1) {
			$this->_error = "Deze omschrijving komt niet voor";
			return false;
		}
		$this->_editrelay = $relay[0];
		# we zetten standaard de cn in het invulvak om makkelijk wijzigen mogelijk te maken
		$this->_terugzz['cn'] = $this->_editrelay['cn'];
		return true;
	}
	

	# omschrijving van een relay veranderen
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
		if ($cn != $this->_editrelay['cn'] and $this->_apldap->isMailRelay($this->_dc, $cn)) {
			$this->_error = "Er bestaat al een relay adres met deze omschrijving";
			return false;
		}
		$this->_editrelay['cn'] = $cn;
		return true;
	}

	function adduid($uid) {
		$this->_terugzz['uid'] = $uid;
		$uid = strtolower($uid);
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
		if (isset($this->_editrelay['uid'][$uid])) {
			$this->_error = "Dit adres is al opgegeven voor deze groep";
			return false;
		}
		# toevoegen
		$this->_editrelay['uid'][$uid] = '';
		return true;
	}

	function rmuid($uid) {
		# bestaat het toevallig wel?
		if (!isset($this->_editrelay['uid'][$uid])) {
			$this->_error = "Ik kan geen adres verwijderen dat er niet in staat...";
			return false;
		}
		# verwijderen
		unset($this->_editrelay['uid'][$uid]);
		return true;
	}

	function saveEditRelay () {
		# van de relay array maken we een andere array die ldap in kan...
		$entry = array();
		$entry['cn'] = $this->_editrelay['cn'];
		# valid relay recipients...
		if (isset($this->_editrelay['uid'])) {
			$entry['uid'] = array_keys($this->_editrelay['uid']);
			# ...worden voorzien van de context
			foreach ($entry['uid'] as $i => $uid) $entry['uid'][$i] = $uid . '@' . $this->_dc;
		}
		# aanpassen
		if (!$this->_apldap->modifyRelay($this->_dc, $entry['cn'], $entry)) {
			$this->_error = "Het aanpassen van het object is mislukt... (databaseprobleem?)";
			return false;
		}
		return true;
	}

}

?>
