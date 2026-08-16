<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../lib/repository.php';
require_once __DIR__ . '/../lib/monthly_channel.php';

$channel = strtolower(trim((string)($_GET['channel'] ?? 'all')));
if (!in_array($channel, ['all', 'standard', 'deluxe', 'vr'], true)) {
    $channel = 'all';
}

$channelLabel = $channel === 'all' ? '月額見放題' : monthly_channel_label($channel);
$title = $channelLabel . 'から作品を探す';
$pageDescription = '作品・女優・ジャンルから月額見放題作品を探し、対象の見放題チャンネルへ進むためのページです。';

$items = [];
try {
    $pdo = db();
    $sourceWhere = items_product_source_where();
    $sourceWhereSql = $sourceWhere !== '' ? ' WHERE ' . $sourceWhere : '';
    $rows = $pdo->query('SELECT * FROM items' . $sourceWhereSql . ' ORDER BY COALESCE(release_date, updated_at) DESC, id DESC LIMIT 240')->fetchAll(PDO::FETCH_ASSOC) ?: [];
    foreach ($rows as $row) {
        if ($channel !== 'all' && monthly_item_channel_key($row) !== $channel) {
            continue;
        }
        $items[] = $row;
        if (count($items) >= 60) {
            break;
        }
    }
} catch (Throwable $e) {
    error_log('[monthly.php] ' . $e->getMessage());
    $items = [];
}

require __DIR__ . '/partials/header.php';
?>
<link rel="stylesheet" href="<?= e(asset_url('css/monthly-ui.css')) ?>">

<section class="monthly-hero">
  <p class="monthly-hero__eyebrow">PinkClub Monthly</p>
  <h1><?= e($channelLabel) ?>から作品を探す</h1>
  <p>単品購入ではなく、好きな作品を入口に「どの見放題チャンネルで楽しむか」を探すためのページです。</p>
</section>

<nav class="monthly-channel-grid" aria-label="月額見放題チャンネル">
  <a class="monthly-channel-card" href="<?= e(public_url('monthly.php?channel=standard')) ?>"><strong>見放題ch</strong><span>定番の見放題作品から探す</span></a>
  <a class="monthly-channel-card" href="<?= e(public_url('monthly.php?channel=deluxe')) ?>"><strong>見放題ch デラックス</strong><span>デラックス対象作品から探す</span></a>
  <a class="monthly-channel-card" href="<?= e(public_url('monthly.php?channel=vr')) ?>"><strong>VRch</strong><span>VRの見放題作品から探す</span></a>
</nav>

<div class="monthly-note">料金・キャンペーン・現在の見放題対象状況は変更される場合があります。最終的なサービス内容はリンク先で確認してください。</div>

<h2 class="monthly-section-title"><?= e($channelLabel) ?>の新着作品</h2>
<?php if ($items === []): ?>
  <div class="pcf-empty">このチャンネルの商品データはまだ取得できていません。管理画面の「月額Floor設定」で正しいFloorを保存し、商品情報API設定からテスト取得してください。</div>
<?php else: ?>
  <section class="monthly-grid">
    <?php foreach ($items as $item): ?>
      <?php
      $itemId = (int)($item['id'] ?? 0);
      $itemTitle = trim((string)($item['title'] ?? ''));
      $imageUrl = trim((string)($item['image_large'] ?? ''));
      if ($imageUrl === '') {
          $imageUrl = trim((string)($item['image_small'] ?? ''));
      }
      $itemUrl = public_url('item.php?id=' . $itemId);
      $badgeLabel = monthly_item_channel_label($item);
      ?>
      <article class="monthly-work-card">
        <a class="monthly-work-card__image" href="<?= e($itemUrl) ?>">
          <?php if ($imageUrl !== ''): ?>
            <img src="<?= e($imageUrl) ?>" alt="<?= e($itemTitle) ?>" loading="lazy" decoding="async">
          <?php endif; ?>
        </a>
        <div class="monthly-work-card__body">
          <span class="monthly-badge"><?= e($badgeLabel) ?></span>
          <h3 class="monthly-work-card__title"><a href="<?= e($itemUrl) ?>"><?= e($itemTitle) ?></a></h3>
          <div class="monthly-work-card__actions">
            <a class="monthly-cta" href="<?= e($itemUrl) ?>">作品を詳しく見る</a>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </section>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
