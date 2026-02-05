<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="">
  <meta name="author" content="">
  <link href="{{asset('backend')}}/img/logo/logo.png" rel="icon">
  <title>Quivitech - Dashboard</title>
  <link href="{{asset('css/app.css')}}" rel="stylesheet">

  <link href="{{asset('backend')}}/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="{{asset('backend')}}/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
  <link href="{{asset('backend')}}/css/ruang-admin.min.css" rel="stylesheet">
</head>

<body id="page-top">
  <div id="app">
  <div id="wrapper">
    <!-- Sidebar -->
    <ul class="navbar-nav sidebar sidebar-light accordion" v-if="$route.meta.layout === 'app'" id="accordionSidebar">
      <a class="sidebar-brand d-flex align-items-center justify-content-center" href="#">
        <div class="sidebar-brand-icon">
          <img src="{{asset('backend')}}/img/logo/logo2.png">
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
      <div class="sidebar-heading">
        Features
      </div>

      <!-- Suppliers Menu -->
      <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#Suppliers"
          aria-expanded="true" aria-controls="Suppliers">
          <i class="fas fa-fw fa-truck-loading"></i>
          <span>Suppliers</span>
        </a>
        <div id="Suppliers" class="collapse" aria-labelledby="headingBootstrap" data-parent="#accordionSidebar">
          <div class="bg-white py-2 collapse-inner rounded">
            <h6 class="collapse-header text-primary font-weight-bold">Supplier Management</h6>
            <router-link class="collapse-item" to="/suppliers">All Suppliers</router-link>
            <router-link class="collapse-item" to="/supplier/create">Add Supplier</router-link>
          </div>
        </div>
      </li>

      <!-- Category Menu -->
      <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#category"
          aria-expanded="true" aria-controls="category">
          <i class="fas fa-fw fa-boxes"></i>
          <span>Category</span>
        </a>
        <div id="category" class="collapse" aria-labelledby="headingBootstrap" data-parent="#accordionSidebar">
          <div class="bg-white py-2 collapse-inner rounded">
            <h6 class="collapse-header text-primary font-weight-bold">Category Management</h6>
            <router-link class="collapse-item" to="/category">Category Lookup</router-link>
            <router-link class="collapse-item" to="/category/create">Add Category Lookup</router-link>

            <hr class="sidebar-divider my-1">

            <h6 class="collapse-header text-primary font-weight-bold">Sub Category Management</h6>
            <router-link class="collapse-item" to="/sub-category">Sub Category Lookup</router-link>
            <router-link class="collapse-item" to="/sub-category/create">Add Sub Category Lookup</router-link>
          </div>
        </div>
      </li>

      <!-- QuiviCraft Menu -->
      <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#craft"
          aria-expanded="true" aria-controls="craft">
          <i class="fas fa-fw fa-tools"></i>
          <span>QuiviCraft</span>
        </a>
        <div id="craft" class="collapse" aria-labelledby="headingBootstrap" data-parent="#accordionSidebar">
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

      <!-- QuiviServe Menu -->
      <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#serve"
          aria-expanded="true" aria-controls="serve">
          <i class="fas fa-fw fa-hammer"></i>
          <span>QuiviServe</span>
        </a>
        <div id="serve" class="collapse" aria-labelledby="headingBootstrap" data-parent="#accordionSidebar">
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

      <!-- QuiviCare Menu -->
      <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#care"
          aria-expanded="true" aria-controls="care">
          <i class="fas fa-fw fa-stethoscope"></i>
          <span>QuiviCare</span>
        </a>
        <div id="care" class="collapse" aria-labelledby="headingBootstrap" data-parent="#accordionSidebar">
          <div class="bg-white py-2 collapse-inner rounded">
            <h6 class="collapse-header text-primary font-weight-bold">QuiviCare Records</h6>
            <router-link class="collapse-item" to="/care-data">All QuiviCare</router-link>
            <router-link class="collapse-item" to="/care-data/create">Add QuiviCare</router-link>

            <hr class="sidebar-divider my-1">

            <h6 class="collapse-header text-primary font-weight-bold">Lookup Tables</h6>
            <router-link class="collapse-item" to="/care">QuiviCare Lookup</router-link>
            <router-link class="collapse-item" to="/care/create">Add QuiviCare Lookup</router-link>
          </div>
        </div>
      </li>

      <!-- Product Menu -->
      <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#Product"
          aria-expanded="true" aria-controls="Product">
          <i class="fas fa-fw fa-truck"></i>
          <span>Product</span>
        </a>
        <div id="Product" class="collapse" aria-labelledby="headingBootstrap" data-parent="#accordionSidebar">
          <div class="bg-white py-2 collapse-inner rounded">
            <h6 class="collapse-header text-primary font-weight-bold">Product Management</h6>
            <router-link class="collapse-item" to="/product">All Products</router-link>
            <router-link class="collapse-item" to="/product/create">Add Product</router-link>

            <hr class="sidebar-divider my-1">

            <h6 class="collapse-header text-primary font-weight-bold">Stock Management</h6>
            <router-link class="collapse-item" to="/product/stock">All Stock</router-link>
          </div>
        </div>
      </li>

      <!-- Customer Menu -->
      <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#Customer"
          aria-expanded="true" aria-controls="Customer">
          <i class="fas fa-users"></i>
          <span>Customer</span>
        </a>
        <div id="Customer" class="collapse" aria-labelledby="headingBootstrap" data-parent="#accordionSidebar">
          <div class="bg-white py-2 collapse-inner rounded">
            <h6 class="collapse-header text-primary font-weight-bold">Customer Management</h6>
            <router-link class="collapse-item" to="/customer">Customer List</router-link>
            <router-link class="collapse-item" to="/customer/create">Pre Register Customer</router-link>
          </div>
        </div>
      </li>

      <!-- Meeting Menu -->
      <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#Meeting"
          aria-expanded="true" aria-controls="Meeting">
          <i class="fas fa-calendar-alt"></i>
          <span>Meeting</span>
        </a>
        <div id="Meeting" class="collapse" aria-labelledby="headingBootstrap" data-parent="#accordionSidebar">
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

      <!-- Orders Menu -->
      <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#Orders"
          aria-expanded="true" aria-controls="Orders">
          <i class="fa fa-check-circle"></i>
          <span>Orders</span>
        </a>
        <div id="Orders" class="collapse" aria-labelledby="headingBootstrap" data-parent="#accordionSidebar">
          <div class="bg-white py-2 collapse-inner rounded">
            <h6 class="collapse-header text-primary font-weight-bold">Order Management</h6>
            <router-link class="collapse-item" to="/orders/all">Order List</router-link>
            <router-link class="collapse-item" to="/orders">Today's Orders</router-link>
          </div>
        </div>
      </li>

      <hr class="sidebar-divider">
      <div class="version" id="version-ruangadmin"></div>
    </ul>
    <!-- Sidebar -->

    <div id="content-wrapper" class="d-flex flex-column">
      <div id="content">
        <!-- TopBar -->
        <nav v-if="$route.meta.layout === 'app'" class="navbar navbar-expand navbar-light bg-navbar topbar mb-4 static-top">
          <button id="sidebarToggleTop" class="btn btn-link rounded-circle mr-3">
            <i class="fa fa-bars"></i>
          </button>
          <ul class="navbar-nav ml-auto">

            <div class="topbar-divider d-none d-sm-block"></div>
            <li class="nav-item dropdown no-arrow">
              <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown"
                aria-haspopup="true" aria-expanded="false">
                <img class="img-profile rounded-circle" src="{{asset('backend')}}/img/boy.png" style="max-width: 60px">
                <span class="ml-2 d-none d-lg-inline text-white small">Admin</span>
              </a>
              <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                <router-link class="dropdown-item" to="/logout">
                  <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                  Logout
                </router-link>
              </div>
            </li>
          </ul>
        </nav>
        <!-- Topbar -->

        <!-- Container Fluid-->
        <div class="container-fluid" id="container-wrapper">
            <router-view></router-view>
        </div>
        <!---Container Fluid-->
      </div>
    </div>
  </div>
  </div>

  <!-- Scroll to top -->
  <a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
  </a>

  <script src="{{asset('js/app.js')}}"></script>
  <script src="{{asset('backend')}}/vendor/jquery/jquery.min.js"></script>
  <script src="{{asset('backend')}}/vendor/jquery-easing/jquery.easing.min.js"></script>
  <script src="{{asset('backend')}}/js/ruang-admin.min.js"></script>
</body>
</html>
