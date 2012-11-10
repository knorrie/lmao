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

class BoxEditContent extends SimpleHTML {
	### private ###
	var $_apuser;
	var $_mailbox;

	### public ###
	function BoxEditContent ($apuser, $mailbox) {
		$this->_apuser =& $apuser;
		$this->_mailbox =& $mailbox;
	}

	function view() {
	
		$editbox = $this->_mailbox->getEditBox();
		$terugzz = $this->_mailbox->getTerugZZ();
		$dc = $this->_mailbox->getDC();
		$error = $this->_mailbox->getError();

		printf(<<<EOT
<div class="tekst"><br />
<div class="kopje1">Emailbox bewerken</div><br />

N.B.
<ul>
<li>U kunt hier de eigenschappen van deze mailbox wijzigen. De wijzigingen zijn onmiddelijk actief!</li>
<li>Het is niet mogelijk om de mailboxnaam van een bestaande emailbox te wijzigen.</li>
<li>Het is niet nodig een mailbox-adres ook in de alias-instellingen als geldig adres op te geven. Als u email naar
meerdere adressen in dit maildomein in deze emailbox wilt afleveren, dan kan dat door bij de alias-instellingen
wel meerdere adressen naar deze mailbox te verwijzen.</li>
</ul>

<div class="kopje2">%s in context: %s</div><br />

EOT
			,
			mb_htmlentities($editbox['uid']),
			mb_htmlentities($dc)
		);

		if ($error != '') printf("<div class=\"kopje2\">FOUT: %s</div><br />\n", $error);

		printf(<<<EOT
<table cellspacing="0" cellpadding="3" border="0" class="table" witdh="650">

<!-- OMSCHRIJVING -->
<tr bgcolor="#DDDDDD"><td valign="top" colspan="5"><b>Omschrijving</b></td></tr>
<tr bgcolor="#EEEEEE"><td valign="top" colspan="5">%s</tr>
<tr bgcolor="#EEEEEE">
<td valign="top" colspan="5">
	<form action="%seditbox.php?dc=%s&uid=%s" method="post">
	<input type="hidden" name="a" value="setcn">
	<table cellspacing="0" cellpadding="0" border="0" class="table">
	<td valign="middle" width="444"><input type="text" name="cn" style="width:400px;" value="%s" class="tekstedit"></td>
	<td valign="middle" width="200" align="right"><input type="image" src="%schange.gif" width="58" height="13" alt="Wijzig"></td>
	</table>
	</form>
</td>
</tr>

<!-- WACHTWOORD -->
<tr bgcolor="#DDDDDD"><td valign="top" colspan="5"><b>Wachtwoord wijzigen:</b></td></tr>
<tr bgcolor="#EEEEEE">
<td valign="top" colspan="5">
	<form action="%seditbox.php?dc=%s&uid=%s" method="post">
	<input type="hidden" name="a" value="setpw">
	<table cellspacing="0" cellpadding="0" border="0" class="table">
	<td valign="middle" width="222">Wachtwoord: <input type="password" name="nwpass" style="width:180px;" value="" class="tekstedit"></td>
	<td valign="middle" width="222">Bevestig: <input type="password" name="nwpass2" style="width:180px;" value="" class="tekstedit"></td>
	<td valign="bottom" width="200" align="right"><input type="image" src="%schange.gif" width="58" height="13" alt="Wijzig"></td>
	</table>
	</form>
</td>
</tr>

<!-- VERWIJDEREN -->
<tr bgcolor="#DDDDDD"><td valign="top" colspan="5"><b>Mailbox verwijderen</b></td></tr>
<tr bgcolor="#EEEEEE">
<td valign="top" colspan="5">
	<form name="deletebox" action="%seditbox.php?dc=%s&uid=%s" method="post" onSubmit="if (confirm('Weet u zeker dat u deze mailbox wilt verwijderen?')) { document.forms.deletebox.submit(); } return false;">
	<input type="hidden" name="a" value="deletebox">
	<table cellspacing="0" cellpadding="0" border="0" class="table">
	<td valign="middle" width="444">
		Let op! Bij het verwijderen van een mailbox worden allereerst de inloggegevens gewist.
		De mailbox zelf zal na 24 uur door een geautomatiseerd proces gewist worden. *<b>Alle</b>*
		email die nog aanwezig was, alsmede webmail-instellingen gaan dan verloren. Als u in de
		tussentijd een mailbox met dezelfde naam als de gewiste mailbox aanmaakt, zal de oude
		email weer zichtbaar zijn, en zal de mailbox niet meer gewist worden.
	</td>
	<td valign="bottom" width="200" align="right"><input type="image" src="%sdelete.gif" width="81" height="13" alt="Verwijder"></td>
	</table>
	</form>
</td>
</tr>

</table>
<br />
[ <a href="%smailbox.php?dc=%s">Terug naar mailboxen %s</a> ]

</div>

EOT
			,
			mb_htmlentities($editbox['cn']),
			AP_URL,
			rawurlencode($dc),
			rawurlencode($editbox['uid']),
			mb_htmlentities($terugzz['cn']),
			AP_PICS,
			
			AP_URL,
			rawurlencode($dc),
			rawurlencode($editbox['uid']),
			AP_PICS,
			
			AP_URL,
			rawurlencode($dc),
			rawurlencode($editbox['uid']),
			AP_PICS,
			
			AP_URL,
			rawurlencode($dc),
			mb_htmlentities($dc)
		);
	}
}	
	
?>
