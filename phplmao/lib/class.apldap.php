<?php

#
# Jeugdkerken NL
#
# -------------------------------------------------------------------
# class.apldap.php
# -------------------------------------------------------------------
# Beheert LDAP toegang
#
# N.B. Let op dat functies in deze klasse verantwoordelijk zijn voor
# de data die LDAP in gaat. Maak dus op de juiste manier gebruik
# van de ldap_escape_(dn|attribute) functies!
#
# -------------------------------------------------------------------
# Historie:
# 31-03-2005
# . gemaakt voor S.P.I.R.I.T.
# 07-04-2006
# . geimporteerd in ap0.4
#

class APLDAP {
	### private ###
	var $_server = "";
	var $_conn = false;
	
	var $_base_people;
	var $_base_mailalias;
	var $_base_mailrelay;
	var $_base_mailbox;
	var $_base_antiplesk;

   	function APLDAP($server, $dobind = true) {
   		# bepaal of we alleen verbinding maken, of ook meteen inloggen.
   		# standaard is dit gewenst, in het geval dat deze klasse gebruikt
   		# wordt om met een ldap bind gebruikersinfo te controleren niet.
		$this->_server = $server;
   		$this->connect($dobind);
   	}

	# Openen van de LDAP connectie, die we regelmatig nodig hebben...
	function connect($dobind) {
		# zijn we al ingelogd?
		if ($this->_conn !== false) $this->disconnect();
		
		# parse sections
		$ldapini = parse_ini_file(ETC_PATH."/ldap.ini",true);
		$conn = ldap_connect($ldapini[$this->_server]['ldap_host'], $ldapini[$this->_server]['ldap_port']);
		ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
		if ($dobind === true) {
			$bind = ldap_bind($conn, $ldapini[$this->_server]['ldap_binddn'], $ldapini[$this->_server]['ldap_passwd']);
			if ($bind !== true) return false;
		}
		# Onthouden van wat instellingen
		$this->_conn = $conn;
		$this->_base_people    = $ldapini[$this->_server]['ldap_base_people'];
		$this->_base_mailalias = $ldapini[$this->_server]['ldap_base_mailalias'];
		$this->_base_mailrelay = $ldapini[$this->_server]['ldap_base_mailrelay'];
		$this->_base_mailbox   = $ldapini[$this->_server]['ldap_base_mailbox'];
		$this->_base_antiplesk = $ldapini[$this->_server]['ldap_base_antiplesk'];
		return true;
	}

	# verbinding sluiten, maar alleen als er een geldige resource is
	function disconnect() {
		@ldap_close ($this->_conn);
		$this->_conn = false;
	}
	
	# functie voor LDAPAuthMech (class.authmech.php) om gebruikersinlog te verifieren
	function checkBindPass($mech, $user, $pass) {
		$validbase = array(
			'people'    => $this->_base_people,
			'antiplesk' => $this->_base_antiplesk,
			'mailbox'     => $this->_base_mailbox
		);
		if (!array_key_exists($mech, $validbase)) return false;
		
		# sanitaire controle
		if (!is_utf8($user)) return false;
		if (!is_utf8($pass)) return false;
		
		# als er geen bindingsangst is gaan we proberen met de ldap te binden...
		if (@ldap_bind($this->_conn, sprintf("uid=%s,%s", $this->ldap_escape_dn($user), $validbase[$mech]), $pass)) return true;
		return false;
	}

	#### MailAlias ####

	# controleert of het domein wat we willen gebruiken wel in ldap voorkomt
	function isMailAliasDomain($dc) {
    	$filter = sprintf("(ou=%s)", $this->ldap_escape_filter($dc));
		$result = ldap_search($this->_conn, $this->_base_mailalias, $filter);
		$num = ldap_count_entries($this->_conn, $result);
		if ($num == 0 or $num === false) return false;
		return true;
	}

	# controleert of een alias met de betreffende 'common name' voorkomt binnen een domain context
	function isMailAlias($dc, $cn) {
		$base = "ou=" . $this->ldap_escape_dn($dc) . ",{$this->_base_mailalias}";
		$filter = sprintf("(cn=%s)", $this->ldap_escape_filter($cn));
		$result = ldap_search($this->_conn, $base, $filter);
		$num = ldap_count_entries($this->_conn, $result);
		if ($num == 0 or $num === false) return false;
		return true;
	}

	# een, of alle aliassen binnen een domein opvragen
	function getMailAlias($dc, $cn='') {
		$base = "ou=" . $this->ldap_escape_dn($dc) . ",{$this->_base_mailalias}";
		if ($cn == '') $filter = "(cn=*)" ;
		else $filter = sprintf("(cn=%s)", $this->ldap_escape_filter($cn));
		$result = ldap_search($this->_conn, $base, $filter);
		$aliaslist = ldap_get_entries($this->_conn, $result);
		return $aliaslist;
	}

	# Voeg een nieuwe alias toe aan een domein
	function addAlias($dc, $cn) {
		$base = "ou={$dc},{$this->_base_mailalias}";
		$dn = 'cn=' . $this->ldap_escape_dn($cn) . ', '. $base;

		# objectClass definities
		$entry = array();
		$entry['objectClass'][] = 'top';
		$entry['objectClass'][] = 'person';
		$entry['objectClass'][] = 'inetOrgPerson';
		$entry['cn'] = $cn;
		$entry['sn'] = time();
		
		return ldap_add($this->_conn, $dn, $entry);
	}

	# Wijzig de informatie van een alias
	# N.B. $entry is een array die al in het juiste formaat moet zijn opgemaakt
	# http://nl2.php.net/manual/en/function.ldap-add.php
	function modifyAlias($dc, $cn, $entry) {
		$base = "ou={$dc},{$this->_base_mailalias}";
		$dn = 'cn=' . $this->ldap_escape_dn($cn) . ', '. $base;
		$entry['sn'] = date('r');
		
		return ldap_modify($this->_conn, $dn, $entry);
	}

	function removeAlias($dc, $cn) {
		$base = "ou={$dc},{$this->_base_mailalias}";
		$dn = 'cn=' . $this->ldap_escape_dn($cn) . ', '. $base;
		return ldap_delete($this->_conn, $dn);
	}
	
	#### MailRelay ####

	# controleert of het domein wat we willen gebruiken wel in ldap voorkomt
	function isMailRelayDomain($dc) {
    	$filter = sprintf("(ou=%s)", $this->ldap_escape_filter($dc));
		$result = ldap_search($this->_conn, $this->_base_mailrelay, $filter);
		$num = ldap_count_entries($this->_conn, $result);
		if ($num == 0 or $num === false) return false;
		return true;
	}

	# controleert of een relay group met de betreffende 'common name' voorkomt binnen een domain context
	function isMailRelay($dc, $cn) {
		$base = "ou=" . $this->ldap_escape_dn($dc) . ",{$this->_base_mailrelay}";
		$filter = sprintf("(cn=%s)", $this->ldap_escape_filter($cn));
		$result = ldap_search($this->_conn, $base, $filter);
		$num = ldap_count_entries($this->_conn, $result);
		if ($num == 0 or $num === false) return false;
		return true;
	}

	# een, of alle relaygroepen binnen een domein opvragen
	function getMailRelay($dc, $cn='') {
		$base = "ou=" . $this->ldap_escape_dn($dc) . ",{$this->_base_mailrelay}";
		if ($cn == '') $filter = "(cn=*)" ;
		else $filter = sprintf("(cn=%s)", $this->ldap_escape_filter($cn));
		$result = ldap_search($this->_conn, $base, $filter);
		$list = ldap_get_entries($this->_conn, $result);
		return $list;
	}

	# Voeg een nieuwe relaygroep toe aan een domein
	function addRelay($dc, $cn) {
		$base = "ou={$dc},{$this->_base_mailrelay}";
		$dn = 'cn=' . $this->ldap_escape_dn($cn) . ', '. $base;

		# objectClass definities
		$entry = array();
		$entry['objectClass'][] = 'top';
		$entry['objectClass'][] = 'person';
		$entry['objectClass'][] = 'inetOrgPerson';
		$entry['cn'] = $cn;
		$entry['sn'] = time();
		
		return ldap_add($this->_conn, $dn, $entry);
	}

	# Wijzig de informatie van een relaygroep
	# N.B. $entry is een array die al in het juiste formaat moet zijn opgemaakt
	# http://nl2.php.net/manual/en/function.ldap-add.php
	function modifyRelay($dc, $cn, $entry) {
		$base = "ou={$dc},{$this->_base_mailrelay}";
		$dn = 'cn=' . $this->ldap_escape_dn($cn) . ', '. $base;
		$entry['sn'] = date('r');
		
		return ldap_modify($this->_conn, $dn, $entry);
	}

	function removeRelay($dc, $cn) {
		$base = "ou={$dc},{$this->_base_mailrelay}";
		$dn = 'cn=' . $this->ldap_escape_dn($cn) . ', '. $base;
		return ldap_delete($this->_conn, $dn);
	}

	#### MailBox ####

	# controleert of een mailbox-login bestaat
	function isMailBox($dc, $uid) {
		$filter = sprintf("(uid=%s@%s)", $this->ldap_escape_filter($uid), $this->ldap_escape_filter($dc));
		$base = sprintf("ou=%s,%s",$dc,$this->_base_mailbox);
		$result = ldap_search($this->_conn, $base, $filter);
		$num = ldap_count_entries($this->_conn, $result);
		if ($num == 0 or $num === false) return false;
		return true;
	}

	# een, of alle aliassen binnen een domein opvragen
	function getMailBox($dc, $uid='') {
		if ($uid == '') $filter = sprintf("(uid=*@%s)", $this->ldap_escape_filter($dc));
		else $filter = sprintf("(uid=%s@%s)", $this->ldap_escape_filter($uid), $this->ldap_escape_filter($dc));
		$base = sprintf("ou=%s,%s",$dc,$this->_base_mailbox);
		$result = ldap_search($this->_conn, $base, $filter);
		return ldap_get_entries($this->_conn, $result);
	}

	# Voeg een nieuwe mailboxlogin toe aan een domein
	function addBox($dc, $uid, $cn, $pass) {
		$dn = sprintf("uid=%s,ou=%s,%s"
			, $this->ldap_escape_dn("{$uid}@{$dc}")
			, $this->ldap_escape_dn($dc)
			, $this->_base_mailbox
		);

		# objectClass definities
		$entry = array();
		$entry['objectClass'][] = 'top';
		$entry['objectClass'][] = 'person';
		$entry['objectClass'][] = 'organizationalPerson';
		$entry['objectClass'][] = 'inetOrgPerson';
		$entry['uid'] = sprintf("%s@%s", $this->ldap_escape_dn($uid), $this->ldap_escape_dn($dc));
		$entry['cn'] = $cn;
		$entry['sn'] = time();
		$entry['userPassword'] = makepasswd($pass);
		
		return ldap_add($this->_conn, $dn, $entry);
	}

	# Wijzig de informatie van een box
	# N.B. $entry is een array die al in het juiste formaat moet zijn opgemaakt
	# http://nl2.php.net/manual/en/function.ldap-add.php
	function modifyBox($dc, $uid, $entry) {
		$dn = sprintf("uid=%s,ou=%s,%s"
			, $this->ldap_escape_dn("{$uid}@{$dc}")
			, $this->ldap_escape_dn($dc)
			, $this->_base_mailbox
		);
		$entry['uid'] .= "@" . $dc;
		$entry['sn'] = time();
		return ldap_modify($this->_conn, $dn, $entry);
	}

	# Verwijder de authenticatie-informatie van de mailbox
	function removeBox($dc, $uid) {
		$dn = sprintf("uid=%s,ou=%s,%s"
			, $this->ldap_escape_dn("{$uid}@{$dc}")
			, $this->ldap_escape_dn($dc)
			, $this->_base_mailbox
		);
		return ldap_delete($this->_conn, $dn);
	}

	#### Escapen van LDAP-invoer ####

	# RFC2253
	function ldap_escape_dn($text) {
		# DN escaping rules
		# A DN may contain special characters which require escaping. These characters are:
		# , (comma), = (equals), + (plus), < (less than), > (greater than), ; (semicolon),
		# \ (backslash), and "" (quotation marks).
		$text = preg_replace ('/([,=+<>;"\x5C])/', '\\\\$1', $text);

		# In addition, the # (number sign) requires
		# escaping if it is the first character in an attribute value, and a space character
		# requires escaping if it is the first or last character in an attribute value.
		$text = preg_replace("/^#/", "\\#", $text);
		$text = preg_replace("/^ /", "\\ ", $text);

		return $text;
	}

	# RFC2254
	# If a value should contain any of the following characters
	#
	#   Character       ASCII value
	#   ---------------------------
	#   *               0x2a
	#   (               0x28
	#   )               0x29
	#   \               0x5c
	#   NUL             0x00
	#
	# the character must be encoded as the backslash '\' character (ASCII
	# 0x5c) followed by the two hexadecimal digits representing the ASCII
	# value of the encoded character. The case of the two hexadecimal
	# digits is not significant.
	function ldap_escape_filter($text) {
		# ascii control characters er uit gooien, die zijn niet nodig in deze applicatie
		$text = preg_replace('/[\x00-\x1F\x7F]/', '', $text);
		# zie opmerking hierboven, \ staat voorop!
		$search  = array("\\",   "*",    "(",    ")",    "\0"  );
		$replace = array("\\5C", "\\2A", "\\28", "\\29", "\\00");
		return str_replace($search, $replace, $text);
	}

}
?>
