<?php

#
# S.P.I.R.I.T.
#
# -------------------------------------------------------------------
# class.menu.php
# -------------------------------------------------------------------
# Menu links, met opties afhankelijk van wel/niet ingelogd zijn
#
# -------------------------------------------------------------------
#

require_once('class.simplehtml.php');

class Menu extends SimpleHTML {
	### private ###
	var $_apuser;

	### public ###
	function Menu(&$apuser) {
		$this->_apuser =& $apuser;
	}

	function view() {
		$loggedin = $this->_apuser->isLoggedIn();	
	
		print(<<<EOT

<div class="tekst"><br>
<div class="kopje2">S.P.I.R.I.T. v 0.4</div><br>
EOT
		);

		#
		# Inlogsysteem
		#

		if ($loggedin) {
			# al ingelogd, dan uitlogoptie weergeven
			
			$username = mb_htmlentities($this->_apuser->getAPUserID());
			$uitlogknop = AP_PICS . "logout.gif";
			$logout_url = AP_URL . "logout.php";
			print(<<<EOT
<center>
U bent: <b>{$username}</b>
<form id="frm_login" action="{$logout_url}" method="post">
<p>
<input type="hidden" name="url" value="{$_SERVER["REQUEST_URI"]}" />
<input type="image" src="{$uitlogknop}" style="width: 80px; height: 13px;" alt="[ uitloggen ]" name="foo" value="bar" />
</p>
</form>
</center>
EOT
			);
		} else {
			# anders inlogvakken
			
			$inlogknop = AP_PICS . "login.gif";
			$login_url = AP_URL . "login.php";
			
			if (isset($_SESSION['auth_error'])) {
				$auth_error = '<br /><br /><span class="teksterror">' . mb_htmlentities($_SESSION['auth_error']) . '</span><br />' . "\n";
				unset($_SESSION['auth_error']);
			} else $auth_error = '';

			print(<<<EOT
<center>
<form id="frm_login" action="{$login_url}" method="post">
<p>
<input type="hidden" name="url" value="{$_SERVER["REQUEST_URI"]}" />
Naam:<br /><input type="text" name="user" class="tekstedit" style="width: 140px;" /><br />
Wachtwoord:<br /><input type="password" name="pass" class="tekstedit" style="width:140px;" /><br />
<input type="image" src="{$inlogknop}" style="width: 76px; height: 13px;" alt="[ inloggen ]" name="foo" value="bar" /><br />
<input type="checkbox" name="checkip" value="true" id="login-checkip" checked>
<label for="login-checkip">Koppel login en IP-adres</label><br />

{$auth_error}
</p>
</form>

</center>
<hr />

EOT
			);
		}
			
		# ok, hieronder komen de menuopties....

		$haswww = count($this->_apuser->getWWW()) > 0;
		$hasalias = count($this->_apuser->getMailAlias()) > 0;
		$hasrelay = count($this->_apuser->getMailRelay()) > 0;
		$hasmailbox = count($this->_apuser->getMailbox()) > 0;
		$hasdb = count($this->_apuser->getMySQLDB()) > 0;

		#
		# Eerst een stukje algemene links, over nieuws en over de server enzo.
		# En eigen instellingen onder kopje Profiel
		printf("<b>Home</b><br />\n&#8226 <a href=\"%s\">Introductie</a><br />\n<br />\n", AP_URL);

		# if ($loggedin) printf(<b>Profiel</b><br />\n&#8226 <a href=\"%s\"><s>Contactgegevens</s></a><br />\n<br />\n", AP_URL);

		#if ($haswww) printf("<b>Website</b><br />\n&#8226 <a href=\"stats.php\">Statistieken</a><br /><br />", AP_URL);

		# Dan mail-instellingen

		print("<b>Email-Instellingen</b><br />\n");
		if ($hasalias) printf('&#8226 <a href="%smailalias.php">Email-Aliassen</a><br />' . "\n", AP_URL);
		if ($hasrelay) printf('&#8226 <a href="%smailrelay.php">Email Relaygroepen</a><br />' . "\n", AP_URL);
		if ($hasmailbox) printf('&#8226 <a href="%smailbox.php">Mailboxen</a><br />' . "\n", AP_URL);
		printf('&#8226 <a href="https://lists.example.com/">Mailman List Management</a><br />'."\n", AP_URL);
		#printf('&#8226 <a href="%simappwd.php">Mailbox-passwordTool</a><br /><br />'."\n", AP_URL);
		
		# Dan documentatie
		
		#print("<b>Documentatie</b><br />\n");
		#printf('&#8226 <a href="%simapuser.php">IMAP Mailboxen</a><br />'."\n", AP_URL);
		#if ($hasalias) print('&#8226 <a target="_blank" href="http://example.com/Mailman">Mailinglist (externe link)</a><br />'."\n");
		#print("<br />\n");

		# Dan overige links
		print("<b>Handige links</b><br />\n");
		if (WEBMAIL_URL != '') printf('&#8226 <a href="%s" target="_blank">Webmail</a><br />'."\n", WEBMAIL_URL);
		#if ($hasdb and PMA_URL != '') printf('&#8226 <a href="%s" target="_blank">phpMyAdmin</a><br />'."\n", PMA_URL);
		print("<br />\n");

		# Dan linkjes naar vrij te bekijken statistieken
		print("<b>Statistieken Algemeen</b><br />\n");
		if (MRTS_URL != '') printf('&#8226 <a href="%s" target="_blank">Dataverkeer</a><br />'."\n", MRTS_URL);
		if (MAILGRAPH_URL != '') printf('&#8226 <a href="%s" target="_blank">Emailverkeer</a><br />'."\n", MAILGRAPH_URL);
		if ($loggedin and MAILQUEUE_URL != '') printf('&#8226 <a href="%s" target="_blank">Mailserver Queue</a><br />'."\n", MAILQUEUE_URL);
		print(<<<EOT

</div>
<br clear="all" />
EOT
		);

	}

}

?>
