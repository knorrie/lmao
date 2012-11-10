<?php

# instellingen & rommeltjes
require_once ("../lib/include.config.php");
require_once('include.common.php');

# login-systeem
session_start();
require_once ("class.apuser.php");
$apuser = new APUser();

### Pagina-onderdelen ###
require_once ("class.menu.php");
require_once ("class.page.php");

# Hopsa menu
$menu = new Menu($apuser);

require_once('class.statscontent.php');
$content = new StatsContent($apuser);

# Maak pagina
$page = new Page ($menu, $content);
$page->appendTitle(" - Statistieken Website");

$page->view();

?>
