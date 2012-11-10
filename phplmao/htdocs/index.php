<?php

# instellingen & rommeltjes
require_once ("../lib/include.config.php");
require_once('include.common.php');

# nodig voor deze pagina
require_once ("class.includer.php");

# login-systeem
session_start();
require_once ("class.apuser.php");
$apuser = new APUser();

### Pagina-onderdelen ###
require_once ("class.menu.php");
require_once ("class.includer.php");
require_once ("class.page.php");

# Hopsa menu
$menu = new Menu($apuser);

# Dit is de indexpagina, die laat links het menu zien en rechts de
# Introductiepagina
$content = new Includer ("", "introductie.html");

# Maak pagina
$page = new Page ($menu, $content);
$page->appendTitle(" - Introductie");

$page->view();

?>
