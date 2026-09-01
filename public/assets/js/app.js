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
}

/**
 * Toggles an "external employee" fieldset when a select's value equals "other".
 */
function initEmployeeToggle(selectId, boxId) {
  var select = document.getElementById(selectId);
  var box = document.getElementById(boxId);
  if (!select || !box) return;

  function sync() {
    if (select.value === 'other') {
      box.style.display = '';
    } else {
      box.style.display = 'none';
      box.querySelectorAll('input').forEach(function (input) { input.value = ''; });
    }
  }

  select.addEventListener('change', sync);
  sync();
}
