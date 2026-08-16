<?php

declare(strict_types=1);
require_once __DIR__ . '/../public/_bootstrap.php';
auth_require_admin();

$pdo = db();
$floors = $pdo->query('SELECT * FROM dmm_floors ORDER BY service_code,floor_code')->fetchAll() ?: [];
$floorLookup = [];
foreach ($floors as $floor) {
    $floorLookup[(string)$floor['service_code'] . ':' . (string)$floor['floor_code']] = $floor;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate_or_fail(post('_csrf'));
    $action = (string)post('action', 'sync');

    if ($action === 'save_targets') {
        $labels = [
            'standard' => '見放題ch',
            'deluxe' => '見放題ch デラックス',
            'vr' => 'VRch',
        ];
        $targets = [];
        $invalid = [];
        foreach ($labels as $key => $label) {
            $value = trim((string)post('target_' . $key, ''));
            if ($value === '' || !isset($floorLookup[$value])) {
                $invalid[] = $label;
                continue;
            }
            [$serviceCode, $floorCode] = explode(':', $value, 2);
            $targets[] = [
                'site' => 'FANZA',
                'service' => $serviceCode,
                'floor' => $floorCode,
                'label' => $label,
            ];
        }

        if ($invalid !== []) {
            flash_set('error', '取得先を保存できませんでした: ' . implode('、', $invalid) . ' を選択してください。');
        } else {
            site_setting_set('monthly_catalog_targets_json', (string)json_encode($targets, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            site_setting_set_many([
                'item_sync_target_index' => '0',
                'item_sync_target_offsets' => '{}',
                'item_sync_test_target_index' => '0',
            ]);
            flash_set('success', '月額動画3チャンネルの取得先を保存しました。商品情報API設定から10件テスト取得してください。');
        }
        app_redirect('admin/sync_floors.php');
    }

    try {
        $count = dmm_sync_service()->syncFloors();
        flash_set('success', "Floor同期: {$count}件。下の候補から月額動画3チャンネルの取得先を選択してください。");
    } catch (Throwable $e) {
        flash_set('error', 'Floor同期失敗: ' . $e->getMessage());
    }
    app_redirect('admin/sync_floors.php');
}

$title = '月額Floor設定';
$settings = settings_get();
$catalogTargets = $settings['catalog_targets'] ?? [];
if (!is_array($catalogTargets)) {
    $catalogTargets = [];
}
$currentByLabel = [];
foreach ($catalogTargets as $target) {
    $label = (string)($target['label'] ?? '');
    $currentByLabel[$label] = (string)($target['service'] ?? '') . ':' . (string)($target['floor'] ?? '');
}

$candidateFloors = array_values(array_filter($floors, static function (array $floor): bool {
    $haystack = mb_strtolower(implode(' ', [
        (string)($floor['service_code'] ?? ''),
        (string)($floor['floor_code'] ?? ''),
        (string)($floor['name'] ?? ''),
    ]));
    foreach (['monthly', '月額', '見放題', 'vr', 'video'] as $needle) {
        if (mb_strpos($haystack, mb_strtolower($needle)) !== false) {
            return true;
        }
    }
    return false;
}));
if ($candidateFloors === []) {
    $candidateFloors = $floors;
}

require __DIR__ . '/includes/header.php';
?>
<section class="admin-card">
  <h1>月額Floor設定</h1>
  <p>FANZA APIのFloorListを取得し、実際に存在するservice / floorを月額動画3チャンネルへ割り当てます。</p>
  <p><strong>現在の400エラーで「floor: Invalid Request Error」が出る場合は、ここでFloor同期と保存を行ってください。</strong></p>
  <form method="post">
    <?= csrf_input() ?>
    <button type="submit" name="action" value="sync">Floor同期を実行</button>
  </form>
</section>

<section class="admin-card">
  <h2>現在の設定確認</h2>
  <table class="admin-table">
    <tr><th>対象</th><th>service</th><th>floor</th><th>確認結果</th></tr>
    <?php foreach ($catalogTargets as $target): ?>
      <?php
      $serviceCode = (string)($target['service'] ?? '');
      $floorCode = (string)($target['floor'] ?? '');
      $targetKey = $serviceCode . ':' . $floorCode;
      ?>
      <tr>
        <td><?= e((string)($target['label'] ?? $floorCode)) ?></td>
        <td><?= e($serviceCode) ?></td>
        <td><?= e($floorCode) ?></td>
        <td><?= $floors === [] ? '未確認' : (isset($floorLookup[$targetKey]) ? '<strong style="color:#19733b;">OK</strong>' : '<strong style="color:#b42318;">要変更</strong>') ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
</section>

<section class="admin-card">
  <h2>月額3チャンネルの取得先</h2>
  <?php if ($floors === []): ?>
    <p>先に「Floor同期を実行」を押してください。</p>
  <?php else: ?>
    <form method="post" class="stack" style="max-width:900px;">
      <?= csrf_input() ?>
      <?php
      $selectors = [
          'standard' => '見放題ch',
          'deluxe' => '見放題ch デラックス',
          'vr' => 'VRch',
      ];
      foreach ($selectors as $key => $label):
          $selectedValue = (string)($currentByLabel[$label] ?? '');
      ?>
        <label><?= e($label) ?><br>
          <select name="target_<?= e($key) ?>" style="width:100%;max-width:760px;">
            <option value="">-- Floorを選択 --</option>
            <?php foreach ($candidateFloors as $floor): ?>
              <?php
              $value = (string)$floor['service_code'] . ':' . (string)$floor['floor_code'];
              $optionText = (string)$floor['name'] . ' (' . (string)$floor['service_code'] . ' / ' . (string)$floor['floor_code'] . ')';
              ?>
              <option value="<?= e($value) ?>" <?= $value === $selectedValue ? 'selected' : '' ?>><?= e($optionText) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
      <?php endforeach; ?>
      <button type="submit" name="action" value="save_targets">この3チャンネルを保存</button>
    </form>
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
