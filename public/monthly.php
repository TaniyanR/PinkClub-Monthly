<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../lib/monthly_channel.php';

$channel = strtolower(trim((string)($_GET['channel'] ?? 'standard')));
if (!in_array($channel, ['standard', 'deluxe', 'vr'], true)) {
    $channel = 'standard';
}

$channelLabel = match ($channel) {
    'deluxe' => '見放題ch デラックス',
    'vr' => 'VRch',
    default => '見放題ch',
};

$title = $channelLabel;
$pdo = db();
$items = [];
try {
    $stmt = $pdo->query('SELECT * FROM items ORDER BY COALESCE(release_date, updated_at) DESC, id DESC LIMIT 180');
    $all = $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
    foreach ($all as $item) {
        if (monthly_channel_key_from_item($item) === $channel) {
            $items[] = $item;
        }
        if (count($items) >= 48) {
            break;
        }
    }
} catch (Throwable $e) {
    $items = [];
}

require __DIR__ . '/partials/header.php';
?>
<link rel="stylesheet" href="<?= e(asset_url('css/monthly-ui.css')) ?>">
<main class="container">
  <section class="monthly-hero">
    <p class="monthly-hero__eyebrow">PinkClub Monthly</p>
    <h1><?= e($channelLabel) ?>の見放題作品</h1>
    <p>作品を1本ずつ購入するのではなく、好きな作品・女優・ジャンルから見放題サービスを探すためのページです。</p>
  </section>

  <nav class="monthly-channel-grid" aria-label="見放題チャンネル">
    <a class="monthly-channel-card" href="<?= e(public_url('monthly.php?channel=standard')) ?>"><strong>見放題ch</strong><span>定番の見放題作品から探す</span></a>
    <a class="monthly-channel-card" href="<?= e(public_url('monthly.php?channel=deluxe')) ?>"><strong>見放題ch デラックス</strong><span>デラックス対象作品から探す</span></a>
    <a class="monthly-channel-card" href="<?= e(public_url('monthly.php?channel=vr')) ?>"><strong>VRch</strong><span>VRの見放題作品から探す</span></a>
  </nav>

  <h2 class="monthly-section-title">新着作品</h2>
  <?php if ($items === []): ?>
    <div class="monthly-note">このチャンネルの商品データはまだ取得できていません。管理画面の月額API診断でFloorListを確認してください。</div>
  <?php else: ?>
    <div class="product-grid">
      <?php foreach ($items as $item): ?>
        <?php
          $cid = (string)($item['content_id'] ?? '');
          $image = trim((string)($item['image_large'] ?? $item['image_small'] ?? ''));
          $affiliateUrl = monthly_item_affiliate_url($item);
        ?>
        <article class="product-card">
          <a href="<?= e(public_url('item.php?cid=' . rawurlencode($cid))) ?>" class="product-card__image-link">
            <?php if ($image !== ''): ?><img src="<?= e($image) ?>" alt="<?= e((string)$item['title']) ?>" loading="lazy"><?php endif; ?>
          </a>
          <div class="product-card__body">
            <span class="monthly-badge"><?= e($channelLabel) ?></span>
            <h3 class="product-card__title"><a href="<?= e(public_url('item.php?cid=' . rawurlencode($cid))) ?>"><?= e((string)$item['title']) ?></a></h3>
            <div class="monthly-item-extra">
              <a href="<?= e(public_url('item.php?cid=' . rawurlencode($cid))) ?>">作品詳細</a>
              <?php if ($affiliateUrl !== ''): ?><a class="monthly-cta" href="<?= e(outbound_url($affiliateUrl, $cid)) ?>" rel="nofollow sponsored">このチャンネルで見る</a><?php endif; ?>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
