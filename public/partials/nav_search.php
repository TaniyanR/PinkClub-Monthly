<?php
declare(strict_types=1);

require_once __DIR__ . '/_helpers.php';

$path = (string)parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$searchQuery = trim((string)($_GET['q'] ?? ''));

$navItems = [
    ['href' => public_url(''), 'label' => 'TOP'],
    ['href' => public_url('monthly.php?channel=standard'), 'label' => '見放題ch'],
    ['href' => public_url('monthly.php?channel=deluxe'), 'label' => '見放題ch デラックス'],
    ['href' => public_url('monthly.php?channel=vr'), 'label' => 'VRch'],
];
?>
<details class="site-mobile-menu only-sp">
    <summary class="site-mobile-menu__summary">メニュー</summary>
    <div class="site-mobile-menu__body">
        <div class="site-mobile-menu__group">
            <?php foreach ($navItems as $item) : ?>
                <a href="<?= e($item['href']) ?>"><?= e($item['label']) ?></a>
            <?php endforeach; ?>
        </div>
        <form class="site-mobile-menu__search" method="get" action="<?= e(public_url('search.php')) ?>">
            <input class="site-search__input" type="search" name="q" value="<?= e($searchQuery) ?>" placeholder="見放題作品を検索" aria-label="見放題作品を検索">
            <button class="site-search__button" type="submit">検索</button>
        </form>
    </div>
</details>
<nav class="site-nav" aria-label="グローバルナビゲーション">
    <?php foreach ($navItems as $index => $item) : ?>
        <?php
        $itemPath = (string)parse_url($item['href'], PHP_URL_PATH);
        $itemQuery = (string)parse_url($item['href'], PHP_URL_QUERY);
        parse_str($itemQuery, $itemParams);
        $isActive = $path === $itemPath;
        if ($isActive && isset($itemParams['channel'])) {
            $isActive = (string)($_GET['channel'] ?? '') === (string)$itemParams['channel'];
        }
        ?>
        <?php if ($index > 0): ?><span class="site-nav__sep" aria-hidden="true"> | </span><?php endif; ?>
        <a class="<?= $isActive ? 'is-active' : '' ?>" href="<?= e($item['href']) ?>"><?= e($item['label']) ?></a>
    <?php endforeach; ?>
    <form class="site-search" method="get" action="<?= e(public_url('search.php')) ?>">
        <input class="site-search__input" type="search" name="q" value="<?= e($searchQuery) ?>" placeholder="見放題作品を検索" aria-label="見放題作品を検索">
        <button class="site-search__button" type="submit">検索</button>
    </form>
</nav>
<script>
window.PinkClubMonthlyConfig = {
  baseUrl: <?= json_encode(rtrim((string)BASE_URL, '/'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
  cssUrl: <?= json_encode(asset_url('css/monthly-ui.css'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
};
</script>
<script src="<?= e(asset_url('js/monthly-ui.js')) ?>" defer></script>
