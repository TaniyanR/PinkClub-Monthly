<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../lib/repository.php';
require_once __DIR__ . '/../lib/monthly_channel.php';

$channel = strtolower(trim((string)($_GET['channel'] ?? 'standard')));
if (!in_array($channel, ['standard', 'deluxe', 'vr'], true)) {
    $channel = 'standard';
}

$channelLabel = monthly_channel_label($channel);
$title = $channelLabel;
$pageDescription = $channelLabel . 'の月額見放題作品を、作品・ジャンル・女優から探せます。';

function monthly_card_movie_url(array $item): string
{
    foreach (['sample_movie_url_720', 'sample_movie_url_644', 'sample_movie_url_560', 'sample_movie_url_476'] as $column) {
        $url = trim((string)($item[$column] ?? ''));
        if ($url !== '') {
            return $url;
        }
    }

    $raw = json_decode((string)($item['raw_json'] ?? ''), true);
    if (!is_array($raw)) {
        return '';
    }
    $movie = $raw['sampleMovieURL'] ?? null;
    if (is_string($movie)) {
        return trim($movie);
    }
    if (is_array($movie)) {
        foreach (['size_720_480', 'size_644_414', 'size_560_360', 'size_476_306'] as $key) {
            $url = trim((string)($movie[$key] ?? ''));
            if ($url !== '') {
                return $url;
            }
        }
    }
    return '';
}

function monthly_card_has_sample_images(array $item): bool
{
    $imageList = trim((string)($item['image_list'] ?? ''));
    if ($imageList !== '') {
        return true;
    }

    $raw = json_decode((string)($item['raw_json'] ?? ''), true);
    if (!is_array($raw)) {
        return false;
    }
    $sample = $raw['sampleImageURL'] ?? null;
    return is_array($sample) && $sample !== [];
}

$items = [];
try {
    $pdo = db();
    $sourceWhere = items_product_source_where();
    $sourceWhereSql = $sourceWhere !== '' ? ' WHERE ' . $sourceWhere : '';
    $rows = $pdo->query('SELECT * FROM items' . $sourceWhereSql . ' ORDER BY COALESCE(release_date, updated_at) DESC, id DESC LIMIT 300')->fetchAll(PDO::FETCH_ASSOC) ?: [];
    foreach ($rows as $row) {
        if (monthly_item_channel_key($row) !== $channel) {
            continue;
        }
        $items[] = $row;
        if (count($items) >= 80) {
            break;
        }
    }
} catch (Throwable $e) {
    error_log('[monthly.php] ' . $e->getMessage());
}

require __DIR__ . '/partials/header.php';
?>
<link rel="stylesheet" href="<?= e(asset_url('css/monthly-ui.css')) ?>">

<section class="monthly-hero monthly-hero--channel">
  <p class="monthly-hero__eyebrow">PinkClub Monthly</p>
  <h1><?= e($channelLabel) ?></h1>
  <?php if ($channel === 'standard'): ?>
    <p>見放題chの対象作品から探します。気になる作品、女優、ジャンルを入口に、自分に合う月額サービスか確認できます。</p>
  <?php elseif ($channel === 'deluxe'): ?>
    <p>見放題ch デラックスの対象作品から探します。通常の見放題chとは分けて、デラックス対象作品だけを確認できます。</p>
  <?php else: ?>
    <p>VRchの対象作品から探します。VR作品は通常動画とは性質が異なるため、商品カードではサンプル動画を表示しません。</p>
  <?php endif; ?>
</section>

<div class="monthly-note">料金・キャンペーン・現在の対象作品は変更される場合があります。最終的なサービス内容はリンク先で確認してください。</div>

<h2 class="monthly-section-title"><?= e($channelLabel) ?>の作品</h2>
<?php if ($items === []): ?>
  <div class="pcf-empty">このチャンネルの作品データはまだ取得できていません。API設定を保存してからテスト取得または自動取得を実行してください。</div>
<?php else: ?>
  <section class="monthly-grid">
    <?php foreach ($items as $item): ?>
      <?php
      $itemId = (int)($item['id'] ?? 0);
      $contentId = trim((string)($item['content_id'] ?? ''));
      $itemTitle = trim((string)($item['title'] ?? ''));
      $imageUrl = trim((string)($item['image_large'] ?? ''));
      if ($imageUrl === '') {
          $imageUrl = trim((string)($item['image_small'] ?? ''));
      }
      $releaseDate = trim((string)($item['release_date'] ?? ''));
      $itemUrl = public_url('item.php?id=' . $itemId);
      $movieUrl = $channel === 'vr' ? '' : monthly_card_movie_url($item);
      $hasSampleImages = monthly_card_has_sample_images($item);
      $sampleImagesUrl = public_url('sample_images.php?content_id=' . rawurlencode($contentId));
      ?>
      <article class="monthly-work-card">
        <a class="monthly-work-card__image" href="<?= e($itemUrl) ?>">
          <?php if ($imageUrl !== ''): ?>
            <img src="<?= e($imageUrl) ?>" alt="<?= e($itemTitle) ?>" loading="lazy" decoding="async">
          <?php else: ?>
            <span class="monthly-work-card__noimage">画像なし</span>
          <?php endif; ?>
        </a>
        <div class="monthly-work-card__body">
          <span class="monthly-badge"><?= e($channelLabel) ?></span>
          <h3 class="monthly-work-card__title"><a href="<?= e($itemUrl) ?>"><?= e($itemTitle) ?></a></h3>
          <div class="monthly-release-date"><?= $releaseDate !== '' ? '発売日：' . e(format_date($releaseDate)) : '発売日：未取得' ?></div>
          <div class="monthly-work-card__actions monthly-work-card__actions--samples">
            <?php if ($channel !== 'vr'): ?>
              <?php if ($movieUrl !== ''): ?>
                <a class="monthly-sample-button" href="<?= e($movieUrl) ?>" target="_blank" rel="noopener noreferrer">サンプル動画</a>
              <?php else: ?>
                <span class="monthly-sample-button is-disabled">サンプル動画</span>
              <?php endif; ?>
            <?php endif; ?>
            <?php if ($hasSampleImages && $contentId !== ''): ?>
              <a class="monthly-sample-button" href="<?= e($sampleImagesUrl) ?>" target="_blank" rel="noopener noreferrer">サンプル画像</a>
            <?php else: ?>
              <span class="monthly-sample-button is-disabled">サンプル画像</span>
            <?php endif; ?>
          </div>
          <a class="monthly-cta monthly-cta--detail" href="<?= e($itemUrl) ?>">作品詳細</a>
        </div>
      </article>
    <?php endforeach; ?>
  </section>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
