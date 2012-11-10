<?php

#
# include.apmailutils.php
#
# Hans van Kranenburg, beheer@jeugdkerken.nl
# (c)2006
#

# geldig local-part voor een email-adres
function isValidLocalpart($lp) {
	if (mb_strlen($lp) > 127) return false;
	# RFC 821
	# http://www.lookuptables.com/
	# Hmmmz, \x2E er uit gehaald ( . )
	if (preg_match('/[^\x21-\x7E]/',$lp)) return false;
	if (preg_match('/[\x3C\x3E\x28\x29\x5B\x5D\x5C\x2C\x3B\x40\x22]/',$lp)) return false;
	return true;
}

# geldige domeinnaam
function isValidDomain ($dc) {
	$regexp="/^[a-z0-9]+([\\.-][a-z0-9]+)*\\.[a-z]{2,4}$/i";
	return preg_match($regexp, $dc);
}

# ip-adres in brackets, als domein voor email-adres te gebruiken
function isBracketIP ($dc) {
	$regexp='/^\[(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})\]$/';
	$matches = array();
	if (!preg_match($regexp, $dc, $matches)) return false;
	for ($i=1; $i<=4; $i++)
		if (substr($matches[$i],0,1) == '0' or intval($matches[$i]) > 255) return false;
	return true;
}

?>
