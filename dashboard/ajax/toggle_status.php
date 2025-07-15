<?php
session_start();
if (!isset($_SESSION['username'], $_SESSION['password'])) {
  http_response_code(403);
  echo "Unauthorized.";
  exit();
}

$username = $_POST['username'] ?? '';
$currentStatus = $_POST['currentStatus'] ?? '';

if (!$username || !$currentStatus) {
  http_response_code(400);
  echo "Data tidak lengkap.";
  exit();
}

$ldap_host = "ldaps://dc1.kangcepu.com";
$ldap_port = 636;
$bind_user = $_SESSION['username'] . "@kangcepu.com";
$bind_pass = $_SESSION['password'];

$ldap_conn = ldap_connect($ldap_host, $ldap_port);
ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);

if (!@ldap_bind($ldap_conn, $bind_user, $bind_pass)) {
  http_response_code(500);
  echo "Gagal bind.";
  exit();
}

$base_dn = "DC=kangcepu,DC=com";
$filter = "(sAMAccountName=$username)";
$search = ldap_search($ldap_conn, $base_dn, $filter, ['dn', 'userAccountControl']);
$entries = ldap_get_entries($ldap_conn, $search);

if ($entries['count'] === 0) {
  http_response_code(404);
  echo "User tidak ditemukan.";
  exit();
}

$dn = $entries[0]['dn'];
$currentUAC = (int)$entries[0]['useraccountcontrol'][0];

$newUAC = $currentStatus === 'Active'
  ? $currentUAC | 2 
  : $currentUAC & ~2; 

$entry = ["userAccountControl" => [$newUAC]];
if (ldap_modify($ldap_conn, $dn, $entry)) {
  echo "Status berhasil diubah.";
} else {
  http_response_code(500);
  echo "Gagal mengubah status.";
}