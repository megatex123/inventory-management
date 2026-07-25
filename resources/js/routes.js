let login = require('./components/auth/login').default;
let register = require('./components/auth/register').default;
let forget = require('./components/auth/forget').default;
let logout = require('./components/auth/logout').default;
//end auth-- ---

//employee
let createEmployee = require('./components/employee/create').default;
let employees = require('./components/employee/index').default;
let employeesedit = require('./components/employee/edit').default;

//employee
let createsuppliers = require('./components/suppliers/create').default;
let suppliers = require('./components/suppliers/index').default;
let suppliersedit = require('./components/suppliers/edit').default;

//Category
let createCategory = require('./components/category/create').default;
let Category = require('./components/category/index').default;
let Categoryedit = require('./components/category/edit').default;

//Brand
let createBrand = require('./components/brand/create').default;
let Brand = require('./components/brand/index').default;
let Brandedit = require('./components/brand/edit').default;

//SubCategory
let createSubCategory = require('./components/sub_category/create').default;
let SubCategory = require('./components/sub_category/index').default;
let SubCategoryedit = require('./components/sub_category/edit').default;

//Craft
let createCraft = require('./components/craft/create').default;
let Craft = require('./components/craft/index').default;
let Craftedit = require('./components/craft/edit').default;

//Serve
let createServe = require('./components/serve/create').default;
let Serve = require('./components/serve/index').default;
let Serveedit = require('./components/serve/edit').default;

//Care
let createCare = require('./components/care/create').default;
let Care = require('./components/care/index').default;
let Careedit = require('./components/care/edit').default;

//Products
let createProduct = require('./components/product/create').default;
let Product = require('./components/product/index').default;
let Productedit = require('./components/product/edit').default;
let stock = require('./components/stock/index').default;
let stockedit = require('./components/stock/edit').default;


//expens
let createexpens = require('./components/expens/create').default;
let expens = require('./components/expens/index').default;
let expensedit = require('./components/expens/edit').default;



//expens
let salarypay = require('./components/salary/allemp').default;
let paysalary = require('./components/salary/create').default;
let salary = require('./components/salary/index').default;
let viewsalary = require('./components/salary/viewsalary').default;


//Customer
let createCustomer = require('./components/customer/create').default;
let customer = require('./components/customer/index').default;
let customeredit = require('./components/customer/edit').default;
let customerRegister = require('./components/auth/register_customer_luar').default;

//orders
let todayorder = require('./components/order/order').default;
let allorder = require('./components/order/allorder').default;
let vieworder = require('./components/order/view').default;
let editorder = require('./components/order/edit').default;

//POS
let pos = require('./components/pos/index').default;

// dashboard
let home = require('./components/home').default;

//meeting
let meeting = require('./components/meeting/index').default;
let createmeeting = require('./components/meeting/create.vue').default;
let meetingedit = require('./components/meeting/edit.vue').default;

//meetingdetails
let meetingdetails = require('./components/meeting_details/index').default;
let createmeetingdetails = require('./components/meeting_details/create.vue').default;
let meetingdetailsedit = require('./components/meeting_details/edit.vue').default;

//uatmeeting
let uatmeeting = require('./components/uat_meeting/index').default;
let createuatmeeting = require('./components/uat_meeting/create.vue').default;
let uatmeetingedit = require('./components/uat_meeting/edit.vue').default;

//servedata
let servedata = require('./components/serve_data/index').default;
let createservedata = require('./components/serve_data/create.vue').default;
let servedataedit = require('./components/serve_data/edit.vue').default;

//servepce
let servepce = require ('./components/serve_pce/index.vue').default;
let servepcecreate = require ('./components/serve_pce/create.vue').default;
let servepceedit = require ('./components/serve_pce/edit.vue').default;

//caredata
let caredata = require('./components/care_data/index').default;
let createcaredata = require('./components/care_data/create.vue').default;
let caredataedit = require('./components/care_data/edit.vue').default;

//serveemps
let servemps = require ('./components/serve_mps/index.vue').default;
let servempscreate = require ('./components/serve_mps/create.vue').default;
let servempsedit = require ('./components/serve_mps/edit.vue').default;

//servebek
let servebek = require ('./components/serve_bek/index.vue').default;
let servebekcreate = require ('./components/serve_bek/create.vue').default;
let servebekedit = require ('./components/serve_bek/edit.vue').default;

//carewarranty
let carewarranty = require ('./components/care_warranty/index.vue').default;
let carewarrantycreate = require ('./components/care_warranty/create.vue').default;
let carewarrantyedit = require ('./components/care_warranty/edit.vue').default;

//master sku
let mastersku = require('./components/master_sku/index.vue').default;
let masterskucreate = require('./components/master_sku/create.vue').default;
let masterskuedit = require('./components/master_sku/edit.vue').default;

//inv care
let invcare = require('./components/inv_care/index.vue').default;
let invcarecreate = require('./components/inv_care/create.vue').default;
let invcareedit = require('./components/inv_care/edit.vue').default;

//inv excl serve
let invexclserve = require('./components/inv_excl_serve/index.vue').default;
let invexclservecreate = require('./components/inv_excl_serve/create.vue').default;
let invexclserveedit = require('./components/inv_excl_serve/edit.vue').default;

//merch items
let merchitems = require('./components/merch_items/index.vue').default;
let merchitemscreate = require('./components/merch_items/create.vue').default;
let merchitemsedit = require('./components/merch_items/edit.vue').default;

//inv merch
let invmerch = require('./components/inv_merch/index.vue').default;
let invmerchcreate = require('./components/inv_merch/create.vue').default;
let invmerchedit = require('./components/inv_merch/edit.vue').default;

//inv excl merch
let invexclmerch = require('./components/inv_excl_merch/index.vue').default;
let invexclmerchcreate = require('./components/inv_excl_merch/create.vue').default;
let invexclmerchedit = require('./components/inv_excl_merch/edit.vue').default;

//merch orders
let merchorders = require('./components/merch_orders/index.vue').default;
let merchorderscreate = require('./components/merch_orders/create.vue').default;
let merchordersedit = require('./components/merch_orders/edit.vue').default;

//plus services
let plusservices = require('./components/plus_services/index.vue').default;
let plusservicescreate = require('./components/plus_services/create.vue').default;
let plusservicesedit = require('./components/plus_services/edit.vue').default;

//plus orders
let plusorders = require('./components/plus_orders/index.vue').default;
let plusorderscreate = require('./components/plus_orders/create.vue').default;
let plusordersedit = require('./components/plus_orders/edit.vue').default;

//thread bom
let threadbom = require('./components/thread_bom/index.vue').default;
let threadbomcreate = require('./components/thread_bom/create.vue').default;
let threadbomedit = require('./components/thread_bom/edit.vue').default;

//inv thread
let invthread = require('./components/inv_thread/index.vue').default;
let invthreadcreate = require('./components/inv_thread/create.vue').default;
let invthreadedit = require('./components/inv_thread/edit.vue').default;

//thread orders
let threadorders = require('./components/thread_orders/index.vue').default;
let threadorderscreate = require('./components/thread_orders/create.vue').default;
let threadordersedit = require('./components/thread_orders/edit.vue').default;

//inventory movement
let inventorymovement = require('./components/inventory_movement/index.vue').default;
let inventorymovementcreate = require('./components/inventory_movement/create.vue').default;
let inventorymovementedit = require('./components/inventory_movement/edit.vue').default;

//craft inspection
let craftinspection = require('./components/craft_inspection/index.vue').default;
let performancetest = require('./components/performance_test/index.vue').default;

//customer progress management
let customer_progress_index = require('./components/customer_progress/index.vue').default;
let customer_progress_create = require('./components/customer_progress/create.vue').default;
let customer_progress_edit = require('./components/customer_progress/edit.vue').default;

export const routes=[

    {path: '/',component:login,name: 'login',meta: { layout: 'auth' }},
    {path: '/register',component:register,name: 'register',meta: { layout: 'auth' }},
    {path: '/forget',component:forget,name: 'forget',meta: { layout: 'auth' }},
    {path: '/logout',component:logout,name: 'logout',meta: { layout: 'auth' }},
    //auth end
    //dashboard
    {path: '/dashboard',component:home,name: 'dashboard',meta: { layout: 'app' }},

    //employee
    {path: '/employee/create',component:createEmployee,name: 'createemployee',meta: { layout: 'app' }},
    {path: '/employee/edit/:id',component:employeesedit,name: 'editemployee',meta: { layout: 'app' }},
    {path: '/employees',component:employees,name: 'employees',meta: { layout: 'app' }},

    // suppliers:
    {path: '/supplier/create',component:createsuppliers,name: 'createsuppliers',meta: { layout: 'app' }},
    {path: '/supplier/edit/:id',component:suppliersedit,name: 'suppliersedit',meta: { layout: 'app' }},
    {path: '/suppliers',component:suppliers,name: 'suppliers',meta: { layout: 'app' }},

     // Category:
     {path: '/category/create',component:createCategory,name: 'createCategory',meta: { layout: 'app' }},
     {path: '/category/edit/:id',component:Categoryedit,name: 'Categoryedit',meta: { layout: 'app' }},
     {path: '/category',component:Category,name: 'Category',meta: { layout: 'app' }},

     // Sub Category:
     {path: '/sub-category/create',component:createSubCategory,name: 'createSubCategory',meta: { layout: 'app' }},
     {path: '/sub-category/edit/:id',component:SubCategoryedit,name: 'SubCategoryedit',meta: { layout: 'app' }},
     {path: '/sub-category',component:SubCategory,name: 'SubCategory',meta: { layout: 'app' }},

      // Brand:
     {path: '/brand/create',component:createBrand,name: 'createBrand',meta: { layout: 'app' }},
     {path: '/brand/edit/:id',component:Brandedit,name: 'Brandedit',meta: { layout: 'app' }},
     {path: '/brand',component:Brand,name: 'Brand',meta: { layout: 'app' }},

     // Craft:
     {path: '/craft/create',component:createCraft,name: 'createCraft',meta: { layout: 'app' }},
     {path: '/craft/edit/:id',component:Craftedit,name: 'Craftedit',meta: { layout: 'app' }},
     {path: '/craft',component:Craft,name: 'Craft',meta: { layout: 'app' }},

     // Serve:
     {path: '/serve/create',component:createServe,name: 'createServe',meta: { layout: 'app' }},
     {path: '/serve/edit/:id',component:Serveedit,name: 'Serveedit',meta: { layout: 'app' }},
     {path: '/serve',component:Serve,name: 'Serve',meta: { layout: 'app' }},

     // Care:
     {path: '/care/create',component:createCare,name: 'createCare',meta: { layout: 'app' }},
     {path: '/care/edit/:id',component:Careedit,name: 'Careedit',meta: { layout: 'app' }},
     {path: '/care',component:Care,name: 'Care',meta: { layout: 'app' }},

    // Products:
    {path: '/product/create',component:createProduct,name: 'createProduct',meta: { layout: 'app' }},
    {path: '/product/edit/:id',component:Productedit,name: 'Productedit',meta: { layout: 'app' }},
    {path: '/product',component:Product,name: 'Product',meta: { layout: 'app' }},
    {path: '/product/stock',component:stock,name: 'stock',meta: { layout: 'app' }},
    {path: '/stock/edit/:id',component:stockedit,name: 'stockedit',meta: { layout: 'app' }},


    // Expens:
    {path: '/expens/create',component:createexpens,name: 'createexpens',meta: { layout: 'app' }},
    {path: '/expens/edit/:id',component:expensedit,name: 'expensedit',meta: { layout: 'app' }},
    {path: '/expens',component:expens,name: 'expens',meta: { layout: 'app' }},

     // Salaries:
     {path: '/salary/pay',component:salarypay,name: 'salarypay',meta: { layout: 'app' }},
     {path: '/salary/pay/:id',component:paysalary,name: 'paysalary',meta: { layout: 'app' }},
     {path: '/salary/view/:id',component:viewsalary,name: 'viewsalary',meta: { layout: 'app' }},
     {path: '/salary',component:salary,name: 'salary',meta: { layout: 'app' }},

      // Customer:
      {path: '/customer/create',component:createCustomer,name: 'createCustomer',meta: { layout: 'app' }},
      {path: '/customer/edit/:id',component:customeredit,name: 'customeredit',meta: { layout: 'app' }},
      {path: '/customer',component:customer,name: 'customer',meta: { layout: 'app' }},
      {path: '/customer/public/:token',component:customerRegister, meta: { layout: 'auth' }, name: 'customerpublic'},


      //pos
      {path: '/pos',component:pos,name: 'pos',meta: { layout: 'app' }},

      //orders
      {path: '/orders',component:todayorder,name: 'todayorder',meta: { layout: 'app' }},
      {path: '/orders/all',component:allorder,name: 'allorder',meta: { layout: 'app' }},
      {path: '/order/view/:id',component:vieworder,name: 'vieworder',meta: { layout: 'app' }},
      {path: '/order/edit/:id',component: editorder ,name: 'editorder',meta: { layout: 'app' }},

      // meeting:
      { path: '/meeting', component: meeting, name: 'meeting', meta: { layout: 'app' } },
      { path: '/meeting/create', component: createmeeting, name: 'createmeeting', meta: { layout: 'app' } },
      { path: '/meeting/edit/:id', component: meetingedit, name: 'meetingedit', meta: { layout: 'app' } },

      // meeting details:
      { path: '/meeting-details', component: meetingdetails, name: 'meetingdetails', meta: { layout: 'app' } },
      { path: '/meeting-details/create', component: createmeetingdetails, name: 'createmeetingdetails', meta: { layout: 'app' } },
      { path: '/meeting-details/edit/:id', component: meetingdetailsedit, name: 'meetingdetailsedit', meta: { layout: 'app' } },

      // uat meeting:
      { path: '/uat-meeting', component: uatmeeting, name: 'uatmeeting', meta: { layout: 'app' } },
      { path: '/uat-meeting/create', component: createuatmeeting, name: 'createuatmeeting', meta: { layout: 'app' } },
      { path: '/uat-meeting/edit/:id', component: uatmeetingedit, name: 'uatmeetingedit', meta: { layout: 'app' } },

      // serve-data:
      { path: '/serve-data', component: servedata, name: 'servedata', meta: { layout: 'app' } },
      { path: '/serve-data/create', component: createservedata, name: 'createservedata', meta: { layout: 'app' } },
      { path: '/serve-data/edit/:id', component: servedataedit, name: 'servedataedit', meta: { layout: 'app' } },

      // serve-pce
      { path: '/serve-pce', component: servepce, name: 'servepce', meta: { layout: 'app' } },
      { path: '/serve-pce/create', component: servepcecreate, name: 'servepcecreate', meta: { layout: 'app' } },
      { path: '/serve-pce/edit/:id', component: servepceedit, name: 'servepceedit', meta: { layout: 'app' } },

      // care-data:
      { path: '/care-data', component: caredata, name: 'caredata', meta: { layout: 'app' } },
      { path: '/care-data/create', component: createcaredata, name: 'createcaredata', meta: { layout: 'app' } },
      { path: '/care-data/edit/:id', component: caredataedit, name: 'caredataedit', meta: { layout: 'app' } },

      // serve-mps
      { path: '/serve-mps', component: servemps, name: 'servemps', meta: { layout: 'app' } },
      { path: '/serve-mps/create', component: servempscreate, name: 'servempscreate',meta: { layout: 'app' } },
      { path: '/serve-mps/edit/:id', component: servempsedit, name: 'servempsedit', meta: { layout: 'app' } },

      // serve-bek
      { path: '/serve-bek', component: servebek, name: 'servebek', meta: { layout: 'app' } },
      { path: '/serve-bek/create', component: servebekcreate, name: 'servebekcreate',meta: { layout: 'app' } },
      { path: '/serve-bek/edit/:id', component: servebekedit, name: 'servebekedit', meta: { layout: 'app' } },

      // care-warranty
      { path: '/care-warranty', component: carewarranty, name: 'carewarranty', meta: { layout: 'app' } },
      { path: '/care-warranty/create', component: carewarrantycreate, name: 'carewarrantycreate',meta: { layout: 'app' } },
      { path: '/care-warranty/edit/:id', component: carewarrantyedit, name: 'carewarrantyedit', meta: { layout: 'app' } },

      // master-sku
      { path: '/master-sku', component: mastersku, name: 'mastersku', meta: { layout: 'app' } },
      { path: '/master-sku/create', component: masterskucreate, name: 'masterskucreate', meta: { layout: 'app' } },
      { path: '/master-sku/edit/:id', component: masterskuedit, name: 'masterskuedit', meta: { layout: 'app' } },

      // inv-care
      { path: '/inv-care', component: invcare, name: 'invcare', meta: { layout: 'app' } },
      { path: '/inv-care/create', component: invcarecreate, name: 'invcarecreate', meta: { layout: 'app' } },
      { path: '/inv-care/edit/:id', component: invcareedit, name: 'invcareedit', meta: { layout: 'app' } },

      // inv-excl-serve
      { path: '/inv-excl-serve', component: invexclserve, name: 'invexclserve', meta: { layout: 'app' } },
      { path: '/inv-excl-serve/create', component: invexclservecreate, name: 'invexclservecreate', meta: { layout: 'app' } },
      { path: '/inv-excl-serve/edit/:id', component: invexclserveedit, name: 'invexclserveedit', meta: { layout: 'app' } },

      // merch-items
      { path: '/merch-items', component: merchitems, name: 'merchitems', meta: { layout: 'app' } },
      { path: '/merch-items/create', component: merchitemscreate, name: 'merchitemscreate', meta: { layout: 'app' } },
      { path: '/merch-items/edit/:id', component: merchitemsedit, name: 'merchitemsedit', meta: { layout: 'app' } },

      // inv-merch
      { path: '/inv-merch', component: invmerch, name: 'invmerch', meta: { layout: 'app' } },
      { path: '/inv-merch/create', component: invmerchcreate, name: 'invmerchcreate', meta: { layout: 'app' } },
      { path: '/inv-merch/edit/:id', component: invmerchedit, name: 'invmerchedit', meta: { layout: 'app' } },

      // inv-excl-merch
      { path: '/inv-excl-merch', component: invexclmerch, name: 'invexclmerch', meta: { layout: 'app' } },
      { path: '/inv-excl-merch/create', component: invexclmerchcreate, name: 'invexclmerchcreate', meta: { layout: 'app' } },
      { path: '/inv-excl-merch/edit/:id', component: invexclmerchedit, name: 'invexclmerchedit', meta: { layout: 'app' } },

      // merch-orders
      { path: '/merch-orders', component: merchorders, name: 'merchorders', meta: { layout: 'app' } },
      { path: '/merch-orders/create', component: merchorderscreate, name: 'merchorderscreate', meta: { layout: 'app' } },
      { path: '/merch-orders/edit/:id', component: merchordersedit, name: 'merchordersedit', meta: { layout: 'app' } },

      // plus-services
      { path: '/plus-services', component: plusservices, name: 'plusservices', meta: { layout: 'app' } },
      { path: '/plus-services/create', component: plusservicescreate, name: 'plusservicescreate', meta: { layout: 'app' } },
      { path: '/plus-services/edit/:id', component: plusservicesedit, name: 'plusservicesedit', meta: { layout: 'app' } },

      // plus-orders
      { path: '/plus-orders', component: plusorders, name: 'plusorders', meta: { layout: 'app' } },
      { path: '/plus-orders/create', component: plusorderscreate, name: 'plusorderscreate', meta: { layout: 'app' } },
      { path: '/plus-orders/edit/:id', component: plusordersedit, name: 'plusordersedit', meta: { layout: 'app' } },

      // thread-bom
      { path: '/thread-bom', component: threadbom, name: 'threadbom', meta: { layout: 'app' } },
      { path: '/thread-bom/create', component: threadbomcreate, name: 'threadbomcreate', meta: { layout: 'app' } },
      { path: '/thread-bom/edit/:id', component: threadbomedit, name: 'threadbomedit', meta: { layout: 'app' } },

      // inv-thread
      { path: '/inv-thread', component: invthread, name: 'invthread', meta: { layout: 'app' } },
      { path: '/inv-thread/create', component: invthreadcreate, name: 'invthreadcreate', meta: { layout: 'app' } },
      { path: '/inv-thread/edit/:id', component: invthreadedit, name: 'invthreadedit', meta: { layout: 'app' } },

      // thread-orders
      { path: '/thread-orders', component: threadorders, name: 'threadorders', meta: { layout: 'app' } },
      { path: '/thread-orders/create', component: threadorderscreate, name: 'threadorderscreate', meta: { layout: 'app' } },
      { path: '/thread-orders/edit/:id', component: threadordersedit, name: 'threadordersedit', meta: { layout: 'app' } },

      // inventory movement
      { path: '/inventory-movements', component: inventorymovement, name: 'inventorymovement', meta: { layout: 'app' } },
      { path: '/inventory-movements/create', component: inventorymovementcreate, name: 'inventorymovementcreate', meta: { layout: 'app' } },
      { path: '/inventory-movements/edit/:id', component: inventorymovementedit, name: 'inventorymovementedit', meta: { layout: 'app' } },

      // craft inspection
      { path: '/order/:id/inspection/:round', component: craftinspection, name: 'craftinspection', meta: { layout: 'app' } },
      { path: '/order/:id/performance-test/:round', component: performancetest, name: 'performancetest', meta: { layout: 'app' } },

      // customer progress management
      { path: '/customer-progress', component: customer_progress_index, name: 'customerprogress', meta: { layout: 'app' } },
      { path: '/customer-progress/create', component: customer_progress_create, name: 'customerprogresscreate', meta: { layout: 'app' } },
      { path: '/customer-progress/edit/:id', component: customer_progress_edit, name: 'customerprogressedit', meta: { layout: 'app' } },
]
