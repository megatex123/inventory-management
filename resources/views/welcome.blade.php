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
            // Clear local storage and redirect regardless of server response
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            window.location.href = "/";
          });
        });
      }

      // 3. FIX BOOTSTRAP COLLAPSE INITIALIZATION
      function initBootstrapComponents() {
        // Initialize all dropdowns
        $('.dropdown-toggle').dropdown();

        // Reinitialize collapse functionality
        $('[data-toggle="collapse"]').off('click.collapse').on('click.collapse', function(e) {
          e.preventDefault();
          e.stopPropagation();

          const target = $(this).data('target');
          const $target = $(target);

          // Close other collapses in the same accordion if needed
          const $parent = $(this).closest('.accordion');
          if ($parent.length) {
            const $siblings = $parent.find('.collapse.show');
            $siblings.not(target).collapse('hide');
          }

          // Toggle current collapse
          $target.collapse('toggle');
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

      // Initial call
      initBootstrapComponents();

      // 4. RE-INIT BOOTSTRAP FOR VUE ROUTES
      const appElement = document.getElementById('app');
      if (appElement && appElement.__vue__ && appElement.__vue__.$router) {
        appElement.__vue__.$router.afterEach((to, from) => {
          // Small delay to ensure DOM updates
          setTimeout(() => {
            initBootstrapComponents();

            // Close any open collapses on route change if needed
            if (from.path !== to.path) {
              $('.collapse.show').collapse('hide');
            }
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
