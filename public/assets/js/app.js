/**
 * Theme toggle: cycles between light/dark and persists the choice.
 * Initial theme is applied earlier by an inline head script (avoids flash-of-wrong-theme);
 * this only wires up the button click.
 */
document.addEventListener('DOMContentLoaded', function () {
  var btn = document.getElementById('themeToggle');
  if (!btn) return;

  btn.addEventListener('click', function () {
    var current = document.documentElement.getAttribute('data-theme');
    var systemDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    var isDark = current === 'dark' || (!current && systemDark);
    var next = isDark ? 'light' : 'dark';
    try { localStorage.setItem('kp_theme', next); } catch (e) {}
    // Reload so canvas-based charts (Chart.js) re-render with the correct theme colors.
    location.reload();
  });
});

/**
 * Pins an open .combobox-menu to its input using fixed coordinates, so the menu is
 * never clipped by a scrolling ancestor (e.g. .modal-body inside a dialog).
 * Flips the menu above the input when there isn't room below.
 */
function positionFloating(anchor, el, matchWidth) {
  var rect = anchor.getBoundingClientRect();

  el.style.position = 'fixed';
  el.style.right = 'auto';
  if (matchWidth) el.style.width = rect.width + 'px';

  // Keep it on screen horizontally, preferring left-alignment with the anchor.
  var left = rect.left;
  if (left + el.offsetWidth > window.innerWidth - 12) {
    left = Math.max(12, window.innerWidth - el.offsetWidth - 12);
  }
  el.style.left = left + 'px';

  // Flip above the anchor when there isn't room below.
  var height = el.offsetHeight;
  if (window.innerHeight - rect.bottom < height + 12 && rect.top > height + 12) {
    el.style.top = (rect.top - height - 6) + 'px';
  } else {
    el.style.top = (rect.bottom + 6) + 'px';
  }
}

function positionComboboxMenu(anchor, menu) {
  positionFloating(anchor, menu, true);
}

/**
 * Keeps an open menu glued to its input while the page or a container scrolls.
 */
function trackComboboxMenu(anchor, menu) {
  function reposition() {
    if (menu.classList.contains('open')) positionComboboxMenu(anchor, menu);
  }
  window.addEventListener('scroll', reposition, true);
  window.addEventListener('resize', reposition);
}

/**
 * Searchable item picker: type to filter, click a suggestion to bind item_id.
 * Category is derived from the picked item — the catalog is the source of truth,
 * there is no manual category field.
 * root: element with class "combobox" containing:
 *   input.combobox-value (hidden, stores item id)
 *   input.combobox-category-value (hidden, stores derived category id)
 *   input.combobox-search (visible text input)
 *   .combobox-menu (suggestion list container)
 *   .combobox-selected-tag / .combobox-category-text (optional "bound" indicator)
 */
function initItemCombobox(root, items) {
  if (!root) return;
  var searchInput = root.querySelector('.combobox-search');
  var hiddenId = root.querySelector('.combobox-value');
  var hiddenCategory = root.querySelector('.combobox-category-value');
  var menu = root.querySelector('.combobox-menu');
  var tag = root.querySelector('.combobox-selected-tag');
  var categoryText = root.querySelector('.combobox-category-text');

  function closeMenu() { menu.classList.remove('open'); }

  function selectItem(it) {
    searchInput.value = it.name;
    hiddenId.value = it.id;
    if (hiddenCategory) hiddenCategory.value = it.category_id || '';
    if (categoryText) categoryText.textContent = it.category_name || 'ไม่ระบุหมวดหมู่';
    if (tag) tag.classList.add('show');
    closeMenu();
  }

  function render(list, query) {
    menu.innerHTML = '';
    if (!list.length) {
      var empty = document.createElement('div');
      empty.className = 'combobox-empty';
      empty.textContent = query ? 'ไม่พบในคลัง — จะบันทึกเป็นรายการใหม่ตามที่พิมพ์' : 'พิมพ์เพื่อค้นหารายการในคลัง';
      menu.appendChild(empty);
      menu.classList.add('open');
      positionComboboxMenu(searchInput, menu);
      return;
    }
    list.forEach(function (it) {
      var row = document.createElement('div');
      row.className = 'combobox-item';
      var nameEl = document.createElement('span');
      nameEl.className = 'combobox-item-name';
      nameEl.textContent = it.name;
      var metaEl = document.createElement('span');
      metaEl.className = 'combobox-item-meta';
      metaEl.textContent = [it.code, it.category_name].filter(Boolean).join(' · ');
      row.appendChild(nameEl);
      row.appendChild(metaEl);
      row.addEventListener('mousedown', function (e) {
        e.preventDefault();
        selectItem(it);
      });
      menu.appendChild(row);
    });
    menu.classList.add('open');
    positionComboboxMenu(searchInput, menu);
  }

  searchInput.addEventListener('input', function () {
    hiddenId.value = '';
    if (hiddenCategory) hiddenCategory.value = '';
    if (tag) tag.classList.remove('show');
    var q = searchInput.value.trim().toLowerCase();
    var filtered = !q ? items.slice(0, 8) : items.filter(function (it) {
      return it.name.toLowerCase().indexOf(q) !== -1 || (it.code || '').toLowerCase().indexOf(q) !== -1;
    }).slice(0, 8);
    render(filtered, q);
  });

  searchInput.addEventListener('focus', function () {
    searchInput.dispatchEvent(new Event('input'));
  });

  document.addEventListener('click', function (e) {
    if (!root.contains(e.target)) closeMenu();
  });

  trackComboboxMenu(searchInput, menu);
}

/**
 * Row "edit" buttons that open a shared dialog instead of a separate page.
 * The button carries the row's values as data-field-<inputName> attributes, plus
 * data-id, data-active and (optionally) data-incidents.
 */
function initRowEditModals() {
  document.querySelectorAll('[data-edit-modal]').forEach(function (btn) {
    var modal = document.getElementById(btn.getAttribute('data-edit-modal'));
    if (!modal) return;

    btn.addEventListener('click', function () {
      modal.querySelectorAll('input[name="id"]').forEach(function (input) {
        input.value = btn.getAttribute('data-id');
      });

      Array.prototype.forEach.call(btn.attributes, function (attr) {
        if (attr.name.indexOf('data-field-') !== 0) return;
        var field = modal.querySelector('[name="' + attr.name.slice(11) + '"]');
        if (!field) return;
        field.value = attr.value;
        // Let the themed dropdown redraw its label.
        if (field.tagName === 'SELECT') field.dispatchEvent(new Event('change', { bubbles: true }));
      });

      var isActive = btn.getAttribute('data-active') === '1';
      var toggle = modal.querySelector('[data-toggle-btn]');
      if (toggle) {
        toggle.textContent = toggle.getAttribute(isActive ? 'data-label-off' : 'data-label-on');
        toggle.classList.toggle('btn-danger-outline', isActive);
        toggle.classList.toggle('btn-secondary', !isActive);
      }

      var count = btn.getAttribute('data-incidents');
      if (count !== null) {
        modal.querySelectorAll('[data-incident-count]').forEach(function (el) {
          el.textContent = count;
        });
      }

      if (modal.showModal) modal.showModal();
    });
  });
}

/**
 * Progressive enhancement for every <select> in a form: the native control is kept
 * (hidden) so the value still submits and existing change listeners still fire,
 * while a themed button + option list is drawn on top of it. Without JS the plain
 * <select> keeps working.
 */
function initStyledSelects(scope) {
  var selects = (scope || document).querySelectorAll('.grid-form select, .filter-form select, .filter-menu-panel select');

  Array.prototype.forEach.call(selects, function (select) {
    if (select.getAttribute('data-styled')) return;
    select.setAttribute('data-styled', '1');

    var wrap = document.createElement('div');
    wrap.className = 'styled-select';
    select.parentNode.insertBefore(wrap, select);
    wrap.appendChild(select);

    var button = document.createElement('button');
    button.type = 'button';
    button.className = 'styled-select-button';
    var label = document.createElement('span');
    button.appendChild(label);
    wrap.appendChild(button);

    var menu = document.createElement('div');
    menu.className = 'styled-select-menu';
    wrap.appendChild(menu);

    function syncLabel() {
      var opt = select.options[select.selectedIndex];
      label.textContent = opt ? opt.textContent : '';
      button.classList.toggle('is-placeholder', !opt || opt.value === '');
    }

    function closeMenu() {
      menu.classList.remove('open');
      button.setAttribute('aria-expanded', 'false');
    }

    function openMenu() {
      menu.innerHTML = '';
      Array.prototype.forEach.call(select.options, function (opt) {
        var row = document.createElement('div');
        row.className = 'styled-select-option' + (opt.selected ? ' selected' : '');
        row.textContent = opt.textContent;
        row.addEventListener('mousedown', function (e) {
          e.preventDefault();
          select.value = opt.value;
          select.dispatchEvent(new Event('change', { bubbles: true }));
          syncLabel();
          closeMenu();
        });
        menu.appendChild(row);
      });
      menu.classList.add('open');
      button.setAttribute('aria-expanded', 'true');
      positionComboboxMenu(button, menu);
    }

    button.addEventListener('click', function () {
      if (menu.classList.contains('open')) closeMenu(); else openMenu();
    });
    document.addEventListener('click', function (e) {
      if (!wrap.contains(e.target)) closeMenu();
    });
    select.addEventListener('change', syncLabel);

    trackComboboxMenu(button, menu);
    syncLabel();
  });
}

/**
 * Forms marked [data-autosubmit] re-submit as soon as a select or date field changes,
 * so filters apply without a separate "apply" button. Text inputs are left alone —
 * they submit on Enter.
 */
function initAutoSubmit() {
  document.querySelectorAll('form[data-autosubmit]').forEach(function (form) {
    form.addEventListener('change', function (e) {
      var field = e.target;
      if (field.tagName === 'SELECT' || field.type === 'date') {
        if (form.requestSubmit) form.requestSubmit(); else form.submit();
      }
    });
  });
}

/**
 * Buttons with data-menu="<elementId>" open that element as a floating panel anchored
 * to the button. Closes on outside click, on Escape, and follows the button on scroll.
 */
function initMenus() {
  document.querySelectorAll('[data-menu]').forEach(function (btn) {
    var panel = document.getElementById(btn.getAttribute('data-menu'));
    if (!panel) return;

    function close() {
      panel.hidden = true;
      btn.classList.remove('is-active');
      btn.setAttribute('aria-expanded', 'false');
    }

    function open() {
      panel.hidden = false;
      btn.classList.add('is-active');
      btn.setAttribute('aria-expanded', 'true');
      positionFloating(btn, panel, false);
    }

    function reposition() {
      if (!panel.hidden) positionFloating(btn, panel, false);
    }

    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      if (panel.hidden) open(); else close();
    });
    document.addEventListener('click', function (e) {
      if (!panel.hidden && !panel.contains(e.target)) close();
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && !panel.hidden) close();
    });
    window.addEventListener('scroll', reposition, true);
    window.addEventListener('resize', reposition);

    close();
  });
}

document.addEventListener('DOMContentLoaded', function () {
  initStyledSelects();
  initMenus();
  initAutoSubmit();
});

/**
 * Wires up buttons/dialogs following the [data-open-modal]/[data-close-modal] convention:
 * a button with data-open-modal="someId" opens <dialog id="someId" class="modal">;
 * a [data-close-modal] inside that dialog, or a click on the backdrop, closes it.
 */
function initModals() {
  document.querySelectorAll('[data-open-modal]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var dialog = document.getElementById(btn.getAttribute('data-open-modal'));
      if (dialog && dialog.showModal) dialog.showModal();
    });
  });
  document.querySelectorAll('dialog.modal').forEach(function (dialog) {
    dialog.querySelectorAll('[data-close-modal]').forEach(function (btn) {
      btn.addEventListener('click', function () { dialog.close(); });
    });
    dialog.addEventListener('click', function (e) {
      if (e.target === dialog) dialog.close();
    });
  });
}

/**
 * Searchable employee picker. Submits "emp:<id>" or "other" through the hidden
 * .combobox-value input, and shows/hides the external-employee fieldset to match.
 * root: element with class "combobox" containing .combobox-value, .combobox-search, .combobox-menu
 */
function initEmployeeCombobox(root, employees, externalBoxId) {
  if (!root) return;
  var searchInput = root.querySelector('.combobox-search');
  var hiddenChoice = root.querySelector('.combobox-value');
  var menu = root.querySelector('.combobox-menu');
  var externalBox = document.getElementById(externalBoxId);

  function closeMenu() { menu.classList.remove('open'); }

  function showExternal(show) {
    if (!externalBox) return;
    externalBox.style.display = show ? '' : 'none';
    if (!show) externalBox.querySelectorAll('input').forEach(function (input) { input.value = ''; });
  }

  function selectEmployee(emp) {
    searchInput.value = emp.full_name + (emp.department ? ' (' + emp.department + ')' : '');
    hiddenChoice.value = 'emp:' + emp.id;
    showExternal(false);
    closeMenu();
  }

  function selectExternal() {
    searchInput.value = '';
    hiddenChoice.value = 'other';
    showExternal(true);
    closeMenu();
  }

  function render(list) {
    menu.innerHTML = '';
    list.forEach(function (emp) {
      var row = document.createElement('div');
      row.className = 'combobox-item';
      var nameEl = document.createElement('span');
      nameEl.className = 'combobox-item-name';
      nameEl.textContent = emp.full_name;
      var metaEl = document.createElement('span');
      metaEl.className = 'combobox-item-meta';
      metaEl.textContent = emp.department || '';
      row.appendChild(nameEl);
      row.appendChild(metaEl);
      row.addEventListener('mousedown', function (e) {
        e.preventDefault();
        selectEmployee(emp);
      });
      menu.appendChild(row);
    });
    if (!list.length) {
      var empty = document.createElement('div');
      empty.className = 'combobox-empty';
      empty.textContent = 'ไม่พบพนักงานในรายชื่อ';
      menu.appendChild(empty);
    }
    var other = document.createElement('div');
    other.className = 'combobox-item combobox-item-other';
    other.textContent = '+ พนักงานจากแผนกอื่น (ระบุชื่อเอง)';
    other.addEventListener('mousedown', function (e) {
      e.preventDefault();
      selectExternal();
    });
    menu.appendChild(other);
    menu.classList.add('open');
    positionComboboxMenu(searchInput, menu);
  }

  searchInput.addEventListener('input', function () {
    hiddenChoice.value = '';
    showExternal(false);
    var q = searchInput.value.trim().toLowerCase();
    var filtered = !q ? employees.slice(0, 8) : employees.filter(function (emp) {
      return emp.full_name.toLowerCase().indexOf(q) !== -1 ||
        (emp.department || '').toLowerCase().indexOf(q) !== -1;
    }).slice(0, 8);
    render(filtered);
  });

  searchInput.addEventListener('focus', function () {
    searchInput.dispatchEvent(new Event('input'));
  });

  document.addEventListener('click', function (e) {
    if (!root.contains(e.target)) closeMenu();
  });

  trackComboboxMenu(searchInput, menu);
  showExternal(hiddenChoice.value === 'other');
}

/**
 * Responsive tables: copy each column header into data-label on its cells so the
 * phone layout (style.css, <=640px) can render rows as labelled cards.
 */
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.table-wrap > .data-table').forEach(function (table) {
    var heads = Array.prototype.map.call(table.querySelectorAll('thead th'), function (th) {
      return th.textContent.trim();
    });
    if (!heads.length) return;
    table.querySelectorAll('tbody tr').forEach(function (tr) {
      Array.prototype.forEach.call(tr.children, function (td, i) {
        if (td.classList.contains('empty-row')) return;
        td.setAttribute('data-label', heads[i] || '');
      });
    });
    table.parentElement.classList.add('is-cards');
  });
});

/**
 * Sidebar: user-controlled collapse to an icon rail, remembered in localStorage.
 * Between 901-1180px the CSS collapses it automatically unless the user expanded it.
 */
document.addEventListener('DOMContentLoaded', function () {
  var sidebar = document.getElementById('sidebar');
  var toggle = document.getElementById('sidebarToggle');
  if (!sidebar || !toggle) return;

  var saved = null;
  try { saved = localStorage.getItem('kp_sidebar'); } catch (e) {}
  if (saved === 'rail') sidebar.classList.add('is-rail');
  if (saved === 'full') sidebar.classList.add('is-expanded');

  toggle.addEventListener('click', function () {
    var autoRail = window.matchMedia('(max-width: 1180px)').matches;
    var isRail = sidebar.classList.contains('is-rail') || (autoRail && !sidebar.classList.contains('is-expanded'));
    sidebar.classList.toggle('is-rail', !isRail);
    sidebar.classList.toggle('is-expanded', isRail);
    try { localStorage.setItem('kp_sidebar', isRail ? 'full' : 'rail'); } catch (e) {}
  });
});

/**
 * PWA: register the service worker, offer the install button when the browser
 * allows it, and show a banner while offline.
 */
(function () {
  if ('serviceWorker' in navigator && (location.protocol === 'https:' || location.hostname === 'localhost')) {
    window.addEventListener('load', function () {
      navigator.serviceWorker.register('/sw.js').catch(function () {});
    });
  }

  var deferredPrompt = null;
  window.addEventListener('beforeinstallprompt', function (e) {
    e.preventDefault();
    deferredPrompt = e;
    var btn = document.getElementById('installApp');
    if (btn) btn.classList.add('show');
  });
  document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('installApp');
    if (!btn) return;
    btn.addEventListener('click', function () {
      if (!deferredPrompt) return;
      deferredPrompt.prompt();
      deferredPrompt.userChoice.then(function () { deferredPrompt = null; btn.classList.remove('show'); });
    });
  });
  window.addEventListener('appinstalled', function () {
    var btn = document.getElementById('installApp');
    if (btn) btn.classList.remove('show');
  });

  function updateOnline() {
    var banner = document.getElementById('offlineBanner');
    if (banner) banner.classList.toggle('show', !navigator.onLine);
  }
  window.addEventListener('online', updateOnline);
  window.addEventListener('offline', updateOnline);
  document.addEventListener('DOMContentLoaded', updateOnline);
})();

// App-like on phones: block iOS pinch-zoom (Safari ignores user-scalable=no).
['gesturestart', 'gesturechange', 'gestureend'].forEach(function (type) {
  document.addEventListener(type, function (e) { e.preventDefault(); }, { passive: false });
});

// Mobile "เมนู" bottom sheet (footer.php).
document.addEventListener('DOMContentLoaded', function () {
  var sheet = document.getElementById('moreMenu');
  var btn = document.getElementById('moreMenuBtn');
  if (!sheet || !btn || !sheet.showModal) return;
  btn.addEventListener('click', function () { sheet.showModal(); });
  sheet.addEventListener('click', function (e) { if (e.target === sheet) sheet.close(); });
  sheet.querySelectorAll('[data-sheet-close]').forEach(function (b) {
    b.addEventListener('click', function () { sheet.close(); });
  });
  var theme = document.getElementById('sheetTheme');
  var toggle = document.getElementById('themeToggle');
  if (theme && toggle) theme.addEventListener('click', function () { toggle.click(); });
});
