<?php

# 
# include.common.php
# 
# (c) 2006 Jeugdkerken NL / C.S.R. Delft
# Hans van Kranenburg
# Jan-Pieter Waagmeester
#

function aplog($msg) {
	openlog("ap04", LOG_ODELAY, LOG_LOCAL0);
	syslog(LOG_INFO, $msg);
	closelog();
}

# http://nl.php.net/manual/en/function.ip2long.php
# User Contributed Notes
function matchCIDR($addr, $cidr) {
   list($ip, $mask) = explode('/', $cidr);
   $bitmask = ($mask != 0) ? 0xffffffff >> (32 - $mask) : 0x00000000;
   return ((ip2long($addr) & $bitmask) == (ip2long($ip) & $bitmask));
}

//over de hele site dezelfde htmlentities gebruiken....
function mb_htmlentities($string){
	return htmlentities($string, ENT_QUOTES, 'UTF-8');
}

// Returns true if $string is valid UTF-8 and false otherwise.
function is_utf8($string) {
   
   // From http://w3.org/International/questions/qa-forms-utf-8.html
   return preg_match('%^(?:
         [\x09\x0A\x0D\x20-\x7E]            # ASCII
       | [\xC2-\xDF][\x80-\xBF]            # non-overlong 2-byte
       |  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
       | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
       |  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
       |  \xF0[\x90-\xBF][\x80-\xBF]{2}    # planes 1-3
       | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
       |  \xF4[\x80-\x8F][\x80-\xBF]{2}    # plane 16
   )*$%xs', $string);
   
} // function is_utf8

function isSecurePW($uid, $passwd, &$error) {
	# We doen een aantal standaard checks die een foutmelding kunnen produceren...
	$error = "";

	$sim_uid = 0; $foo = similar_text($uid,$passwd,$sim_uid);

	# Korter dan 6 mag niet...
	if (mb_strlen($passwd) < 6) {
		$error = "Het wachtwoord moet minimaal 6 tekens lang zijn. :-/";
	# is het geldige utf8?
	} elseif (!is_utf8($passwd)) {
		$error = "Het nieuwe wachtwoord bevat ongeldige karakters... :-(";
	} elseif (preg_match('/^[0-9]*$/', $passwd)) {
		$error = "Het nieuwe wachtwoord moet ook letters of leestekens bevatten... :-|";
	} elseif (preg_match('/^[A-Za-z]*$/', $passwd)) {
		$error = "Het nieuwe wachtwoord moet ook een cijfer of leesteken bevatten... :-S";
	} elseif ($uid == $passwd) {
		$error = "Het wachtwoord mag niet gelijk zijn aan je gebruikersnaam! :-@";
	} elseif ($sim_uid > 60) {
		$error = "Het wachtwoord lijkt teveel op je gebruikersnaam ;-]";
	#} elseif () {
	}
	return ($error == "");
}

function genpasswd($n) {
	$pass = '';
	if ($n < 6) return genpasswd(6);
	for ($i = 0; $i < $n; $i++) $pass .= chr(rand(97,122));
	return $pass;
}

function makepasswd($pass) {
	$salt = mhash_keygen_s2k(MHASH_SHA1, $pass, substr(pack('h*', md5(mt_rand())), 0, 8), 4);
	return "{SSHA}" . base64_encode(mhash(MHASH_SHA1, $pass.$salt).$salt);
}

?>
