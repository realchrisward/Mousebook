/* =====================================================
   mousebook.js  v2.0
   Enhances Mousebook pages with:
     - Credential dropdown in header
     - Sidebar collapse / expand with toggle
     - Nav icons, grouping labels, and dividers
     - Default collapsed sidebar on task pages
     - Touch tap-and-hold tooltips for collapsed nav
     - Centered login layout for databases.php

   Add to every PHP page before </body>:
     php/ pages:    <script src="../mousebook.js"></script>
     index.php:     <script src="./mousebook.js"></script>
     pages/ pages:  <script src="../mousebook.js"></script>
   ===================================================== */
(function () {
  'use strict';

  /* ── Tabler icons — load from CDN with fallback ─────
     If the CDN is unreachable (internal network / VPN),
     icons degrade gracefully to text labels only.
     The rest of the JS runs regardless.              */
  function loadTablerIcons(callback) {
    if (document.querySelector('link[href*="tabler-icons"]')) {
      callback(); return;
    }
    var lnk = document.createElement('link');
    lnk.rel  = 'stylesheet';
    lnk.href = 'https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.33.0/dist/tabler-icons.min.css';

    var done = false;
    function finish() { if (!done) { done = true; callback(); } }

    lnk.onload  = finish;
    lnk.onerror = finish;                    // CDN failed — continue without icons
    setTimeout(finish, 3000);                // 3 s timeout — continue either way
    document.head.appendChild(lnk);
  }

  /* ── Custom mice SVG for Strains nav item ────────── */
  var MICE_SVG = '<svg width="20" height="13" viewBox="-32 -9 64 26" aria-hidden="true" style="flex-shrink:0;display:inline-block;vertical-align:middle">'
    + '<path d="M-19 11 Q-26 8 -29 13" stroke="#2b92b9" stroke-width="1.8" fill="none" stroke-linecap="round"/>'
    + '<ellipse cx="-9" cy="8" rx="11" ry="7" fill="#2b92b9"/>'
    + '<ellipse cx="-9" cy="3" rx="5" ry="5" fill="#2b92b9"/>'
    + '<ellipse cx="-13" cy="-1" rx="2" ry="1.2" fill="#2b92b9"/>'
    + '<ellipse cx="-6" cy="-1" rx="2" ry="1.2" fill="#2b92b9"/>'
    + '<ellipse cx="-9" cy="-5" rx="1.2" ry="2" fill="#2b92b9"/>'
    + '<path d="M18 12 Q25 9 29 13" stroke="#ef7000" stroke-width="1.8" fill="none" stroke-linecap="round"/>'
    + '<ellipse cx="9" cy="8" rx="11" ry="7" fill="#ef7000"/>'
    + '<ellipse cx="9" cy="3" rx="5" ry="5" fill="#ef7000"/>'
    + '<ellipse cx="5" cy="-1" rx="2" ry="1.2" fill="#ef7000"/>'
    + '<ellipse cx="12" cy="-1" rx="2" ry="1.2" fill="#ef7000"/>'
    + '<ellipse cx="9" cy="-5" rx="1.2" ry="2" fill="#ef7000"/>'
    + '</svg>';

  /* ── Nav config: text match → icon class + group ─── */
  /* Matches are lowercase substrings of button value   */
  var NAV_CFG = [
    { m: 'home',          icon: 'ti-home',             group: null },
    { m: 'allele',        icon: 'ti-dna',              group: 'Manage' },
    { m: 'strain',        icon: 'MICE',                group: 'Manage' },
    { m: 'line',          icon: 'ti-hierarchy',         group: 'Manage' },
    { m: 'manage animal', icon: 'ti-paw',              group: 'Manage' },
    { m: 'manage cage',   icon: 'ti-layout-grid-add',  group: 'Manage' },
    { m: 'role',          icon: 'ti-tags',             group: 'Manage' },
    { m: 'genotyp',       icon: 'ti-test-pipe',        group: 'Query & Print' },
    { m: 'query',         icon: 'ti-table',            group: 'Query & Print' },
    { m: 'view animal',   icon: 'ti-paw',              group: 'Query & Print' },
    { m: 'card print',    icon: 'ti-printer',          group: 'Query & Print' },
    { m: 'export',        icon: 'ti-download',         group: 'Query & Print' },
    { m: 'cage location', icon: 'ti-map-pin',          group: 'Locations' },
    { m: 'cage role',     icon: 'ti-tag',              group: 'Locations' },
    { m: 'location',      icon: 'ti-map-pin',          group: 'Locations' },
    { m: 'litter',        icon: 'ti-notes',            group: 'Query & Print' },
  ];

  function navConfig(text) {
    var t = text.toLowerCase();
    for (var i = 0; i < NAV_CFG.length; i++) {
      if (t.indexOf(NAV_CFG[i].m) !== -1) return NAV_CFG[i];
    }
    return { icon: 'ti-circle-dotted', group: null };
  }

  function makeIcon(icon) {
    if (icon === 'MICE') return MICE_SVG;
    return '<i class="ti ' + icon + '" aria-hidden="true"></i>';
  }

  /* ── Is this a home / login page? ────────────────── */
  function isHomePage() {
    var p = window.location.pathname;
    return p.match(/index\.php$/) || p.match(/databases\.php$/) || p === '/' || p === '';
  }

  /* ── Sidebar toggle ──────────────────────────────── */
  function toggleSidebar() {
    var nav = document.getElementById('left_navmenu');
    if (!nav) return;
    var collapsed = nav.classList.toggle('collapsed');
    syncToggleIcons(collapsed);
  }

  function syncToggleIcons(collapsed) {
    ['mbHeaderToggleIcon', 'mbNavToggleIcon'].forEach(function (id) {
      var el = document.getElementById(id);
      if (el) el.className = collapsed
        ? 'ti ti-layout-sidebar-left-expand'
        : 'ti ti-layout-sidebar-left-collapse';
    });
  }

  /* ── Enhance header ──────────────────────────────── */
  function enhanceHeader() {
    var header = document.getElementById('header');
    if (!header) return;

    var form    = header.querySelector('form');
    if (!form) return;

    var h2      = header.querySelector('h2');
    var h1      = header.querySelector('h1');
    var dbIn    = header.querySelector('input[name="dbname"]');
    var userIn  = header.querySelector('input[name="xusername"], input[name="username"]');
    var passIn  = header.querySelector('input[name="xpassword"], input[name="password"]');
    var statBtn = header.querySelector('#statusbutton');
    var loginBtn = header.querySelector('#loginbutton');
    var discoBtn = header.querySelector('#discobutton');

    var pageTitle   = h2      ? h2.textContent.trim()       : '';
    var dbName      = dbIn    ? dbIn.value                   : (h1 ? h1.textContent.trim() : '');
    var username    = userIn  ? userIn.value                 : '';
    var statusColor = statBtn ? statBtn.style.backgroundColor : '#ccc';
    var formAction  = form.action  || '';
    var formMethod  = form.method  || 'post';
    var loginName   = loginBtn ? loginBtn.name  : 'button_login';
    var loginVal    = loginBtn ? loginBtn.value : 'connect';
    var discoName   = discoBtn ? discoBtn.name  : 'button_disco';
    var discoVal    = discoBtn ? discoBtn.value : 'disco';

    header.innerHTML = [
      '<div class="mb-header-inner">',
      '  <div class="mb-header-left">',
      '    <button type="button" class="mb-nav-toggle-header" id="mbHeaderToggle" aria-label="Toggle sidebar">',
      '      <i class="ti ti-layout-sidebar-left-collapse" id="mbHeaderToggleIcon"></i>',
      '    </button>',
      '    <div class="mb-header-titles">',
      '      <div class="mb-header-subtitle">' + pageTitle + '</div>',
      '      <div class="mb-header-db">' + dbName + '</div>',
      '    </div>',
      '  </div>',
      '  <div class="mb-header-right">',
      '    <button type="button" class="mb-user-btn" id="mbUserBtn">',
      '      <span class="mb-status-dot" style="background-color:' + statusColor + '"></span>',
      '      <i class="ti ti-user" aria-hidden="true"></i>',
      '      <span class="mb-username-label">' + (username || 'sign in') + '</span>',
      '      <i class="ti ti-chevron-down" aria-hidden="true"></i>',
      '    </button>',
      '  </div>',
      '  <div class="mb-dropdown" id="mbDropdown">',
      '    <form action="' + formAction + '" method="' + formMethod + '">',
      '      <input type="hidden" name="dbname" value="' + (dbIn ? dbIn.value : '') + '">',
      '      <label class="mb-form-label" for="mbUserField">Username</label>',
      '      <input class="mb-form-input" type="text" id="mbUserField" name="xusername" value="' + username + '" autocomplete="username">',
      '      <label class="mb-form-label" for="mbPassField">Password</label>',
      '      <input class="mb-form-input" type="password" id="mbPassField" name="xpassword" autocomplete="current-password">',
      '      <div class="mb-dropdown-actions">',
      '        <button type="submit" name="' + loginName + '" value="' + loginVal + '" class="mb-btn-connect">',
      '          <i class="ti ti-plug-connected" aria-hidden="true"></i> connect',
      '        </button>',
      '        <button type="submit" name="' + discoName + '" value="' + discoVal + '" class="mb-btn-disco">',
      '          <i class="ti ti-plug" aria-hidden="true"></i> disco',
      '        </button>',
      '      </div>',
      '    </form>',
      '  </div>',
      '</div>'
    ].join('\n');

    /* Wire up header toggle */
    document.getElementById('mbHeaderToggle').addEventListener('click', toggleSidebar);

    /* Wire up dropdown */
    var userBtn  = document.getElementById('mbUserBtn');
    var dropdown = document.getElementById('mbDropdown');

    userBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      dropdown.classList.toggle('open');
    });
    dropdown.addEventListener('click', function (e) { e.stopPropagation(); });
    document.addEventListener('click', function () { dropdown.classList.remove('open'); });

    /* Sync toggle icon with nav state on init */
    var nav = document.getElementById('left_navmenu');
    if (nav) syncToggleIcons(nav.classList.contains('collapsed'));
  }

  /* ── Enhance sidebar nav ─────────────────────────── */
  function enhanceNav() {
    var nav = document.getElementById('left_navmenu');
    if (!nav) return;

    var forms     = nav.querySelectorAll('form');
    var lastGroup = undefined;

    forms.forEach(function (form) {
      var btn = form.querySelector('input.button[type=submit]');
      if (!btn) return;

      var text   = btn.value || '';
      var cfg    = navConfig(text);

      /* Insert group divider + label when group changes */
      if (cfg.group !== lastGroup) {
        if (lastGroup !== undefined) {
          var div = document.createElement('div');
          div.className = 'mb-nav-divider';
          nav.insertBefore(div, form);
        }
        if (cfg.group) {
          var lbl = document.createElement('div');
          lbl.className = 'mb-nav-label';
          lbl.textContent = cfg.group;
          nav.insertBefore(lbl, form);
        }
        lastGroup = cfg.group;
      }

      /* Replace input[type=submit] with button[type=submit] so icons can sit inside */
      var newBtn = document.createElement('button');
      newBtn.type      = 'submit';
      newBtn.className = 'button';
      newBtn.name      = btn.name || '';

      var tooltip = '<span class="mb-nav-tooltip">' + text + '</span>';
      newBtn.innerHTML = makeIcon(cfg.icon)
        + '<span class="mb-nav-text">' + text + '</span>'
        + tooltip;

      btn.parentNode.replaceChild(newBtn, btn);
    });

    /* Touch tap-and-hold tooltips */
    var touchTimer;
    nav.querySelectorAll('button[type=submit]').forEach(function (btn) {
      btn.addEventListener('touchstart', function () {
        touchTimer = setTimeout(function () {
          if (nav.classList.contains('collapsed')) {
            var tt = btn.querySelector('.mb-nav-tooltip');
            if (tt) {
              tt.classList.add('visible');
              setTimeout(function () { tt.classList.remove('visible'); }, 1600);
            }
          }
        }, 500);
      });
      btn.addEventListener('touchend',  function () { clearTimeout(touchTimer); });
      btn.addEventListener('touchmove', function () { clearTimeout(touchTimer); });
    });
  }

  /* ── Set default sidebar state ───────────────────── */
  function setDefaultSidebarState() {
    var nav = document.getElementById('left_navmenu');
    if (!nav) return;
    if (!isHomePage()) {
      nav.classList.add('collapsed');
      syncToggleIcons(true);
    }
  }

  /* ── Login page (databases.php) enhancement ──────── */
  function enhanceLoginPage() {
    if (!isHomePage()) return;
    var nav = document.getElementById('left_navmenu');
    if (nav && nav.querySelectorAll('form').length > 1) return; /* not databases.php */

    document.body.classList.add('mb-login-page');

    var right = document.getElementById('right_content');
    if (!right) return;

    /* Collect existing database buttons rendered by PHP */
    var dbForms = right.querySelectorAll('form[id="dbaccessform"], form');
    if (dbForms.length === 0) return;

    /* Build login card */
    var card = document.createElement('div');
    card.className = 'mb-login-card';

    var header = document.getElementById('header');
    var userIn = header ? header.querySelector('input[name="xusername"]') : null;
    var passIn = header ? header.querySelector('input[name="xpassword"]') : null;
    var username = userIn ? userIn.value : '';

    card.innerHTML = '<h2>Sign in</h2>'
      + '<p class="mb-login-sub">Enter your credentials to access your colony databases.</p>';

    /* Move database buttons into a grid inside the card */
    var dbLabel = document.createElement('div');
    dbLabel.className = 'mb-db-section-label';
    dbLabel.textContent = 'Your databases';

    var dbGrid = document.createElement('div');
    dbGrid.className = 'mb-db-grid';

    dbForms.forEach(function (f) {
      var submitBtn = f.querySelector('input[type=submit], button[type=submit]');
      if (!submitBtn) return;
      var label = submitBtn.value || submitBtn.textContent || 'database';
      var newBtn = document.createElement('button');
      newBtn.className = 'mb-db-btn';
      newBtn.type = 'submit';
      newBtn.innerHTML = '<i class="ti ti-paw" style="display:block;font-size:20px;margin-bottom:4px" aria-hidden="true"></i>' + label;
      /* Clone hidden inputs into a wrapping form */
      var wrapForm = document.createElement('form');
      wrapForm.method = f.method || 'post';
      wrapForm.action = f.action || '';
      f.querySelectorAll('input[type=hidden]').forEach(function (inp) {
        wrapForm.appendChild(inp.cloneNode());
      });
      wrapForm.appendChild(newBtn);
      dbGrid.appendChild(wrapForm);
    });

    card.appendChild(dbLabel);
    card.appendChild(dbGrid);

    /* Clear and replace content */
    right.innerHTML = '';
    right.appendChild(card);
  }

  /* ── Boot ────────────────────────────────────────── */
  document.addEventListener('DOMContentLoaded', function () {
    setDefaultSidebarState();
    // Load icons then run all enhancements.
    // If CDN unreachable, enhancements still run after 3s timeout —
    // nav buttons show text labels only (no icons).
    loadTablerIcons(function () {
      enhanceHeader();
      enhanceNav();
      enhanceLoginPage();
    });
  });

})();
