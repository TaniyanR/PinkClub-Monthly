<?php
declare(strict_types=1);

$monthlyPlans = [
    [
        'key' => 'standard',
        'name' => '見放題ch',
        'price' => '3,980円 / 月',
        'works' => '205,000本以上',
        'exclusive' => '43,500本以上',
        'catch' => '迷ったら、まずはこれ',
        'summary' => '多彩なジャンルを幅広く楽しみたい人向け。毎月たくさんの作品を見たい場合の基本プランです。',
        'points' => ['幅広いジャンル', 'テレビ・ゲーム機でも楽しみやすい', '初めて月額動画を選ぶ人向け'],
        'image' => 'images/monthly-standard.svg',
        'class' => 'standard',
    ],
    [
        'key' => 'deluxe',
        'name' => '見放題ch デラックス',
        'price' => '8,980円 / 月',
        'works' => '430,000本以上',
        'exclusive' => '99,500本以上',
        'catch' => '作品数・画質を重視',
        'summary' => '作品数と画質を優先して選びたい人向け。4K作品を含む、より豪華なラインナップを楽しめます。',
        'points' => ['3プラン中もっとも作品数が多い', '4K作品あり', 'FANZA独占作品も多い'],
        'image' => 'images/monthly-deluxe.svg',
        'class' => 'deluxe',
    ],
    [
        'key' => 'vr',
        'name' => 'VRch',
        'price' => '2,800円 / 月',
        'works' => '15,500本以上',
        'exclusive' => '8,650本以上',
        'catch' => 'VR作品なら、このチャンネル',
        'summary' => '通常動画ではなく、VR作品を集中して楽しみたい人向け。没入感を重視するなら最も分かりやすい選択です。',
        'points' => ['VR作品専用', 'マルチデバイス対応', '通常動画のサンプル動画表示はなし'],
        'image' => 'images/monthly-vr.svg',
        'class' => 'vr',
    ],
];
?>
<section class="monthly-home-intro" aria-labelledby="monthly-home-title">
  <p class="monthly-hero__eyebrow">PinkClub Monthly</p>
  <h1 id="monthly-home-title">月額動画には3つの見放題サービスがあります</h1>
  <p>「見放題ch」「見放題ch デラックス」「VRch」の3種類です。まず違いを比較して、そのあとに<strong>自分はどれに入るか</strong>を作品から選びましょう。</p>
</section>

<section class="monthly-plan-comparison" aria-labelledby="monthly-plan-heading">
  <div class="monthly-plan-comparison__heading">
    <h2 id="monthly-plan-heading">では、どれに入りますか？</h2>
    <p>価格だけでなく、作品数・独占作品・画質・VR対応まで比べて選ぶのがおすすめです。</p>
  </div>

  <div class="monthly-plan-grid">
    <?php foreach ($monthlyPlans as $plan): ?>
      <article class="monthly-plan-card monthly-plan-card--<?= e((string)$plan['class']) ?>">
        <img class="monthly-plan-card__visual" src="<?= e(asset_url((string)$plan['image'])) ?>" alt="<?= e((string)$plan['name']) ?>の特徴イメージ" loading="lazy" decoding="async">
        <div class="monthly-plan-card__body">
          <h3><?= e((string)$plan['name']) ?></h3>
          <div class="monthly-plan-card__price"><?= e((string)$plan['price']) ?></div>
          <div class="monthly-plan-card__catch"><?= e((string)$plan['catch']) ?></div>
          <p><?= e((string)$plan['summary']) ?></p>

          <dl class="monthly-plan-card__stats">
            <div><dt>作品数</dt><dd><?= e((string)$plan['works']) ?></dd></div>
            <div><dt>FANZA独占</dt><dd><?= e((string)$plan['exclusive']) ?></dd></div>
          </dl>

          <ul class="monthly-plan-card__points">
            <?php foreach ((array)$plan['points'] as $point): ?>
              <li><?= e((string)$point) ?></li>
            <?php endforeach; ?>
          </ul>

          <a class="monthly-plan-card__cta" href="<?= e(public_url('monthly.php?channel=' . rawurlencode((string)$plan['key']))) ?>"><?= e((string)$plan['name']) ?>の作品を見る</a>
        </div>
      </article>
    <?php endforeach; ?>
  </div>

  <div class="monthly-plan-comparison__note">料金・作品数・独占作品数は2026年8月にFANZA月額動画の表示内容を確認した値です。変更される場合があるため、入会前に必ずFANZA公式ページで最新情報を確認してください。</div>
</section>
