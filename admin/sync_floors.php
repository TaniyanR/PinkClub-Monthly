<?php

declare(strict_types=1);
require_once __DIR__ . '/../public/_bootstrap.php';
auth_require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate_or_fail(post('_csrf'));
    try {
        $count = dmm_sync_service()->syncFloors();
        flash_set('success', "Floor同期: {$count}件");
    } catch (Throwable $e) {
        flash_set('error', 'Floor同期失敗: ' . $e->getMessage());
    }
    app_redirect('admin/sync_floors.php');
}

$title = 'Floors';
$floors = db()->query('SELECT * FROM dmm_floors ORDER BY service_code,floor_code')->fetchAll();
$floorLookup = [];
foreach ($floors as $floor) {
    $floorLookup[(string)$floor['service_code'] . ':' . (string)$floor['floor_code']] = true;
}

$settings = settings_get();
$catalogTargets = $settings['catalog_targets'] ?? [];
if (!is_array($catalogTargets) || $catalogTargets === []) {
    $catalogTargets = [[
        'site' => (string)($settings['site'] ?? 'FANZA'),
        'service' => (string)($settings['service'] ?? 'monthly'),
        'floor' => (string)($settings['floor'] ?? 'monthly_videoa'),
        'label' => '商品',
    ]];
}

require __DIR__ . '/includes/header.php';
?>
<section class="admin-card">
  <h1>Floors</h1>
  <p>FANZA APIの最新Floor一覧を取得し、月額動画3チャンネルの設定値が現在も有効か確認します。</p>
  <form method="post">
    <?= csrf_input() ?>
    <button type="submit">Floor同期を実行</button>
  </form>
</section>

<section class="admin-card">
  <h2>月額動画の設定確認</h2>
  <?php if ($floors === []): ?>
    <p>まだFloor一覧を取得していません。「Floor同期を実行」を押してください。</p>
  <?php endif; ?>
  <table class="admin-table">
    <tr><th>対象</th><th>service</th><th>floor</th><th>確認結果</th></tr>
    <?php foreach ($catalogTargets as $target): ?>
      <?php
      $serviceCode = (string)($target['service'] ?? '');
      $floorCode = (string)($target['floor'] ?? '');
      $targetKey = $serviceCode . ':' . $floorCode;
      $isFound = isset($floorLookup[$targetKey]);
      ?>
      <tr>
        <td><?= e((string)($target['label'] ?? $floorCode)) ?></td>
        <td><?= e($serviceCode) ?></td>
        <td><?= e($floorCode) ?></td>
        <td>
          <?php if ($floors === []): ?>
            未確認
          <?php elseif ($isFound): ?>
            OK
          <?php else: ?>
            <strong>要確認</strong>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
  <?php if ($floors !== []): ?>
    <p><small>「要確認」が表示された場合は、下のFloor一覧で現在のservice / floorコードを確認してください。</small></p>
  <?php endif; ?>
</section>

<section class="admin-card">
  <h2>取得済みFloor一覧</h2>
  <table class="admin-table">
    <tr><th>service</th><th>floor</th><th>name</th></tr>
    <?php foreach ($floors as $f): ?>
      <tr><td><?= e($f['service_code']) ?></td><td><?= e($f['floor_code']) ?></td><td><?= e($f['name']) ?></td></tr>
    <?php endforeach; ?>
  </table>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
