(() => {
  'use strict';

  const config = window.PinkClubMonthlyConfig || {};
  const base = String(config.baseUrl || '').replace(/\/$/, '');
  const publicUrl = (path = '') => `${base}/${String(path).replace(/^\//, '')}`;

  if (!document.querySelector('link[data-pcm-monthly-ui]')) {
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = String(config.cssUrl || `${base}/assets/css/monthly-ui.css`);
    link.dataset.pcmMonthlyUi = '1';
    document.head.appendChild(link);
  }

  const isHome = (() => {
    const path = location.pathname.replace(/\/+$/, '/');
    const basePath = (() => {
      try { return new URL(base || location.origin).pathname.replace(/\/+$/, '/'); } catch (_) { return '/'; }
    })();
    return path === basePath || path === `${basePath}index.php` || /\/public\/(?:index\.php)?$/.test(path);
  })();

  const injectHomeIntro = () => {
    if (!isHome || document.querySelector('.monthly-hero')) return;
    const target = document.querySelector('.site-main__body');
    if (!target) return;

    const wrapper = document.createElement('div');
    wrapper.innerHTML = `
      <section class="monthly-hero">
        <p class="monthly-hero__eyebrow">PinkClub Monthly</p>
        <h1>月額見放題から、好きな作品を探す</h1>
        <p>1本ずつ購入するサイトではありません。作品・女優・ジャンルから探して、気になる作品が見られる月額チャンネルへ進めます。</p>
      </section>
      <nav class="monthly-channel-grid" aria-label="月額見放題チャンネル">
        <a class="monthly-channel-card" href="${publicUrl('monthly.php?channel=standard')}"><strong>見放題ch</strong><span>定番の見放題作品から探す</span></a>
        <a class="monthly-channel-card" href="${publicUrl('monthly.php?channel=deluxe')}"><strong>見放題ch デラックス</strong><span>デラックス対象作品から探す</span></a>
        <a class="monthly-channel-card" href="${publicUrl('monthly.php?channel=vr')}"><strong>VRch</strong><span>VRの見放題作品から探す</span></a>
      </nav>`;
    target.prepend(wrapper);
  };

  const adaptItemDetail = () => {
    if (!/\/item\.php$/i.test(location.pathname)) return;
    document.body.classList.add('pcm-monthly-item');

    const body = document.querySelector('.site-main__body');
    if (body && !body.querySelector('.pcm-monthly-explainer')) {
      const note = document.createElement('div');
      note.className = 'monthly-note pcm-monthly-explainer';
      note.textContent = 'このサイトは月額見放題作品を探すためのサイトです。料金・キャンペーン・現在の見放題対象状況はリンク先で最終確認してください。';
      body.prepend(note);
    }

    document.querySelectorAll('a.pcf-btn').forEach((link) => {
      const text = (link.textContent || '').trim();
      if (text.includes('購入')) {
        link.textContent = 'この作品が見放題のチャンネルを見る';
      }
    });
  };

  const renameSaleLanguage = () => {
    document.querySelectorAll('input[placeholder="商品検索"]').forEach((input) => {
      input.placeholder = '見放題作品を検索';
      input.setAttribute('aria-label', '見放題作品を検索');
    });
  };

  const run = () => {
    injectHomeIntro();
    adaptItemDetail();
    renameSaleLanguage();
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run, { once: true });
  } else {
    run();
  }
})();
