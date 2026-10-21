<?php
require_once __DIR__ . '/db_mysqli.php';

$schemaFile = __DIR__ . '/../sql/schema.sql';
$sql = file_get_contents($schemaFile);
$mysqli->multi_query($sql);
while ($mysqli->more_results() && $mysqli->next_result()) {;}

$adminPass = password_hash('admin123', PASSWORD_DEFAULT);
$studentPass = password_hash('student123', PASSWORD_DEFAULT);

$stmt1 = $mysqli->prepare("UPDATE users SET password_hash = ? WHERE username = 'admin'");
$stmt1->bind_param("s", $adminPass);
$stmt1->execute();
$stmt1->close();

$stmt2 = $mysqli->prepare("UPDATE users SET password_hash = ? WHERE username = 'candidate99'");
$stmt2->bind_param("s", $studentPass);
$stmt2->execute();
$stmt2->close();

echo "Database seeded successfully. Total students: 21\n";
