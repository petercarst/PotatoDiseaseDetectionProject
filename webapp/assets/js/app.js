/**
 * uhAI Intelligence - front-end interactions
 * Upload/drag-drop, AJAX scan, live results, history delete.
 */
(function () {
  'use strict';

  const MAX_FILE_SIZE = 5 * 1024 * 1024;
  const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

  // ---------------------------------------------------------------------
  // Toasts
  // ---------------------------------------------------------------------
  function showToast(message, type) {
    const stack = document.getElementById('toastStack');
    if (!stack) return;
    const toast = document.createElement('div');
    toast.className = 'ui-toast ' + (type === 'error' ? 'is-error' : 'is-success');
    toast.innerHTML =
      '<i class="bi ' + (type === 'error' ? 'bi-exclamation-circle-fill' : 'bi-check-circle-fill') + '"></i>' +
      '<span>' + message + '</span>';
    stack.appendChild(toast);
    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transition = 'opacity .25s ease';
      setTimeout(() => toast.remove(), 250);
    }, 3200);
  }

  // ---------------------------------------------------------------------
  // API status badge
  // ---------------------------------------------------------------------
  function checkApiStatus() {
    const badge = document.getElementById('apiStatusBadge');
    if (!badge) return;

    fetch('ping.php')
      .then((r) => r.json())
      .then((data) => {
        badge.classList.remove('is-online', 'is-offline');
        if (data.online) {
          badge.classList.add('is-online');
          badge.innerHTML = '<span class="ui-dot"></span> AI engine online';
        } else {
          badge.classList.add('is-offline');
          badge.innerHTML = '<span class="ui-dot"></span> AI engine offline';
        }
      })
      .catch(() => {
        badge.classList.remove('is-online');
        badge.classList.add('is-offline');
        badge.innerHTML = '<span class="ui-dot"></span> AI engine offline';
      });
  }

  checkApiStatus();
  setInterval(checkApiStatus, 20000);

  // ---------------------------------------------------------------------
  // Upload + scan (index.php)
  // ---------------------------------------------------------------------
  const dropzone = document.getElementById('dropzone');
  if (dropzone) {
    const fileInput = document.getElementById('fileInput');
    const placeholder = document.getElementById('dropzonePlaceholder');
    const previewWrap = document.getElementById('previewWrap');
    const previewImg = document.getElementById('previewImg');
    const scanFrame = previewWrap.querySelector('.ui-scan-frame');
    const scanForm = document.getElementById('scanForm');
    const scanBtn = document.getElementById('scanBtn');
    const scanBtnText = document.getElementById('scanBtnText');
    const resetBtn = document.getElementById('resetBtn');
    const resultPanel = document.getElementById('resultPanel');
    const errorBox = document.getElementById('scanError');
    const errorText = document.getElementById('scanErrorText');
    const recentList = document.getElementById('recentList');
    const recentEmpty = document.getElementById('recentEmpty');
    const template = document.getElementById('recentItemTemplate');
    const cameraInput = document.getElementById('cameraInput');
    const browseBtn = document.getElementById('browseBtn');
    const cameraBtn = document.getElementById('cameraBtn');

    let currentFile = null;

    function hideError() {
      errorBox.classList.add('d-none');
    }

    function showError(message) {
      errorText.textContent = message;
      errorBox.classList.remove('d-none');
    }

    function openBrowser() {
      fileInput.click();
    }

    dropzone.addEventListener('click', openBrowser);
    dropzone.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        openBrowser();
      }
    });

    // These sit inside the dropzone, which is itself click-to-browse —
    // stop propagation so a button click doesn't also fire the dropzone's
    // own click handler and open a second file dialog.
    browseBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      fileInput.click();
    });
    cameraBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      cameraInput.click();
    });
    cameraInput.addEventListener('change', () => {
      if (cameraInput.files && cameraInput.files.length) handleFile(cameraInput.files[0]);
    });

    ['dragenter', 'dragover'].forEach((evt) => {
      dropzone.addEventListener(evt, (e) => {
        e.preventDefault();
        e.stopPropagation();
        dropzone.classList.add('is-dragover');
      });
    });
    ['dragleave', 'drop'].forEach((evt) => {
      dropzone.addEventListener(evt, (e) => {
        e.preventDefault();
        e.stopPropagation();
        dropzone.classList.remove('is-dragover');
      });
    });
    dropzone.addEventListener('drop', (e) => {
      const files = e.dataTransfer.files;
      if (files && files.length) handleFile(files[0]);
    });

    fileInput.addEventListener('change', () => {
      if (fileInput.files && fileInput.files.length) handleFile(fileInput.files[0]);
    });

    function handleFile(file) {
      hideError();

      if (!ALLOWED_TYPES.includes(file.type)) {
        showError('Unsupported file type. Please choose a JPG, PNG or WEBP image.');
        return;
      }
      if (file.size > MAX_FILE_SIZE) {
        showError('That image is larger than 5 MB. Please choose a smaller file.');
        return;
      }

      currentFile = file;
      const url = URL.createObjectURL(file);
      previewImg.src = url;
      placeholder.classList.add('d-none');
      previewWrap.classList.remove('d-none');
      resultPanel.classList.add('d-none');
      resetBtn.classList.remove('d-none');
      scanBtn.disabled = false;
    }

    resetBtn.addEventListener('click', () => {
      currentFile = null;
      fileInput.value = '';
      cameraInput.value = '';
      previewWrap.classList.add('d-none');
      placeholder.classList.remove('d-none');
      resultPanel.classList.add('d-none');
      resetBtn.classList.add('d-none');
      scanBtn.disabled = true;
      hideError();
    });

    scanForm.addEventListener('submit', (e) => {
      e.preventDefault();
      if (!currentFile) return;

      hideError();
      scanBtn.disabled = true;
      scanBtnText.textContent = 'Analyzing leaf...';
      scanFrame.classList.add('is-scanning');
      dropzone.classList.add('is-scanning');

      const formData = new FormData();
      formData.append('leaf', currentFile);

      fetch('scan.php', { method: 'POST', body: formData })
        .then(async (res) => {
          const data = await res.json();
          if (!res.ok || !data.success) {
            throw new Error(data.message || 'Something went wrong while scanning.');
          }
          return data.scan;
        })
        .then((scan) => renderResult(scan))
        .catch((err) => showError(err.message))
        .finally(() => {
          scanBtn.disabled = false;
          scanBtnText.textContent = 'Scan Leaf';
          scanFrame.classList.remove('is-scanning');
          dropzone.classList.remove('is-scanning');
        });
    });

    function colorClass(prefix, color) {
      const map = { life: 'life', warning: 'warning', danger: 'danger', secondary: 'secondary' };
      return prefix + '-' + (map[color] || 'secondary');
    }

    function renderResult(scan) {
      const resultIcon = document.getElementById('resultIcon');
      resultIcon.className = 'ui-result-icon ' + (scan.color === 'life' ? 'text-life' : 'text-' + scan.color);
      resultIcon.innerHTML = '<i class="bi ' + scan.icon + '"></i>';

      document.getElementById('resultClass').textContent = scan.predicted_class;
      document.getElementById('resultConfidence').textContent = scan.confidence.toFixed(1) + '%';
      document.getElementById('resultSummary').textContent = scan.summary;
      document.getElementById('resultAdvice').textContent = scan.advice;

      const bar = document.getElementById('resultBar');
      bar.style.width = '0%';
      requestAnimationFrame(() => { bar.style.width = scan.confidence + '%'; });

      resultPanel.classList.remove('d-none');

      prependRecent(scan);
      bumpStat(scan);

      showToast('Scan complete: ' + scan.predicted_class + ' (' + scan.confidence.toFixed(1) + '%)', 'success');
    }

    function prependRecent(scan) {
      if (!recentList) return;
      if (recentEmpty) recentEmpty.remove();

      const node = template.content.cloneNode(true);
      const img = node.querySelector('img');
      img.src = scan.image_url;
      node.querySelector('[data-field="class"]').textContent = scan.predicted_class;
      node.querySelector('[data-field="confidence"]').textContent = scan.confidence.toFixed(1);
      node.querySelector('[data-field="time"]').textContent = 'just now';
      const badge = node.querySelector('.ui-badge');
      badge.classList.add(colorClass('ui-badge', scan.color));
      badge.querySelector('i').classList.add(scan.icon);

      recentList.prepend(node);
      while (recentList.children.length > 6) {
        recentList.removeChild(recentList.lastElementChild);
      }
    }

    function bumpStat(scan) {
      const totalEl = document.querySelector('[data-stat="total"]');
      if (totalEl) totalEl.textContent = (parseInt(totalEl.textContent, 10) || 0) + 1;

      let key = null;
      if (scan.predicted_class === 'Healthy') key = 'healthy';
      else if (scan.predicted_class === 'Early Blight') key = 'early_blight';
      else if (scan.predicted_class === 'Late Blight') key = 'late_blight';

      if (key) {
        const el = document.querySelector('[data-stat="' + key + '"]');
        if (el) el.textContent = (parseInt(el.textContent, 10) || 0) + 1;
      }
    }
  }

  // ---------------------------------------------------------------------
  // History page: delete scans
  // ---------------------------------------------------------------------
  const historyBody = document.getElementById('historyBody');
  if (historyBody) {
    historyBody.addEventListener('click', (e) => {
      const btn = e.target.closest('[data-delete]');
      if (!btn) return;

      const id = btn.getAttribute('data-delete');
      if (!confirm('Delete this scan permanently?')) return;

      btn.disabled = true;

      const formData = new FormData();
      formData.append('id', id);

      fetch('delete.php', { method: 'POST', body: formData })
        .then(async (res) => {
          const data = await res.json();
          if (!res.ok || !data.success) throw new Error(data.message || 'Could not delete this scan.');
          return data;
        })
        .then(() => {
          const row = historyBody.querySelector('tr[data-id="' + id + '"]');
          if (row) row.remove();
          showToast('Scan deleted.', 'success');
          if (!historyBody.children.length) {
            setTimeout(() => location.reload(), 600);
          }
        })
        .catch((err) => {
          btn.disabled = false;
          showToast(err.message, 'error');
        });
    });
  }
})();
