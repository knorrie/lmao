<?php

#
# S.P.I.R.I.T.
#
# -------------------------------------------------------------------
# class.mailboxcontent.php
# -------------------------------------------------------------------
#
# Historie:
# 28-05-2006 Hans van Kranenburg
# . aangemaakt
#

require_once("class.simplehtml.php");

class MailBoxContent extends SimpleHTML {
	### private ###
	var $_apuser;
	var $_mailbox;

	### public ###
	function MailBoxContent ($apuser, $mailbox) {
		$this->_apuser =& $apuser;
		$this->_mailbox =& $mailbox;
	}

	function view() {
		$maildcs = $this->_apuser->getMailBox();
		$dc = $this->_mailbox->getDC();
		$terugzz = $this->_mailbox->getTerugZZ();
		
		printf(<<<EOT
<div class="tekst"><br/>
<div class="kopje1">Mailboxen van %s</div>
<br/>

<table cellspacing="0" cellpadding="0" border="0" class="tekst">
<tr>
<td valign="top" width="210">
<div class="kopje2">Nieuwe mailbox toevoegen:</div>

<form action="%smailbox.php?dc=%s" method="post">
<input type="hidden" name="a" value="newbox">
<table cellspacing="0" cellpadding="1" border="0" class="table">
<tr><td>Mailboxnaam (zonder @context):</td></tr>
<tr height="25"><td valign="top"><input type="text" name="uid" size="30" value="%s" class="tekstedit"></td></tr>
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
			mb_htmlentities($terugzz['uid']),
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
		
		# evt. foutmelding bij het toevoegen of verwijderen van een mailbox
		if (($error = $this->_mailbox->getError()) != '') printf("<b>Foutmelding:</b> %s<br /><br />", $error);
		
		# nu gaan we de lijst met mailboxen afbeelden
		$mboxen = $this->_mailbox->getBox();
		$mboxen_aantal = count($mboxen);
		
		printf('<div class="kopje2">Er %s %s emailbox%s aanwezig</div><br />',
			($mboxen_aantal == 1) ? "is " : "zijn ",
			(int)$mboxen_aantal,
			($mboxen_aantal == 1) ? "" : "en "
		);
		
		if ($mboxen_aantal > 0) {
			$this->_view_list($mboxen, false, $dc);
			print('<br /><br />');
		}
	}

	function _view_list($list, $witregel, $dc) {
	
		print(<<<EOT
<table cellspacing="0" cellpadding="3" border="0" class="table">
<tr bgcolor="#FFFFFF">
<td valign="top" class="kopje2">Mailboxnaam:</td><td>&nbsp;</td>
<td valign="top" class="kopje2">Omschrijving:</td><td>&nbsp;</td>
</tr>
EOT
		);

		foreach ($list as $n => $item) {

			#$uid = substr($item['uid'], 0, strpos($item['uid'], '@')-1);

			$color = ($n%2==0) ? "#DDDDDD" : "#EEEEEE";

			printf(<<<EOT
<tr bgcolor="%s">
<td valign="top" width="250">%s</td><td>&nbsp;</td>
<td valign="top" width="500">%s</td>

EOT
				,
				$color,
				mb_htmlentities($item['uid']),
				mb_htmlentities($item['cn'])
			);

			printf(<<<EOT
<td valign="bottom">
	<table cellspacing="0" cellpadding="0" border="0" class="table">
	<tr bgcolor="%s">
	<td valign="top" width="91" align="center">
<!--
		<form name="frmdelete%s" action="%s?dc=%s" method="post" onSubmit="if (confirm('Weet u zeker dat u de mailbox %s wilt verwijderen?')) { document.forms.frmdelete%s.submit(); } return false;">
		<input type="hidden" name="a" value="deletebox">
		<input type="hidden" name="box" value="%s">
		<input type="image" src="%sdelete.gif" width="81" height="13" border=0 alt="Verwijder" name="foo" value="bar">
		</form>
-->
	</td>
	<td valign="top" width="68" align="center">

		<a href="editbox.php?dc=%s&uid=%s"><img src="%smeer.gif" width="58" height="12" border="0" alt="Meer..."></a>
	</td>
	</tr>
	</table>
</td>
</tr>
EOT
				,
				$color,
				(int)$n,
				$_SERVER['PHP_SELF'],
				mb_htmlentities($dc),
				mb_htmlentities($item['uid']),
				(int)$n,
				mb_htmlentities($item['cn']),
				AP_PICS,
				mb_htmlentities($dc),
				rawurlencode($item['uid']),
				AP_PICS
			);
		}
		print('</table>');
	}
}

?>
