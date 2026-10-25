<?php

function getDatabaseConfig(): array
{
    $isInfinityFree = isset($_SERVER['HTTP_HOST'])
        && stripos($_SERVER['HTTP_HOST'], 'localhost') === false
        && stripos($_SERVER['HTTP_HOST'], '127.0.0.1') === false;

    if ($isInfinityFree) {
        return [
            'host' => getenv('INFINITYFREE_DB_HOST') ?: 'sql207.infinityfree.com',
            'dbname' => getenv('INFINITYFREE_DB_NAME') ?: 'if0_43225044_studentMS',
            'username' => getenv('INFINITYFREE_DB_USER') ?: 'if0_43225044',
            'password' => getenv('INFINITYFREE_DB_PASSWORD') ?: 'VbmjjIOlakzHqif',
        ];
    }

    return [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'dbname' => getenv('DB_NAME') ?: 'student_management_db',
        'username' => getenv('DB_USER') ?: 'root',
        'password' => getenv('DB_PASSWORD') ?: '',
    ];
}
