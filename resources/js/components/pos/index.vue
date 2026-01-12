<template>
  <div>
    <!-- Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
      <h1 class="h3 mb-0 text-gray-800">POS</h1>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><router-link to="/">Home</router-link></li>
        <li class="breadcrumb-item active" aria-current="page">POS</li>
      </ol>
    </div>

    <div class="row mb-3">
      <!-- Products Panel -->
      <div class="col-xl-7 col-lg-7">
        <div class="card mb-4">
          <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Products Sold</h6>
          </div>

          <div class="card-body">
            <!-- Category Tabs -->
            <ul class="nav nav-tabs mb-3" role="tablist">
              <li class="nav-item">
                <a class="nav-link" :class="{ active: selectedCategoryId === null }" @click="selectCategory(null)">
                  All Products
                </a>
              </li>
              <li class="nav-item" v-for="category in categories" :key="category.id">
                <a 
                  class="nav-link" 
                  :class="{ active: selectedCategoryId === category.id }"
                  @click="selectCategory(category.id)"
                >
                  {{ category.name }}
                </a>
              </li>
            </ul>

            <!-- Sub-category Buttons -->
            <div v-if="filteredSubCategories.length > 0" class="sub-category-wrapper mb-3 px-2 d-flex flex-wrap border-bottom pb-2">
              <button 
                class="btn btn-sm mr-2 mb-2"
                :class="selectedSubCategoryId === null ? 'btn-primary' : 'btn-outline-primary'"
                @click="selectSubCategory(null)"
              >
                All
              </button>
              <button 
                v-for="sub in filteredSubCategories" 
                :key="sub.id"
                class="btn btn-sm mr-2 mb-2"
                :class="selectedSubCategoryId === sub.id ? 'btn-primary' : 'btn-outline-primary'"
                @click="selectSubCategory(sub.id)"
              >
                {{ sub.name }}
              </button>
            </div>

            <!-- Search -->
            <input type="text" class="form-control w-100 mb-3" v-model="searchItem" :placeholder="selectedCategoryId ? 'Search in this category...' : 'Search All Products...'">

            <!-- Products Grid -->
            <div class="row">
              <div class="col-lg-3 col-md-3 col-sm-6 col-6" v-for="product in displayedProducts" :key="product.id">
                <button class="btn btn-sm" @click.prevent="AddToCart(product.id)">
                  <div class="card" style="width: 8.5rem; margin-bottom: 5px;">
                    <img :src="product.image" id="em_photo" class="card-img-top">
                    <div class="card-body">
                      <h6 class="card-title">{{ product.product_name }}</h6>
                      <span class="badge badge-success" v-if="product.product_qty >= 1">Available {{ product.product_qty }}</span>
                      <span class="badge badge-danger" v-else>Stock Out</span>
                    </div>
                  </div>
                </button>
              </div>

              <div v-if="displayedProducts.length === 0" class="col-12 text-center py-4">
                <p class="text-muted">No products found.</p>
              </div>
            </div>

          </div>
        </div>
      </div>

      <!-- Cart Panel -->
      <div class="col-xl-5 col-lg-5">
        <div class="card mb-4">
          <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Expense Insert</h6>
            <router-link class="btn btn-primary text-white" to="/customer/create">Add Customer</router-link>
          </div>
          <div class="card-body">
            <div class="card">
              <div class="table-responsive">
                <table class="table align-items-center table-flush" style="font-size:12px">
                  <thead class="thead-light">
                    <tr>
                      <th>Name</th>
                      <th>Type</th>
                      <th>Qty</th>
                      <th>Unit (RM)</th>
                      <th>Total (RM)</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="cart in carts" :key="cart.cart_id">
                      <td>{{ cart.pro_name }}</td>
                      <td>{{ cart.category_name }}</td>
                      <td class="d-flex px-0">
                        <button @click.prevent="inc(cart.cart_id)" class="btn btn-success btn-sm p-1 mr-1">+</button>
                        <input type="text" style="width:20px;border:none" readonly :value="cart.pro_qty">
                        <button v-if="cart.pro_qty != 1" @click.prevent="dec(cart.cart_id)" class="btn btn-danger btn-sm p-1">-</button>
                      </td>
                      <td>{{ formatNumber(cart.pro_price) }}</td>
                      <td>{{ formatNumber(cart.sub_total) }}</td>
                      <td>
                        <a href="javascript:void(0)" @click="removeItem(cart.cart_id)" class="btn btn-sm btn-danger">X</a>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div class="card-footer">
                <ul class="list-group">
                  <li class="list-group-item d-flex justify-content-between align-items-center">
                    Total Quantity: <strong>{{ totalCart }}</strong>
                  </li>
                  <li class="list-group-item d-flex justify-content-between align-items-center">
                    Total Amount Product: <strong>RM {{ formatNumber(totalSub.toFixed(2)) }}</strong>
                  </li>
                  <li class="list-group-item d-flex justify-content-between align-items-center">
                    QuiviCraft: 
                    <span v-if="totalSub <= 7000.00">Inessential kit</span>
                    <span v-else-if="totalSub > 10000.00">Premium</span>
                    <span v-else>Silver</span>
                  </li>
                </ul>
                <br>
                <form @submit.prevent="orderdone">
                  <label class="mb-2">Customer Name</label>
                  <select class="form-control" v-model="customer_id">
                    <option v-for="customer in Customers" :key="customer.id" :value="customer.id">
                      {{ customer.full_name }}
                    </option>
                  </select>
                  <button class="btn btn-primary mt-3" type="submit">Submit</button>
                </form>
              </div>

            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

</template>

<script>
export default {
  data() {
    return {
      suppliers: [],
      categories: [],
      subCategoriesOptions: [],
      CatProduct: [],
      Customers: [],
      carts: [],
      customer_id: '',
      searchItem: '',
      selectedCategoryId: null,
      selectedSubCategoryId: null
    }
  },
  computed: {
    filteredSubCategories() {
      if (!this.selectedCategoryId) return [];
      return this.subCategoriesOptions.filter(sub => sub.cat_id == this.selectedCategoryId);
    },
    displayedProducts() {
      let products = this.CatProduct;
      if (this.selectedSubCategoryId) {
        products = products.filter(p => p.sub_cat_id === this.selectedSubCategoryId);
      }
      if (this.searchItem) {
        const search = this.searchItem.toLowerCase();
        products = products.filter(p => p.product_name.toLowerCase().includes(search));
      }
      return products;
    },
    totalCart() {
      return this.carts.reduce((sum, c) => sum + parseFloat(c.pro_qty), 0);
    },
    totalSub() {
      return this.carts.reduce((sum, c) => sum + parseFloat(c.pro_qty) * parseFloat(c.pro_price), 0);
    }
  },
  methods: {
    selectCategory(id) {
      this.selectedCategoryId = id;
      this.selectedSubCategoryId = null;
      if (id) {
        axios.get(`/api/getproductcategoy/${id}`)
          .then(res => { this.CatProduct = res.data })
          .catch(err => console.error(err));
      } else {
        axios.get('/api/product')
          .then(res => { this.CatProduct = res.data })
          .catch(err => console.error(err));
      }
    },
    selectSubCategory(id) {
      this.selectedSubCategoryId = id;
    },
    AddToCart(id) {
      axios.get(`/api/addCart/${id}`)
        .then(res => { 
          notification.customNoti(res.data); 
          this.getCarts();
        });
    },
    inc(id) { axios.get(`/api/cart/cartInc/${id}`).then(() => this.getCarts()) },
    dec(id) { axios.get(`/api/cart/cartDec/${id}`).then(() => this.getCarts()) },
    removeItem(id) { 
      axios.get(`/api/cart/remove/${id}`)
        .then(() => { this.carts = this.carts.filter(c => c.cart_id != id) }) 
    },
    getCarts() { axios.get('/api/carts/get').then(res => { this.carts = res.data }) },
    getCustomer() { axios.get('/api/customer').then(res => { this.Customers = res.data }) },
    getEmp() { axios.get('/api/product').then(res => { this.suppliers = res.data; this.CatProduct = res.data }) },
    getCat() { axios.get('/api/categories').then(res => { this.categories = res.data }) },
    getSubCategoriesOptions() { axios.get('/api/sub-categories').then(res => { this.subCategoriesOptions = res.data }) },
    formatNumber(value) { return Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }
  },
  created() {
    this.getEmp();
    this.getCat();
    this.getSubCategoriesOptions();
    this.getCustomer();
    this.getCarts();
  }
}
</script>

<style scoped>
img#em_photo {
  width: 120px !important;
  height: 120px !important;
  object-fit: contain;
  margin: auto;
  display: block;
}
h6.card-title { font-size: 12px !important; }
.sub-category-wrapper .btn {
  border-radius: 50px;
  padding: 2px 15px;
  font-weight: 500;
}
</style>
