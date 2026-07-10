/* ML Popup Pro – Frontend Core v1.5.3 */
/* Vanilla JS – no external deps – blocker-friendly – first-party only */
(function () {
  'use strict';
  if (typeof mlppData === 'undefined') { return; }

  var popups   = mlppData.popups   || [];
  var settings = mlppData.settings || {};
  var ajaxUrl  = mlppData.ajaxUrl  || '';
  var nonce    = mlppData.nonce    || '';

  var globalMethod  = settings.storage_method  || 'cookie';
  var globalExpDays = parseInt(settings.expiration_days, 10) || 30;

  /* ── Storage helpers ─────────────────────────── */
  function resolveMethod(popup) {
    var cfg = popup.storage_cfg || {};
    return cfg.storage_method || globalMethod;
  }
  function resolveExpDays(popup) {
    var cfg = popup.storage_cfg || {};
    return parseInt(cfg.expiration_days, 10) || globalExpDays;
  }

  function storageGet(key, method) {
    try {
      if (method === 'localStorage')   { return localStorage.getItem(key); }
      if (method === 'sessionStorage') { return sessionStorage.getItem(key); }
      if (method === 'cookie')         { return getCookie(key); }
    } catch (e) {}
    return null;
  }

  function storageSet(key, value, method, days) {
    try {
      if (method === 'localStorage')   { localStorage.setItem(key, value); return; }
      if (method === 'sessionStorage') { sessionStorage.setItem(key, value); return; }
      if (method === 'cookie')         { setCookie(key, value, days); return; }
    } catch (e) {}
  }

  function getCookie(name) {
    var match = document.cookie.match(
      new RegExp('(?:^|; )' + name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '=([^;]*)')
    );
    return match ? decodeURIComponent(match[1]) : null;
  }

  function setCookie(name, value, days) {
    var expires = '';
    if (days) {
      var d = new Date();
      d.setTime(d.getTime() + days * 864e5);
      expires = '; expires=' + d.toUTCString();
    }
    document.cookie = name + '=' + encodeURIComponent(value) + expires + '; path=/; SameSite=Lax';
  }

  /* ── Storage key helpers ─────────────────────── */
  function key(name, id) { return 'mlpp_' + name + '_' + id; }

  function getVal(popup, name) {
    return storageGet(key(name, popup.id), resolveMethod(popup));
  }
  function setVal(popup, name, value, days) {
    var m = resolveMethod(popup);
    if (m === 'none') return;
    storageSet(key(name, popup.id), value, m, days || resolveExpDays(popup));
  }

  /* ── Frequency / block check ─────────────────── */
  function shouldShowPopup(popup) {
    var cfg    = popup.storage_cfg || {};
    var trig   = popup.triggers    || {};
    var method = resolveMethod(popup);
    var id     = popup.id;

    if (method === 'none') return true;

    // block_on_closed
    if (cfg.block_on_closed !== '0' && cfg.block_on_closed !== false) {
      if (getVal(popup, 'closed')) return false;
    }
    // block_on_seen
    if (cfg.block_on_seen !== '0' && cfg.block_on_seen !== false) {
      if (getVal(popup, 'seen')) return false;
    }
    // block_on_primary_click
    if (cfg.block_on_primary_click === '1' || cfg.block_on_primary_click === true) {
      if (getVal(popup, 'primary_clicked')) return false;
    }
    // block_on_secondary_click
    if (cfg.block_on_secondary_click === '1' || cfg.block_on_secondary_click === true) {
      if (getVal(popup, 'secondary_clicked')) return false;
    }
    // block_on_converted
    if (cfg.block_on_converted === '1' || cfg.block_on_converted === true) {
      if (getVal(popup, 'converted')) return false;
    }
    // max_impressions
    var maxImp = parseInt(cfg.max_impressions, 10) || 0;
    if (maxImp > 0) {
      var views = parseInt(getVal(popup, 'views') || '0', 10);
      if (views >= maxImp) return false;
    }

    // trigger frequency
    var freq = trig.frequency || 'once_session';
    if (freq === 'always') return true;
    if (freq === 'once_session')  { return !storageGet(key('seen', id), 'sessionStorage'); }
    if (freq === 'once_visitor')  { return !getVal(popup, 'seen'); }
    if (freq === 'until_closed')  { return !getVal(popup, 'closed'); }
    if (freq === 'every_x_days') {
      var last = parseInt(getVal(popup, 'seen') || '0', 10);
      var freqDays = parseInt(trig.frequency_days, 10) || 7;
      return !last || (Date.now() - last) > freqDays * 864e5;
    }
    return true;
  }

  function markShown(popup) {
    var cfg    = popup.storage_cfg || {};
    var trig   = popup.triggers    || {};
    var method = resolveMethod(popup);
    var expDays = resolveExpDays(popup);
    var freq   = trig.frequency || 'once_session';
    var id     = popup.id;

    // always mark sessionStorage for once_session guard
    try { sessionStorage.setItem(key('seen', id), '1'); } catch(e){}

    if (method === 'none') return;

    if (freq === 'once_visitor') {
      setVal(popup, 'seen', '1', expDays);
    } else if (freq === 'every_x_days') {
      setVal(popup, 'seen', String(Date.now()), parseInt(trig.frequency_days, 10) || 7);
    } else if (cfg.block_on_seen !== '0' && cfg.block_on_seen !== false) {
      setVal(popup, 'seen', '1', parseInt(cfg.seen_expire_days, 10) || expDays);
    }

    // increment view counter
    var views = parseInt(getVal(popup, 'views') || '0', 10) + 1;
    setVal(popup, 'views', String(views), expDays);
  }

  function markClosed(popup) {
    var cfg = popup.storage_cfg || {};
    if (resolveMethod(popup) === 'none') return;
    if (cfg.block_on_closed !== '0' && cfg.block_on_closed !== false) {
      setVal(popup, 'closed', '1', parseInt(cfg.closed_expire_days, 10) || resolveExpDays(popup));
    }
  }

  function markClicked(popup, which) {
    var cfg = popup.storage_cfg || {};
    if (resolveMethod(popup) === 'none') return;
    var exp = parseInt(cfg.click_expire_days, 10) || resolveExpDays(popup);
    if (which === 'primary' && (cfg.block_on_primary_click === '1' || cfg.block_on_primary_click === true)) {
      setVal(popup, 'primary_clicked', '1', exp);
    }
    if (which === 'secondary' && (cfg.block_on_secondary_click === '1' || cfg.block_on_secondary_click === true)) {
      setVal(popup, 'secondary_clicked', '1', exp);
    }
  }

  /* ── Pageview requirement ────────────────────── */
  // Dedicated per-popup page-load counter ('pv'), independent from the
  // impression counter ('views') used by max_impressions. It is incremented
  // on every page load so the "pageviews" trigger can actually accumulate.
  function bumpPageviews(popup) {
    var trig = popup.triggers || {};
    if ((trig.trigger_type || 'immediate') !== 'pageviews') return;
    if (resolveMethod(popup) === 'none') return; // cannot persist a count
    var n = parseInt(getVal(popup, 'pv') || '0', 10) + 1;
    setVal(popup, 'pv', String(n), resolveExpDays(popup));
  }

  function meetsPageviewReq(popup) {
    var trig = popup.triggers || {};
    if ((trig.trigger_type || 'immediate') !== 'pageviews') return true;
    if (resolveMethod(popup) === 'none') return true; // no persistence → show
    var required = parseInt(trig.pageviews, 10) || 1;
    var views = parseInt(getVal(popup, 'pv') || '0', 10);
    return views >= required;
  }

  /* ── Device detection ────────────────────────── */
  function getDevice() {
    if (/tablet|ipad/i.test(navigator.userAgent)) return 'tablet';
    if (/mobi|android/i.test(navigator.userAgent)) return 'mobile';
    return 'desktop';
  }

  /* ── Analytics ───────────────────────────────── */
  function track(popupId, eventType, variantLabel) {
    if (!ajaxUrl) return;
    var fd = new FormData();
    fd.append('action', 'mlpp_event');
    fd.append('nonce', nonce);
    fd.append('popup_id', popupId);
    fd.append('event_type', eventType);
    fd.append('variant_label', variantLabel || '');
    fd.append('page_url', window.location.href);
    fd.append('device_type', getDevice());
    fetch(ajaxUrl, { method: 'POST', body: fd }).catch(function(){});
  }

  /* ── Webhook (conversion) ───────────────────── */
  function fireWebhook(payload) {
    var url = settings.webhook_url;
    if (!url || settings.webhook_enabled !== '1') return;
    try {
      fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
        mode: 'no-cors',
        keepalive: true
      }).catch(function(){});
    } catch (e) {}
  }

  /* ── Build popup element ─────────────────────── */
  var focusBefore = null;

  function closePopup(popup, el) {
    el.remove();
    markClosed(popup);
    track(popup.id, 'close');
    if (focusBefore && focusBefore.focus) focusBefore.focus();
  }

  function applyStyle(el, css) {
    Object.keys(css).forEach(function(k) { el.style[k] = css[k]; });
  }

  function getPopupBackground(design, fallback) {
    var color = design.bg_color || fallback;
    var percent = design.bg_opacity === undefined || design.bg_opacity === '' ? 100 : parseInt(design.bg_opacity, 10);
    if (isNaN(percent)) percent = 100;
    percent = Math.max(0, Math.min(100, percent));
    if (percent >= 100) return color;

    var hex = String(color || '').replace('#', '').trim();
    if (hex.length === 3) hex = hex.split('').map(function(c) { return c + c; }).join('');
    if (!/^[0-9a-fA-F]{6}$/.test(hex)) return color;

    return 'rgba(' + parseInt(hex.slice(0, 2), 16) + ',' + parseInt(hex.slice(2, 4), 16) + ',' + parseInt(hex.slice(4, 6), 16) + ',' + (percent / 100) + ')';
  }

  function getSurfaceOverflow(popup) {
    return popup.image_url && popup.image_position === 'only' ? 'hidden' : 'auto';
  }

  function createCloseBtn(popup, el) {
    var d = popup.design || {};
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'mlpp-close';
    btn.setAttribute('aria-label', 'Fechar');
    btn.textContent = d.close_style === 'text' ? 'Fechar' : '×';
    btn.addEventListener('click', function() { closePopup(popup, el); });
    return btn;
  }

  function openImageLink(popup) {
    if (!popup.image_link_url) return;
    if (popup.image_link_target === '_blank') {
      window.open(popup.image_link_url, '_blank', 'noopener,noreferrer');
      return;
    }
    window.location.href = popup.image_link_url;
  }

  function trackImageLink(popup) {
    track(popup.id, 'image_click');
  }

  function createPopupImage(popup, placement) {
    var img = document.createElement('img');
    var fit = popup.image_fit === 'contain' ? 'contain' : popup.image_fit === 'original' ? 'none' : 'cover';
    img.src = popup.image_url;
    img.alt = popup.image_alt || '';
    img.className = placement === 'side' ? 'mlpp-img mlpp-side-img' : 'mlpp-img';

    var styles = {
      borderRadius: popup.image_radius || '8px',
      objectFit: fit,
      display: 'block'
    };

    if (placement === 'only') {
      styles.width = popup.image_fit === 'original' ? 'auto' : '100%';
      styles.height = popup.image_fit === 'original' ? 'auto' : '100%';
      styles.maxWidth = '100%';
      styles.maxHeight = '100%';
      styles.margin = '0 auto';
    } else if (placement === 'side') {
      styles.width = '100%';
      styles.height = 'auto';
      styles.maxWidth = '100%';
      styles.maxHeight = '100%';
    } else {
      styles.width = popup.image_fit === 'original' ? 'auto' : '100%';
      styles.height = 'auto';
      styles.maxWidth = '100%';
      styles.maxHeight = 'none';
      styles.margin = '0 auto 16px';
    }

    applyStyle(img, styles);

    if (!popup.image_link_url) return img;

    var link = document.createElement('a');
    link.href = popup.image_link_url;
    link.target = popup.image_link_target === '_blank' ? '_blank' : '_self';
    link.className = 'mlpp-image-link mlpp-image-link-' + placement;
    link.setAttribute('aria-label', popup.image_alt || popup.title || 'Abrir link da imagem');
    if (link.target === '_blank') link.rel = 'noopener noreferrer';

    if (placement === 'side') {
      img.classList.remove('mlpp-side-img');
      link.classList.add('mlpp-side-img');
      applyStyle(img, { width:'100%', height:'auto', maxWidth:'100%', maxHeight:'100%' });
    } else if (placement === 'only') {
      applyStyle(link, { display:'flex', alignItems:'center', justifyContent:'center', width:'100%', height:'100%', maxWidth:'100%', maxHeight:'100%', minWidth:'0', minHeight:'0', overflow:'hidden' });
    } else {
      applyStyle(link, { display:'block', maxWidth:'100%' });
    }

    link.addEventListener('click', function() { trackImageLink(popup); });
    link.appendChild(img);
    return link;
  }

  function bindBackgroundImageLink(popup, target) {
    if (!popup.image_link_url || !target) return;
    target.classList.add('mlpp-background-clickable');
    target.addEventListener('click', function(e) {
      if (e.defaultPrevented || e.button !== 0) return;
      if (e.target.closest('a,button,input,textarea,select,label,[role="button"]')) return;
      trackImageLink(popup);
      openImageLink(popup);
    });
  }

  function getScreenPositionStyles(position) {
    var pos = position || 'bottom_right';
    var css = { top:'auto', bottom:'24px', left:'auto', right:'24px' };
    if (pos === 'bottom_left') {
      css.left = '24px';
      css.right = 'auto';
    } else if (pos === 'top_right') {
      css.top = '24px';
      css.bottom = 'auto';
    } else if (pos === 'top_left') {
      css.top = '24px';
      css.bottom = 'auto';
      css.left = '24px';
      css.right = 'auto';
    }
    return css;
  }

  function createContent(popup, el) {
    var d    = popup.design || {};
    var frag = document.createDocumentFragment();
    var contentTarget = frag;

    if (popup.image_url && popup.image_position === 'only') {
      frag.appendChild(createPopupImage(popup, 'only'));
      return frag;
    }

    if (popup.image_url && popup.image_position === 'top') {
      frag.appendChild(createPopupImage(popup, 'top'));
    }

    if (popup.image_url && (popup.image_position === 'left' || popup.image_position === 'right')) {
      var layout = document.createElement('div');
      var copy = document.createElement('div');
      var sideImage = createPopupImage(popup, 'side');
      layout.className = 'mlpp-content-side mlpp-image-' + popup.image_position;
      copy.className = 'mlpp-content-copy';

      if (popup.image_position === 'left') {
        layout.appendChild(sideImage);
        layout.appendChild(copy);
      } else {
        layout.appendChild(copy);
        layout.appendChild(sideImage);
      }

      frag.appendChild(layout);
      contentTarget = copy;
    }

    if (popup.title) {
      var h = document.createElement('p');
      h.className = 'mlpp-title';
      h.style.textAlign = d.text_align || 'left';
      h.textContent = popup.title;
      contentTarget.appendChild(h);
    }
    if (popup.subtitle) {
      var s = document.createElement('p');
      s.className = 'mlpp-subtitle';
      s.style.textAlign = d.text_align || 'left';
      s.textContent = popup.subtitle;
      contentTarget.appendChild(s);
    }
    if (popup.body) {
      var b = document.createElement('div');
      b.className = 'mlpp-body';
      b.style.textAlign = d.text_align || 'left';
      b.innerHTML = popup.body;
      contentTarget.appendChild(b);
    }
    if (popup.custom_html) {
      var ch = document.createElement('div');
      ch.innerHTML = popup.custom_html;
      contentTarget.appendChild(ch);
    }

    if (popup.btn_primary_text || popup.btn_secondary_text) {
      var actWrap = document.createElement('div');
      actWrap.className = 'mlpp-act';
      actWrap.style.textAlign = d.text_align || 'left';

      if (popup.btn_primary_text) {
        var bp = document.createElement('a');
        bp.href = popup.btn_primary_url || '#';
        bp.className = 'mlpp-btn-p';
        applyStyle(bp, { background: d.btn_color || '#155e6f', color: d.btn_text_color || '#fff' });
        bp.textContent = popup.btn_primary_text;
        bp.addEventListener('click', function() {
          markClicked(popup, 'primary');
          track(popup.id, 'primary_click');
        });
        actWrap.appendChild(bp);
      }
      if (popup.btn_secondary_text) {
        var bs = document.createElement('a');
        bs.href = popup.btn_secondary_url || '#';
        bs.className = 'mlpp-btn-s';
        applyStyle(bs, { background: d.btn2_color || '#64748b', color: d.btn2_text_color || '#fff' });
        bs.textContent = popup.btn_secondary_text;
        bs.addEventListener('click', function() {
          markClicked(popup, 'secondary');
          track(popup.id, 'secondary_click');
        });
        actWrap.appendChild(bs);
      }
      contentTarget.appendChild(actWrap);
    }
    return frag;
  }

  function getAnimClass(popup) {
    var d = popup.design || {};
    if (d.animation === '0' || d.animation_type === 'none') return '';
    var map = { fade:'mlpp-anim-fade', slide_down:'mlpp-anim-slide-down', slide_up:'mlpp-anim-slide-up', zoom:'mlpp-anim-zoom' };
    return map[d.animation_type || 'fade'] || 'mlpp-anim-fade';
  }

  function buildModal(popup) {
    var d  = popup.design || {};
    var ov = document.createElement('div');
    ov.className = 'mlpp-ov ' + getAnimClass(popup);
    ov.setAttribute('role','dialog');
    ov.setAttribute('aria-modal','true');
    ov.setAttribute('aria-label', popup.title || 'Popup');
    ov.style.background = d.overlay_color || 'rgba(0,0,0,.55)';

    var box = document.createElement('div');
    box.className = 'mlpp-box mlpp-modal';
    if (popup.image_url && popup.image_position === 'only') box.classList.add('mlpp-image-only-popup');
    applyStyle(box, {
      background: getPopupBackground(d, '#fff'),
      color: d.text_color || '#102a43',
      borderRadius: d.border_radius || '16px',
      padding: d.padding || '36px 32px 28px',
      boxShadow: d.shadow || '0 24px 64px rgba(15,23,42,.22)',
      width: d.width || '600px',
      maxWidth: d.max_width || '95vw',
      height: d.height || 'auto',
      maxHeight: d.max_height || '90vh',
      overflowY: getSurfaceOverflow(popup),
      boxSizing: 'border-box'
    });
    box.appendChild(createCloseBtn(popup, ov));
    box.appendChild(createContent(popup, ov));
    ov.appendChild(box);
    ov.addEventListener('click', function(e) { if (e.target === ov) closePopup(popup, ov); });
    return ov;
  }

  function buildBottomBar(popup) {
    var d  = popup.design || {};
    var el = document.createElement('div');
    el.className = 'mlpp-bottom ' + getAnimClass(popup);
    if (popup.image_url && popup.image_position === 'only') el.classList.add('mlpp-image-only-popup');
    el.setAttribute('role','dialog');
    el.setAttribute('aria-label', popup.title || 'Popup');
    applyStyle(el, { background: getPopupBackground(d, '#1e293b'), color: d.text_color || '#fff', boxShadow: d.shadow || '', height: d.height || 'auto', maxHeight: d.max_height || '90vh', overflowY: getSurfaceOverflow(popup), boxSizing: 'border-box' });
    el.appendChild(createContent(popup, el));
    el.appendChild(createCloseBtn(popup, el));
    return el;
  }

  function buildSlideIn(popup) {
    var d  = popup.design || {};
    var el = document.createElement('div');
    el.className = 'mlpp-slide ' + getAnimClass(popup);
    if (popup.image_url && popup.image_position === 'only') el.classList.add('mlpp-image-only-popup');
    el.setAttribute('role','dialog');
    el.setAttribute('aria-label', popup.title || 'Popup');
    applyStyle(el, { background: getPopupBackground(d, '#fff'), color: d.text_color || '#102a43', borderRadius: d.border_radius || '16px', boxShadow: d.shadow || '0 16px 48px rgba(15,23,42,.2)', padding: d.padding || '24px', width: d.width || '360px', maxWidth: d.max_width || 'calc(100vw - 48px)', height: d.height || 'auto', maxHeight: d.max_height || '90vh', overflowY: getSurfaceOverflow(popup), boxSizing: 'border-box' });
    applyStyle(el, getScreenPositionStyles(d.screen_position));
    el.appendChild(createCloseBtn(popup, el));
    el.appendChild(createContent(popup, el));
    return el;
  }

  function buildFullscreen(popup) {
    var d  = popup.design || {};
    var el = document.createElement('div');
    el.className = 'mlpp-full ' + getAnimClass(popup);
    el.setAttribute('role','dialog');
    el.setAttribute('aria-modal','true');
    el.setAttribute('aria-label', popup.title || 'Popup');
    applyStyle(el, { background: getPopupBackground(d, '#0f172a'), color: d.text_color || '#f8fafc' });
    var inner = document.createElement('div');
    inner.className = 'mlpp-full-inner';
    if (popup.image_url && popup.image_position === 'only') inner.classList.add('mlpp-image-only-popup');
    applyStyle(inner, { height: d.height || 'auto', maxHeight: d.max_height || '90vh', overflowY: getSurfaceOverflow(popup), boxSizing: 'border-box' });
    inner.appendChild(createCloseBtn(popup, el));
    inner.appendChild(createContent(popup, el));
    el.appendChild(inner);
    return el;
  }

  function buildFloating(popup) {
    var d  = popup.design || {};
    var el = document.createElement('div');
    el.className = 'mlpp-float ' + getAnimClass(popup);
    if (popup.image_url && popup.image_position === 'only') el.classList.add('mlpp-image-only-popup');
    el.setAttribute('role','dialog');
    el.setAttribute('aria-label', popup.title || 'Popup');
    applyStyle(el, { background: getPopupBackground(d, '#fff'), color: d.text_color || '#102a43', borderRadius: d.border_radius || '16px', boxShadow: d.shadow || '0 16px 40px rgba(15,23,42,.2)', padding: d.padding || '22px', width: d.width || '320px', maxWidth: d.max_width || 'calc(100vw - 48px)', height: d.height || 'auto', maxHeight: d.max_height || '90vh', overflowY: getSurfaceOverflow(popup), boxSizing: 'border-box' });
    applyStyle(el, getScreenPositionStyles(d.screen_position));
    el.appendChild(createCloseBtn(popup, el));
    el.appendChild(createContent(popup, el));
    return el;
  }

  function attachKeyboard(popup, el) {
    var esc = function(e) { if (e.key === 'Escape') { closePopup(popup, el); document.removeEventListener('keydown', esc); } };
    document.addEventListener('keydown', esc);

    var focusEls = el.querySelectorAll('a,button,input,textarea,select,[tabindex]:not([tabindex="-1"])');
    if (focusEls.length) {
      focusEls[0].focus();
      el.addEventListener('keydown', function(e) {
        if (e.key !== 'Tab') return;
        var first = focusEls[0], last = focusEls[focusEls.length-1];
        if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
        else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
      });
    }
  }

  function showPopup(popup) {
    if (!shouldShowPopup(popup)) return;

    var container = document.getElementById('mlpp-container');
    if (!container) return;

    focusBefore = document.activeElement;

    var d  = popup.design || {};
    var el;
    switch (popup.popup_type) {
      case 'bottom_bar':         el = buildBottomBar(popup); break;
      case 'slide_in':           el = buildSlideIn(popup);   break;
      case 'fullscreen_overlay': el = buildFullscreen(popup); break;
      case 'floating_box':       el = buildFloating(popup);  break;
      default:                   el = buildModal(popup);
    }

    // Mobile layout override
    if (d.mobile_layout === 'hidden' && getDevice() === 'mobile') return;
    if (d.mobile_layout === 'fullscreen' && getDevice() === 'mobile') {
      var mobileTarget = el.querySelector('.mlpp-box') || el.querySelector('.mlpp-full-inner') || el;
      applyStyle(mobileTarget, { borderRadius:'0', maxWidth:'100%', width:'100%', height:'100%', maxHeight:'100%', boxSizing:'border-box' });
      if (el.classList.contains('mlpp-ov')) applyStyle(el, { padding:'0' });
      if (!el.classList.contains('mlpp-ov')) applyStyle(el, { inset:'0' });
    }

    // Background image
    if (popup.image_url && popup.image_position === 'background') {
      var target = el.querySelector('.mlpp-box') || el.querySelector('.mlpp-full-inner') || el;
      applyStyle(target, {
        backgroundImage: 'url(' + popup.image_url + ')',
        backgroundSize: popup.image_fit === 'contain' ? 'contain' : 'cover',
        backgroundPosition: 'center',
        backgroundRepeat: 'no-repeat'
      });
      bindBackgroundImageLink(popup, target);
    }

    container.appendChild(el);
    attachKeyboard(popup, el);
    attachGoalTracking(popup, el);
    markShown(popup);
    track(popup.id, 'impression', popup.variant_label || '');
  }

  /* Goal tracking: fire conversion once when the visitor clicks any
     element inside the popup that matches a configured CSS selector.
     Marked per-popup via a guard so a single click does not generate
     multiple conversion events. When a webhook is configured we also
     POST the conversion payload to it. */
  var goalFired = Object.create(null);
  function attachGoalTracking(popup, el) {
    var selectors = Array.isArray(popup.goal_selectors) ? popup.goal_selectors : [];
    if (!selectors.length) return;
    var pid = popup.id;
    el.addEventListener('click', function(e) {
      var target = e.target;
      if (!target || !target.closest) return;
      for (var i = 0; i < selectors.length; i++) {
        var sel = selectors[i];
        if (!sel) continue;
        try {
          if (target.closest(sel)) {
            if (goalFired[pid]) return;
            goalFired[pid] = 1;
            track(pid, 'conversion', popup.variant_label || '');
            fireWebhook({
              event: 'conversion',
              popup_id: pid,
              variant_label: popup.variant_label || '',
              page_url: window.location.href,
              device: getDevice(),
              ts: Date.now()
            });
            return;
          }
        } catch (err) { /* invalid selector — ignore */ }
      }
    });
  }

  /* ── Trigger logic ───────────────────────────── */
  function initTrigger(popup) {
    bumpPageviews(popup);
    if (!meetsPageviewReq(popup)) return;
    var trig = popup.triggers || {};
    var type = trig.trigger_type || 'immediate';

    if (type === 'immediate') { showPopup(popup); }
    else if (type === 'delay') {
      setTimeout(function() { showPopup(popup); }, (parseInt(trig.delay_seconds,10)||0)*1000);
    }
    else if (type === 'scroll') {
      var pct  = parseInt(trig.scroll_percent,10) || 50;
      var done = false;
      window.addEventListener('scroll', function onS() {
        if (done) return;
        var scrolled = (window.scrollY + window.innerHeight) / document.documentElement.scrollHeight * 100;
        if (scrolled >= pct) { done=true; window.removeEventListener('scroll',onS); showPopup(popup); }
      }, {passive:true});
    }
    else if (type === 'exit_intent') {
      var firedExit = false;
      function tryShow() {
        if (firedExit) return;
        firedExit = true;
        window.removeEventListener('mouseleave', onMouseLeave);
        window.removeEventListener('scroll', onFastScrollUp);
        document.removeEventListener('visibilitychange', onHidden);
        showPopup(popup);
      }
      // Desktop: cursor leaves the viewport through the top edge.
      function onMouseLeave(e) {
        if (e.clientY > 20) return;
        tryShow();
      }
      // Mobile + desktop fine-tune: rapid upward scroll is the canonical
      // "user is about to leave / scroll back to chrome" signal. We
      // sample scrollY twice within 100ms and require delta < -150 px/s
      // before firing. Touch-equivalent: `touchmove` with negative dy.
      var lastScrollY = window.scrollY;
      var lastSampleAt = Date.now();
      function onFastScrollUp() {
        var now = Date.now();
        var y = window.scrollY;
        var dt = Math.max(1, now - lastSampleAt);
        var velocity = (lastScrollY - y) / dt; // px/ms, positive = up
        lastScrollY = y; lastSampleAt = now;
        if (velocity >= 1.2) tryShow(); // 1.2 px/ms ≈ 1200 px/s
      }
      // Final fallback: page becomes hidden (tab switch on mobile).
      function onHidden() {
        if (document.visibilityState === 'hidden') tryShow();
      }
      window.addEventListener('mouseleave', onMouseLeave);
      window.addEventListener('scroll', onFastScrollUp, { passive: true });
      document.addEventListener('visibilitychange', onHidden);
    }
    else if (type === 'selector' && trig.selector) {
      document.querySelectorAll(trig.selector).forEach(function(el) {
        el.addEventListener('click', function() { showPopup(popup); });
      });
    }
    // shortcode: handled by data-mlpp-id buttons
  }

  /* ── Shortcode buttons ───────────────────────── */
  function attachShortcodes() {
    document.querySelectorAll('[data-mlpp-id]').forEach(function(btn) {
      var pid = parseInt(btn.getAttribute('data-mlpp-id'), 10);
      for (var i=0; i<popups.length; i++) {
        if (popups[i].id === pid) {
          var p = popups[i];
          btn.addEventListener('click', function() { showPopup(p); });
          break;
        }
      }
    });
  }

  /* ── Boot ────────────────────────────────────── */
  function boot() {
    popups.forEach(initTrigger);
    attachShortcodes();
  }

  if (document.readyState !== 'loading') boot();
  else document.addEventListener('DOMContentLoaded', boot);
})();
