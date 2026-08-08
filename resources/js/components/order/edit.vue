<template>
  <div>
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
      <h1 class="h3 mb-0 text-gray-800">Edit QuiviCraft : {{ orderData.order_id }}</h1>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><router-link to="/">Home</router-link></li>
        <li class="breadcrumb-item"><router-link to="/orders/all">Orders</router-link></li>
        <li class="breadcrumb-item active" aria-current="page">Edit QuiviCraft</li>
      </ol>
    </div>

    <div class="row mb-3">
      <div class="col-xl-7 col-lg-7">
        <div class="card mb-4">
          <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Select Products</h6>
          </div>

          <div class="card-body">
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

            <input type="text" class="form-control w-100 mb-3" v-model="searchItem" placeholder="Search Products...">

            <div class="row">
              <div class="col-lg-3 col-md-3 col-sm-6 col-6" v-for="product in displayedProducts" :key="product.id">
                <button class="btn btn-sm" @click.prevent="addToCart(product)">
                  <div class="card" style="width: 8.5rem; margin-bottom: 5px;">
                    <img :src="product.image" id="em_photo" class="card-img-top">
                    <div class="card-body">
                      <h6 class="card-title">{{ product.product_name }}</h6>
                      <h7 class="card-title">RM {{ formatNumber(product.price) }}</h7>
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

      <div class="col-xl-5 col-lg-5">
        <div class="card mb-4">
          <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Edit Order Items</h6>
            <div>
              <!-- <button class="btn btn-sm btn-warning mr-2" @click="loadOriginalOrder">
                Reset
              </button> -->
              <router-link :to="'/order/view/' + orderId" class="btn btn-sm btn-info">
                View Order
              </router-link>
            </div>
          </div>
          <div class="card-body">
            <div class="card">
              <div class="table-responsive">
                <table class="table align-items-center table-flush" style="font-size:12px">
                  <thead class="thead-light">
                    <tr>
                      <th>Product</th>
                      <th>Type</th>
                      <th>Qty</th>
                      <th>Price (RM)</th>
                      <th>Total (RM)</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="item in cartItems" :key="item.pro_id">
                      <td>{{ item.product_name }}</td>
                      <td>{{ item.category_name }}</td>
                      <td class="d-flex px-0 align-items-center">
                        <button v-if="item.pro_qty != 1" @click="decrementQty(item)" class="btn btn-danger btn-sm p-1">-</button>
                        <input type="number"
                          v-model="item.pro_qty"
                          @change="updateItemTotal(item)"
                          class="form-control form-control-sm text-center"
                          style="width: 50px;"
                          min="1"
                          :max="item.max_stock">
                        <button v-if="canIncreaseQuantity(item)" @click="incrementQty(item)" class="btn btn-success btn-sm p-1 mr-1">+</button>
                      </td>
                      <td>{{ formatNumber(item.pro_price) }}</td>
                      <td>{{ formatNumber(item.sub_total) }}</td>
                      <td>
                        <button @click="removeFromCart(item.pro_id)" class="btn btn-sm btn-danger">
                          <i class="fa fa-trash"></i>
                        </button>
                      </td>
                    </tr>
                    <tr v-if="cartItems.length === 0">
                      <td colspan="6" class="text-center text-muted py-3">
                        No items in cart. Add products from the left panel.
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div class="card-footer">
                <div class="form-group mb-3">
                  <label class="font-weight-bold">Customer</label>
                  <select class="form-control" v-model="customer_id" required disabled>
                    <option v-for="customer in customers"
                            :key="customer.id"
                            :value="customer.id"
                            :selected="customer.id === orderData.customer_id" disabled>
                      {{ customer.full_name }} - {{ customer.phone }}
                    </option>
                  </select>
                </div>

                <!-- Build Type Section - Radio Buttons -->
                <div class="mt-3">
                    <label class="mb-2 font-weight-bold">Build Type</label>
                    <div class="d-flex">
                    <div class="form-check mr-4">
                        <input
                        class="form-check-input"
                        type="radio"
                        name="buildType"
                        id="editBuildWorking"
                        :value="1"
                        v-model="is_reason"
                        >
                        <label class="form-check-label" for="editBuildWorking">
                        Workstation
                        </label>
                    </div>
                    <div class="form-check">
                        <input
                        class="form-check-input"
                        type="radio"
                        name="buildType"
                        id="editBuildGaming"
                        :value="2"
                        v-model="is_reason"
                        >
                        <label class="form-check-label" for="editBuildGaming">
                        Gaming
                        </label>
                    </div>
                    </div>
                    <small class="text-muted">Select the purpose of this build (optional)</small>
                </div>

                <!-- Build Ways Section - Radio Buttons -->
                <div class="mt-3">
                    <label class="mb-2 font-weight-bold">Build Ways</label>
                    <div class="d-flex">
                    <div class="form-check mr-4">
                        <input
                        class="form-check-input"
                        type="radio"
                        name="buildWay"
                        id="editBuildOnsite"
                        value="onsite"
                        v-model="build_way"
                        >
                        <label class="form-check-label" for="editBuildOnsite">
                        Onsite
                        </label>
                    </div>
                    <div class="form-check">
                        <input
                        class="form-check-input"
                        type="radio"
                        name="buildWay"
                        id="editBuildStudio"
                        value="studio"
                        v-model="build_way"
                        >
                        <label class="form-check-label" for="editBuildStudio">
                        Studio
                        </label>
                    </div>
                    </div>
                    <small class="text-muted">Select where this build will take place (optional)</small>
                </div>

                <!-- Tag Along Section - Radio Buttons, only when Onsite -->
                <div class="mt-3" v-if="build_way === 'onsite'">
                    <label class="mb-2 font-weight-bold">Tag Along</label>
                    <div class="d-flex">
                    <div class="form-check mr-4">
                        <input
                        class="form-check-input"
                        type="radio"
                        name="tagAlong"
                        id="editTagAlongYes"
                        :value="true"
                        v-model="tag_along"
                        >
                        <label class="form-check-label" for="editTagAlongYes">
                        Yes
                        </label>
                    </div>
                    <div class="form-check">
                        <input
                        class="form-check-input"
                        type="radio"
                        name="tagAlong"
                        id="editTagAlongNo"
                        :value="false"
                        v-model="tag_along"
                        >
                        <label class="form-check-label" for="editTagAlongNo">
                        No
                        </label>
                    </div>
                    </div>
                    <small class="text-muted">Does the customer want to be present during the onsite build?</small>
                </div>

                <!-- QuiviCare Opt-Out -->
                <div class="mt-3">
                    <div class="custom-control custom-switch">
                        <input
                        type="checkbox"
                        class="custom-control-input"
                        id="editSkipQuiviCare"
                        v-model="skip_quivicare"
                        >
                        <label class="custom-control-label" for="editSkipQuiviCare">
                        Customer doesn't want QuiviCare
                        </label>
                    </div>
                    <small class="text-muted">When checked, QuiviCare won't be created when this order is confirmed</small>
                </div>

                <!-- Upgrade PCE -->
                <div class="mt-3" v-if="!skip_quivicare">
                    <div class="custom-control custom-switch">
                        <input
                        type="checkbox"
                        class="custom-control-input"
                        id="editUpgradePceEnabled"
                        v-model="upgrade_pce_enabled"
                        >
                        <label class="custom-control-label" for="editUpgradePceEnabled">
                        {{ upgrade_pce_enabled ? 'Upgrade PCE Enabled (+RM69.90)' : 'Upgrade PCE Disabled' }}
                        </label>
                    </div>
                    <div v-if="upgrade_pce_enabled" class="mt-2">
                        <label class="form-label small">Upgrade PCE Notes <span class="text-danger">*</span></label>
                        <textarea
                        v-model="upgrade_pce_notes"
                        class="form-control"
                        rows="3"
                        :maxlength="500"
                        ></textarea>
                        <small class="form-text text-muted" v-if="upgrade_pce_notes">{{ upgrade_pce_notes.length }}/500 characters</small>
                    </div>
                    <small class="text-muted">Only applies if this order ends up on the Collector's Edition tier at approval — adds RM69.90 to the QuiviServe service fee</small>
                </div>

                <div v-if="cartValidationErrors.length > 0" class="alert alert-info alert-dismissible fade show" role="alert">
                    <strong>Cart Validation :</strong>
                    <ul class="mb-0 mt-1">
                        <li v-for="(error, index) in cartValidationErrors" :key="index">{{ error }}</li>
                    </ul>
                </div>

                <div class="border-top pt-3">
                  <h6 class="font-weight-bold mb-3">Order Summary</h6>

                  <div class="d-flex justify-content-between mb-2">
                    <span>Subtotal:</span>
                    <span class="font-weight-bold">RM {{ formatNumber(totalSub.toFixed(2)) }}</span>
                  </div>

                  <div class="d-flex justify-content-between mb-2">
                    <span>Total Quantity:</span>
                    <span class="font-weight-bold">{{ totalCart }}</span>
                  </div>
                  <hr>

                  <div class="alert alert-info">
                    <h6 class="alert-heading">Service Tier</h6>
                    <div v-if="totalSub <= 7000.00" class="mb-2">
                      <strong>QuiviCraft:</strong> Inessential Kit
                    </div>
                    <div v-else-if="totalSub > 10000.00" class="mb-2">
                      <strong>QuiviCraft:</strong> Premium
                    </div>
                    <div v-else class="mb-2">
                      <strong>QuiviCraft:</strong> Silver
                    </div>
                    <div class="small text-muted">
                      This tier will automatically update when you save the order.
                    </div>
                  </div>

                  <div class="d-flex justify-content-between mt-4">
                    <button class="btn btn-secondary" @click="cancelEdit">
                      Cancel
                    </button>
                    <button class="btn btn-primary" @click="updateOrder" :disabled="isSaving || cartItems.length === 0 || cartValidationErrors.length > 0">
                      <span v-if="isSaving">
                        <i class="fa fa-spinner fa-spin"></i> Saving...
                      </span>
                      <span v-else>
                        <i class="fa fa-save"></i> Update Order
                      </span>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="successModal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header bg-success text-white">
            <h5 class="modal-title">Success</h5>
            <button type="button" class="close" data-dismiss="modal">
              <span>&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="text-center">
              <i class="fa fa-check-circle fa-4x text-success mb-3"></i>
              <h4>Order Updated Successfully!</h4>
              <p>Your order has been updated. New total: <strong>RM {{ formatNumber(newOrderTotal) }}</strong></p>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Continue Editing</button>
            <router-link :to="'/orders/view/' + orderId" class="btn btn-primary">
              View Order
            </router-link>
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
      orderId: this.$route.params.id,
      orderData: {},
      customers: [],
      categories: [],
      subCategoriesOptions: [],
      products: [],
      cartItems: [],
      originalCartItems: [],
      customer_id: '',
      is_reason: null,
      build_way: null,
      tag_along: null,
      upgrade_pce_enabled: null,
      upgrade_pce_notes: '',
      skip_quivicare: false,
      searchItem: '',
      selectedCategoryId: null,
      selectedSubCategoryId: null,
      showCurrentProducts: true,
      isSaving: false,
      newOrderTotal: 0,
      categoryRules: {
        'CPU': { min: 1, max: 1, description: 'Exactly 1 required' },
        'MBD': { min: 1, max: 1, dependencies: ['CPU'], description: 'Exactly 1 required, needs CPU' },
        'GPU': { min: 0, max: null, description: 'Optional' },
        'RAM': { min: 1, description: 'Minimum 1 required' },
        'SSD': { min: 0, exclusiveWith: ['HDD'], description: 'Optional, cannot have with HDD' },
        'HDD': { min: 0, exclusiveWith: ['SSD'], description: 'Optional, cannot have with SSD' },
        'AIO': { min: 1, max: 1, exclusiveWith: ['HSF'], description: 'Exactly 1 required, cannot have with HSF' },
        'HSF': { min: 1, max: 1, exclusiveWith: ['AIO'], description: 'Exactly 1 required, cannot have with AIO' },
        'PSU': { min: 1, max: null, description: 'Minimum 1 required' },
        'CSE': { min: 1, max: 1, description: 'Exactly 1 required' },
        'FAN': { description: 'No restrictions' },
        'ACC-SAG': { description: 'No restrictions' },
        'ACC-CTL': { description: 'No restrictions' },
        'ACC-HUB': { description: 'No restrictions' },
        'PER-MON': { description: 'No restrictions' },
        'PER-MOU': { description: 'No restrictions' },
        'PER-HDS': { description: 'No restrictions' },
        'PER-MIC': { description: 'No restrictions' },
        'PER-MSP': { description: 'No restrictions' },
        'PER-KEY': { description: 'No restrictions' },
        'PER-CAM': { description: 'No restrictions' },
      }
    }
  },
  computed: {
    filteredSubCategories() {
      if (!this.selectedCategoryId) return [];
      return this.subCategoriesOptions.filter(sub => sub.cat_id == this.selectedCategoryId);
    },
    displayedProducts() {
      let products = this.products;

      if (this.selectedCategoryId) {
        products = products.filter(p => p.cat_id === this.selectedCategoryId);
      }

      if (this.selectedSubCategoryId) {
        products = products.filter(p => p.sub_cat_id === this.selectedSubCategoryId);
      }

      if (this.searchItem) {
        const search = this.searchItem.toLowerCase();
        products = products.filter(p =>
          p.product_name.toLowerCase().includes(search) ||
          p.product_code.toLowerCase().includes(search)
        );
      }

      return products;
    },
    totalCart() {
      return this.cartItems.reduce((sum, c) => sum + parseInt(c.pro_qty), 0);
    },
    totalSub() {
      return this.cartItems.reduce((sum, c) => {
        const subTotal = parseFloat(c.sub_total) || 0;
        return sum + (isNaN(subTotal) ? 0 : subTotal);
      }, 0);
    },
    currentOrderTotal() {
      return this.cartItems.reduce((sum, item) => {
        const price = parseFloat(item.pro_price) || 0;
        const qty = parseInt(item.pro_qty) || 0;
        return sum + (price * qty);
      }, 0);
    },
    // Get cart items grouped by category with quantities
    cartByCategory() {
      const categoryMap = {};
      this.cartItems.forEach(item => {
        if (!categoryMap[item.category_name]) {
          categoryMap[item.category_name] = {
            items: [],
            totalQty: 0,
            totalPrice: 0
          };
        }
        categoryMap[item.category_name].items.push(item);
        categoryMap[item.category_name].totalQty += parseInt(item.pro_qty);
        categoryMap[item.category_name].totalPrice += parseFloat(item.sub_total);
      });
      return categoryMap;
    },
    // Check if cart meets all category rules
    cartValidationErrors() {
      const errors = [];
      const categoryMap = this.cartByCategory;

      // Check mandatory categories (min 1)
      const mandatoryCategories = ['CPU', 'MBD', 'RAM', 'PSU'];
      mandatoryCategories.forEach(catName => {
        if (!categoryMap[catName]) {
          errors.push(`${catName} is required (minimum 1)`);
        }
      });

      // Check CPU exactly 1
      if (categoryMap['CPU'] && categoryMap['CPU'].totalQty !== 1) {
        errors.push('CPU: Exactly 1 required');
      }

      // Check MBD exactly 1
      if (categoryMap['MBD'] && categoryMap['MBD'].totalQty !== 1) {
        errors.push('MBD: Exactly 1 required');
      }

      // Check MBD dependency on CPU
      if (categoryMap['MBD'] && !categoryMap['CPU']) {
        errors.push('Motherboard (MBD) requires CPU');
      }

      // Check AIO/HSF requirement (one of them required)
      const hasAIO = categoryMap['AIO'];
      const hasHSF = categoryMap['HSF'];

      if (!hasAIO && !hasHSF) {
        errors.push('Either AIO or HSF is required');
      }

      // Check AIO exactly 1 if present
      if (hasAIO && hasAIO.totalQty !== 1) {
        errors.push('AIO: Exactly 1 required');
      }

      // Check HSF exactly 1 if present
      if (hasHSF && hasHSF.totalQty !== 1) {
        errors.push('HSF: Exactly 1 required');
      }

      // Check AIO/HSF mutual exclusivity
      if (hasAIO && hasHSF) {
        errors.push('Cannot have both AIO and HSF - choose one');
      }

      // Check SSD/HDD requirement (at least one)
      const hasSSD = categoryMap['SSD'];
      const hasHDD = categoryMap['HDD'];

      if (!hasSSD && !hasHDD) {
        errors.push('Either SSD or HDD is required');
      }

      // Check SSD/HDD mutual exclusivity
      if (hasSSD && hasHDD) {
        errors.push('Cannot have both SSD and HDD - choose one');
      }

      // Check CSE exactly 1
      if (!categoryMap['CSE']) {
        errors.push('CSE: Exactly 1 required');
      } else if (categoryMap['CSE'].totalQty !== 1) {
        errors.push('CSE: Exactly 1 required');
      }

      return errors;
    }
  },
  created() {
    const routeId = this.$route.params.id;
    if (routeId) {
      this.orderId = routeId;
      this.loadOrderData();
    }
    this.loadAllData();
  },
  methods: {
    // Get category rule safely
    getCategoryRule(categoryName) {
      return this.categoryRules[categoryName] || null;
    },

    canIncreaseQuantity(cartItem) {
      const categoryName = cartItem.category_name;
      const rule = this.getCategoryRule(categoryName);

      // Check if this is a category with max 1 (exactly one required)
      if (rule && rule.max === 1) {
        if (cartItem.pro_qty >= 1) {
          return false;
        }
      }

      // Check if increasing would violate mutual exclusivity
      if (rule && rule.exclusiveWith && rule.exclusiveWith.length > 0) {
        for (const exclusiveCat of rule.exclusiveWith) {
          const exclusiveQty = this.cartItems.filter(item => item.category_name === exclusiveCat)
                                        .reduce((sum, item) => sum + parseInt(item.pro_qty), 0);
          if (exclusiveQty > 0) {
            return false;
          }
        }
      }

      return true;
    },

    loadOrderData() {
      console.log('Fetching order detail with ID:', this.orderData.order_id);
      axios.get(`/api/order/get/${this.orderId}`)
        .then(res => {
          console.log('Order API response:', res.data);
          if (res.data.success && res.data.order) {
            this.orderData = res.data.order;
            this.customer_id = res.data.order.customer_id || '';
            this.is_reason = res.data.order.is_reason;
            this.build_way = res.data.order.build_way;
            this.tag_along = res.data.order.tag_along;
            this.upgrade_pce_enabled = res.data.order.upgrade_pce_enabled;
            this.upgrade_pce_notes = res.data.order.upgrade_pce_notes || '';
            this.skip_quivicare = !!res.data.order.skip_quivicare;

            if (res.data.details && res.data.details.length > 0) {
              this.cartItems = res.data.details.map(item => {
                const proPrice = parseFloat(item.pro_price) || 0;
                const proQty = parseInt(item.pro_qty) || 0;
                const subTotal = proPrice * proQty;

                return {
                  pro_id: item.pro_id,
                  product_name: item.product_name || 'Unknown Product',
                  product_code: item.product_code || 'N/A',
                  pro_price: proPrice,
                  pro_qty: proQty,
                  sub_total: subTotal,
                  max_stock: parseInt(item.product_qty) || 0,
                  category_name: item.category_name || item.cat_id || 'Unknown Category'
                };
              });
              this.originalCartItems = JSON.parse(JSON.stringify(this.cartItems));
            }
            this.showNotification('Order data loaded successfully', 'success');
          } else {
            const errorMsg = res.data.message || 'Failed to load order data';
            this.showNotification(errorMsg, 'error');
            this.$router.push('/orders/all');
          }
        })
        .catch(err => {
          console.error('Error loading order data:', err);
          this.showNotification('Failed to load order data', 'error');
          this.$router.push('/orders/all');
        });
    },

    loadProducts() {
      axios.get('/api/product/all')
        .then(res => {
          console.log('Products loaded:', res.data.length);
          this.products = res.data;
        })
        .catch(err => {
          console.error('Error loading products:', err);
          this.showNotification('Failed to load products', 'error');
        });
    },

    selectCategory(id) {
      this.selectedCategoryId = id;
      this.selectedSubCategoryId = null;
    },

    selectSubCategory(id) {
      this.selectedSubCategoryId = id;
    },

    addToCart(product) {
      console.log('Adding product to cart:', product);

      // Check if product has valid selling_price
      if (!product.price || isNaN(parseFloat(product.price))) {
        this.showNotification('Product price is not valid', 'error');
        return;
      }

      const existingItem = this.cartItems.find(item => item.pro_id === product.id);

      if (existingItem) {
        if (existingItem.pro_qty < existingItem.max_stock) {
          existingItem.pro_qty++;
          existingItem.sub_total = existingItem.pro_qty * existingItem.pro_price;
        } else {
          this.showNotification('Cannot exceed available stock', 'warning');
        }
      } else {
        const sellingPrice = parseFloat(product.price) || 0;
        const productQty = parseInt(product.product_qty) || 0;

        this.cartItems.push({
          pro_id: product.id,
          product_name: product.product_name || 'Unknown Product',
          product_code: product.product_code || 'N/A',
          pro_price: sellingPrice,
          pro_qty: 1,
          sub_total: sellingPrice,
          max_stock: productQty,
          category_name: product.category_name || 'Processing...'
        });
      }

      console.log('Cart items after add:', this.cartItems); // Debug log
    },

    incrementQty(item) {
      if (!this.canIncreaseQuantity(item)) {
        this.showNotification('This category is already at its limit', 'warning');
        return;
      }
      if (item.pro_qty < item.max_stock) {
        item.pro_qty++;
        this.updateItemTotal(item);
      } else {
        this.showNotification('Cannot exceed available stock', 'warning');
      }
    },

    decrementQty(item) {
      if (item.pro_qty > 1) {
        item.pro_qty--;
        this.updateItemTotal(item);
      } else {
        this.removeFromCart(item.pro_id);
      }
    },

    updateItemTotal(item) {
      // Ensure we have valid numbers
      const price = parseFloat(item.pro_price) || 0;
      const qty = parseInt(item.pro_qty) || 1;
      item.sub_total = price * qty;
    },

    removeFromCart(productId) {
      this.cartItems = this.cartItems.filter(item => item.pro_id !== productId);
    },

    loadOriginalOrder() {
      this.cartItems = JSON.parse(JSON.stringify(this.originalCartItems));
      this.customer_id = this.orderData.customer_id;
      this.showNotification('Order reset to original', 'success');
    },

    // Update order
    updateOrder() {
      console.log('Updating order, cart items:', this.cartItems); // Debug log

      if (this.cartItems.length === 0) {
        this.showNotification('Please add at least one product to the order', 'error');
        return;
      }

      if (!this.customer_id) {
        this.showNotification('Please select a customer', 'error');
        return;
      }

      // Validate all cart items have valid data
      const invalidItems = this.cartItems.filter(item => {
        return isNaN(parseFloat(item.pro_price)) ||
               isNaN(parseInt(item.pro_qty)) ||
               !item.pro_id;
      });

      if (invalidItems.length > 0) {
        this.showNotification('Some items have invalid data. Please check product prices and quantities.', 'error');
        return;
      }

      if (this.upgrade_pce_enabled && !this.upgrade_pce_notes.trim()) {
        this.showNotification('Upgrade PCE notes are required when upgrade is enabled', 'error');
        return;
      }

      this.isSaving = true;

      // Prepare products data
      const productsData = this.cartItems.map(item => ({
        id: item.pro_id,
        qty: item.pro_qty,
        price: item.pro_price
      }));

      axios.post(`/api/order/update/${this.orderId}`, {
        customer_id: this.customer_id,
        products: productsData,
        total_amount: this.totalSub,
        total_qty: this.totalCart,
        is_reason: this.is_reason,
        build_way: this.build_way,
        tag_along: this.tag_along,
        skip_quivicare: this.skip_quivicare,
        upgrade_pce_enabled: this.upgrade_pce_enabled,
        upgrade_pce_notes: this.upgrade_pce_notes
      })
      .then(res => {
        console.log('Update response:', res.data); // Debug log
        this.isSaving = false;
        this.newOrderTotal = res.data.total || this.totalSub;

        // Show success modal
        $('#successModal').modal('show');

        // Reload order data to get updated values
        this.loadOrderData();

        this.showNotification(res.data.message || 'Order updated successfully', 'success');
      })
      .catch(err => {
        this.isSaving = false;
        console.error('Update error:', err);
        const errorMsg = err.response?.data?.error ||
                        err.response?.data?.message ||
                        'Failed to update order';
        this.showNotification(errorMsg, 'error');
      });

      window.location.reload();
    },

    cancelEdit() {
      this.$router.push('/orders/all');
    },

    formatNumber(value) {
      // Handle NaN and invalid values
      const num = Number(value);
      if (isNaN(num)) return '0.00';

      return num.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      });
    },

    // Unified notification handler
    showNotification(message, type = 'info') {
      if (notification && notification.customNoti) {
        notification.customNoti(message);
      } else if (notification && notification[type]) {
        notification[type](message);
      } else {
        // Fallback to different notification methods
        switch(type) {
          case 'success':
            if (notification && notification.successNotification) {
              notification.successNotification(message);
            } else {
              alert('SUCCESS: ' + message);
            }
            break;
          case 'error':
            if (notification && notification.errorNotification) {
              notification.errorNotification(message);
            } else {
              alert('ERROR: ' + message);
            }
            break;
          case 'warning':
            if (notification && notification.warningNotification) {
              notification.warningNotification(message);
            } else {
              alert('WARNING: ' + message);
            }
            break;
          default:
            alert(message);
        }
      }
    },

    // Load all necessary data
    loadAllData() {
      // Load categories
      axios.get('/api/categories/all')
        .then(res => {
          this.categories = res.data;
          console.log('Categories loaded:', this.categories.length);
        })
        .catch(err => {
          console.error('Error loading categories:', err);
          this.showNotification('Failed to load categories', 'error');
        });

      // Load sub-categories
      axios.get('/api/sub-categories/all')
        .then(res => {
          this.subCategoriesOptions = res.data;
          console.log('Sub-categories loaded:', this.subCategoriesOptions.length);
        })
        .catch(err => {
          console.error('Error loading sub-categories:', err);
          this.showNotification('Failed to load sub-categories', 'error');
        });

      // Load customers
      axios.get('/api/customer/all')
        .then(res => {
          this.customers = res.data;
          console.log('Customers loaded:', this.customers.length);
        })
        .catch(err => {
          console.error('Error loading customers:', err);
          this.showNotification('Failed to load customers', 'error');
        });

      this.loadProducts();
      this.loadOrderData();
    }
  },
  watch: {
    cartItems: {
      deep: true,
      handler() {
        // Debug: log cart items when they change
        console.log('Cart items changed:', this.cartItems);
        console.log('Total Sub:', this.totalSub);
        console.log('Total Cart:', this.totalCart);
      }
    },
    build_way(newVal) {
      if (newVal !== 'onsite') {
        this.tag_along = null;
      }
    }
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
h6.card-title {
  font-size: 12px !important;
  height: 36px;
  overflow: hidden;
}
.sub-category-wrapper .btn {
  border-radius: 50px;
  padding: 2px 15px;
  font-weight: 500;
}

/* Fix for NaN display */
td {
  vertical-align: middle !important;
}

input[type="number"] {
  text-align: center;
  border: 1px solid #ddd;
  border-radius: 3px;
}

.btn-sm {
  padding: 0.15rem 0.5rem;
  font-size: 0.75rem;
}
</style>
