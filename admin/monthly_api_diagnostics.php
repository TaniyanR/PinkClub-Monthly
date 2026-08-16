<?php

declare(strict_types=1);

require_once __DIR__ . '/../public/_bootstrap.php';
auth_require_admin();

$title = '月額API診断';
$pdo = db();
$message = '';
$messageType = 'success';
$selectedItemId = max(0, (int)($_GET['item_id'] ?? 0));
$rawJson = '';
$item = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate_or_fail((string)post('_csrf', ''));
    $action = (string)post('action', '');
    if ($action === 'sync_floors') {
        try {
            $count = dmm_sync_service()->syncFloors();
            $message = 'FloorListを同期しました: ' . $count . '件';
        } catch (Throwable $e) {
            $message = 'FloorList同期に失敗しました: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

if ($selectedItemId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM items WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $selectedItemId]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    if (is_array($item)) {
        $rawJson = trim((string)($item['raw_json'] ?? ''));
    }
}

$floors = [];
try {
    $floors = $pdo->query("SELECT service_code, floor_code, name, updated_at FROM dmm_floors ORDER BY service_code, floor_code")->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    $floors = [];
}

$monthlyFloors = array_values(array_filter($floors, static function (array $row): bool {
    $haystack = mb_strtolower(implode(' ', [
        (string)($row['service_code'] ?? ''),
        (string)($row['floor_code'] ?? ''),
        (string)($row['name'] ?? ''),
    ]));
    foreach (['monthly', '月額', '見放題', 'vr'] as $needle) {
        if (mb_strpos($haystack, mb_strtolower($needle)) !== false) {
            return true;
        }
    }
    return false;
}));

$recentItems = [];
try {
    $recentItems = $pdo->query('SELECT id, content_id, title, affiliate_url, updated_at FROM items ORDER BY id DESC LIMIT 20')->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    $recentItems = [];
}

require __DIR__ . '/includes/header.php';
?>
<section class="card">
  <h1>月額API診断</h1>
  <p>月額見放題版の実データを確認するための管理画面です。FloorListの実値と、保存済み商品の生JSONをここで確認できます。</p>

  <?php if ($message !== ''): ?>
    <div class="admin-notice <?= $messageType === 'success' ? 'admin-notice--success' : 'admin-notice--error' ?>"><p><?= e($message) ?></p></div>
  <?php endif; ?>

  <div style="display:flex;gap:10px;flex-wrap:wrap;margin:16px 0;">
    <form method="post">
      <?= csrf_input() ?>
      <button type="submit" name="action" value="sync_floors">FloorListを再同期</button>
    </form>
    <a class="button-secondary" href="<?= e(admin_url('api_items.php')) ?>">商品情報API設定へ</a>
  </div>
</section>

<section class="card">
  <h2>月額候補のFloorList</h2>
  <?php if ($monthlyFloors === []): ?>
    <p>月額・見放題・VRに該当しそうなFloorがまだ見つかっていません。まず「FloorListを再同期」を実行してください。</p>
  <?php else: ?>
    <table class="admin-table">
      <tr><th>service</th><th>floor</th><th>名称</th><th>更新日時</th></tr>
      <?php foreach ($monthlyFloors as $row): ?>
        <tr>
          <td><?= e((string)($row['service_code'] ?? '')) ?></td>
          <td><strong><?= e((string)($row['floor_code'] ?? '')) ?></strong></td>
          <td><?= e((string)($row['name'] ?? '')) ?></td>
          <td><?= e((string)($row['updated_at'] ?? '')) ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
</section>

<section class="card">
  <h2>保存済み商品の生データ</h2>
  <?php if ($recentItems === []): ?>
    <p>商品がまだ保存されていません。正しいFloorが分かったら商品情報API設定からテスト取得してください。</p>
  <?php else: ?>
    <table class="admin-table">
      <tr><th>ID</th><th>タイトル</th><th>更新日時</th><th>確認</th></tr>
      <?php foreach ($recentItems as $row): ?>
        <tr>
          <td><?= e((string)$row['id']) ?></td>
          <td><?= e((string)$row['title']) ?></td>
          <td><?= e((string)$row['updated_at']) ?></td>
          <td><a href="<?= e(admin_url('monthly_api_diagnostics.php?item_id=' . (string)$row['id'])) ?>">生JSONを見る</a></td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>

  <?php if (is_array($item)): ?>
    <h3 style="margin-top:24px;">#<?= e((string)$item['id']) ?> <?= e((string)$item['title']) ?></h3>
    <?php
      $decoded = $rawJson !== '' ? json_decode($rawJson, true) : null;
      $pretty = is_array($decoded)
          ? json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
          : $rawJson;
    ?>
    <pre style="white-space:pre-wrap;word-break:break-word;background:#111;color:#f5f5f5;padding:16px;border-radius:8px;max-height:700px;overflow:auto;"><?= e((string)$pretty) ?></pre>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
