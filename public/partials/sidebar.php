<?php

declare(strict_types=1);

require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/app_features.php';
require_once __DIR__ . '/../../lib/contact_page_slug.php';
require_once __DIR__ . '/../../lib/public_counts.php';
require_once __DIR__ . '/_helpers.php';

$sortMode = site_setting_get('link.sort_mode', 'registered');
$orderBy = $sortMode === 'kana' ? 'ps.name ASC, ps.id ASC' : 'ps.id DESC';
$canRenderAd = function_exists('render_ad');

$partnerLinks = [];
$textRssSiteCount = null;
$sitePostCount = null;
$siteActressCount = null;
$fixedPages = [];
$sidebarGenres = [];
$sidebarActresses = [];
$sidebarSeries = [];
$defaultFixedPages = [
    ['slug' => 'about', 'title' => 'サイトについて', 'href' => public_url('page.php?slug=about')],
    ['slug' => 'privacy-policy', 'title' => 'Privacy Policy', 'href' => public_url('page.php?slug=privacy-policy')],
    ['slug' => CONTACT_PAGE_SLUG, 'title' => 'お問い合わせ', 'href' => public_url('page.php?slug=que')],
];

$publicCounts = pcf_public_counts();
$sitePostCount = $publicCounts['posts'];
$siteActressCount = $publicCounts['actresses'];

try {
    $stmt = db()->query('SELECT id,name FROM genres WHERE name <> "" ORDER BY updated_at DESC,id DESC LIMIT 10');
    $sidebarGenres = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (Throwable) {
    $sidebarGenres = [];
}

try {
    $stmt = db()->query('SELECT id,name FROM actresses WHERE name <> "" ORDER BY updated_at DESC,id DESC LIMIT 8');
    $sidebarActresses = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (Throwable) {
    $sidebarActresses = [];
}

try {
    if (db_table_exists(db(), 'series_master')) {
        $stmt = db()->query('SELECT id,name FROM series_master WHERE name <> "" ORDER BY updated_at DESC,id DESC LIMIT 8');
    } else {
        $stmt = db()->query('SELECT id,name FROM series WHERE name <> "" ORDER BY updated_at DESC,id DESC LIMIT 8');
    }
    $sidebarSeries = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (Throwable) {
    $sidebarSeries = [];
}

try {
    $stmt = db()->query("SELECT ps.id, ps.name, ps.url, COALESCE(ps.show_link, ps.is_enabled, 1) AS show_link FROM partner_sites ps WHERE COALESCE(ps.show_link, ps.is_enabled, 1) = 1 ORDER BY {$orderBy}");
    $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    $seenPartnerUrls = [];
    foreach ($rows as $row) {
        $url = rss_normalize_url((string)($row['url'] ?? ''));
        if ($url === '' || isset($seenPartnerUrls[$url])) {
            continue;
        }
        $seenPartnerUrls[$url] = true;
        $partnerLinks[] = $row;
    }
} catch (Throwable) {
    $partnerLinks = [];
}

try {
    $stmt = db()->query('SELECT COUNT(DISTINCT pr.partner_site_id) FROM partner_rss pr INNER JOIN partner_sites ps ON ps.id = pr.partner_site_id WHERE pr.feed_url <> "" AND COALESCE(pr.show_rss, pr.is_enabled, 1) = 1');
    $textRssSiteCount = $stmt ? (int)$stmt->fetchColumn() : null;
} catch (Throwable) {
    $textRssSiteCount = null;
}

try {
    $stmt = db()->query('SELECT slug,title FROM fixed_pages WHERE is_published=1 ORDER BY id ASC');
    $fixedPages = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (Throwable) {
    $fixedPages = [];
}
if ($fixedPages === []) {
    $fixedPages = $defaultFixedPages;
}
?>
<aside class="sidebar site-sidebar">
    <?php $pageType = function_exists('ad_current_page_type') ? ad_current_page_type() : 'home'; ?>

    <section class="sidebar-block monthly-sidebar-block">
        <h2 class="monthly-sidebar-title">一覧メニュー</h2>
        <ul class="sidebar-links">
            <li><a href="<?= e(public_url('items.php')) ?>">見放題作品一覧</a></li>
            <li><a href="<?= e(public_url('genres.php')) ?>">ジャンル一覧</a></li>
            <li><a href="<?= e(public_url('actresses.php')) ?>">女優一覧</a></li>
            <li><a href="<?= e(public_url('series_list.php')) ?>">シリーズ一覧</a></li>
        </ul>
    </section>

    <section class="sidebar-block monthly-sidebar-block">
        <h2 class="monthly-sidebar-title">ジャンル</h2>
        <?php if ($sidebarGenres === []): ?>
            <p class="sidebar-empty">ジャンル取得後に表示されます。</p>
        <?php else: ?>
            <ul class="sidebar-links">
                <?php foreach ($sidebarGenres as $genre): ?>
                    <li><a href="<?= e(public_url('genre.php?id=' . (int)$genre['id'])) ?>"><?= e((string)$genre['name']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section class="sidebar-block monthly-sidebar-block">
        <h2 class="monthly-sidebar-title">おすすめ女優</h2>
        <?php if ($sidebarActresses === []): ?>
            <p class="sidebar-empty">女優情報取得後に表示されます。</p>
        <?php else: ?>
            <ul class="sidebar-links">
                <?php foreach ($sidebarActresses as $actress): ?>
                    <li><a href="<?= e(public_url('actress.php?id=' . (int)$actress['id'])) ?>"><?= e((string)$actress['name']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section class="sidebar-block monthly-sidebar-block">
        <h2 class="monthly-sidebar-title">おすすめシリーズ</h2>
        <?php if ($sidebarSeries === []): ?>
            <p class="sidebar-empty">シリーズ情報取得後に表示されます。</p>
        <?php else: ?>
            <ul class="sidebar-links">
                <?php foreach ($sidebarSeries as $series): ?>
                    <li><a href="<?= e(public_url('series_detail.php?id=' . (int)$series['id'])) ?>"><?= e((string)$series['name']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <?php if ($canRenderAd && (!function_exists('should_show_ad') || should_show_ad('sidebar_bottom', $pageType, 'pc'))): ?>
    <section class="sidebar-block sidebar-block--ad1 only-pc">
        <div class="site-ad site-ad--rectangle"><?php render_ad('sidebar_bottom', $pageType, 'pc'); ?></div>
    </section>
    <?php endif; ?>

    <?php if (site_setting_get('link.rss_display.pc_image', '1') === '1'): ?>
    <section class="sidebar-block">
        <?php include __DIR__ . '/rss_image_widget.php'; ?>
    </section>
    <?php endif; ?>

    <?php if (site_setting_get('link.rss_display.pc_text_sidebar', '1') === '1'): ?>
    <section class="sidebar-block sidebar-block--text-rss">
        <?php
        $prevTextRssUsedKeys = $GLOBALS['pcf_rss_widget_used_keys'] ?? null;
        $prevTextRssMaxItems = $GLOBALS['pcf_rss_widget_max_items'] ?? null;
        $GLOBALS['pcf_rss_widget_used_keys'] = [];
        if ($textRssSiteCount !== null) {
            $GLOBALS['pcf_rss_widget_max_items'] = min(50, max(0, $textRssSiteCount * 5));
        } else {
            unset($GLOBALS['pcf_rss_widget_max_items']);
        }
        include __DIR__ . '/rss_text_widget.php';
        if ($prevTextRssUsedKeys === null) {
            unset($GLOBALS['pcf_rss_widget_used_keys']);
        } else {
            $GLOBALS['pcf_rss_widget_used_keys'] = $prevTextRssUsedKeys;
        }
        if ($prevTextRssMaxItems === null) {
            unset($GLOBALS['pcf_rss_widget_max_items']);
        } else {
            $GLOBALS['pcf_rss_widget_max_items'] = $prevTextRssMaxItems;
        }
        ?>
    </section>
    <?php endif; ?>

    <section class="sidebar-block">
        <ul class="sidebar-links sidebar-links--pages">
            <?php if ($sitePostCount !== null): ?><li><a style="color:#000;">公開作品数：<strong><?= e(number_format($sitePostCount)) ?></strong></a></li><?php endif; ?>
            <?php if ($siteActressCount !== null): ?><li><a style="color:#000;">公開女優数：<strong><?= e(number_format($siteActressCount)) ?></strong></a></li><?php endif; ?>
            <?php foreach ($fixedPages as $page): ?>
                <?php
                $pageHref = trim((string)($page['href'] ?? ''));
                if ($pageHref === '') {
                    $pageSlug = (string)($page['slug'] ?? '');
                    if ($pageSlug === CONTACT_PAGE_OLD_SLUG) {
                        $pageSlug = CONTACT_PAGE_SLUG;
                    }
                    $pageHref = public_url('page.php?slug=' . $pageSlug);
                }
                ?>
                <li><a href="<?= e($pageHref) ?>"><?= e((string)($page['title'] ?? '')) ?></a></li>
            <?php endforeach; ?>
        </ul>
    </section>

    <?php if (site_setting_get('link.rss_display.pc_partner_links', '1') === '1' && $partnerLinks !== []): ?>
    <section class="sidebar-block">
        <ul class="sidebar-links sidebar-links--partners">
            <?php foreach ($partnerLinks as $link) : ?>
                <li><a href="<?= e((string)$link['url']) ?>" target="_blank" rel="noopener noreferrer"><?= e((string)$link['name']) ?></a></li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php endif; ?>
</aside>
