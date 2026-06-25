<?php
// export subscribers as csv
require_once __DIR__ . '/../lib/auth.php';
require_login();

$rows = db()->query("SELECT email, name, source, status, created_at FROM subscribers ORDER BY id DESC")->fetchAll();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="subscribers-' . date('Y-m-d') . '.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['Email', 'Name', 'Source', 'Status', 'Joined']);
foreach ($rows as $r) {
    fputcsv($out, [$r['email'], $r['name'], $r['source'], $r['status'], $r['created_at']]);
}
fclose($out);
