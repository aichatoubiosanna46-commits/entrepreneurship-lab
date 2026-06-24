<?php
// sitemap.php — Sitemap XML automatique
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/xml; charset=utf-8');
$pdo = getPDO();

$urls = [];

// Pages statiques
$urls[] = ['url' => SITE_URL . '/',               'prio' => '1.0', 'freq' => 'daily',   'date' => date('Y-m-d')];
$urls[] = ['url' => SITE_URL . '/catalogue.php',  'prio' => '0.9', 'freq' => 'daily',   'date' => date('Y-m-d')];
$urls[] = ['url' => SITE_URL . '/blog.php',       'prio' => '0.7', 'freq' => 'weekly',  'date' => date('Y-m-d')];
$urls[] = ['url' => SITE_URL . '/forum.php',      'prio' => '0.6', 'freq' => 'daily',   'date' => date('Y-m-d')];
$urls[] = ['url' => SITE_URL . '/register.php',   'prio' => '0.8', 'freq' => 'monthly', 'date' => date('Y-m-d')];
$urls[] = ['url' => SITE_URL . '/rgpd.php',       'prio' => '0.3', 'freq' => 'yearly',  'date' => date('Y-m-d')];

// Cours publiés
try {
    $cours = $pdo->query("SELECT slug, updated_at FROM courses WHERE actif=1 AND statut='publie' ORDER BY updated_at DESC")->fetchAll();
    foreach ($cours as $c) {
        $urls[] = [
            'url'  => SITE_URL . '/course_detail.php?slug=' . urlencode($c['slug']),
            'prio' => '0.8',
            'freq' => 'weekly',
            'date' => date('Y-m-d', strtotime($c['updated_at'] ?? 'now')),
        ];
    }
} catch (Exception $e) {}

// Articles blog
try {
    $articles = $pdo->query("SELECT slug, updated_at FROM blog_posts WHERE publie=1 ORDER BY updated_at DESC")->fetchAll();
    foreach ($articles as $a) {
        $urls[] = [
            'url'  => SITE_URL . '/blog_article.php?slug=' . urlencode($a['slug']),
            'prio' => '0.6',
            'freq' => 'monthly',
            'date' => date('Y-m-d', strtotime($a['updated_at'] ?? 'now')),
        ];
    }
} catch (Exception $e) {}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $u) {
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($u['url']) . "</loc>\n";
    echo "    <lastmod>" . $u['date'] . "</lastmod>\n";
    echo "    <changefreq>" . $u['freq'] . "</changefreq>\n";
    echo "    <priority>" . $u['prio'] . "</priority>\n";
    echo "  </url>\n";
}
echo '</urlset>';
