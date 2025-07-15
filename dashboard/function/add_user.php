<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username'], $_SESSION['password'])) {
    http_response_code(403);
    echo json_encode(['status' => false, 'message' => 'Unauthorized']);
    exit;
}

include '../../function/config.php';

$nama       = trim($_POST['nama'] ?? '');
$username   = trim($_POST['username'] ?? '');
$email      = trim($_POST['email'] ?? '');
$password   = $_POST['password'] ?? '';
$groups     = $_POST['groups'] ?? [];
$forceChange = isset($_POST['force_change']);

if (!$nama || !$username || !$password || empty($groups)) {
    http_response_code(400);
    echo json_encode(['status' => false, 'message' => 'Lengkapi semua data']);
    exit;
}

$ou_map = [
    "GSU" => "OU=GSU,OU=Cepu Group,DC=utamacorp,DC=com",
    "PSU" => "OU=PSU,OU=Cepu Group,DC=utamacorp,DC=com",
    "RU"  => "OU=RU,OU=Cepu Group,DC=utamacorp,DC=com",
    "UCF" => "OU=UCF,OU=Cepu Group,DC=utamacorp,DC=com",
    "UCO" => "OU=UCO,OU=Cepu Group,DC=utamacorp,DC=com",
    "Users" => "CN=Users,DC=Cepu,DC=com"
];
$main_ou = $ou_map[$groups[0]] ?? null;
if (!$main_ou) {
    echo json_encode(['status' => false, 'message' => 'OU tidak valid']);
    exit;
}

$ldap_host = "ldaps://dc1.kangcepu.com";
$ldap_port = 636;
$ldap_conn = ldap_connect($ldap_host, $ldap_port);
ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);

$bind_user = $_SESSION['username'] . "@kangcepu.com";
$bind_pass = $_SESSION['password'];

$groups = $_POST['groups'] ?? [];
$forceChange = isset($_POST['force_change']) && $_POST['force_change'] === 'on';

if (!@ldap_bind($ldap_conn, $bind_user, $bind_pass)) {
    echo json_encode(['status' => false, 'message' => 'Gagal bind ke server AD']);
    exit;
}
$search = ldap_search($ldap_conn, "DC=kangcepu,DC=com", "(sAMAccountName=$username)");
$result = ldap_get_entries($ldap_conn, $search);
if ($result['count'] > 0) {
    echo json_encode(['status' => false, 'message' => 'Username sudah digunakan']);
    exit;
}


$dn = "CN=$nama,$main_ou";
$entry = [
    "cn" => $nama,
    "samaccountname" => $username,
    "userprincipalname" => "$username@kangcepu.com",
    "objectClass" => ["top", "person", "organizationalPerson", "user"],
    "displayname" => $nama,
    "userAccountControl" => 544, 
];

if ($email) {
    $entry['mail'] = $email;
}

if (!ldap_add($ldap_conn, $dn, $entry)) {
    echo json_encode(['status' => false, 'message' => 'Gagal tambah user: ' . ldap_error($ldap_conn)]);
    exit;
}
$unicodePwd = mb_convert_encoding('"' . $password . '"', 'UTF-16LE');
ldap_mod_replace($ldap_conn, $dn, ["unicodePwd" => $unicodePwd]);
$mod_attrs = [];
if ($forceChange) {
    $mod_attrs["pwdLastSet"] = [0]; 
}
$mod_attrs["userAccountControl"] = [512];
ldap_mod_replace($ldap_conn, $dn, $mod_attrs);
echo json_encode(['status' => true, 'message' => 'User berhasil ditambahkan']);
?>