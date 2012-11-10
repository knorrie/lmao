<?php

#
# S.P.I.R.I.T.
#
# -------------------------------------------------------------------
# class.boxeditcontent.php
# -------------------------------------------------------------------
#
# Historie:
# 28-05-2006 Hans van Kranenburg
# . aangemaakt

require_once("class.simplehtml.php");

class PWToolContent extends SimpleHTML {
	### private ###
	var $_apuser;
	var $_mailbox;

	### public ###
	function PWToolContent ($apuser, $mailbox) {
		$this->_apuser =& $apuser;
		$this->_mailbox =& $mailbox;
	}

	function view() {
	
		$editbox = $this->_mailbox->getEditBox();
		$terugzz = $this->_mailbox->getTerugZZ();
		$dc = $this->_mailbox->getDC();
		$error = $this->_mailbox->getError();

		print(<<<EOT
<div class="tekst"><br />
<div class="kopje1">Mailbox Passwordtool</div><br />

U kunt met onderstaande formulier het wachtwoord van een mailbox wijzigen. Typ in het eerste vak de volledige mailboxnaam
user@domain en typ tevens uw huidige wachtwoord, en twee maal een nieuw. Nadat u [ <b>Wijzig</b> ] heeft gekozen, zult u zien
wat het resultaat is.<br /><br />

EOT
		);

		if ($error != '') printf("<div class=\"kopje2\">N.B.: %s</div><br />\n", $error);

		printf(<<<EOT

<form action="%simappwd.php" method="post">
<input type="hidden" name="a" value="setpw">

<table cellspacing="0" cellpadding="3" border="0" class="table" witdh="650">

<!-- MAILBOXNAAM -->
<tr bgcolor="#DDDDDD"><td valign="top" colspan="5"><b>Mailboxnaam</b></td></tr>
<tr bgcolor="#EEEEEE">
<td valign="top" colspan="5"><input type="text" name="uid" style="width:400px;" value="%s" class="tekstedit"></td>
</tr>

<!-- WACHTWOORD OUD -->
<tr bgcolor="#DDDDDD"><td valign="top" colspan="5"><b>Oude Wachtwoord</b></td></tr>
<tr bgcolor="#EEEEEE">
<td valign="top" colspan="5"><input type="password" name="oldpass" style="width:400px;" value="" class="tekstedit"></td>
</tr>

<!-- WACHTWOORD NIEUW -->
<tr bgcolor="#DDDDDD"><td valign="top" colspan="5"><b>Nieuwe wachtwoord</b></td></tr>
<tr bgcolor="#EEEEEE">
<td valign="top" colspan="5">
	<table cellspacing="0" cellpadding="0" border="0" class="table">
	<td valign="middle" width="222">Wachtwoord: <input type="password" name="nwpass" style="width:180px;" value="" class="tekstedit"></td>
	<td valign="middle" width="222">Bevestig: <input type="password" name="nwpass2" style="width:180px;" value="" class="tekstedit"></td>
	<td valign="bottom" width="200" align="right"><input type="image" src="%schange.gif" width="58" height="13" alt="Wijzig"></td>
	</table>
</td>
</tr>

</table>

</form>

</div>

EOT
			,
			AP_URL,
			mb_htmlentities($terugzz['uid']),

			AP_PICS
		);
	}
}	
	
?>
