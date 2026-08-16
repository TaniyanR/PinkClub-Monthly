<?php

declare(strict_types=1);

require_once __DIR__ . '/../public/_bootstrap.php';
require_once __DIR__ . '/../lib/app.php';

/** @var string $pageTitle */
/** @var string $testButtonLabel */

if (!isset($pageTitle)) {
    throw new RuntimeException('api settings page title is not initialized.');
}

auth_require_admin();

$apiType = 'items';
$title = $pageTitle;
$testButtonLabel = (string)($testButtonLabel ?? '商品情報を10件テスト取得して保存');
$message = '';
$messageType = 'success';
$cred = api_credential_get($apiType);
$apiId = (string)($cred['api_id'] ?? '');
$affiliateId = (string)($cred['affiliate_id'] ?? '');
$testResult = null;
$savedRows = [];
$currentPage = 1;
$perPage = 50;
$totalRows = 0;
$totalPages = 1;

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
$testTargetIndex = max(0, (int)site_setting_get('item_sync_test_target_index', '0'));
if (!isset($catalogTargets[$testTargetIndex])) {
    $testTargetIndex = 0;
}

$saveTargets = [
    'items' => ['table' => 'items', 'label' => '商品', 'id_column' => 'id', 'name_column' => 'title'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate_or_fail((string)post('_csrf', ''));
    $action = (string)post('action', 'save');
    $postedTargetIndex = max(0, (int)post('test_target_index', $testTargetIndex));
    if (isset($catalogTargets[$postedTargetIndex])) {
        $testTargetIndex = $postedTargetIndex;
    }

    if ($action === 'save') {
        $apiId = trim((string)post('api_id', ''));
        $affiliateId = trim((string)post('affiliate_id', ''));
        api_credential_set($apiType, $apiId, $affiliateId);
        site_setting_set('item_sync_test_target_index', (string)$testTargetIndex);
        $message = '設定を保存しました。';
        $messageType = 'success';
    }

    if ($action === 'test_save') {
        try {
            $apiId = trim((string)post('api_id', $apiId));
            $affiliateId = trim((string)post('affiliate_id', $affiliateId));
            api_credential_set($apiType, $apiId, $affiliateId);
            $sync = dmm_sync_service($apiType);

            $target = $catalogTargets[$testTargetIndex];
            $targetSite = (string)($target['site'] ?? 'FANZA');
            $targetService = (string)($target['service'] ?? 'monthly');
            $targetFloor = (string)($target['floor'] ?? 'monthly_videoa');
            $targetLabel = trim((string)($target['label'] ?? $targetFloor));
            $testOffsetKey = 'item_sync_test_offset_' . (string)$testTargetIndex;

            $beforeCount = (int)db()->query('SELECT COUNT(*) FROM items')->fetchColumn();
            $testOffset = (int)site_setting_get($testOffsetKey, '1');
            if ($testOffset < 1) {
                $testOffset = 1;
            }
            $sortModes = ['rank', 'date', 'review'];
            $sortIndex = (int)site_setting_get('item_sync_test_sort_index', '0');
            if ($sortIndex < 0) {
                $sortIndex = 0;
            }
            $sort = $sortModes[$sortIndex % count($sortModes)];
            $processed = 0;
            $nextOffset = $testOffset;
            $attempts = 0;
            $afterCount = $beforeCount;
            while ($attempts < 12) {
                $attempts++;
                $result = $sync->syncItemsBatch(
                    $targetSite,
                    $targetService,
                    $targetFloor,
                    10,
                    $nextOffset,
                    ['sort' => $sort]
                );
                $processed += (int)($result['synced_count'] ?? 0);
                $nextOffset = (int)($result['next_offset'] ?? 1);
                if ($nextOffset < 1) {
                    $nextOffset = 1;
                }
                $afterCount = (int)db()->query('SELECT COUNT(*) FROM items')->fetchColumn();
                if ($afterCount > $beforeCount) {
                    break;
                }
            }
            site_setting_set_many([
                $testOffsetKey => (string)$nextOffset,
                'item_sync_test_target_index' => (string)$testTargetIndex,
                'item_sync_test_sort_index' => (string)($sortIndex + 1),
            ]);
            $inserted = max(0, $afterCount - $beforeCount);
            $updated = max(0, $processed - $inserted);

            $message = $targetLabel . ' の商品情報を10件テスト取得して保存しました。処理件数: ' . (string)$processed
                . ' / 保存済み合計: ' . (string)$afterCount
                . ' / 新規追加: ' . (string)$inserted
                . ' / 更新: ' . (string)$updated
                . ' / sort: ' . $sort
                . ' / 試行: ' . (string)$attempts
                . ' / 次回offset: ' . (string)$nextOffset;
            $messageType = 'success';
        } catch (Throwable $e) {
            $message = '保存に失敗しました: ' . $e->getMessage();
            $messageType = 'error';
        }
    }

    if ($action === 'delete_row') {
        $target = $saveTargets[$apiType] ?? null;
        if (is_array($target)) {
            $id = (int)post('row_id', 0);
            if ($id > 0) {
                $deleteStmt = db()->prepare('DELETE FROM items WHERE id = :id');
                $deleteStmt->execute([':id' => $id]);
                $message = '商品を削除しました（商品ページで使用する画像情報を含む）。';
                $messageType = 'success';
            }
        }
    }
}

$target = $saveTargets[$apiType] ?? null;
if (is_array($target)) {
    $currentPage = max(1, (int)($_GET['page'] ?? 1));
    $countStmt = db()->query('SELECT COUNT(*) AS c FROM ' . $target['table']);
    $totalRows = (int)(($countStmt ? $countStmt->fetch(PDO::FETCH_ASSOC) : ['c' => 0])['c'] ?? 0);
    $totalPages = max(1, (int)ceil($totalRows / $perPage));
    if ($currentPage > $totalPages) {
        $currentPage = $totalPages;
    }
    $offset = ($currentPage - 1) * $perPage;

    $stmt = db()->prepare(
        'SELECT ' . $target['id_column'] . ' AS row_id, content_id, ' . $target['name_column'] . ' AS row_name, updated_at
         FROM ' . $target['table'] . '
         ORDER BY ' . $target['id_column'] . ' DESC
         LIMIT :limit OFFSET :offset'
    );
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $savedRows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

require __DIR__ . '/includes/header.php';
?>
<section class="card">
  <h1><?= e($pageTitle) ?></h1>
  <p>このページで保存した APIID / アフィリエイトID は、商品・ジャンル・女優・シリーズの同期で共通利用されます。</p>
  <p><a href="<?= e(admin_url('sync_floors.php')) ?>">先にFloor同期で月額サービスの取得先を確認</a>してください。現在の設定値が無効な場合、ItemList APIは floor の400エラーになります。</p>

  <?php if ($message !== ''): ?>
    <div class="admin-notice <?= $messageType === 'success' ? 'admin-notice--success' : 'admin-notice--error' ?>">
      <p><?= e($message) ?></p>
    </div>
  <?php endif; ?>

  <form method="post" class="stack" style="max-width:700px;">
    <?= csrf_input() ?>
    <div>
      <label>APIID<br><input type="text" name="api_id" value="<?= e($apiId) ?>" style="width:100%"></label>
    </div>
    <div>
      <label>アフィリエイトID<br><input type="text" name="affiliate_id" value="<?= e($affiliateId) ?>" style="width:100%"></label>
    </div>
    <div>
      <label>10件テスト取得の対象<br>
        <select name="test_target_index" style="width:100%;max-width:520px;">
          <?php foreach ($catalogTargets as $index => $catalogTarget): ?>
            <?php
            $catalogLabel = trim((string)($catalogTarget['label'] ?? ''));
            $catalogService = (string)($catalogTarget['service'] ?? '');
            $catalogFloor = (string)($catalogTarget['floor'] ?? '');
            $catalogOptionLabel = $catalogLabel !== '' ? $catalogLabel : $catalogFloor;
            ?>
            <option value="<?= e((string)$index) ?>" <?= $index === $testTargetIndex ? 'selected' : '' ?>>
              <?= e($catalogOptionLabel . ' (' . $catalogService . ' / ' . $catalogFloor . ')') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
      <button type="submit" name="action" value="save">保存</button>
      <button type="submit" name="action" value="test_save" class="button-secondary"><?= e($testButtonLabel) ?></button>
    </div>
  </form>

  <?php if (is_array($target)): ?>
    <h2>保存済み<?= e($target['label']) ?>（全<?= e((string)$totalRows) ?>件 / 最新50件）</h2>
    <table class="admin-table">
      <tr><th>No.</th><th>名称</th><th>更新日時</th><th>操作</th></tr>
      <?php foreach ($savedRows as $index => $row): ?>
        <tr>
          <td><?= e((string)max(1, $totalRows - $offset - (int)$index)) ?></td>
          <td><a href="<?= e(public_url('item.php?cid=' . rawurlencode((string)($row['content_id'] ?? '')))) ?>" target="_blank" rel="noopener noreferrer"><?= e((string)($row['row_name'] ?? '')) ?></a></td>
          <td><?= e((string)($row['updated_at'] ?? '')) ?></td>
          <td>
            <form method="post">
              <?= csrf_input() ?>
              <input type="hidden" name="action" value="delete_row">
              <input type="hidden" name="row_id" value="<?= e((string)($row['row_id'] ?? '0')) ?>">
              <input type="hidden" name="test_target_index" value="<?= e((string)$testTargetIndex) ?>">
              <button type="submit" class="button-secondary">削除</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
