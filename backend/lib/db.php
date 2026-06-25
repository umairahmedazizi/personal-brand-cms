<?php
// db connection (mysql in prod, sqlite for local), schema + seed

function cfg(): array
{
    static $config = null;
    if ($config === null) {
        $config = require dirname(__DIR__) . '/config.php';
    }
    return $config;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $c = cfg()['db'];

    if ($c['driver'] === 'mysql') {
        $dsn = "mysql:host={$c['mysql_host']};dbname={$c['mysql_name']};charset={$c['mysql_charset']}";
        $pdo = new PDO($dsn, $c['mysql_user'], $c['mysql_pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } else {
        $path = $c['sqlite_path'];
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $pdo = new PDO('sqlite:' . $path, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON');
    }

    migrate($pdo, $c['driver']);
    seed($pdo);

    return $pdo;
}

// create tables if missing, works on both sqlite and mysql
function migrate(PDO $pdo, string $driver): void
{
    $pk = $driver === 'mysql'
        ? 'INTEGER PRIMARY KEY AUTO_INCREMENT'
        : 'INTEGER PRIMARY KEY AUTOINCREMENT';

    $suffix = $driver === 'mysql' ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4' : '';

    $tables = [];

    $tables[] = "CREATE TABLE IF NOT EXISTS users (
        id $pk,
        email VARCHAR(255) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        name VARCHAR(255) NOT NULL DEFAULT '',
        created_at VARCHAR(32) NOT NULL
    )$suffix";

    $tables[] = "CREATE TABLE IF NOT EXISTS contacts (
        id $pk,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        subject VARCHAR(255) NOT NULL DEFAULT '',
        message TEXT,
        status VARCHAR(20) NOT NULL DEFAULT 'new',
        ip VARCHAR(64) NOT NULL DEFAULT '',
        created_at VARCHAR(32) NOT NULL
    )$suffix";

    $tables[] = "CREATE TABLE IF NOT EXISTS subscribers (
        id $pk,
        email VARCHAR(255) NOT NULL UNIQUE,
        name VARCHAR(255) NOT NULL DEFAULT '',
        source VARCHAR(64) NOT NULL DEFAULT 'website',
        status VARCHAR(20) NOT NULL DEFAULT 'active',
        created_at VARCHAR(32) NOT NULL
    )$suffix";

    $tables[] = "CREATE TABLE IF NOT EXISTS posts (
        id $pk,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL UNIQUE,
        excerpt TEXT,
        body_html TEXT,
        cover_image VARCHAR(255) NOT NULL DEFAULT '',
        status VARCHAR(20) NOT NULL DEFAULT 'draft',
        published_at VARCHAR(32) NOT NULL DEFAULT '',
        created_at VARCHAR(32) NOT NULL,
        updated_at VARCHAR(32) NOT NULL
    )$suffix";

    $tables[] = "CREATE TABLE IF NOT EXISTS books (
        id $pk,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL UNIQUE,
        subtitle VARCHAR(255) NOT NULL DEFAULT '',
        description TEXT,
        body_html TEXT,
        cover_image VARCHAR(255) NOT NULL DEFAULT '',
        buy_url VARCHAR(500) NOT NULL DEFAULT '',
        detail_url VARCHAR(500) NOT NULL DEFAULT '',
        status VARCHAR(20) NOT NULL DEFAULT 'published',
        featured INTEGER NOT NULL DEFAULT 0,
        sort_order INTEGER NOT NULL DEFAULT 0,
        meta_foreword VARCHAR(255) NOT NULL DEFAULT '',
        meta_format VARCHAR(255) NOT NULL DEFAULT '',
        meta_price VARCHAR(64) NOT NULL DEFAULT '',
        meta_edition VARCHAR(255) NOT NULL DEFAULT '',
        meta_genre VARCHAR(255) NOT NULL DEFAULT '',
        meta_themes VARCHAR(255) NOT NULL DEFAULT '',
        created_at VARCHAR(32) NOT NULL,
        updated_at VARCHAR(32) NOT NULL
    )$suffix";

    // key/value store for admin-editable settings
    $tables[] = "CREATE TABLE IF NOT EXISTS settings (
        skey VARCHAR(64) NOT NULL PRIMARY KEY,
        svalue TEXT,
        updated_at VARCHAR(32) NOT NULL DEFAULT ''
    )$suffix";

    // per-ip throttle log for the chatbot
    $tables[] = "CREATE TABLE IF NOT EXISTS chat_throttle (
        id $pk,
        ip VARCHAR(64) NOT NULL,
        created_at INTEGER NOT NULL
    )$suffix";

    foreach ($tables as $sql) {
        $pdo->exec($sql);
    }

    // columns added after launch, so old databases upgrade in place
    ensure_column($pdo, $driver, 'books', 'detail_url', "VARCHAR(500) NOT NULL DEFAULT ''");
}

// add a column only if it's not already there
function ensure_column(PDO $pdo, string $driver, string $table, string $col, string $definition): void
{
    $exists = false;
    if ($driver === 'mysql') {
        $stmt = $pdo->query("SHOW COLUMNS FROM `$table` LIKE " . $pdo->quote($col));
        $exists = (bool) $stmt->fetch();
    } else {
        foreach ($pdo->query("PRAGMA table_info($table)")->fetchAll() as $c) {
            if (($c['name'] ?? '') === $col) { $exists = true; break; }
        }
    }
    if (!$exists) {
        $pdo->exec("ALTER TABLE $table ADD COLUMN $col $definition");
    }
}

// first admin + starter content, only runs while tables are empty
function seed(PDO $pdo): void
{
    $now = gmdate('Y-m-d H:i:s');

    // admin user
    $count = (int) $pdo->query('SELECT COUNT(*) AS c FROM users')->fetch()['c'];
    if ($count === 0) {
        $a = cfg()['admin'];
        $stmt = $pdo->prepare(
            'INSERT INTO users (email, password_hash, name, created_at) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            strtolower(trim($a['email'])),
            password_hash($a['password'], PASSWORD_DEFAULT),
            $a['name'],
            $now,
        ]);
    }

    // books
    $count = (int) $pdo->query('SELECT COUNT(*) AS c FROM books')->fetch()['c'];
    if ($count === 0) {
        $books = [
            [
                'title' => 'Through the Storm',
                'slug' => 'through-the-storm',
                'subtitle' => 'Awareness, Acceptance, and the Art of Being Human',
                'description' => 'Crisis is not an interruption to your life. It is the rhythm through which your life grows. Drawing on psychology, philosophy, and the stories of real people navigating real upheaval, Adrian Cole shows that the chaos you are living through is not random — it follows a pattern. And once you see it, everything changes.',
                'body_html' => '<p>Crisis is not an interruption to your life. It is the rhythm through which your life grows. <em>Through the Storm</em> draws on psychology, philosophy, and the stories of real people navigating real upheaval to show that the chaos you are living through is not random — it follows a pattern.</p><p>With a foreword by Olympic gold medallist Marcus Reed, the book offers a framework for meeting hardship with awareness and acceptance rather than fear.</p>',
                'cover_image' => '/assets/img/book-through-the-storm.jpg',
                'buy_url' => 'https://northwindpress.example/shop/',
                'detail_url' => '/through-the-storm/',
                'status' => 'published',
                'featured' => 1,
                'sort_order' => 1,
                'meta_foreword' => 'Marcus Reed',
                'meta_format' => 'Paperback',
                'meta_price' => 'USD 18',
                'meta_edition' => 'First · May 2026',
                'meta_genre' => 'Self-help',
                'meta_themes' => 'Awareness · Acceptance · Resilience',
            ],
            [
                'title' => 'Leading in Practice',
                'slug' => 'leading-in-practice',
                'subtitle' => 'Leadership & professional growth',
                'description' => "A grounded leadership development guide for professionals and emerging executives — drawn from two decades of leading finance, transformation, and people across some of the country's most complex organisations. Less theory, more of what actually works when the stakes are real.",
                'body_html' => "<p>A grounded leadership development guide for professionals and emerging executives — drawn from two decades of leading finance, transformation, and people across some of the country's most complex organisations.</p><p>Less theory, more of what actually works when the stakes are real.</p>",
                'cover_image' => '/assets/img/book-leading-in-practice.jpg',
                'buy_url' => 'https://northwindpress.example/shop/',
                'status' => 'coming_soon',
                'featured' => 0,
                'sort_order' => 2,
                'meta_genre' => 'Leadership Development',
                'meta_themes' => 'Professionals & Executives',
            ],
            [
                'title' => 'The Ladder',
                'slug' => 'the-ladder',
                'subtitle' => 'Political literary fiction',
                'description' => "A literary novel set at the intersection of power, hierarchy, and governance — and the people who climb. The Ladder turns a finance leader's understanding of institutions into a story about ambition, loyalty, and the quiet machinery of authority.",
                'body_html' => "<p>A literary novel set at the intersection of power, hierarchy, and governance — and the people who climb. <em>The Ladder</em> turns a finance leader's understanding of institutions into a story about ambition, loyalty, and the quiet machinery of authority.</p>",
                'cover_image' => '/assets/img/book-the-ladder.jpg',
                'buy_url' => 'https://northwindpress.example/shop/',
                'status' => 'coming_soon',
                'featured' => 0,
                'sort_order' => 3,
                'meta_genre' => 'Literary Fiction',
                'meta_themes' => 'Power · Hierarchy · Governance',
            ],
        ];
        $stmt = $pdo->prepare(
            'INSERT INTO books (title, slug, subtitle, description, body_html, cover_image, buy_url, detail_url,
                status, featured, sort_order, meta_foreword, meta_format, meta_price, meta_edition,
                meta_genre, meta_themes, created_at, updated_at)
             VALUES (:title, :slug, :subtitle, :description, :body_html, :cover_image, :buy_url, :detail_url,
                :status, :featured, :sort_order, :meta_foreword, :meta_format, :meta_price, :meta_edition,
                :meta_genre, :meta_themes, :created_at, :updated_at)'
        );
        foreach ($books as $b) {
            $b += [
                'detail_url' => '',
                'meta_foreword' => '', 'meta_format' => '', 'meta_price' => '',
                'meta_edition' => '', 'meta_genre' => '', 'meta_themes' => '',
            ];
            $b['created_at'] = $now;
            $b['updated_at'] = $now;
            $stmt->execute($b);
        }
    }

    // starter blog post
    $count = (int) $pdo->query('SELECT COUNT(*) AS c FROM posts')->fetch()['c'];
    if ($count === 0) {
        $stmt = $pdo->prepare(
            'INSERT INTO posts (title, slug, excerpt, body_html, cover_image, status, published_at, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            'Finding Steady Ground in a Crisis',
            'finding-steady-ground-in-a-crisis',
            'When everything shifts at once, the instinct is to grip harder. The discipline is to loosen — to find the one thing you can stand on, and start there.',
            '<p>When everything shifts at once, the instinct is to grip harder. The discipline is to loosen — to find the one thing you can stand on, and start there.</p><p>Every crisis arrives disguised as an ending. Only later, looking back, do we recognise it as a hinge — the point at which one version of our life closed and another, unasked for, began.</p><h2>The first move is not action</h2><p>The first move is attention. Before you can change anything, you have to see clearly what is actually happening, stripped of the story you are telling yourself about it.</p>',
            '',
            'published',
            $now,
            $now,
            $now,
        ]);
    }

    // default chatbot settings
    $defaults = [
        'bot_enabled' => '1',
        'bot_persona' =>
            "You are the friendly assistant on the personal website of Adrian Cole — author, "
            . "entrepreneur, and business leader. You help visitors learn about his books "
            . "(Through the Storm, Leading in Practice, The Ladder) and his ideas on leadership and "
            . "resilience. Be warm, concise, and genuine. Never invent facts, prices, or quotes.",
        'bot_knowledge_1' =>
            "ABOUT THE BOOKS\n"
            . "- Through the Storm: Awareness, Acceptance, and the Art of Being Human. A self-help book "
            . "with a foreword by Olympic gold medallist Marcus Reed. Its core idea: crisis is not an "
            . "interruption to life — it is the rhythm through which life grows.\n"
            . "- Leading in Practice: a grounded leadership-development guide for professionals and "
            . "emerging executives, drawn from two decades leading finance and transformation.\n"
            . "- The Ladder: political literary fiction about power, hierarchy, governance, and the people who climb.",
        'bot_knowledge_2' =>
            "FAQ\n"
            . "- Where can I buy the books? Through Northwind Press at northwindpress.example/shop.\n"
            . "- Which book should I start with? Through the Storm is the flagship and a great entry point.\n"
            . "- How can I contact Adrian? Via the Contact page on this site.",
        'bot_greeting' => "Hi! I can help you explore Adrian Cole's books and ideas. What would you like to know?",
    ];
    $has = $pdo->prepare('SELECT 1 FROM settings WHERE skey = ?');
    $ins = $pdo->prepare('INSERT INTO settings (skey, svalue, updated_at) VALUES (?, ?, ?)');
    foreach ($defaults as $k => $v) {
        $has->execute([$k]);
        if (!$has->fetch()) {
            $ins->execute([$k, $v, $now]);
        }
    }
}
