<?php
declare(strict_types = 1);

use yii\db\Connection;

// Resolve database path relative to project root
$basePath = dirname(__DIR__, 3);
$dbDsn = $_ENV['DB_DSN'];

// If it's SQLite and uses relative path, make it absolute
if (str_starts_with($dbDsn, 'sqlite:') && !str_starts_with($dbDsn, 'sqlite:/')) {
    $relativePath = str_replace('sqlite:', '', $dbDsn);
    $absolutePath = $basePath . DIRECTORY_SEPARATOR . $relativePath;
    $dbDsn = 'sqlite:' . $absolutePath;
}

return [
	'class' => Connection::class,
	'dsn' => $dbDsn,
	'username' => $_ENV['DB_USER'],
	'password' => $_ENV['DB_PASS'],
	'enableSchemaCache' => false,
];
