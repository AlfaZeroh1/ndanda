/* Ndanda Tributes – shared front-end behaviour (vanilla JS) */
(function () {
  'use strict';

  window.postJSON = async function (url, data) {
    const res = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': APP.csrf, 'Accept': 'application/json' },
      body: new URLSearchParams(data || {}).toString()
    });
    let json = {};
    try { json = await res.json(); } catch (e) { json = { ok: false, message: 'Unexpected server response.' }; }
    return json;
  };

  window.toast = function (msg, type) {
    let box = document.getElementById('toastBox');
    if (!box) {
      box = document.createElement('div');
      box.id = 'toastBox';
      box.className = 'toast-container position-fixed bottom-0 start-50 translate-middle-x p-3';
      box.style.zIndex = 2000;
      document.body.appendChild(box);
    }
    const el = document.createElement('div');
    el.className = 'toast align-items-center text-bg-' + (type || 'dark') + ' border-0';
    el.innerHTML = '<div class="d-flex"><div class="toast-body"></div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>';
    el.querySelector('.toast-body').textContent = msg;
    box.appendChild(el);
    new bootstrap.Toast(el, { delay: 2600 }).show();
  };

  // Copy-to-clipboard: <button data-copy="text"> or data-copy-target="#input"
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('[data-copy], [data-copy-target]');
    if (!btn) return;
    e.preventDefault();
    const text = btn.dataset.copy || (document.querySelector(btn.dataset.copyTarget) || {}).value || '';
    navigator.clipboard.writeText(text).then(function () { toast('Link copied'); }, function () { prompt('Copy this link:', text); });
  });

  // Native share sheet with WhatsApp fallback
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('[data-share]');
    if (!btn) return;
    e.preventDefault();
    const url = btn.dataset.share, title = btn.dataset.title || document.title;
    if (navigator.share) {
      navigator.share({ title: title, text: title, url: url }).catch(function () {});
    } else {
      window.open('https://wa.me/?text=' + encodeURIComponent(title + ' ' + url), '_blank');
    }
  });

  // Confirm before destructive submits: <form data-confirm="Are you sure?">
  document.addEventListener('submit', function (e) {
    const msg = e.target.dataset.confirm;
    if (msg && !confirm(msg)) e.preventDefault();
  });

  // Package builder: highlight checked services and update totals
  function updateTotals() {
    const opts = document.querySelectorAll('.svc-option input[type=checkbox]');
    if (!opts.length) return;
    let total = 0, count = 0;
    const list = document.getElementById('summaryList');
    if (list) list.innerHTML = '';
    opts.forEach(function (cb) {
      const card = cb.closest('.svc-option');
      card.classList.toggle('checked', cb.checked);
      if (cb.checked) {
        const price = parseFloat(cb.dataset.price || 0);
        total += price;
        if (price > 0) count++;
        if (list) {
          const li = document.createElement('li');
          li.className = 'd-flex justify-content-between py-1';
          const name = document.createElement('span'); name.textContent = cb.dataset.name;
          const p = document.createElement('span'); p.className = 'fw-semibold'; p.textContent = price > 0 ? 'KES ' + price.toLocaleString() : 'Free';
          li.append(name, p);
          list.appendChild(li);
        }
      }
    });
    document.querySelectorAll('[data-total]').forEach(function (el) { el.textContent = 'KES ' + total.toLocaleString(); });
    document.querySelectorAll('[data-count]').forEach(function (el) { el.textContent = count; });
  }
  document.addEventListener('change', function (e) { if (e.target.closest('.svc-option')) updateTotals(); });
  document.addEventListener('DOMContentLoaded', updateTotals);

  // QR codes: <div data-qr="https://..." data-size="200"> (needs qrcodejs on the page)
  window.renderQRs = function () {
    if (typeof QRCode === 'undefined') return;
    document.querySelectorAll('[data-qr]').forEach(function (el) {
      el.innerHTML = '';
      const size = parseInt(el.dataset.size || '200', 10);
      new QRCode(el, { text: el.dataset.qr, width: size, height: size, colorDark: el.dataset.color || '#000000', colorLight: '#ffffff', correctLevel: QRCode.CorrectLevel.H });
    });
  };
  document.addEventListener('DOMContentLoaded', function () { window.renderQRs(); });

  // Download a rendered QR as PNG: <button data-qr-download="#qrEl" data-filename="x.png">
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('[data-qr-download]');
    if (!btn) return;
    const box = document.querySelector(btn.dataset.qrDownload);
    if (!box) return;
    const canvas = box.querySelector('canvas');
    const img = box.querySelector('img');
    const src = canvas ? canvas.toDataURL('image/png') : (img ? img.src : null);
    if (!src) return;
    const a = document.createElement('a');
    a.href = src; a.download = btn.dataset.filename || 'qr-code.png';
    document.body.appendChild(a); a.click(); a.remove();
  });

  // Image preview for file inputs: <input type=file data-preview="#img">
  document.addEventListener('change', function (e) {
    const input = e.target;
    if (!input.matches('input[type=file][data-preview]') || !input.files[0]) return;
    const img = document.querySelector(input.dataset.preview);
    if (img) { img.src = URL.createObjectURL(input.files[0]); img.classList.remove('d-none'); }
  });

  // Admin sidebar toggle
  document.addEventListener('click', function (e) {
    if (e.target.closest('[data-toggle-side]')) document.querySelector('.admin-side').classList.toggle('show');
  });
})();
