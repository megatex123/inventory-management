<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link href="{{asset('backend')}}/img/logo/logo.png" rel="icon">
  <title>Quivitech - Dashboard</title>
  <link href="{{asset('css/app.css')}}" rel="stylesheet">
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

        <li class="nav-item active">
          <router-link class="nav-link" to="/dashboard">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span>
          </router-link>
        </li>

        <hr class="sidebar-divider">
        <div class="sidebar-heading">Features</div>

        <li class="nav-item">
          <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#Orders" aria-expanded="true" aria-controls="Orders">
            <i class="fa fa-check-circle"></i>
            <span>Orders</span>
          </a>
          <div id="Orders" class="collapse" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
              <h6 class="collapse-header text-primary font-weight-bold">Order Management</h6>
              <router-link class="collapse-item" to="/pos">Create Orders</router-link>
              <router-link class="collapse-item" to="/orders">Today's Orders</router-link>
              <router-link class="collapse-item" to="/orders/all">Order List</router-link>
            </div>
          </div>
        </li>

        <li class="nav-item">
          <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#Customer" aria-expanded="true" aria-controls="Customer">
            <i class="fas fa-users"></i>
            <span>Customer</span>
          </a>
          <div id="Customer" class="collapse" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
              <h6 class="collapse-header text-primary font-weight-bold">Customer Management</h6>
              <router-link class="collapse-item" to="/customer">Customer List</router-link>
              <router-link class="collapse-item" to="/customer/create">Pre Register Customer</router-link>
            </div>
          </div>
        </li>

        <li class="nav-item">
          <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#Meeting" aria-expanded="true" aria-controls="Meeting">
            <i class="fas fa-calendar-alt"></i>
            <span>Meeting</span>
          </a>
          <div id="Meeting" class="collapse" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
              <h6 class="collapse-header text-primary font-weight-bold">Meeting Management</h6>
              <router-link class="collapse-item" to="/meeting">Meeting List</router-link>
              <router-link class="collapse-item" to="/meeting/create">Create Meeting</router-link>

              <hr class="sidebar-divider my-1">

              <h6 class="collapse-header text-primary font-weight-bold">Meeting Details</h6>
              <router-link class="collapse-item" to="/meeting-details">Meeting Detail List</router-link>
              <router-link class="collapse-item" to="/meeting-details/create">Create Meeting Detail</router-link>
            </div>
          </div>
        </li>

        <li class="nav-item">
          <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#craft" aria-expanded="true" aria-controls="craft">
            <i class="fas fa-fw fa-tools"></i>
            <span>QuiviCraft</span>
          </a>
          <div id="craft" class="collapse" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
              <h6 class="collapse-header text-primary font-weight-bold">QuiviCraft Operations</h6>
              <router-link class="collapse-item" to="/pos">Create QuiviCraft</router-link>
              <router-link class="collapse-item" to="/orders/all">Order QuiviCraft</router-link>

              <hr class="sidebar-divider my-1">

              <h6 class="collapse-header text-primary font-weight-bold">Lookup Tables</h6>
              <router-link class="collapse-item" to="/craft">QuiviCraft Lookup</router-link>
              <router-link class="collapse-item" to="/craft/create">Add QuiviCraft Lookup</router-link>
            </div>
          </div>
        </li>

        <li class="nav-item">
          <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#category" aria-expanded="true" aria-controls="category">
            <i class="fas fa-fw fa-boxes"></i>
            <span>Category</span>
          </a>
          <div id="category" class="collapse" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
              <h6 class="collapse-header text-primary font-weight-bold">Category Management</h6>
              <router-link class="collapse-item" to="/category">Code Lookup</router-link>
              <router-link class="collapse-item" to="/category/create">Add Code Lookup</router-link>

              <hr class="sidebar-divider my-1">

              <h6 class="collapse-header text-primary font-weight-bold">Sub Category Management</h6>
              <router-link class="collapse-item" to="/sub-category">Sub Code Lookup</router-link>
              <router-link class="collapse-item" to="/sub-category/create">Add Sub Code Lookup</router-link>
            </div>
          </div>
        </li>

        <li class="nav-item">
          <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#serve" aria-expanded="true" aria-controls="serve">
            <i class="fas fa-fw fa-hammer"></i>
            <span>QuiviServe</span>
          </a>
          <div id="serve" class="collapse" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
              <h6 class="collapse-header text-primary font-weight-bold">QuiviServe Records</h6>
              <router-link class="collapse-item" to="/serve-data">All QuiviServe</router-link>
              <router-link class="collapse-item" to="/serve-data/create">Add QuiviServe</router-link>

              <hr class="sidebar-divider my-1">

              <h6 class="collapse-header text-primary font-weight-bold">QuiviServe BEK</h6>
              <router-link class="collapse-item" to="/serve-bek">All QuiviServe BEK</router-link>
              <router-link class="collapse-item" to="/serve-bek/create">Add QuiviServe BEK</router-link>

              <hr class="sidebar-divider my-1">

              <h6 class="collapse-header text-primary font-weight-bold">QuiviServe MPS</h6>
              <router-link class="collapse-item" to="/serve-mps">All QuiviServe MPS</router-link>
              <router-link class="collapse-item" to="/serve-mps/create">Add QuiviServe MPS</router-link>

              <hr class="sidebar-divider my-1">

              <h6 class="collapse-header text-primary font-weight-bold">QuiviServe PCE</h6>
              <router-link class="collapse-item" to="/serve-pce">All QuiviServe PCE</router-link>
              <router-link class="collapse-item" to="/serve-pce/create">Add QuiviServe PCE</router-link>

              <hr class="sidebar-divider my-1">

              <h6 class="collapse-header text-primary font-weight-bold">Lookup Tables</h6>
              <router-link class="collapse-item" to="/serve">QuiviServe Lookup</router-link>
              <router-link class="collapse-item" to="/serve/create">Add QuiviServe Lookup</router-link>
            </div>
          </div>
        </li>

        <li class="nav-item">
          <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#care" aria-expanded="true" aria-controls="care">
            <i class="fas fa-fw fa-stethoscope"></i>
            <span>QuiviCare</span>
          </a>
          <div id="care" class="collapse" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
              <h6 class="collapse-header text-primary font-weight-bold">QuiviCare Records</h6>
              <router-link class="collapse-item" to="/care-data">All QuiviCare</router-link>
              <router-link class="collapse-item" to="/care-data/create">Add QuiviCare</router-link>

              <hr class="sidebar-divider my-1">

              <h6 class="collapse-header text-primary font-weight-bold">QuiviCare Warranties</h6>
              <router-link class="collapse-item" to="/care-warranty">All QuiviCare Warranties</router-link>
              <router-link class="collapse-item" to="/care-warranty/create">Add QuiviCare Warranties</router-link>

              <hr class="sidebar-divider my-1">

              <h6 class="collapse-header text-primary font-weight-bold">Lookup Tables</h6>
              <router-link class="collapse-item" to="/care">QuiviCare Lookup</router-link>
              <router-link class="collapse-item" to="/care/create">Add QuiviCare Lookup</router-link>
            </div>
          </div>
        </li>

        <li class="nav-item">
          <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#Product" aria-expanded="true" aria-controls="Product">
            <i class="fas fa-fw fa-truck"></i>
            <span>Inventory</span>
          </a>
          <div id="Product" class="collapse" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
              <h6 class="collapse-header text-primary font-weight-bold">Product Management</h6>
              <router-link class="collapse-item" to="/product">All Products</router-link>
              <router-link class="collapse-item" to="/product/create">Add Product</router-link>

              <hr class="sidebar-divider my-1">

              <h6 class="collapse-header text-primary font-weight-bold">Product Warranty <br> Management</h6>
              <router-link class="collapse-item" to="/product-warranty">All Products Warranty</router-link>
              <router-link class="collapse-item" to="/product-warranty/create">Add Product Warranty</router-link>

              <hr class="sidebar-divider my-1">

              <h6 class="collapse-header text-primary font-weight-bold">Stock Management</h6>
              <router-link class="collapse-item" to="/product/stock">All Stock</router-link>

              <h6 class="collapse-header text-primary font-weight-bold">Product Brand <br> Management</h6>
              <router-link class="collapse-item" to="/brand">All Products Brand</router-link>
              <router-link class="collapse-item" to="/brand/create">Add Product Brand</router-link>
            </div>
          </div>
        </li>

        <li class="nav-item">
          <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#Suppliers" aria-expanded="true" aria-controls="Suppliers">
            <i class="fas fa-fw fa-truck-loading"></i>
            <span>Suppliers</span>
          </a>
          <div id="Suppliers" class="collapse" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
              <h6 class="collapse-header text-primary font-weight-bold">Supplier Management</h6>
              <router-link class="collapse-item" to="/suppliers">All Suppliers</router-link>
              <router-link class="collapse-item" to="/supplier/create">Add Supplier</router-link>
            </div>
          </div>
        </li>

        <hr class="sidebar-divider">
        <div class="version" style="padding:10px; font-size: 10px;">Version 0.0.4 <br>By Enigma Code Solution</div>
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
  <script src="{{asset('js/app.js')}}"></script>

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
