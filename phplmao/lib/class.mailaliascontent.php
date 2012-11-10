<?php

#
# S.P.I.R.I.T.
#
# -------------------------------------------------------------------
# class.mailaliascontent.php
# -------------------------------------------------------------------
#
# Historie:
# 01-04-2005 Hans van Kranenburg
# . aangemaakt
# 23-05-2006
# . hernoemd naar mailaliascontent ipv apmailcontent
# . aangepast voor ap04
#

require_once("class.simplehtml.php");

class MailAliasContent extends SimpleHTML {
	### private ###
	var $_apuser;
	var $_mailalias;

	### public ###
	function MailAliasContent ($apuser, $mailalias) {
		$this->_apuser =& $apuser;
		$this->_mailalias =& $mailalias;
	}

	function view() {
		$maildcs = $this->_apuser->getMailAlias();
		$dc = $this->_mailalias->getDC();
		$terugzz = $this->_mailalias->getTerugZZ();
		
		printf(<<<EOT
<div class="tekst"><br/>
<div class="kopje1">Mailaliassen van %s</div>
<br/>

<table cellspacing="0" cellpadding="0" border="0" class="tekst">
<tr>
<td valign="top" width="210">
<div class="kopje2">Nieuw Item toevoegen:</div>

<form action="%smailalias.php?dc=%s" method="post">
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
		
		# evt. foutmelding bij het toevoegen of verwijderen van een alias
		if (($error = $this->_mailalias->getError()) != '') printf("<b>Foutmelding:</b> %s<br /><br />", $error);
		
		# nu gaan we de lijst met aliassen afbeelden
		$aliassen = $this->_mailalias->getAlias();
		$aliassen_aantal = count($aliassen);
		
		printf('<div class="kopje2">Er %s %s email-alias%s gedefinieerd</div><br />',
			($aliassen_aantal == 1) ? "is " : "zijn ",
			(int)$aliassen_aantal,
			($aliassen_aantal == 1) ? "" : "sen "
		);
		
		$result_alias = array();
		$result_groep = array();
		
		for ($n=0; $n<$aliassen_aantal; $n++) {
			if (!isset($aliassen[$n]['mail']) or count($aliassen[$n]['mail']) <=1 ) $result_alias[$n] = $aliassen[$n];
			else $result_groep[$n] = $aliassen[$n];
		}
		
		if (count($result_alias) > 0) {
			$this->_view_list($result_alias, false, $dc);
			print('<br /><br />');
		}
		if (count($result_groep) > 0) {
		   $this->_view_list($result_groep, false, $dc);
			print('<br /><br />');
		}
	}

	function _view_list($list, $witregel, $dc) {
		
		print(<<<EOT
<table cellspacing="0" cellpadding="3" border="0" class="table">
<tr bgcolor="#FFFFFF">
<td valign="top" class="kopje2">Ontvangt email voor:</td><td>&nbsp;</td>
<td valign="top" class="kopje2">Wordt afgeleverd naar:</td><td>&nbsp;</td>
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

			if (!isset($item['mail']) or count($item['mail']) == 0) echo "-\n";
			else foreach ($item['mail'] as $mail => $error) {
				if ($error != '') print('<span class="teksterror">');
				echo mb_htmlentities($mail);
				if ($error != '') print('</span>');
				print("<br />\n");
			}

			printf(<<<EOT
</td>
<td valign="bottom">
	<table cellspacing="0" cellpadding="0" border="0" class="table">
	<tr bgcolor="#EEEEEE">
	<td valign="top" width="76" align="center">
		<a href="editalias.php?dc=%s&cn=%s"><img src="%sedit.gif" width="66" height="13" border="0" alt="Bewerk"></a>
	</td>
	<td valign="top" width=91 align="center">
		<form name="frmdelete%s" action="%s?dc=%s" method="post" onSubmit="if (confirm('Weet u zeker dat u deze alias wilt verwijderen?')) { document.forms.frmdelete%s.submit(); } return false;">
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
