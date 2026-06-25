<?php
// simple key/value store for admin-editable settings (chatbot persona, knowledge)
require_once __DIR__ . '/db.php';

function get_setting(string $key, string $default = ''): string
{
    $stmt = db()->prepare('SELECT svalue FROM settings WHERE skey = ?');
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? (string) $row['svalue'] : $default;
}

function set_setting(string $key, string $value): void
{
    $now = gmdate('Y-m-d H:i:s');
    $exists = db()->prepare('SELECT 1 FROM settings WHERE skey = ?');
    $exists->execute([$key]);
    if ($exists->fetch()) {
        $stmt = db()->prepare('UPDATE settings SET svalue = ?, updated_at = ? WHERE skey = ?');
        $stmt->execute([$value, $now, $key]);
    } else {
        $stmt = db()->prepare('INSERT INTO settings (skey, svalue, updated_at) VALUES (?, ?, ?)');
        $stmt->execute([$key, $value, $now]);
    }
}
