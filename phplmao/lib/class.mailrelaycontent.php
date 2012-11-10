<?php

#
# S.P.I.R.I.T.
#
# -------------------------------------------------------------------
# class.mailrelaycontent.php
# -------------------------------------------------------------------
#
# Historie:
# 23-05-2006
# . copied from mailaliascontent and adjusted to relay editing
#

require_once("class.simplehtml.php");

class MailRelayContent extends SimpleHTML {
	### private ###
	var $_apuser;
	var $_mailrelay;

	### public ###
	function MailRelayContent ($apuser, $mailrelay) {
		$this->_apuser =& $apuser;
		$this->_mailrelay =& $mailrelay;
	}

	function view() {
		$maildcs = $this->_apuser->getMailRelay();
		$dc = $this->_mailrelay->getDC();
		$terugzz = $this->_mailrelay->getTerugZZ();
		
		printf(<<<EOT
<div class="tekst"><br/>
<div class="kopje1">Relay-adressen van %s</div>
<br/>

<table cellspacing="0" cellpadding="0" border="0" class="tekst">
<tr>
<td valign="top" width="210">
<div class="kopje2">Nieuwe groep toevoegen:</div>

<form action="%smailrelay.php?dc=%s" method="post">
<input type="hidden" name="a" value="newcn">
<table cellspacing="0" cellpadding="1" border="0" class="table">
<tr><td>Omschrijving:</td></tr>
<tr height="25"><td valign="top"><input type="text" name="cn" size="30" value="%s" class="tekstedit"></td></tr>
<tr><td valign="top"><input type="image" src="%sadd.gif" width="90" height="13" border="0" alt="Toevoegen"></td></tr>
</table>
</form>
</td>
EOT
			,
			mb_htmlentities($dc),
			AP_URL,
			mb_htmlentities($dc),
			mb_htmlentities($terugzz['cn']),
			AP_PICS
		);

		if (count($maildcs) > 1) {
			printf(<<<EOT
<td width="20">&nbsp;</td>
<td valign="top" width="260">
<div class="kopje2">Kies context:</div>
<form name="frmchangecontext" action="%s" method="post">
<table cellspacing="0" cellpadding="1" border="0" class="table">
<tr><td>Huidige context: <b>%s</b></td></tr>
<tr height="25"><td valign="top"><select name="selectdc" onchange="document.forms.frmchangecontext.submit()" class="tekstedit">
EOT
				,
				$_SERVER['PHP_SELF'],
				mb_htmlentities($dc)
			);
			foreach ($maildcs as $maildc) {
				printf('<option value="%s"%s>&nbsp;%s&nbsp;</option>',
					mb_htmlentities($maildc),
					($maildc == $dc) ? " selected" : "",
					mb_htmlentities($maildc)
				);
			}

			printf (<<<EOT
</select></td></tr>
<tr><td valign="top"><input type="image" src="%schange.gif" width="58" height="13" border=0 alt="Wijzig"></td></tr>
</table>
</form>

</td>
EOT
				,
				AP_PICS
			);
			
		}
		print(<<<EOT
</tr>
</table>
<br /><br />
EOT
		);
		
		# evt. foutmelding bij het toevoegen of verwijderen van een relay
		if (($error = $this->_mailrelay->getError()) != '') printf("<b>Foutmelding:</b> %s<br /><br />", $error);
		
		# nu gaan we de lijst met relays afbeelden
		$relays = $this->_mailrelay->getRelay();
		$relays_aantal = count($relays);
		
		printf('<div class="kopje2">Er %s %s email-relay groep%s gedefinieerd</div><br />',
			($relaysen_aantal == 1) ? "is " : "zijn ",
			(int)$relays_aantal,
			($relays_aantal == 1) ? "" : "en "
		);
		
		if ($relays_aantal > 0) {
			$this->_view_list($relays, false, $dc);
			print('<br /><br />');
		}
	}

	function _view_list($list, $witregel, $dc) {
		
		print(<<<EOT
<table cellspacing="0" cellpadding="3" border="0" class="table">
<tr bgcolor="#FFFFFF">
<td valign="top" class="kopje2">Ontvangt email voor:</td><td>&nbsp;</td>
<td valign="top" class="kopje2">&nbsp;</td><td>&nbsp;</td>
</tr>
EOT
		);

		foreach ($list as $n => $item) {
			printf(<<<EOT
<tr bgcolor="#DDDDDD">
<td valign="top" colspan="5"><b>%s</b></td>
</tr>
<tr bgcolor="#EEEEEE">
<td valign="top" width=250>
EOT
				,
				mb_htmlentities($item['cn'])
			);

			if (!isset($item['uid']) or count($item['uid']) == 0) print("-\n");
			else foreach ($item['uid'] as $uid => $error) {
				if ($error != '') print ('<span class="teksterror">');
				print(mb_htmlentities($uid));
				if ($error != '') print('</span>');
				print("<br />\n");
			}
			print(<<<EOT
</td><td>&nbsp;</td>
<td valign="top" width=500>
EOT
			);

			print("<br />\n");

			printf(<<<EOT
</td>
<td valign="bottom">
	<table cellspacing="0" cellpadding="0" border="0" class="table">
	<tr bgcolor="#EEEEEE">
	<td valign="top" width="76" align="center">
		<a href="editrelay.php?dc=%s&cn=%s"><img src="%sedit.gif" width="66" height="13" border="0" alt="Bewerk"></a>
	</td>
	<td valign="top" width=91 align="center">
		<form name="frmdelete%s" action="%s?dc=%s" method="post" onSubmit="if (confirm('Weet u zeker dat u deze relay wilt verwijderen?')) { document.forms.frmdelete%s.submit(); } return false;">
		<input type="hidden" name="a" value="deletecn">
		<input type="hidden" name="cn" value="%s">
		<input type="image" src="%sdelete.gif" width="81" height="13" border=0 alt="Verwijder" name="foo" value="bar">
		</form>
	</td>
	</tr>
	</table>
</td>
</tr>
EOT
				,
				mb_htmlentities($dc),
				rawurlencode($item['cn']),
				AP_PICS,
				(int)$n,
				$_SERVER['PHP_SELF'],
				mb_htmlentities($dc),
				(int)$n,
				mb_htmlentities($item['cn']),
				AP_PICS
			);
		}
		print('</table>');
	}
}

?>
