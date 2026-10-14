<?php
// config/mimetypes.php

return [
    'categories' => [
        'images' => [
            'label' => 'Images',
            'icon' => 'image',
            'color' => '#3b82f6',
            'extensions' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
            'mimes' => [
                'image/jpeg',
                'image/png',
                'image/webp',
                'image/gif',
            ]
        ],
        'documents' => [
            'label' => 'Documents',
            'icon' => 'file-text',
            'color' => '#6366f1',
            'extensions' => ['pdf', 'doc', 'docx'],
            'mimes' => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ]
        ],
        'spreadsheets' => [
            'label' => 'Spreadsheets',
            'icon' => 'table',
            'color' => '#10b981',
            'extensions' => ['xls', 'xlsx', 'csv'],
            'mimes' => [
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'text/csv',
                'text/plain',
            ]
        ],
        'presentations' => [
            'label' => 'Presentations',
            'icon' => 'presentation',
            'color' => '#f59e0b',
            'extensions' => ['ppt', 'pptx'],
            'mimes' => [
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            ]
        ],
        'archives' => [
            'label' => 'Archives',
            'icon' => 'archive',
            'color' => '#8b5cf6',
            'extensions' => ['zip'],
            'mimes' => [
                'application/zip',
                'application/x-zip-compressed',
            ]
        ],
        'text' => [
            'label' => 'Text & Code',
            'icon' => 'file-code',
            'color' => '#64748b',
            'extensions' => ['txt', 'csv'],
            'mimes' => [
                'text/plain',
                'text/csv',
            ]
        ],
    ],

    // Strictly forbidden dangerous file extensions
    'blacklisted_extensions' => [
        'php', 'php3', 'php4', 'php5', 'phtml', 'phar',
        'exe', 'dll', 'so', 'com', 'bat', 'cmd', 'sh', 'bash',
        'vbs', 'vbe', 'js', 'jse', 'wsf', 'wsh', 'ps1', 'psm1',
        'msi', 'scr', 'hta', 'cpl', 'jar', 'jsp', 'asp', 'aspx',
        'cgi', 'pl', 'py', 'rb'
    ],
];
