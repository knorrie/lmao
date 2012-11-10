<?php

#
# AntiPlesk
#
# -------------------------------------------------------------------
# class.page.php
# -------------------------------------------------------------------
# Page is de klasse waarbinnen een pagina in elkaar wordt gezooid
#
# -------------------------------------------------------------------
# Historie:
# 18-12-2004 Hans van Kranenburg
# . aangemaakt C.S.R. Delft
# 28-03-2005
# . aangepast voor S.P.I.R.I.T.
# 05-04-2006
# . aangepast naar ap 0.4
#

require_once('class.simplehtml.php');

class Page extends SimpleHTML {

	# content linkerhelft, array van objecten
	var $_menu;
	# content rechterhelft, array van objecten
	var $_content;

	# standaardtitel van de pagina
	var $_title = 'S.P.I.R.I.T.';

	### public ###

	function Page(&$menu, &$content) {
		$this->_menu =& $menu;
		$this->_content =& $content;
	}

	function setTitle($title) { $this->_title = $title; }
	function appendTitle($title) { $this->_title .= $title; }

	function view() {
		header('Content-Type: text/html; charset=UTF-8');
	
		# uitvoer formatteren
		$title = mb_htmlentities($this->_title);
		$css = AP_URL . 'spirit.css';
		
		print(<<<EOT
<html>

<head>
<title>{$title}</title>
<meta http-equiv='content-type' content='text/xhtml; charset=UTF-8' />
<meta name="keywords" content="jeugdkerk jeugdkerken hosting webhosting beheer" />
<meta name="description" content="S.P.I.R.I.T - Beheerinterface Jeugdkerken NL" />
<!--<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />-->
<link href="{$css}" REL="stylesheet" TYPE="text/css">
</head>

<body>

<table width="100%" height="100%" align="left" valign="bottom" cellpadding="0" cellspacing="0" border="1">
<tr>

<td width="200" height="100%" valign="top" align="left" bgcolor="#DDDDDD">

<table width="100%" height="100%" align="left" valign=bottom cellpadding="0" cellspacing="0" border="0">
<tr>
<td width=200 height="100%" valign="top" align="left" bgcolor="#DDDDDD">
EOT
		);

		$this->_menu->view();

		print(<<<EOT
<div class="tekst">
<center>
<hr />&copy; 2007 <span class="linkdisable">Knorrie.org</span>
</center>
</div>

</td>
</tr>

</table>

</td>

<td valign="top" align="left">
EOT
		);
		
		$this->_content->view();
		
		print(<<<EOT

</td>
</tr>
</table>


</body>
</html>
EOT
		);
	}

}

?>
