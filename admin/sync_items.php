<?php

declare(strict_types=1);
require_once __DIR__ . '/../public/_bootstrap.php';
auth_require_admin();
$itemSettings = settings_get();
$catalogTargets = $itemSettings['catalog_targets'] ?? [];
if (!is_array($catalogTargets) || $catalogTargets === []) {
    $catalogTargets = [[
        'site' => (string)($itemSettings['site'] ?? 'FANZA'),
        'service' => (string)($itemSettings['service'] ?? 'monthly'),
        'floor' => (string)($itemSettings['floor'] ?? 'monthly_videoa'),
        'label' => '商品',
    ]];
}

$selectedTargetIndex = max(0, (int)($_POST['target_index'] ?? 0));
if (!isset($catalogTargets[$selectedTargetIndex])) {
    $selectedTargetIndex = 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate_or_fail(post('_csrf'));
    try {
        $target = $catalogTargets[$selectedTargetIndex];
        $siteCode = (string)($target['site'] ?? 'FANZA');
        $serviceCode = (string)($target['service'] ?? 'monthly');
        $floorCode = (string)($target['floor'] ?? 'monthly_videoa');
        $label = trim((string)($target['label'] ?? $floorCode));
        $count = dmm_sync_service()->syncItems($siteCode, $serviceCode, $floorCode);
        flash_set('success', $label . " 商品同期: {$count}件");
    } catch (Throwable $e) {
        flash_set('error', '商品同期失敗: ' . $e->getMessage());
    }
    app_redirect('admin/sync_items.php');
}

$title = 'Items';
$logs = db()->query("SELECT * FROM sync_logs WHERE sync_type IN ('item','items') ORDER BY id DESC LIMIT 30")->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<section class="admin-card">
  <h1>Items</h1>
  <p>月額動画の取得先を選んで手動同期できます。各チャンネルのAPI接続確認にも利用できます。</p>
  <form method="post">
    <?= csrf_input() ?>
    <label>取得対象
      <select name="target_index">
        <?php foreach ($catalogTargets as $index => $target): ?>
          <?php
          $targetLabel = trim((string)($target['label'] ?? ''));
          $targetService = (string)($target['service'] ?? '');
          $targetFloor = (string)($target['floor'] ?? '');
          $optionLabel = $targetLabel !== '' ? $targetLabel : $targetFloor;
          ?>
          <option value="<?= e((string)$index) ?>" <?= $index === $selectedTargetIndex ? 'selected' : '' ?>>
            <?= e($optionLabel . ' (' . $targetService . ' / ' . $targetFloor . ')') ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>
    <button type="submit">同期</button>
  </form>
</section>

<section class="admin-card">
  <h2>同期履歴</h2>
  <table class="admin-table">
    <tr><th>時刻</th><th>結果</th><th>件数</th><th>メッセージ</th></tr>
    <?php foreach ($logs as $l): ?>
      <tr><td><?= e($l['created_at']) ?></td><td><?= $l['is_success'] ? 'OK' : 'NG' ?></td><td><?= e($l['synced_count']) ?></td><td><?= e($l['message']) ?></td></tr>
    <?php endforeach; ?>
  </table>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
