<?php

#
# S.P.I.R.I.T.
#
# -------------------------------------------------------------------
# class.aliaseditcontent.php
# -------------------------------------------------------------------
#
# Historie:
# 25-05-2006 Hans van Kranenburg
# . aangemaakt

require_once("class.simplehtml.php");

class AliasEditContent extends SimpleHTML {
	### private ###
	var $_apuser;
	var $_mailalias;

	### public ###
	function AliasEditContent ($apuser, $mailalias) {
		$this->_apuser =& $apuser;
		$this->_mailalias =& $mailalias;
	}

	function view() {
	
		$editalias = $this->_mailalias->getEditAlias();
		$terugzz = $this->_mailalias->getTerugZZ();
		$dc = $this->_mailalias->getDC();
		$error = $this->_mailalias->getError();

		printf(<<<EOT
<div class="tekst"><br />
<div class="kopje1">Emailobject bewerken</div><br />

N.B. U kunt hier alle eigenschappen van dit email-object wijzigen. De wijzigingen zijn onmiddelijk actief!
<br /><br />
<div class="kopje2">%s in context: %s</div><br />

EOT
			,
			mb_htmlentities($editalias['cn']),
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
	<form action="%seditalias.php?dc=%s&cn=%s" method="post">
	<input type="hidden" name="a" value="setcn">
	<table cellspacing="0" cellpadding="0" border="0" class="table">
	<td valign="middle" width="444"><input type="text" name="setcn" style="width:400px;" value="%s" class="tekstedit"></td>
	<td valign="middle" width="200" align="right"><input type="image" src="%schange.gif" width="58" height="13" alt="Wijzig"></td>
	</table>
	</form>
</td>
</tr>

<!-- ONTVANGT EMAIL VOOR -->
<tr bgcolor="#DDDDDD">
<td valign="top" colspan="5">
	<table cellspacing=0 cellpadding=0 border=0 class="table">
	<tr bgcolor="#DDDDDD">
	<td valign="top" width="644">&nbsp;<b>Ontvangt email voor:</b></td>
	</tr>
	</table>
</td>
</tr>

EOT
			,
			mb_htmlentities($editalias['cn']),
			AP_URL,
			mb_htmlentities($dc),
			rawurlencode($editalias['cn']),
			mb_htmlentities($terugzz['cn']),
			AP_PICS
		);

		if (isset($editalias['uid'])) foreach ($editalias['uid'] as $uid => $foo) printf(<<<EOT
<tr bgcolor="#EEEEEE">
<td valign="top" width="214">%s</td><td width="4"></td>
<td valign="top" width="194">@%s</td><td width="4"></td>
<td valign="middle" width="204" align="right">
	<form action="%seditalias.php?dc=%s&cn=%s" method="post">
	<input type="hidden" name="a" value="rmuid">
	<input type="hidden" name="uid" value="%s">
	<input type="image" src="%sdelete.gif" width="81" height="13" alt="Verwijder">
	</form>
</td>
</tr>

EOT
			,
			mb_htmlentities($uid),
			mb_htmlentities($dc),
			AP_URL,
			mb_htmlentities($dc),
			rawurlencode($editalias['cn']),
			mb_htmlentities($uid),
			AP_PICS
		);

		printf(<<<EOT
<tr bgcolor="#EEEEEE">
<td valign="top" colspan="5">
	<form action="%seditalias.php?dc=%s&cn=%s" method="post">
	<input type="hidden" name="a" value="adduid">
	<table cellspacing="0" cellpadding="0" border="0" class="table">
	<td valign="middle" width="217"><input type="text" name="uid" style="width:190px;" value="%s" class="tekstedit"></td><td width="13">&nbsp;</td>
	<td valign="middle" width="197">@%s</td><td width="10">&nbsp;</td>
	<td valign="middle" width="207" align="right"><input type="image" src="%sadd.gif" width="90" height="13" alt="Toevoegen"></td>
	</table>
	</form>
</td>
</tr>

<!-- BESTEMMING -->
<tr bgcolor="#DDDDDD">
<td valign="top" colspan="5">
	<table cellspacing="0" cellpadding="0" border="0" class="table">
	<tr bgcolor="#DDDDDD">
	<td valign="top" width=644>&nbsp;<b>Wordt afgeleverd naar:</b></td>
	</tr>
	</table>
</td>
</tr>

EOT
			,
			AP_URL,
			mb_htmlentities($dc),
			rawurlencode($editalias['cn']),
			mb_htmlentities($terugzz['uid']),
			mb_htmlentities($dc),
			AP_PICS
		);

		if (isset($editalias['mail'])) foreach ($editalias['mail'] as $mail => $error) {
			printf(<<<EOT
<tr bgcolor="#EEEEEE">
<td valign="top" width="424" colspan="3">%s</td><td width="4"></td>
<td valign="middle" width="204" align="right">
	<form action="%seditalias.php?dc=%s&cn=%s" method="post">
	<input type="hidden" name="a" value="rmmail">
	<input type="hidden" name="mail" value="%s">
	<input type="image" src="%sdelete.gif" width="81" height="13" alt="Verwijder">
	</form>
</td>
</tr>

EOT
				,
				mb_htmlentities($mail),
				AP_URL,
				mb_htmlentities($dc),
				rawurlencode($editalias['cn']),			
				mb_htmlentities($mail),
				AP_PICS
			);

			if ($error != '') printf(<<<EOT
<tr bgcolor="#EEEEEE">
<td valign="top" width="644" colspan="5"><span class="teksterror">%s</span></td>
</tr>

EOT
				,
				mb_htmlentities($error)
			);

		}
		
		printf(<<<EOT
<tr bgcolor="#EEEEEE">
<td valign="top" colspan="5">
	<form action="%seditalias.php?dc=%s&cn=%s" method="post">
	<input type="hidden" name="a" value="addmail">
	<table cellspacing="0" cellpadding="0" border="0" class="table">
	<td valign="middle" width="437"><input type="text" name="mail" style="width:300px;" value="%s" class="tekstedit"></td>
	<td valign="middle" width="207" align="right"><input type="image" src="%sadd.gif" width="90" height="13" alt="Toevoegen" border="0"></td>
	</table>
	</form>
</td>
</tr>

EOT
			,
			AP_URL,
			mb_htmlentities($dc),
			rawurlencode($editalias['cn']),
			mb_htmlentities($terugzz['mail']),
			AP_PICS
		);

		printf(<<<EOT
</table>
<br />
<br />
[ <a href="%smailalias.php?dc=%s">Terug naar mailaliassen %s</a> ]

</div>

EOT
			,
			AP_URL,
			mb_htmlentities($dc),
			mb_htmlentities($dc)
		);
	}
}	
	
?>
