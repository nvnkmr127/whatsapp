<?php
$pdo = new PDO('sqlite:C:\Users\madhu\OneDrive\Desktop\watxio\database\database.sqlite');
$stmt = $pdo->query('PRAGMA table_info(messages)');
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($cols as $col) {
    if ($col['name'] == 'reply_to_message_id') {
        echo 'EXISTS';
        exit;
    }
}
echo 'DOES_NOT_EXIST';
