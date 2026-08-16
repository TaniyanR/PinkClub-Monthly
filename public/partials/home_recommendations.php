<?php
declare(strict_types=1);

require_once __DIR__ . '/../../lib/monthly_channel.php';

$monthlyHomeChannels = [
    'standard' => ['label' => '見放題ch', 'items' => []],
    'deluxe' => ['label' => '見放題ch デラックス', 'items' => []],
    'vr' => ['label' => 'VRch', 'items' => []],
];

try {
    $pdo = db();
    $sourceWhere = items_product_source_where();
    $sourceWhereSql = $sourceWhere !== '' ? ' WHERE ' . $sourceWhere : '';
    $rows = $pdo->query(
        'SELECT * FROM items' . $sourceWhereSql .
        ' ORDER BY COALESCE(release_date, updated_at) DESC, updated_at DESC, id DESC LIMIT 600'
    )->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $seen = ['standard' => [], 'deluxe' => [], 'vr' => []];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $key = monthly_item_channel_key($row);
        if (!isset($monthlyHomeChannels[$key])) {
            continue;
        }
        $contentId = strtolower(trim((string)($row['content_id'] ?? '')));
        $rowId = (string)($row['id'] ?? '');
        $dedupeKey = $contentId !== '' ? $contentId : $rowId;
        if ($dedupeKey !== '' && isset($seen[$key][$dedupeKey])) {
            continue;
        }
        if ($dedupeKey !== '') {
            $seen[$key][$dedupeKey] = true;
        }
        $monthlyHomeChannels[$key]['items'][] = $row;
        if (
            count($monthlyHomeChannels['standard']['items']) >= 20
            && count($monthlyHomeChannels['deluxe']['items']) >= 20
            && count($monthlyHomeChannels['vr']['items']) >= 20
        ) {
            break;
        }
    }
} catch (Throwable $e) {
    error_log('[home monthly channels] ' . $e->getMessage());
}
?>

<div id="monthly-home-channel-sections">
<?php foreach ($monthlyHomeChannels as $channelKey => $channelData): ?>
  <?php
  $channelItems = array_slice((array)$channelData['items'], 0, 20);
  $topItems = array_slice($channelItems, 0, 5);
  $bottomItems = array_slice($channelItems, 5, 15);
  $label = (string)$channelData['label'];
  ?>
  <?php if ($channelItems !== []): ?>
    <section class="rail-section only-pc home-feature-section monthly-home-channel" data-monthly-channel="<?= e($channelKey) ?>">
      <h2><a href="<?= e(public_url('monthly.php?channel=' . rawurlencode($channelKey))) ?>"><?= e($label) ?></a></h2>
      <div class="rail-row rail-row--210 rail-row--no-scroll rail-row--top-shift rail-row--between-gap">
        <?php foreach ($topItems as $item) { render_item_card($item, 210, null, false, false); } ?>
      </div>
      <?php if ($bottomItems !== []): ?>
      <div class="rail-row rail-row--200 rail-row--wide-thumb rail-row--bottom-scroll rail-row--bottom-horizontal rail-row--home-taxonomy">
        <?php foreach ($bottomItems as $item) { render_item_card($item, 200, null, true); } ?>
      </div>
      <?php endif; ?>
    </section>

    <section class="rail-section only-sp monthly-home-channel" data-monthly-channel="<?= e($channelKey) ?>">
      <h2><a href="<?= e(public_url('monthly.php?channel=' . rawurlencode($channelKey))) ?>"><?= e($label) ?></a></h2>
      <div class="rail-row rail-row--210 rail-row--no-scroll rail-row--top-shift">
        <?php foreach ($topItems as $item) { render_item_card($item, 210, null, true, false); } ?>
      </div>
    </section>
  <?php endif; ?>
<?php endforeach; ?>
</div>

<script>
(() => {
  const removeLegacyHomeSections = () => {
    const legacyTitles = new Set(['新作作品', '新着作品', 'ピックアップ']);
    document.querySelectorAll('.site-main__body > section.rail-section').forEach((section) => {
      if (section.closest('#monthly-home-channel-sections')) return;
      const heading = section.querySelector(':scope > h2');
      if (heading && legacyTitles.has((heading.textContent || '').trim())) {
        section.remove();
      }
    });
  };

  const cleanVrCards = () => {
    document.querySelectorAll('[data-monthly-channel="vr"] .rail-card').forEach((card) => {
      card.querySelectorAll('button, a, span').forEach((control) => {
        if ((control.textContent || '').trim() === 'サンプル動画') {
          control.remove();
        }
      });
    });
  };

  const run = () => {
    removeLegacyHomeSections();
    cleanVrCards();
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run, { once: true });
  } else {
    run();
  }
})();
</script>
