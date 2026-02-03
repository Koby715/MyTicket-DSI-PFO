<?php
/**
 * logout.php
 * Déconnexion de la session administrative
 */

session_start();
session_unset();
session_destroy();

header("Location: admin-login.php");
exit;
