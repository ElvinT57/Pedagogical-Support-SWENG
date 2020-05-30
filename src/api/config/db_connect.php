<?php
$hostName = '18.222.151.240:3306';
$userName = 'remote';
$password = 'ZnTma8UBhXyT7xSz';
$database = 'PedagogicalSupport2';

try {
    $dbh = new PDO("mysql:host={$hostName};dbname={$database}", $userName, $password);
} catch(PDOException $e) {
    die('Error: Could not connect to database:' . $e->getmessage());
}

?>