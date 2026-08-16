<?php

declare(strict_types=1);
$currentMonthlyChannel = strtolower(trim((string)($_GET['channel'] ?? '')));
?>
<div class="monthly-channel-grid">
  <a class="monthly-channel-card" href="<?= e(public_url('monthly.php?channel=standard')) ?>"><strong>見放題ch</strong><span>定番の見放題</span></a>
  <a class="monthly-channel-card" href="<?= e(public_url('monthly.php?channel=deluxe')) ?>"><strong>見放題ch デラックス</strong><span>デラックス対象</span></a>
  <a class="monthly-channel-card" href="<?= e(public_url('monthly.php?channel=vr')) ?>"><strong>VRch</strong><span>VR見放題</span></a>
</div>
