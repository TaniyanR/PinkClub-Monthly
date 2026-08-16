<?php

declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/lib/monthly_channel.php';
/** @var array $item */
?>
<span class="monthly-badge"><?= e(monthly_channel_label_from_item($item)) ?></span>
