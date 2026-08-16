<?php

declare(strict_types=1);

require_once __DIR__ . '/../public/_bootstrap.php';
auth_require_admin();

$title = '月額API診断';
$pdo = db();
$settings = settings_get();
$catalogTargets = $settings['catalog_targets'] ?? [];
$itemId = max(0, (int)($_GET['item_id'] ?? 0));
$selectedItem = null;
$prettyJson = '';

$floors = [];
try {
    $floors = $pdo->query('SELECT service_code,floor_code,name,updated_at FROM dmm_floors ORDER BY service_code,floor_code')->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable) {
    $floors = [];
}

$floorLookup = [];
foreach ($floors as $floor) {
    $floorLookup[(string)$floor['service_code'] . ':' . (string)$floor['floor_code']] = $floor;
}

$recentItems = [];
try {
    $recentItems = $pdo->query('SELECT id,content_id,title,updated_at FROM items ORDER BY id DESC LIMIT 20')->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable) {
    $recentItems = [];
}

if ($itemId > 0) {
    try {
        $stmt = $pdo->prepare('SELECT id,content_id,title,raw_json,affiliate_url,updated_at FROM items WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $itemId]);
        $selectedItem = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if (is_array($selectedItem)) {
            $raw = trim((string)($selectedItem['raw_json'] ?? ''));
            if ($raw !== '') {
                $decoded = json_decode($raw, true);
                $prettyJson = is_array($decoded)
                    ? (string)json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
                    : $raw;
            }
        }
    } catch (Throwable) {
        $selectedItem = null;
    }
}

require __DIR__ . '/includes/header.php';
?>
<section class="admin-card">
  <h1>月額API診断</h1>
  <p>月額APIに実際に何が入っているかを確認するための画面です。推測で表示項目を増やさず、ここで確認できたデータから公開画面を調整します。</p>
  <p><a href="<?= e(admin_url('sync_floors.php')) ?>">月額Floor設定</a> ／ <a href="<?= e(admin_url('api_items.php')) ?>">商品情報API設定</a></p>
</section>

<section class="admin-card">
  <h2>現在の3チャンネル設定</h2>
  <table class="admin-table">
    <tr><th>対象</th><th>service</th><th>floor</th><th>FloorList確認</th></tr>
    <?php foreach ($catalogTargets as $target): ?>
      <?php $key = (string)($target['service'] ?? '') . ':' . (string)($target['floor'] ?? ''); ?>
      <tr>
        <td><?= e((string)($target['label'] ?? '')) ?></td>
        <td><?= e((string)($target['service'] ?? '')) ?></td>
        <td><?= e((string)($target['floor'] ?? '')) ?></td>
        <td><?= isset($floorLookup[$key]) ? 'OK' : '未確認 / 要変更' ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
</section>

<section class="admin-card">
  <h2>最近保存した商品</h2>
  <?php if ($recentItems === []): ?>
    <p>まだ商品がありません。月額Floor設定を保存してから10件テスト取得してください。</p>
  <?php else: ?>
    <table class="admin-table">
      <tr><th>ID</th><th>タイトル</th><th>更新日時</th><th>API生データ</th></tr>
      <?php foreach ($recentItems as $row): ?>
        <tr>
          <td><?= e((string)$row['id']) ?></td>
          <td><?= e((string)$row['title']) ?></td>
          <td><?= e((string)$row['updated_at']) ?></td>
          <td><a href="<?= e(admin_url('monthly_api_diagnostics.php?item_id=' . (string)$row['id'])) ?>">見る</a></td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
</section>

<?php if (is_array($selectedItem)): ?>
<section class="admin-card">
  <h2>API生データ: <?= e((string)$selectedItem['title']) ?></h2>
  <?php if ($prettyJson === ''): ?>
    <p>raw_json が保存されていません。</p>
  <?php else: ?>
    <pre style="white-space:pre-wrap;word-break:break-word;max-height:760px;overflow:auto;background:#111;color:#f6f6f6;padding:16px;border-radius:7px;"><?= e($prettyJson) ?></pre>
  <?php endif; ?>
</section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
