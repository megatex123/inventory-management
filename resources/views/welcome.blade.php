<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link href="{{asset('backend')}}/img/logo/logo.png" rel="icon">
  <title>Quivitech - Dashboard</title>
  <link href="{{mix('css/app.css')}}" rel="stylesheet">
  <link href="{{asset('backend')}}/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="{{asset('backend')}}/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
  <link href="{{asset('backend')}}/css/ruang-admin.min.css" rel="stylesheet">

  <style>
    /* FIX: Sidebar Shrink Issue */
    .sidebar {
      transition: width 0.2s;
    }

    /* When toggled, ensure it doesn't disappear and keeps a slim width */
    .sidebar.toggled {
      width: 100px !important;
      overflow: visible !important;
    }

    /* Hide text labels only when toggled to prevent overlapping */
    .sidebar.toggled .nav-item .nav-link span {
      display: none;
    }

    /* Keep icons centered when shrunk */
    .sidebar.toggled .nav-item .nav-link {
      text-align: center;
      padding: 0.75rem 1rem;
      width: 100px !important;
    }

    /* Fix the logo area when shrunk */
    .sidebar.toggled .sidebar-brand .sidebar-brand-text {
      display: none;
    }

    /* Ensure content adjusts */
    body.sidebar-toggled #content-wrapper {
      width: 100%;
    }

    /* Fix collapsed menu items spacing */
    .sidebar.toggled .nav-item .collapse {
      position: absolute;
      left: 100px;
      top: 0;
      z-index: 1;
      min-width: 200px;
    }

    /* Ensure dropdown arrows are visible */
    .sidebar .nav-link .fas.fa-fw {
      margin-right: 0.5rem;
    }

    .sidebar.toggled .nav-link .fas.fa-fw {
      margin-right: 0;
    }
  </style>
</head>

<body id="page-top">
  <div id="app">
    <div id="wrapper">
      <ul class="navbar-nav sidebar sidebar-light accordion" v-if="!['/', '/register', '/forget'].includes($route.path)" id="accordionSidebar">
        <a class="sidebar-brand d-flex align-items-center justify-content-center" href="/dashboard">
          <div class="sidebar-brand-icon">
            <img src="{{asset('backend')}}/img/logo/logo.png">
          </div>
          <div class="sidebar-brand-text mx-3">Quivitech</div>
        </a>
        <hr class="sidebar-divider my-0">

        @foreach($sidebarMenu as $item)
          @include('partials.sidebar-menu-item', ['item' => $item])
          @if($item->type === 'link')
            <hr class="sidebar-divider">
            <div class="sidebar-heading">Features</div>
          @endif
        @endforeach

        <hr class="sidebar-divider">
        <div class="version" style="padding:10px; font-size: 10px;">Version {{ env('SOFTWAREVERSION')}} <br>By Enigma Code Solution</div>
      </ul>

      <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
          <nav v-if="!['/', '/register', '/forget'].includes($route.path)" class="navbar navbar-expand navbar-light bg-navbar topbar mb-4 static-top">
            <button id="sidebarToggleTop" class="btn btn-link rounded-circle mr-3">
              <i class="fa fa-bars"></i>
            </button>
            <ul class="navbar-nav ml-auto">
              <div class="topbar-divider d-none d-sm-block"></div>
              <li class="nav-item dropdown no-arrow">
                <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown">
                  <img class="img-profile rounded-circle" src="{{asset('backend')}}/img/boy.png" style="max-width: 60px">
                  <span class="ml-2 d-none d-lg-inline text-white small">Admin</span>
                </a>
                <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in">
                  <a class="dropdown-item" href="#" id="logout-trigger">
                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                    Logout
                  </a>
                </div>
              </li>
            </ul>
          </nav>

          <div class="container-fluid" id="container-wrapper">
            <router-view></router-view>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="{{asset('backend')}}/vendor/jquery/jquery.min.js"></script>
  <script src="{{asset('backend')}}/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="{{asset('backend')}}/vendor/jquery-easing/jquery.easing.min.js"></script>
  <script src="{{asset('backend')}}/js/ruang-admin.min.js"></script>
  <script src="{{mix('js/app.js')}}"></script>

  <script>
    document.addEventListener('DOMContentLoaded', function() {

      // 1. FIX SIDEBAR TOGGLE LOGIC
      const sidebarToggle = document.getElementById('sidebarToggleTop');
      const sidebar = document.getElementById('accordionSidebar');

      if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function(e) {
          e.preventDefault();
          document.body.classList.toggle('sidebar-toggled');
          sidebar.classList.toggle('toggled');
        });
      }

      // 2. FIX LOGOUT (JWT TOKEN PARSE ERROR)
      const logoutBtn = document.getElementById('logout-trigger');
      if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
          e.preventDefault();

          // Get token from LocalStorage (standard for JWT setups)
          const token = localStorage.getItem('token');

          // Send AJAX logout to server with Token in Header
          fetch('/api/auth/logout', {
            method: 'POST',
            headers: {
              'Authorization': 'Bearer ' + token,
              'Accept': 'application/json',
              'Content-Type': 'application/json'
            }
          }).finally(() => {
            // Clear all client-side state regardless of server response, so
            // no stale session data or cached page content survives a logout.
            localStorage.clear();
            sessionStorage.clear();

            if (window.caches && caches.keys) {
              caches.keys().then(function(names) {
                names.forEach(function(name) { caches.delete(name); });
              });
            }

            // location.replace (not href) so logout doesn't leave a
            // back-button entry pointing at the now-logged-out page.
            window.location.replace("/");
          });
        });
      }

      // 3. FIX BOOTSTRAP COLLAPSE INITIALIZATION
      function initBootstrapComponents() {
        // Initialize all dropdowns
        $('.dropdown-toggle').dropdown();

        // Reinitialize collapse functionality. Each group's target div
        // already carries data-parent="#accordionSidebar" (see
        // partials/sidebar-menu-item.blade.php), so Bootstrap's own collapse
        // plugin closes sibling groups automatically as part of show() --
        // do NOT also close siblings manually here. Bootstrap's show()
        // refuses to open a target if it finds an "active" (.show or
        // .collapsing) sibling within the same data-parent that is still
        // mid-transition; a manual hide() on the sibling immediately before
        // toggling the target puts that sibling into exactly that transition
        // state, so the target's open would be silently dropped and require
        // a second click to actually show. Toggling just the target and
        // letting Bootstrap's own accordion logic close the rest avoids the
        // race entirely.
        $('[data-toggle="collapse"]').off('click.collapse').on('click.collapse', function(e) {
          e.preventDefault();
          e.stopPropagation();
          $($(this).data('target')).collapse('toggle');
        });

        // Ensure all collapse elements are properly initialized
        $('.collapse').each(function() {
          const $this = $(this);
          if (!$this.data('bs.collapse')) {
            $this.collapse({
              toggle: false
            });
          }
        });
      }

      // 3b. AUTO-EXPAND THE SIDEBAR GROUP FOR THE CURRENT PAGE
      // Longest-prefix match: currentPath must equal a nav link's href, or
      // be a sub-path of it (e.g. "/refunds/edit/5" under a "/refunds"
      // link) -- picks the most specific link if more than one matches.
      function findActiveMenuCollapse() {
        const currentPath = window.location.pathname;
        let bestLink = null;
        let bestLength = -1;

        document.querySelectorAll('#accordionSidebar a.collapse-item[href]').forEach(function(link) {
          const href = link.getAttribute('href');
          if (!href || href === '/') return;
          const isMatch = currentPath === href || currentPath.indexOf(href + '/') === 0;
          if (isMatch && href.length > bestLength) {
            bestLink = link;
            bestLength = href.length;
          }
        });

        return bestLink ? bestLink.closest('.collapse') : null;
      }

      function expandActiveMenu() {
        const collapseEl = findActiveMenuCollapse();
        if (collapseEl && !collapseEl.classList.contains('show')) {
          $(collapseEl).collapse('show');
        }
      }

      // Initial call
      initBootstrapComponents();
      expandActiveMenu();

      // 4. RE-INIT BOOTSTRAP FOR VUE ROUTES
      const appElement = document.getElementById('app');
      if (appElement && appElement.__vue__ && appElement.__vue__.$router) {
        appElement.__vue__.$router.afterEach((to, from) => {
          // Small delay to ensure DOM updates
          setTimeout(() => {
            initBootstrapComponents();

            // Opening the new page's group is enough on its own: Bootstrap's
            // native data-parent="#accordionSidebar" accordion behavior
            // closes whichever other group was open as an automatic part of
            // showing this one (see the click-handler comment above for why
            // closing it manually first would instead block the open). If
            // the new page doesn't belong to any group, nothing here forces
            // the previous one shut, which is harmless.
            expandActiveMenu();
          }, 100);
        });
      }

      // 5. Handle sidebar collapse state persistence
      const sidebarState = localStorage.getItem('sidebar_toggled');
      if (sidebarState === 'true') {
        document.body.classList.add('sidebar-toggled');
        sidebar.classList.add('toggled');
      }

      sidebarToggle.addEventListener('click', function() {
        const isToggled = sidebar.classList.contains('toggled');
        localStorage.setItem('sidebar_toggled', isToggled);
      });
    });
  </script>
</body>
</html>
