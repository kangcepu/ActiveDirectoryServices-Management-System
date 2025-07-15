<?php
session_start();
include '../../function/config.php';


$year = date('Y');
$monthly_users_enabled = array_fill(1, 12, 0);
$monthly_users_disabled = array_fill(1, 12, 0);

$base_dn = "DC=kangcepu,DC=com";
$filter = "(&(objectClass=user)(whenCreated>={$year}0101000000.0Z))";
$attributes = ['whenCreated', 'userAccountControl'];

$search = ldap_search($ldap_conn, $base_dn, $filter, $attributes);
if ($search) {
    $entries = ldap_get_entries($ldap_conn, $search);
    for ($i = 0; $i < $entries['count']; $i++) {
        if (!empty($entries[$i]['whencreated'][0])) {
            $whenCreated = $entries[$i]['whencreated'][0];
            $date = DateTime::createFromFormat('YmdHis.0Z', $whenCreated);

            if ($date && $date->format('Y') == $year) {
                $month = (int)$date->format('n');
                $uac = (int)($entries[$i]['useraccountcontrol'][0] ?? 512);
                $is_disabled = ($uac & 2) === 2;

                if ($is_disabled) {
                    $monthly_users_disabled[$month]++;
                } else {
                    $monthly_users_enabled[$month]++;
                }
            }
        }
    }
}
header('Content-Type: application/json');
echo json_encode([
    'enabled' => array_values($monthly_users_enabled),
    'disabled' => array_values($monthly_users_disabled),
]);
?>
