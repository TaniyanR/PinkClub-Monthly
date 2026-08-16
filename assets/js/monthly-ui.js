(() => {
  'use strict';

  const config = window.PinkClubMonthlyConfig || {};
  const base = String(config.baseUrl || '').replace(/\/$/, '');
  const vrPattern = /(?:【|\[|［)?\s*VR\s*(?:】|\]|］)?|8KVR|VR専用/i;

  if (!document.querySelector('link[data-pcm-monthly-ui]')) {
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = String(config.cssUrl || `${base}/assets/css/monthly-ui.css`);
    link.dataset.pcmMonthlyUi = '1';
    document.head.appendChild(link);
  }

  const removeRankingUi = (root = document) => {
    root.querySelectorAll?.('#access-ranking').forEach((section) => section.remove());
  };

  const cardTitle = (card) => {
    const node = card.querySelector('.pcf-dm-card__title, .rail-card__title, .monthly-work-card__title, h2, h3, h4');
    return (node?.textContent || '').trim();
  };

  const removeVrMovieControls = (root = document) => {
    root.querySelectorAll?.('.pcf-dm-card, .rail-card, .monthly-work-card, article').forEach((card) => {
      if (!vrPattern.test(cardTitle(card))) return;
      card.querySelectorAll('button, a, span').forEach((control) => {
        const text = (control.textContent || '').trim();
        if (text === 'サンプル動画' || text === '元サイトで見る') {
          control.remove();
        }
      });
    });

    if (/\/item\.php$/i.test(location.pathname)) {
      const pageTitle = (document.querySelector('h1')?.textContent || document.title || '').trim();
      if (vrPattern.test(pageTitle)) {
        document.querySelectorAll('.pcf-item-sample-movie').forEach((area) => {
          const section = area.closest('section');
          if (section && /サンプル動画/.test(section.textContent || '')) {
            section.remove();
          } else {
            area.remove();
          }
        });
        document.querySelectorAll('h2, h3').forEach((heading) => {
          if ((heading.textContent || '').trim() === 'サンプル動画') {
            heading.remove();
          }
        });
      }
    }
  };

  const adaptItemDetail = () => {
    if (!/\/item\.php$/i.test(location.pathname)) return;
    document.body.classList.add('pcm-monthly-item');

    const body = document.querySelector('.site-main__body');
    if (body && !body.querySelector('.pcm-monthly-explainer')) {
      const note = document.createElement('div');
      note.className = 'monthly-note pcm-monthly-explainer';
      note.textContent = 'このサイトは月額見放題作品を探すためのサイトです。料金・キャンペーン・現在の対象状況はリンク先で最終確認してください。';
      body.prepend(note);
    }

    document.querySelectorAll('a.pcf-btn').forEach((link) => {
      const text = (link.textContent || '').trim();
      if (text.includes('購入')) {
        link.textContent = 'この作品が見放題のチャンネルを見る';
      }
    });
  };

  const cleanMonthlyUi = (root = document) => {
    removeRankingUi(root);
    removeVrMovieControls(root);
  };

  const run = () => {
    adaptItemDetail();
    cleanMonthlyUi(document);

    const observer = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        mutation.addedNodes.forEach((node) => {
          if (!(node instanceof Element)) return;
          cleanMonthlyUi(node.matches('#access-ranking, .pcf-dm-card, .rail-card, .monthly-work-card, article') ? node.parentElement || node : node);
        });
      });
    });
    observer.observe(document.body, { childList: true, subtree: true });
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run, { once: true });
  } else {
    run();
  }
})();
