<?php
$user = 'testuser';
$password = 'test1234';
$server = '10.0.2.6:1521/FREEPDB1';
$conn = oci_connect($user, $password, $server);
$sql = "select user from dual";
$stid = oci_parse($conn, $sql);
oci_execute($stid);
$row = oci_fetch_array($stid, OCI_BOTH);
print($row["USER"]);
oci_free_statement($stid);
?>
