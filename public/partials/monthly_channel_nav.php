<?php

declare(strict_types=1);
?>
<nav class="monthly-channel-grid" aria-label="月額見放題チャンネル">
  <a class="monthly-channel-card" href="<?= e(public_url('monthly.php?channel=standard')) ?>">
    <strong>見放題ch</strong>
    <span>定番の見放題作品から探す</span>
  </a>
  <a class="monthly-channel-card" href="<?= e(public_url('monthly.php?channel=deluxe')) ?>">
    <strong>見放題ch デラックス</strong>
    <span>デラックス対象作品から探す</span>
  </a>
  <a class="monthly-channel-card" href="<?= e(public_url('monthly.php?channel=vr')) ?>">
    <strong>VRch</strong>
    <span>VRの見放題作品から探す</span>
  </a>
</nav>
