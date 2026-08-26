<?php

/**
 * Migration: Create `users` table and insert sample admin user
 * Run this file in project context (CLI bootstrap loads helpers/service container).
 */

use DevinciIT\Blprnt\Database\Schema;
use DevinciIT\Blprnt\Database\TableBuilder;
use DevinciIT\Blprnt\Support\Hash;

// Create users table
Schema::create('users', function (TableBuilder $table) {
    $table->id();
    $table->string('username');
    $table->string('password');
    $table->string('email', true);
    $table->timestamps();
    $table->softDeletes();
});

// Insert sample admin user (username: admin, password: admin)
// Only insert if it does not already exist.
//
// Gated behind BLPRNT_SEED (set by migrate:run/migrate:fresh — true when
// APP_ENV is local/development/dev, or when --seed was passed explicitly)
// so this can't seed a well-known credential pair into a real deploy just
// by running `migrate:run` there. SCHEMA_DUMP is still checked too, since
// schema:dump requires this file for its SQL without ever wanting it to
// touch a live database at all.
$isSchemaDump = defined('SCHEMA_DUMP') && SCHEMA_DUMP;
$seedAllowed = defined('BLPRNT_SEED') && BLPRNT_SEED;

if (!$isSchemaDump && $seedAllowed) {
    $pdo = db(); // helper that returns \PDO
    $check = $pdo->prepare('SELECT COUNT(1) FROM users WHERE username = ?');
    $check->execute(['admin']);
    $exists = (int) $check->fetchColumn();

    if ($exists === 0) {
        $hash = Hash::make('admin');
        $stmt = $pdo->prepare('INSERT INTO users (username, password, email, created_at, updated_at) VALUES (:username, :password, :email, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)');
        $stmt->execute([
            ':username' => 'admin',
            ':password' => $hash,
            ':email' => 'admin@example.com',
        ]);
        echo "Inserted sample admin user (username: admin)\n";
    } else {
        echo "Admin user already exists, skipping insert.\n";
    }
}
