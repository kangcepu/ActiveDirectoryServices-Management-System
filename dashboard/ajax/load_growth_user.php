<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username'], $_SESSION['password'])) {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized"]);
    exit();
}

$ouList = [
    "GSU", "PSU", "RU", "UCF", "UCO", "Users"
];

$ldap_host = "ldaps://dc1.kangcepu.com";
$ldap_port = 636;
$bind_user = $_SESSION['username'] . "@kangcepu.com";
$bind_pass = $_SESSION['password'];

$ldap_conn = ldap_connect($ldap_host, $ldap_port);
ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);

if (!@ldap_bind($ldap_conn, $bind_user, $bind_pass)) {
    echo json_encode(["error" => "Gagal bind ke AD"]);
    exit();
}

$currentYear = date('Y');
$enabledPerMonth = array_fill(0, 12, 0);
$disabledPerMonth = array_fill(0, 12, 0);

foreach ($ouList as $label) {
    if ($label === "Users") {
        $dn = "CN=Users,DC=kangcepu,DC=com";
    } else {
        $dn = "OU=$label,OU=Cepu Group,DC=kangcepu,DC=com";
    }

    $search = @ldap_search($ldap_conn, $dn, "(objectClass=user)", ['whencreated', 'useraccountcontrol']);
    if (!$search) continue;

    $entries = ldap_get_entries($ldap_conn, $search);
    if (!$entries || $entries['count'] == 0) continue;

    for ($i = 0; $i < $entries["count"]; $i++) {
        $whencreated = $entries[$i]["whencreated"][0] ?? null;
        if (!$whencreated || strlen($whencreated) < 8) continue;

        $year = substr($whencreated, 0, 4);
        $month = (int)substr($whencreated, 4, 2);

        if ($year == $currentYear && $month >= 1 && $month <= 12) {
            $uac = (int)($entries[$i]["useraccountcontrol"][0] ?? 512);
            if ($uac & 2) {
                $disabledPerMonth[$month - 1]++;
            } else {
                $enabledPerMonth[$month - 1]++;
            }
        }
    }
}

echo json_encode([
    "enabled" => $enabledPerMonth,
    "disabled" => $disabledPerMonth
]);
