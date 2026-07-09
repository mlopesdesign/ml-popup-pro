/* ML Popup Pro – Admin JS v1.0.12 */
(function ($) {
  'use strict';

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  ready(function () {
    var wrap = document.querySelector('.mlpp-wrap');
    if (!wrap) return;

    /* ── TOAST ─────────────────────────────────── */
    var toastArea = document.getElementById('mlpp-toast-area');
    window.mlppToast = function (msg, type) {
      if (!toastArea || !msg) return;
      var t = document.createElement('div');
      t.className = 'mlpp-toast mlpp-toast-' + (type || 'success');
      t.textContent = msg;
      toastArea.appendChild(t);
      requestAnimationFrame(function () { t.classList.add('is-visible'); });
      setTimeout(function () {
        t.classList.remove('is-visible');
        setTimeout(function () { if (t.parentNode) t.parentNode.removeChild(t); }, 220);
      }, 4200);
    };
    var initMsg = wrap.getAttribute('data-toast-message');
    var initType = wrap.getAttribute('data-toast-type') || 'success';
    if (initMsg) window.mlppToast(decodeURIComponent(initMsg), initType);

    /* ── TABS ────────────────────────────────────── */
    document.querySelectorAll('.mlpp-tab-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var target = btn.getAttribute('data-tab');
        if (!target) return;
        var group = btn.closest('.mlpp-tabs-container') || document;
        group.querySelectorAll('.mlpp-tab-btn').forEach(function (b) { b.classList.remove('is-active'); });
        group.querySelectorAll('.mlpp-tab-panel').forEach(function (p) { p.classList.remove('is-active'); p.hidden = true; });
        btn.classList.add('is-active');
        var panel = document.getElementById(target);
        if (panel) { panel.classList.add('is-active'); panel.hidden = false; }
        try {
          var url = new URL(window.location.href);
          url.searchParams.set('tab', target.replace('mlpp-tab-', ''));
          window.history.replaceState({}, '', url.toString());
        } catch (e) {}
      });
    });

    /* ── COLOR PICKERS ───────────────────────────── */
    if ($ && $.fn.wpColorPicker) {
      $('.mlpp-color-picker').wpColorPicker();
    }

    /* ── MEDIA UPLOADER ──────────────────────────── */
    document.querySelectorAll('.mlpp-media-select-btn').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        var zone = btn.closest('.mlpp-media-zone-wrap') || document.getElementById(btn.getAttribute('data-zone'));
        if (!zone) return;
        var preview = zone.querySelector('.mlpp-media-preview');
        var previewImg = zone.querySelector('.mlpp-media-preview img');
        var placeholder = zone.querySelector('.mlpp-media-placeholder');
        var hiddenId = zone.querySelector('input[data-field="attachment_id"]');
        var hiddenUrl = zone.querySelector('input[data-field="image_url"]');
        var hiddenAlt = zone.querySelector('input[data-field="image_alt"]');
        var removeBtn = zone.querySelector('.mlpp-media-remove-btn');

        var frame = wp.media({
          title: 'Selecionar imagem',
          button: { text: 'Usar esta imagem' },
          multiple: false
        });

        frame.on('select', function () {
          var att = frame.state().get('selection').first().toJSON();
          var url = att.sizes && att.sizes.large ? att.sizes.large.url : att.url;

          if (hiddenId) hiddenId.value = att.id;
          if (hiddenUrl) hiddenUrl.value = att.url;
          if (hiddenAlt) hiddenAlt.value = att.alt || att.title || '';

          if (previewImg) previewImg.src = url;
          if (preview) preview.classList.add('has-image');
          if (placeholder) placeholder.style.display = 'none';
          if (removeBtn) removeBtn.style.display = '';

          // fire preview update
          document.dispatchEvent(new CustomEvent('mlpp:image-updated', { detail: { url: url, id: att.id } }));
        });

        frame.open();
      });
    });

    /* Remove image */
    document.querySelectorAll('.mlpp-media-remove-btn').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        var zone = btn.closest('.mlpp-media-zone-wrap');
        if (!zone) return;
        var preview = zone.querySelector('.mlpp-media-preview');
        var placeholder = zone.querySelector('.mlpp-media-placeholder');
        var previewImg = zone.querySelector('.mlpp-media-preview img');
        zone.querySelectorAll('input[data-field]').forEach(function (i) { i.value = ''; });
        if (preview) preview.classList.remove('has-image');
        if (previewImg) previewImg.removeAttribute('src');
        if (placeholder) placeholder.style.display = '';
        btn.style.display = 'none';
        document.dispatchEvent(new CustomEvent('mlpp:image-updated', { detail: { url: '', id: '' } }));
      });
    });

    /* ── LIVE MINI PREVIEW ───────────────────────── */
    var previewBox = document.getElementById('mlpp-live-preview');
    function colorWithOpacity(hex, percent) {
      var clean = String(hex || '#ffffff').replace('#', '').trim();
      if (clean.length === 3) clean = clean.split('').map(function (c) { return c + c; }).join('');
      if (!/^[0-9a-fA-F]{6}$/.test(clean)) return hex || '#ffffff';
      var alpha = Math.max(0, Math.min(100, parseInt(percent, 10) || 0)) / 100;
      return 'rgba(' + parseInt(clean.slice(0, 2), 16) + ',' + parseInt(clean.slice(2, 4), 16) + ',' + parseInt(clean.slice(4, 6), 16) + ',' + alpha + ')';
    }
    function updatePreview() {
      if (!previewBox) return;
      var bgColor = (document.getElementById('mlpp_design_bg_color') || {}).value || '#ffffff';
      var bgOpacityEl = document.getElementById('mlpp_design_bg_opacity');
      var bgOpacity = bgOpacityEl ? bgOpacityEl.value : '100';
      var textColor = (document.getElementById('mlpp_design_text_color') || {}).value || '#102a43';
      var btnColor = (document.getElementById('mlpp_design_btn_color') || {}).value || '#155e6f';
      var title = (document.getElementById('mlpp_title') || {}).value || 'Título do popup';
      var subtitle = (document.getElementById('mlpp_subtitle') || {}).value || '';
      var btnText = (document.getElementById('mlpp_btn_primary_text') || {}).value || 'Botão';
      var radius = (document.getElementById('mlpp_design_border_radius') || {}).value || '16px';
      previewBox.style.background = colorWithOpacity(bgColor, bgOpacity);
      previewBox.style.color = textColor;
      previewBox.style.borderRadius = radius;
      var pt = previewBox.querySelector('.mlpp-pv-title');
      var ps = previewBox.querySelector('.mlpp-pv-sub');
      var pb = previewBox.querySelector('.mlpp-pv-btn');
      if (pt) pt.textContent = title;
      if (ps) ps.textContent = subtitle;
      if (pb) { pb.textContent = btnText; pb.style.background = btnColor; }
    }
    ['mlpp_title','mlpp_subtitle','mlpp_btn_primary_text','mlpp_design_bg_color','mlpp_design_bg_opacity','mlpp_design_text_color','mlpp_design_btn_color','mlpp_design_border_radius'].forEach(function (id) {
      var el = document.getElementById(id);
      if (el) el.addEventListener('input', updatePreview);
    });
    // wp-color-picker fires change on the hidden input
    document.querySelectorAll('.mlpp-color-picker').forEach(function (el) {
      el.addEventListener('change', updatePreview);
    });
    updatePreview();

    /* ── TRIGGER TYPE VISIBILITY ─────────────────── */
    var triggerSel = document.getElementById('mlpp_trigger_type');
    if (triggerSel) {
      function toggleTriggerFields() {
        var val = triggerSel.value;
        var map = {
          'delay':       ['mlpp-trigger-delay'],
          'scroll':      ['mlpp-trigger-scroll'],
          'selector':    ['mlpp-trigger-selector'],
          'pageviews':   ['mlpp-trigger-pageviews'],
        };
        document.querySelectorAll('.mlpp-trigger-field').forEach(function (el) { el.style.display = 'none'; });
        if (map[val]) map[val].forEach(function (id) {
          var el = document.getElementById(id);
          if (el) el.style.display = '';
        });
      }
      triggerSel.addEventListener('change', toggleTriggerFields);
      toggleTriggerFields();
    }

    /* ── STORAGE TYPE VISIBILITY ─────────────────── */
    var storageSel = document.getElementById('mlpp_storage_method');
    if (storageSel) {
      function toggleStorageFields() {
        var none = storageSel.value === 'none';
        document.querySelectorAll('.mlpp-storage-dependent').forEach(function (el) {
          el.style.opacity = none ? '.4' : '';
          el.querySelectorAll('input,select').forEach(function (i) { i.disabled = none; });
        });
      }
      storageSel.addEventListener('change', toggleStorageFields);
      toggleStorageFields();
    }

    /* ── COPY SHORTCODE ──────────────────────────── */
    document.querySelectorAll('[data-copy]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var text = btn.getAttribute('data-copy');
        navigator.clipboard.writeText(text).then(function () {
          window.mlppToast('Copiado!', 'success');
        }).catch(function () {
          window.mlppToast('Falha ao copiar.', 'error');
        });
      });
    });

    /* ── CONFIRM DANGEROUS ACTIONS ───────────────── */
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
      el.addEventListener('click', function (e) {
        if (!confirm(el.getAttribute('data-confirm'))) e.preventDefault();
      });
    });

    /* ── SCOPE SHOW/HIDE ─────────────────────────── */
    var scopeSel = document.getElementById('mlpp_rules_scope');
    if (scopeSel) {
      function toggleScopeFields() {
        var val = scopeSel.value;
        document.querySelectorAll('.mlpp-scope-field').forEach(function (el) { el.style.display = 'none'; });
        var show = document.getElementById('mlpp-scope-' + val.replace(/_/g, '-'));
        if (show) show.style.display = '';
      }
      scopeSel.addEventListener('change', toggleScopeFields);
      toggleScopeFields();
    }
  });

})(typeof jQuery !== 'undefined' ? jQuery : null);
