<?php
session_start();
if (!isset($_SESSION['username'], $_SESSION['password'])) {
    http_response_code(403);
    echo "Unauthorized.";
    exit();
}

include '../../function/config.php';

$label = $_POST['ou'] ?? $_GET['ou'] ?? '';

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

if (!isset($ou_map[$label])) {
    echo "<tr><td colspan='6'>OU tidak ditemukan.</td></tr>";
    exit();
}

$dn = $ou_map[$label];
$ldap_host   = "ldaps://dc1.kangcepu.com";
$ldap_port   = 636;
$bind_user   = $_SESSION['username'] . "@kangcepu.com";
$bind_pass   = $_SESSION['password'];
$ldap_conn = ldap_connect($ldap_host, $ldap_port);
ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);

if (!@ldap_bind($ldap_conn, $bind_user, $bind_pass)) {
    echo "<tr><td colspan='6'>Gagal bind ke server AD.</td></tr>";
    exit();
}
$search = ldap_search($ldap_conn, $dn, "(objectClass=user)", [
  'cn', 'samaccountname', 'mail', 'useraccountcontrol', 'whencreated', 'lastlogontimestamp'
]);
$entries = ldap_get_entries($ldap_conn, $search);

if (!$entries || $entries['count'] == 0) {
    echo "<tr><td colspan='6'>Tidak ada user ditemukan.</td></tr>";
    exit();
}
for ($i = 0; $i < $entries['count']; $i++) {
    $nama = htmlspecialchars($entries[$i]['cn'][0] ?? '-');
    $user = htmlspecialchars($entries[$i]['samaccountname'][0] ?? '-');
    $email = htmlspecialchars($entries[$i]['mail'][0] ?? '-');
    $uac = (int)($entries[$i]['useraccountcontrol'][0] ?? 512);
    $status = ($uac & 2) ? '<span class="badge badge-danger">Disabled</span>' : '<span class="badge badge-success">Active</span>';
$created_raw = $entries[$i]['whencreated'][0] ?? '';
if ($created_raw) {
    $created_dt = DateTime::createFromFormat('YmdHis\.0\Z', $created_raw);
    $created_at = $created_dt ? $created_dt->format('Y-m-d H:i') : '-';
} else {
    $created_at = '-';
}
$lastlogon_raw = $entries[$i]['lastlogontimestamp'][0] ?? 0;
$lastlogon_at = '-';
if ($lastlogon_raw > 0) {
    $timestamp = ($lastlogon_raw / 10000000) - 11644473600;
    $lastlogon_at = date("Y-m-d H:i", $timestamp);
}
$statusText = ($uac & 2) ? 'Disabled' : 'Active';
$statusClass = ($uac & 2) ? 'badge-danger' : 'badge-success';
$status = "<span class='badge $statusClass status-toggle' data-username='$user' data-status='$statusText' style='cursor:pointer;'>$statusText</span>";

    echo "<tr>
        <td>" . ($i + 1) . "</td>
        <td>$nama</td>
        <td>$user</td>
        <td>$email</td>
        <td>$status</td>
        <td>$created_at</td>
        <td>$lastlogon_at</td>
        <td><button class='btn btn-sm btn-warning'>Edit</button></td>
    </tr>";
}
?>
