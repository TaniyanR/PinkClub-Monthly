<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/lib/monthly_channel.php';

/** @var array $item */
$monthlyAffiliateUrl = monthly_item_affiliate_url($item);
$monthlyChannelLabel = monthly_channel_label_from_item($item);
?>
<div class="monthly-item-extra">
  <span class="monthly-badge"><?= e($monthlyChannelLabel) ?>対象</span>
  <?php if ($monthlyAffiliateUrl !== ''): ?>
    <a class="monthly-cta" href="<?= e(outbound_url($monthlyAffiliateUrl, (string)($item['content_id'] ?? ''))) ?>" rel="nofollow sponsored">
      <?= e(monthly_channel_cta_label($item)) ?>
    </a>
  <?php endif; ?>
</div>
