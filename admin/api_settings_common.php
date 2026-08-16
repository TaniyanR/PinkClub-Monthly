<?php

declare(strict_types=1);

require_once __DIR__ . '/../public/_bootstrap.php';
require_once __DIR__ . '/../lib/app.php';

auth_require_admin();

if (!isset($pageTitle)) {
    throw new RuntimeException('api settings page title is not initialized.');
}

$apiType = 'items';
$title = $pageTitle;
$testButtonLabel = (string)($testButtonLabel ?? '月額動画を10件テスト取得して保存');
$message = '';
$messageType = 'success';
$cred = api_credential_get($apiType);
$apiId = (string)($cred['api_id'] ?? '');
$affiliateId = (string)($cred['affiliate_id'] ?? '');
$savedRows = [];
$perPage = 50;
$totalRows = 0;

function monthly_detect_catalog_targets_from_floor_list(PDO $pdo): array
{
    $rows = $pdo->query('SELECT service_code,floor_code,name FROM dmm_floors ORDER BY service_code,floor_code')->fetchAll(PDO::FETCH_ASSOC) ?: [];
    if ($rows === []) {
        return [];
    }

    $matches = ['standard' => null, 'deluxe' => null, 'vr' => null];
    foreach ($rows as $row) {
        $name = trim((string)($row['name'] ?? ''));
        $service = trim((string)($row['service_code'] ?? ''));
        $floor = trim((string)($row['floor_code'] ?? ''));
        if ($name === '' || $service === '' || $floor === '') {
            continue;
        }

        $normalized = mb_strtolower($name . ' ' . $service . ' ' . $floor);
        if ($matches['vr'] === null && (str_contains($normalized, 'vr') || str_contains($name, 'ＶＲ'))) {
            $matches['vr'] = ['site' => 'FANZA', 'service' => $service, 'floor' => $floor, 'label' => 'VRch'];
            continue;
        }
        if ($matches['deluxe'] === null && (str_contains($name, 'デラックス') || str_contains($normalized, 'deluxe'))) {
            $matches['deluxe'] = ['site' => 'FANZA', 'service' => $service, 'floor' => $floor, 'label' => '見放題ch デラックス'];
            continue;
        }
        if ($matches['standard'] === null && (str_contains($name, '見放題') || str_contains($normalized, 'monthly'))) {
            $matches['standard'] = ['site' => 'FANZA', 'service' => $service, 'floor' => $floor, 'label' => '見放題ch'];
        }
    }

    return array_values(array_filter([$matches['standard'], $matches['deluxe'], $matches['vr']], 'is_array'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate_or_fail((string)post('_csrf', ''));
    $action = (string)post('action', 'save');
    $apiId = trim((string)post('api_id', $apiId));
    $affiliateId = trim((string)post('affiliate_id', $affiliateId));

    if ($action === 'save') {
        api_credential_set($apiType, $apiId, $affiliateId);
        $message = 'API設定を保存しました。';
    }

    if ($action === 'test_save') {
        try {
            api_credential_set($apiType, $apiId, $affiliateId);
            $sync = dmm_sync_service($apiType);

            // 利用者にFloor設定を要求せず、FANZAのFloor一覧から月額3種を内部判定する。
            $sync->syncFloors();
            $detectedTargets = monthly_detect_catalog_targets_from_floor_list(db());
            if (count($detectedTargets) < 1) {
                throw new RuntimeException('月額動画の取得先を自動判定できませんでした。FANZAのFloor一覧を確認してください。');
            }
            site_setting_set('monthly_catalog_targets_json', (string)json_encode($detectedTargets, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            $processed = 0;
            $messages = [];
            foreach ($detectedTargets as $target) {
                if ($processed >= 10) {
                    break;
                }
                $remaining = 10 - $processed;
                $batch = min(4, $remaining);
                $result = $sync->syncItemsBatch(
                    (string)$target['site'],
                    (string)$target['service'],
                    (string)$target['floor'],
                    $batch,
                    1,
                    ['sort' => 'rank']
                );
                $count = (int)($result['synced_count'] ?? 0);
                $processed += $count;
                $messages[] = (string)$target['label'] . ':' . $count . '件';
            }

            $message = '月額動画をテスト取得して保存しました。' . implode(' / ', $messages) . ' / 合計:' . $processed . '件';
            $messageType = 'success';
        } catch (Throwable $e) {
            $message = '保存に失敗しました: ' . $e->getMessage();
            $messageType = 'error';
        }
    }

    if ($action === 'delete_row') {
        $id = (int)post('row_id', 0);
        if ($id > 0) {
            db()->prepare('DELETE FROM items WHERE id = :id')->execute([':id' => $id]);
            $message = '商品を削除しました。';
        }
    }
}

try {
    $totalRows = (int)db()->query('SELECT COUNT(*) FROM items')->fetchColumn();
    $stmt = db()->prepare('SELECT id AS row_id,content_id,title AS row_name,updated_at FROM items ORDER BY id DESC LIMIT :limit');
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->execute();
    $savedRows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable) {
    $savedRows = [];
}

require __DIR__ . '/includes/header.php';
?>
<section class="card">
  <h1><?= e($pageTitle) ?></h1>
  <p>APIIDとアフィリエイトIDを保存します。月額動画の取得先はテスト取得時に内部で確認するため、Floor設定は不要です。</p>

  <?php if ($message !== ''): ?>
    <div class="admin-notice <?= $messageType === 'success' ? 'admin-notice--success' : 'admin-notice--error' ?>"><p><?= e($message) ?></p></div>
  <?php endif; ?>

  <form method="post" class="stack" style="max-width:700px;">
    <?= csrf_input() ?>
    <div><label>APIID<br><input type="text" name="api_id" value="<?= e($apiId) ?>" style="width:100%"></label></div>
    <div><label>アフィリエイトID<br><input type="text" name="affiliate_id" value="<?= e($affiliateId) ?>" style="width:100%"></label></div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
      <button type="submit" name="action" value="save">保存</button>
      <button type="submit" name="action" value="test_save" class="button-secondary"><?= e($testButtonLabel) ?></button>
    </div>
  </form>

  <h2>保存済み商品（全<?= e((string)$totalRows) ?>件 / 最新50件）</h2>
  <table class="admin-table">
    <tr><th>No.</th><th>名称</th><th>更新日時</th><th>操作</th></tr>
    <?php foreach ($savedRows as $index => $row): ?>
      <tr>
        <td><?= e((string)max(1, $totalRows - (int)$index)) ?></td>
        <td><a href="<?= e(public_url('item.php?cid=' . rawurlencode((string)($row['content_id'] ?? '')))) ?>" target="_blank" rel="noopener noreferrer"><?= e((string)($row['row_name'] ?? '')) ?></a></td>
        <td><?= e((string)($row['updated_at'] ?? '')) ?></td>
        <td>
          <form method="post">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="delete_row">
            <input type="hidden" name="row_id" value="<?= e((string)($row['row_id'] ?? '0')) ?>">
            <button type="submit" class="button-secondary">削除</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
