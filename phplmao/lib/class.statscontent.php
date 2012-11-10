<?php

#
# S.P.I.R.I.T.
#
# -------------------------------------------------------------------
# class.statscontent.php
# -------------------------------------------------------------------
#
# Historie:
# 21-03.2007 Hans van Kranenburg
# . aangemaakt

require_once("class.simplehtml.php");

class StatsContent extends SimpleHTML {
	### private ###
	var $_apuser;

	### public ###
	function StatsContent ($apuser) {
		$this->_apuser =& $apuser;
	}

	function view() {
	
		print(<<<EOT
<div class="tekst"><br />
<div class="kopje1">Statistieken Website</div><br />

Van de volgende websites zijn statistieken te bekijken: (opent in nieuw venster)
<br /><br />

EOT
		);

		$www = $this->_apuser->getWWW();

		if (count($www) == 0) {
			print('Helaas, geen websites gevonden onder uw login.<br />');
		} else {
			foreach($www as $dc)
				printf ('&#8226 <a href="%sstats/%s/", target="_blank">%s</a><br />'. "\n"
					,AP_URL
					,$dc
					,$dc
				);
		}

		print(<<<EOT
</div>

EOT
		);
	}
}	
?>
