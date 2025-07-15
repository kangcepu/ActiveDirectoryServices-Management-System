<?php
session_start();
if (!isset($_SESSION['username'], $_SESSION['password'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

include '../../function/config.php';

$bind_user = $_SESSION['username'] . '@kangcepu.com';
$bind_pass = $_SESSION['password'];
$ldap_host = 'ldaps://dc1.kangcepu.com';
$ldap_port = 636;

$ldap_conn = ldap_connect($ldap_host, $ldap_port);
ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);

if (!@ldap_bind($ldap_conn, $bind_user, $bind_pass)) {
    echo json_encode(['error' => 'Failed to bind to AD']);
    exit();
}

$ou_list_user = ['GSU', 'PSU', 'RU', 'UCF', 'UCO', 'Users'];
$ou_list_pc = ['Computer', 'USB Allowed', 'USB Restricted'];

$ou_map = [
    "GSU" => "OU=GSU,OU=Cepu Group,DC=kangcepu,DC=com",
    "PSU" => "OU=PSU,OU=Cepu Group,DC=kangcepu,DC=com",
    "RU" => "OU=RU,OU=Cepu Group,DC=kangcepu,DC=com",
    "UCF" => "OU=UCF,OU=Cepu Group,DC=kangcepu,DC=com",
    "UCO" => "OU=UCO,OU=Cepu Group,DC=kangcepu,DC=com",
    "Users" => "CN=Users,DC=kangcepu,DC=com",
    "Computer" => "CN=Computers,DC=kangcepu,DC=com",
    "USB Allowed" => "OU=USB Allowed,OU=Cepu Computers,DC=kangcepu,DC=com",
    "USB Restricted" => "OU=USB Restricted,OU=Cepu Computers,DC=kangcepu,DC=com"
];

function countObjects($conn, $dns, $filter) {
    $active = 0;
    $disabled = 0;

    foreach ($dns as $label) {
        global $ou_map;
        $dn = $ou_map[$label] ?? '';
        if (!$dn) continue;

        $search = @ldap_search($conn, $dn, $filter, ['useraccountcontrol']);
        if (!$search) continue;

        $entries = ldap_get_entries($conn, $search);
        for ($i = 0; $i < $entries['count']; $i++) {
            $uac = (int)($entries[$i]['useraccountcontrol'][0] ?? 512);
            if ($uac & 2) {
                $disabled++;
            } else {
                $active++;
            }
        }
    }

    return [$active, $disabled];
}

list($userActive, $userDisabled) = countObjects($ldap_conn, $ou_list_user, "(objectClass=user)");
list($pcActive, $pcDisabled) = countObjects($ldap_conn, $ou_list_pc, "(objectClass=computer)");

echo json_encode([
    'user_active' => $userActive,
    'user_disabled' => $userDisabled,
    'pc_active' => $pcActive,
    'pc_disabled' => $pcDisabled
]);
