<?php
// backend/seed.php
// Simple seeder to create an admin user and sample case stories.
require __DIR__ . '/db.php';

// This script is intended to be run inside the backend container after the DB is ready:
//   docker exec -i <backend-container> php /var/www/html/seed.php

try {
    $db = getPDO();

    // Create admin user if not exists
    $email = 'admin@example.com';
    $password = 'admin123'; // change in production
    $name = 'Admin';
    $role = 'admin';

    // Check existing
    $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $exists = $stmt->fetchColumn();

    if (!$exists) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $ins = $db->prepare('INSERT INTO users (email, password_hash, name, role) VALUES (?, ?, ?, ?)');
        $ins->execute([$email, $hash, $name, $role]);
        echo "Inserted admin user: {$email}\n";
    } else {
        echo "Admin user already exists: {$email}\n";
    }

    // Insert a few sample case_stories if table is empty
    $count = $db->query('SELECT COUNT(*) FROM case_stories')->fetchColumn();
    if ($count == 0) {
        $samples = [
            [
                'title' => '毒品案獲緩刑 - 台北王先生',
                'client_name' => '王先生',
                'lawyer' => '李仲唯',
                'uploader' => 'Admin',
                'upload_date' => date('Y-m-d', strtotime('-30 days')),
                'description' => '透過精準的法律策略與證據分析，協助當事人取得緩刑並保留社會功能。'
            ],
            [
                'title' => '詐欺車手交保',
                'client_name' => '匿名',
                'lawyer' => '李澤泰',
                'uploader' => 'Admin',
                'upload_date' => date('Y-m-d', strtotime('-10 days')),
                'description' => '在檢察官欲聲押情況下，成功為當事人爭取到交保。'
            ]
        ];

        $stmt = $db->prepare('INSERT INTO case_stories (title, client_name, lawyer, uploader, upload_date, description) VALUES (?, ?, ?, ?, ?, ?)');
        foreach ($samples as $s) {
            $stmt->execute([$s['title'], $s['client_name'], $s['lawyer'], $s['uploader'], $s['upload_date'], $s['description']]);
        }
        echo "Inserted sample case_stories (" . count($samples) . ")\n";
    } else {
        echo "case_stories table already has {$count} rows\n";
    }

    echo "Seeding complete.\n";
} catch (Exception $e) {
    echo 'Seeder error: ' . $e->getMessage() . "\n";
    exit(1);
}

?>
