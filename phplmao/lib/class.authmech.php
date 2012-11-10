<?php

#
# Jeugdkerken NL
#
# -------------------------------------------------------------------
# class.authmech.php
# -------------------------------------------------------------------
# Authemticatiemechanismes
# -------------------------------------------------------------------
# Historie:
# 28-03-2005
# . gemaakt voor S.P.I.R.I.T.
#

#
# De klasse AuthFactory bevat alleen een Factory Method, die een object
# van de gewenste Authenticatiesoort teruggeeft.
#
# Bij het toevoegen van een nieuw authenticatiemechanisme moet er dus
# een mapping tussen de naam en de klasse worden gemaakt in de Factory
#

require_once('class.apldap.php');

class AuthFactory {
	function Create($mech) {
		//if ($mech == 'ldap_people') return new LDAPAuthMech('people');
		//if ($mech == 'ldap_antiplesk') return new LDAPAuthMech('antiplesk');
		//if ($mech == 'ldap_mailbox') return new LDAPAuthMech('mailbox');
		if ($mech == 'md5') return new MD5AuthMech();
		if ($mech == 'ipv4') return new IPAuthMech();
		if ($mech == 'true') return new TrueAuthMech();
		return new AuthMech();
	}
}

# De authenticatiemechanismes overerven van AuthMech en herdefinieren
# de authenticate() functie. Standaard geeft deze een false, zodat
# de authenticatie faalt.
# Password veranderen kan met het oude password en een nieuwe, als
# het authenticatiemechanisme het ondersteunt.

class AuthMech {
	function authenticate($user,$pass) { return false; }
	function changepass($user, $oldpass, $newpass) { return false; }
}

// Broken!
class LDAPAuthMech extends AuthMech {
	var $_mech = '';

	function LDAPAuthMech($mech) {
		$this->_mech = $mech;
	}

	function authenticate($user,$pass) {
		# We gaan het password checken via de LDAP klasse
		$apldap = new APLDAP(false);
		#aplog("LDAPAuthMech::authenticate - mech={$this->_mech} user={$user} pass={$pass}");
		return $apldap->checkBindPass($this->_mech, $user, $pass);
	}
	function changepass($user, $oldpass, $newpass) { return false; }
}

class TrueAuthMech extends AuthMech {
	# altijd goed, wordt voor nobody gebruikt
	function authenticate($user,$pass) { return true; }
}

class IPAuthMech extends AuthMech {
	function authenticate($user,$pass) {
		$ipauth = parse_ini_file(ETC_PATH."/ipauth.ini");
		if (!array_key_exists($user, $ipauth)) return false;
		$cidrs = explode(':', $ipauth[$user]);
		foreach ($cidrs as $cidr) {
			#aplog(sprintf("IPAuthMech::authenticate - trying to match ipv4: %s in %s", $_SERVER['REMOTE_ADDR'], trim($cidr)));
			if (matchCIDR($_SERVER['REMOTE_ADDR'], trim($cidr))) return true;
		}
		return false;
	}
}

class MD5AuthMech extends AuthMech {
	function authenticate($user,$pass) {
		return (md5($pass) == $user);
	}
}

?>
