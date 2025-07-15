<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['username'])) {
    header("Location: ../index.php");
    exit();
}

$displayName = $_SESSION['displayName'] ?? $_SESSION['username'];

$ldap_host   = "ldaps://dc1.kangcepu.com";
$ldap_port   = 636;
$ldap_domain = "kangcepu.com";

$ldap_conn = ldap_connect($ldap_host, $ldap_port);
ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);

$bind_user = $_SESSION['username'] . '@' . $ldap_domain;
if (!@ldap_bind($ldap_conn, $bind_user, $_SESSION['password'])) {
    session_destroy();
    die("Autentikasi LDAP gagal. Silakan login ulang.");
}
?>
