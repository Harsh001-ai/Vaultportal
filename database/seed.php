<?php
// database/seed.php

require_once __DIR__ . '/../app/autoload.php';

use App\Database;

echo "Seeding Secure Asset Collection & Delivery Portal database...\n";

// Ensure storage directory exists
$uploadDir = realpath(__DIR__ . '/../storage/uploads') ?: (__DIR__ . '/../storage/uploads');
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
    $uploadDir = realpath($uploadDir);
}

// 1. Create Admin
$adminEmail = 'admin@portal.local';
$adminUser = Database::fetch("SELECT * FROM users WHERE email = :email", ['email' => $adminEmail]);
if (!$adminUser) {
    $adminId = Database::insert('users', [
        'uuid' => '00000000-0000-4000-8000-000000000001',
        'name' => 'Alexander Vance (Chief Admin)',
        'email' => $adminEmail,
        'password_hash' => password_hash('Admin@123456!', PASSWORD_DEFAULT),
        'role' => 'admin',
        'status' => 'active',
        'storage_quota_bytes' => 10 * 1024 * 1024 * 1024, // 10 GB
        'storage_used_bytes' => 0,
        'created_at' => date('Y-m-d H:i:s')
    ]);
    echo "Created Admin: admin@portal.local / Admin@123456!\n";
} else {
    $adminId = $adminUser['id'];
}

// 2. Create Standard Users
$usersData = [
    [
        'uuid' => '10000000-0000-4000-8000-000000000002',
        'name' => 'Sarah Connor',
        'email' => 'sarah@marketingagency.com',
        'password' => 'User@123456!',
        'role' => 'user',
        'quota' => 2 * 1024 * 1024 * 1024, // 2 GB
    ],
    [
        'uuid' => '20000000-0000-4000-8000-000000000003',
        'name' => 'Alex Mercer',
        'email' => 'alex@designstudio.io',
        'password' => 'User@123456!',
        'role' => 'user',
        'quota' => 1024 * 1024 * 1024, // 1 GB
    ],
    [
        'uuid' => '30000000-0000-4000-8000-000000000004',
        'name' => 'Michael Scott',
        'email' => 'michael@financegroup.com',
        'password' => 'User@123456!',
        'role' => 'user',
        'quota' => 500 * 1024 * 1024, // 500 MB
    ]
];

$userMap = [];
foreach ($usersData as $ud) {
    $u = Database::fetch("SELECT * FROM users WHERE email = :email", ['email' => $ud['email']]);
    if (!$u) {
        $uid = Database::insert('users', [
            'uuid' => $ud['uuid'],
            'name' => $ud['name'],
            'email' => $ud['email'],
            'password_hash' => password_hash($ud['password'], PASSWORD_DEFAULT),
            'role' => $ud['role'],
            'status' => 'active',
            'storage_quota_bytes' => $ud['quota'],
            'storage_used_bytes' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        $userMap[$ud['email']] = $uid;
        echo "Created User: {$ud['email']} / {$ud['password']}\n";
    } else {
        $userMap[$ud['email']] = $u['id'];
    }
}

// 3. Create Collections
$collectionsData = [
    ['name' => 'Q4 Global Marketing Campaign', 'desc' => 'Campaign visual assets, social kits, and banners', 'user_id' => $userMap['sarah@marketingagency.com'], 'is_global' => 1],
    ['name' => 'Brand Identity & Guidelines 2026', 'desc' => 'Approved typography, iconography, and color palettes', 'user_id' => $userMap['alex@designstudio.io'], 'is_global' => 1],
    ['name' => 'Q3 Financial Audits & Statements', 'desc' => 'Quarterly balances, revenue exports, and payroll sheets', 'user_id' => $userMap['michael@financegroup.com'], 'is_global' => 0],
    ['name' => 'Enterprise Architecture Diagrams', 'desc' => 'System topology, network diagrams, and specs', 'user_id' => null, 'is_global' => 1],
];

$collMap = [];
foreach ($collectionsData as $cd) {
    $existing = Database::fetch("SELECT * FROM collections WHERE name = :name", ['name' => $cd['name']]);
    if (!$existing) {
        $cid = Database::insert('collections', [
            'uuid' => sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)),
            'user_id' => $cd['user_id'],
            'name' => $cd['name'],
            'description' => $cd['desc'],
            'is_global' => $cd['is_global'],
            'created_at' => date('Y-m-d H:i:s')
        ]);
        $collMap[$cd['name']] = $cid;
    } else {
        $collMap[$cd['name']] = $existing['id'];
    }
}

// Helper to write sample file with genuine binary signature
function createSampleFile(string $filename, string $content): array {
    global $uploadDir;
    $storedName = hash('sha256', $filename . microtime(true)) . '.' . pathinfo($filename, PATHINFO_EXTENSION);
    $path = $uploadDir . DIRECTORY_SEPARATOR . $storedName;
    file_put_contents($path, $content);
    return [
        'stored_name' => $storedName,
        'path' => $path,
        'size' => filesize($path),
        'sha256' => hash_file('sha256', $path)
    ];
}

// Minimal valid 1x1 transparent PNG
$pngData = base64_decode("iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==");
// Valid PDF minimal structure
$pdfData = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n3 0 obj\n<< /Type /Page /Parent 2 0 R /Resources <<>> /MediaBox [0 0 612 792] >>\nendobj\nxref\n0 4\n0000000000 65535 f \n0000000009 00000 n \n0000000058 00000 n \n0000000115 00000 n \ntrailer\n<< /Size 4 /Root 1 0 R >>\nstartxref\n212\n%%EOF\n";

$assetsToCreate = [
    [
        'uploader_id' => $userMap['sarah@marketingagency.com'],
        'collection_id' => $collMap['Q4 Global Marketing Campaign'],
        'filename' => 'Q4_Hero_Banner_Autumn.png',
        'ext' => 'png',
        'mime' => 'image/png',
        'content' => $pngData,
        'status' => 'ready',
        'reason' => null
    ],
    [
        'uploader_id' => $userMap['sarah@marketingagency.com'],
        'collection_id' => $collMap['Q4 Global Marketing Campaign'],
        'filename' => 'Social_Media_Copy_Plan.csv',
        'ext' => 'csv',
        'mime' => 'text/csv',
        'content' => "Platform,Post Date,Caption,Target Audience\nLinkedIn,2026-11-01,Transform your digital workflow today!,B2B Executives\nTwitter/X,2026-11-02,Securing enterprise digital assets at scale.,DevOps Teams\n",
        'status' => 'ready',
        'reason' => null
    ],
    [
        'uploader_id' => $userMap['alex@designstudio.io'],
        'collection_id' => $collMap['Brand Identity & Guidelines 2026'],
        'filename' => 'Corporate_Brand_Book_v2.pdf',
        'ext' => 'pdf',
        'mime' => 'application/pdf',
        'content' => $pdfData,
        'status' => 'ready',
        'reason' => null
    ],
    [
        'uploader_id' => $userMap['alex@designstudio.io'],
        'collection_id' => $collMap['Brand Identity & Guidelines 2026'],
        'filename' => 'App_Logo_Monochrome.png',
        'ext' => 'png',
        'mime' => 'image/png',
        'content' => $pngData,
        'status' => 'ready',
        'reason' => null
    ],
    [
        'uploader_id' => $userMap['michael@financegroup.com'],
        'collection_id' => $collMap['Q3 Financial Audits & Statements'],
        'filename' => 'Q3_Operating_Budget_Forecast.csv',
        'ext' => 'csv',
        'mime' => 'text/csv',
        'content' => "Department,Budget Allocated,Expenditure,Variance\nEngineering,1200000,1145000,55000\nMarketing,450000,432000,18000\nOperations,310000,305000,5000\n",
        'status' => 'ready',
        'reason' => null
    ],
    [
        'uploader_id' => $userMap['michael@financegroup.com'],
        'collection_id' => $collMap['Q3 Financial Audits & Statements'],
        'filename' => 'Auditor_Signed_Letter.pdf',
        'ext' => 'pdf',
        'mime' => 'application/pdf',
        'content' => $pdfData,
        'status' => 'ready',
        'reason' => null
    ],
    [
        'uploader_id' => $userMap['sarah@marketingagency.com'],
        'collection_id' => null,
        'filename' => 'quarantine_audit_sample.pdf',
        'ext' => 'pdf',
        'mime' => 'application/pdf',
        'content' => "%PDF-1.4\n% [SIMULATED-INERT-QUARANTINE-FIXTURE]\n1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n3 0 obj\n<< /Type /Page /Parent 2 0 R /Resources <<>> /MediaBox [0 0 612 792] >>\nendobj\nxref\n0 4\n0000000000 65535 f \n0000000009 00000 n \n0000000078 00000 n \n0000000135 00000 n \ntrailer\n<< /Size 4 /Root 1 0 R >>\nstartxref\n232\n%%EOF\n",
        'status' => 'quarantined',
        'reason' => 'Test simulation marker detected: file flagged for quarantine testing.'
    ]
];

foreach ($assetsToCreate as $a) {
    $existing = Database::fetch("SELECT * FROM assets WHERE original_filename = :fn AND uploader_id = :uid", [
        'fn' => $a['filename'],
        'uid' => $a['uploader_id']
    ]);

    if (!$existing) {
        $fileInfo = createSampleFile($a['filename'], $a['content']);
        Database::insert('assets', [
            'uuid' => sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)),
            'uploader_id' => $a['uploader_id'],
            'collection_id' => $a['collection_id'],
            'original_filename' => $a['filename'],
            'stored_filename' => $fileInfo['stored_name'],
            'storage_path' => $fileInfo['path'],
            'mime_type' => $a['mime'],
            'file_extension' => $a['ext'],
            'file_size_bytes' => $fileInfo['size'],
            'sha256_checksum' => $fileInfo['sha256'],
            'status' => $a['status'],
            'quarantine_reason' => $a['reason'],
            'created_at' => date('Y-m-d H:i:s', time() - mt_rand(3600, 86400 * 5))
        ]);

        if ($a['status'] === 'ready') {
            Database::query("UPDATE users SET storage_used_bytes = storage_used_bytes + :bytes WHERE id = :id", [
                'bytes' => $fileInfo['size'],
                'id' => $a['uploader_id']
            ]);
        }
    }
}

// 5. Create Sample Initial Download Events for Audit Trail Demonstration
$existingDownloads = Database::fetch("SELECT COUNT(*) as cnt FROM download_events");
if (($existingDownloads['cnt'] ?? 0) == 0) {
    Database::insert('download_events', [
        'export_job_id' => null,
        'asset_id' => 1,
        'downloaded_by_id' => $adminId,
        'downloaded_by_name' => 'Alexander Vance (Chief Admin)',
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
        'download_status' => 'success',
        'downloaded_at' => date('Y-m-d H:i:s', time() - 7200)
    ]);
}

echo "Database seeding completed successfully!\n";
