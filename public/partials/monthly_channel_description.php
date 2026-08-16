<?php

declare(strict_types=1);
$monthlyChannelKey = strtolower(trim((string)($_GET['channel'] ?? 'standard')));
$monthlyChannelDescription = match ($monthlyChannelKey) {
    'vr' => 'VR作品を月額見放題サービスから探します。',
    'deluxe' => '見放題ch デラックスの対象作品から探します。',
    default => '見放題chの対象作品から探します。',
};
?>
<p><?= e($monthlyChannelDescription) ?></p>
