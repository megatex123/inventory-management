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

let productwarranty = require ('./components/product_warranty/index.vue').default;
let productwarrantycreate = require ('./components/product_warranty/create.vue').default;
let productwarrantyedit = require ('./components/product_warranty/edit.vue').default;

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

      { path: '/product-warranty', component: productwarranty, name: 'productwarranty', meta: { layout: 'app' } },
      { path: '/product-warranty/create', component: productwarrantycreate, name: 'productwarrantycreate', meta: { layout: 'app' } },
      { path: '/product-warranty/edit/:id', component: productwarrantyedit, name: 'productwarrantyedit', meta: { layout: 'app' } },
]
