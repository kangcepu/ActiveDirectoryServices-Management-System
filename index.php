<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$ldap_host   = "ldaps://dc1.kangcepu.com";
$ldap_port   = 636;
$ldap_domain = "kangcepu.com";
$base_dn     = "DC=kangcepu,DC=com";
$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username && $password) {
        $ldap_conn = ldap_connect($ldap_host, $ldap_port);
        ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);
        $bind_user = "$username@$ldap_domain";
        if (@ldap_bind($ldap_conn, $bind_user, $password)) {
            $_SESSION['username'] = $username;
            $_SESSION['password'] = $password;
            $filter = "(sAMAccountName=$username)";
            $attributes = ["displayName"];
            $search = @ldap_search($ldap_conn, $base_dn, $filter, $attributes);

            if ($search) {
                $entries = ldap_get_entries($ldap_conn, $search);
                if ($entries["count"] > 0 && isset($entries[0]["displayname"][0])) {
                    $_SESSION['displayName'] = $entries[0]["displayname"][0];
                } else {
                    $_SESSION['displayName'] = $username; 
                }
            } else {
                $_SESSION['displayName'] = $username;  
            }
            header("Location: dashboard/index.php");
            exit();
        } else {
            $error_message = ldap_error($ldap_conn);
        }
    } else {
        $error_message = "Username dan password tidak boleh kosong.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Active Directory</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
  <div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-md">
    <h2 class="text-2xl font-bold mb-6 text-center text-gray-700">Login AD</h2>
    <?php if ($error_message): ?>
      <div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-4 text-sm">
        <?= htmlspecialchars($error_message) ?>
      </div>
    <?php endif; ?>
    <form method="post" class="space-y-4">
      <div>
        <label class="block text-gray-600 text-sm font-medium mb-1">Username</label>
        <input type="text" name="username" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-400" required>
      </div>
      <div>
        <label class="block text-gray-600 text-sm font-medium mb-1">Password</label>
        <input type="password" name="password" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-400" required>
      </div>
      <div>
        <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">Login</button>
      </div>
    </form>
  </div>
</body>
</html>
