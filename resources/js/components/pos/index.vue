<template>
  <div>
    <!-- Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
      <h1 class="h3 mb-0 text-gray-800">QuiviCraft</h1>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><router-link to="/">Home</router-link></li>
        <li class="breadcrumb-item active" aria-current="page">QuiviCraft</li>
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
              <li class="nav-item" v-for="category in sortedCategories" :key="category.id">
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
              <div class="col-lg-2 col-md-2 col-sm-12 col-12" v-for="product in displayedProducts" :key="product.id">
                <button
                  class="btn btn-sm col-lg-12"
                  @click.prevent="AddToCart(product.id)"
                  :disabled="!canAddToCart(product)"
                  :title="getCategoryRestrictionMessage(product)"
                >
                  <div class="card product-card" :class="{ 'disabled-product': !canAddToCart(product) }" style="margin-bottom: 5px;">
                    <div class="image-container">
                      <img :src="product.image" id="em_photo" :alt="product.product_name">
                      <!-- Hover Popup Image -->
                      <div class="hover-popup" @click.stop>
                        <div class="popup-image-wrapper">
                          <img :src="product.image" alt="Larger view">
                        </div>
                        <div class="popup-info">
                          <h6>{{ product.product_name }}</h6>
                          <p><strong>Price:</strong> RM {{ formatNumber(product.price) }}</p>
                          <p><strong>Last Updated:</strong> {{ formatDate(product.price_updated_at) }}</p>
                          <p><strong>Category:</strong> {{ product.category_name }}</p>
                          <p v-if="product.product_qty < 1" class="text-danger"><strong>Status:</strong> Stock Out</p>
                          <p v-else><strong>Status:</strong> In Stock</p>
                          <!-- Show restriction warning in popup -->
                          <p v-if="getCategoryRestrictionMessage(product)" class="text-warning restriction-warning">
                            ⚠️ {{ getCategoryRestrictionMessage(product) }}
                          </p>
                        </div>
                      </div>
                    </div>
                    <div class="card-body p-2">
                      <h6 class="card-title text-truncate" :title="product.product_name">{{ product.product_name }}</h6>
                      <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="badge badge-success price-badge">RM {{ formatNumber(product.price) }}</span>
                        <span class="badge badge-primary date-badge">
                            Updated: {{ formatDate(product.price_updated_at) }}
                        </span>
                        <span class="badge badge-danger stock-badge" v-if="product.product_qty < 1">Stock Out</span>
                        <!-- Show category restriction badge -->
                        <span v-if="getCategoryRestrictionMessage(product)" class="badge badge-warning restriction-badge">
                            Restricted
                        </span>
                      </div>
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
                      <td>
                        {{ cart.category_name }}
                        <span v-if="isMaxOneCategory(cart.category_name)" class="badge badge-info ml-1" title="One per category">1x</span>
                        <span v-if="cart.category_name === 'SSD' || cart.category_name === 'HDD'" class="badge badge-warning ml-1" :title="getMutualExclusivityMessage(cart.category_name)">
                          {{ cart.category_name === 'SSD' ? 'No HDD' : 'No SSD' }}
                        </span>
                      </td>
                      <td class="d-flex px-0">
                        <button @click.prevent="inc(cart.cart_id)" class="btn btn-success btn-sm p-1 mr-1" v-if="canIncreaseQuantity(cart)">+</button>
                        <input type="text" style="width:20px;border:none;text-align:center" readonly :value="cart.pro_qty">
                        <button v-if="cart.pro_qty != 1" @click.prevent="dec(cart.cart_id)" class="btn btn-danger btn-sm p-1 ml-1">-</button>
                      </td>
                      <td>{{ formatNumber(cart.pro_price) }}</td>
                      <td>{{ formatNumber(cart.sub_total) }}</td>
                      <td>
                        <a href="javascript:void(0)" @click="removeItem(cart.cart_id)" class="btn btn-sm btn-danger">X</a>
                      </td>
                    </tr>
                    <tr v-if="carts.length === 0">
                      <td colspan="6" class="text-center text-muted py-3">
                        No items in cart. Add products from the left panel.
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div class="card-footer">

                <!-- Validation Errors Alert -->
                <div v-if="cartValidationErrors.length > 0" class="alert alert-info alert-dismissible fade show" role="alert">
                    <strong>Cart Validation :</strong>
                    <ul class="mb-0 mt-1">
                        <li v-for="(error, index) in cartValidationErrors" :key="index">{{ error }}</li>
                    </ul>
                </div>

                <!-- Service Tiers -->
                <div class="alert alert-light">
                  <h6 class="alert-heading">Service Tier</h6>
                  <div v-if="totalSub <= 7000.00" class="mb-2">
                    <strong>QuiviCraft:</strong> Essential kit
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

                <!-- Category Summary -->
                <div v-if="carts.length > 0" class="alert alert-light border mb-3">
                  <h6 class="alert-heading mb-2">Cart Summary</h6>
                  <div class="small">
                    <div v-for="(catData, catName) in cartByCategory" :key="catName" class="d-flex justify-content-between">
                      <span>{{ catName }}:</span>
                      <span class="font-weight-bold">{{ catData.totalQty }} x item(s)</span>
                    </div>
                    <hr class="my-1">
                    <div class="d-flex justify-content-between">
                      <span>Total Items:</span>
                      <span class="font-weight-bold">{{ totalCart }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                      <span>Total Amount:</span>
                      <span class="font-weight-bold text-primary">RM {{ formatNumber(totalSub) }}</span>
                    </div>
                  </div>
                </div>

                <form @submit.prevent="orderdone">
                  <label class="mb-2">Customer Name</label>
                  <select class="form-control" v-model="customer_id" required>
                    <option value="" disabled>Select a customer</option>
                    <option v-for="customer in Customers" :key="customer.id" :value="customer.id">
                      {{ customer.full_name }}
                    </option>
                  </select>
                  <!-- Build Type Section - Radio Buttons -->
                  <div class="mt-3">
                      <label class="mb-2 font-weight-bold">Build Type</label>
                      <div class="d-flex">
                      <div class="form-check mr-4">
                          <input
                          class="form-check-input"
                          type="radio"
                          name="buildType"
                          id="buildWorking"
                          :value="1"
                          v-model="build_type"
                          >
                          <label class="form-check-label" for="buildWorking">
                          Workstation
                          </label>
                      </div>
                      <div class="form-check">
                          <input
                          class="form-check-input"
                          type="radio"
                          name="buildType"
                          id="buildGaming"
                          :value="2"
                          v-model="build_type"
                          >
                          <label class="form-check-label" for="buildGaming">
                          Gaming
                          </label>
                      </div>
                      </div>
                      <small class="text-muted">Select the purpose of this build (optional)</small>
                  </div>
                  <button class="btn btn-primary mt-3" type="submit" :disabled="carts.length === 0 || cartValidationErrors.length > 0">Submit Order</button>
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
      selectedSubCategoryId: null,
      build_type: null,
      // Category rules configuration based on requirements
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
    sortedCategories() {
        // Define your custom order
        const categoryOrder = [
        'CPU', 'MBD', 'GPU', 'RAM', 'SSD', 'HDD',
        'AIO', 'HSF', 'PSU', 'CSE', 'FAN', 'ACC',
        'PER-MON', 'PER-MOU', 'PER-HDS', 'PER-MIC',
        'PER-MSP', 'PER-KEY', 'PER-CAM',
        ];

        // Sort categories based on the custom order
        return [...this.categories].sort((a, b) => {
        const indexA = categoryOrder.indexOf(a.name);
        const indexB = categoryOrder.indexOf(b.name);

        // If category not found in order list, put it at the end
        if (indexA === -1) return 1;
        if (indexB === -1) return -1;

        return indexA - indexB;
        });
    },
    filteredSubCategories() {
      if (!this.selectedCategoryId) return [];
      return this.subCategoriesOptions.filter(sub => sub.cat_id == this.selectedCategoryId);
    },
    displayedProducts() {
      let products = this.CatProduct;

      // Filter by subcategory if selected
      if (this.selectedSubCategoryId) {
        products = products.filter(p => p.sub_cat_id === this.selectedSubCategoryId);
      }

      // Filter by search term
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
      return this.carts.reduce((sum, c) => sum + (parseFloat(c.pro_qty) * parseFloat(c.pro_price)), 0);
    },
    // Get cart items grouped by category with quantities
    cartByCategory() {
      const categoryMap = {};
      this.carts.forEach(item => {
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
  methods: {
    formatDate(dateString) {
      if (!dateString) return 'N/A';
      const date = new Date(dateString);
      return date.toLocaleDateString('en-MY', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });
    },

    formatNumber(value) {
      return Number(value).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      });
    },

    getCategoryName(categoryId) {
      const category = this.categories.find(c => c.id === categoryId);
      return category ? category.name : 'Unknown';
    },

    // Get category rule safely
    getCategoryRule(categoryName) {
      return this.categoryRules[categoryName] || null;
    },

    // Check if category has max of 1
    isMaxOneCategory(categoryName) {
      const rule = this.getCategoryRule(categoryName);
      return rule && rule.max === 1;
    },

    // Check if category has min of 1
    isMinOneCategory(categoryName) {
      const rule = this.getCategoryRule(categoryName);
      return rule && rule.min === 1;
    },

    getMutualExclusivityMessage(categoryName) {
      if (categoryName === 'SSD') return 'Cannot have HDD with SSD';
      if (categoryName === 'HDD') return 'Cannot have SSD with HDD';
      return '';
    },

    selectCategory(id) {
      this.selectedCategoryId = id;
      this.selectedSubCategoryId = null;

      if (id) {
        axios.get(`/api/getproductcategoy/${id}`)
          .then(res => {
            this.CatProduct = res.data;
          })
          .catch(err => {
            console.error('Error loading category products:', err);
            notification.customNoti('Error loading products');
          });
      } else {
        this.getEmp();
      }
    },

    selectSubCategory(id) {
      this.selectedSubCategoryId = id;
    },

    // Check if a product can be added to cart
    canAddToCart(product) {
      // Check if product is in stock
      if (product.product_qty < 1) return false;

      // Get category name for this product
      const category = this.categories.find(c => c.id === product.category_id);
      if (!category) return true;

      const categoryName = category.name;
      const rule = this.getCategoryRule(categoryName);

      // If category not in rules, allow
      if (!rule) return true;

      // Get current category quantity from cart
      const categoryItems = this.carts.filter(item => item.category_name === categoryName);
      const currentQty = categoryItems.reduce((sum, item) => sum + parseInt(item.pro_qty), 0);
      const existingProduct = this.carts.find(item => item.pro_id === product.id);

      // Check maximum limit for categories that require exactly 1
      if (rule.max === 1 && currentQty >= 1 && !existingProduct) {
        return false;
      }

      // Check mutual exclusivity
      if (rule.exclusiveWith && rule.exclusiveWith.length > 0) {
        for (const exclusiveCat of rule.exclusiveWith) {
          const exclusiveQty = this.carts.filter(item => item.category_name === exclusiveCat)
                                        .reduce((sum, item) => sum + parseInt(item.pro_qty), 0);
          if (exclusiveQty > 0) {
            return false;
          }
        }
      }

      // Special case: MBD requires CPU
      if (categoryName === 'MBD') {
        const cpuQty = this.carts.filter(item => item.category_name === 'CPU')
                                .reduce((sum, item) => sum + parseInt(item.pro_qty), 0);
        if (cpuQty === 0) {
          return false;
        }
      }

      return true;
    },

    // Check if quantity can be increased
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
          const exclusiveQty = this.carts.filter(item => item.category_name === exclusiveCat)
                                        .reduce((sum, item) => sum + parseInt(item.pro_qty), 0);
          if (exclusiveQty > 0) {
            return false;
          }
        }
      }

      return true;
    },

    // Get restriction message for tooltip
    getCategoryRestrictionMessage(product) {
      // Check if product is in stock
      if (product.product_qty < 1) {
        return 'This product is out of stock';
      }

      const category = this.categories.find(c => c.id === product.category_id);
      if (!category) return '';

      const categoryName = category.name;
      const rule = this.getCategoryRule(categoryName);

      if (!rule) return '';

      // Get current category quantity
      const categoryItems = this.carts.filter(item => item.category_name === categoryName);
      const currentQty = categoryItems.reduce((sum, item) => sum + parseInt(item.pro_qty), 0);
      const existingProduct = this.carts.find(item => item.pro_id === product.id);

      // Check maximum limit
      if (rule.max === 1 && currentQty >= 1 && !existingProduct) {
        return `⚠️ Maximum ${rule.max} ${categoryName} allowed`;
      }

      // Check mutual exclusivity
      if (rule.exclusiveWith && rule.exclusiveWith.length > 0) {
        for (const exclusiveCat of rule.exclusiveWith) {
          const exclusiveQty = this.carts.filter(item => item.category_name === exclusiveCat)
                                        .reduce((sum, item) => sum + parseInt(item.pro_qty), 0);
          if (exclusiveQty > 0) {
            return `⚠️ Cannot add ${categoryName} when ${exclusiveCat} is present`;
          }
        }
      }

      // Check dependencies
      if (categoryName === 'MBD' && !this.carts.some(item => item.category_name === 'CPU')) {
        return '⚠️ Motherboard requires CPU to be present';
      }

      // Check if product already in cart
      if (existingProduct) {
        if (rule.max === 1) {
          return '⚠️ Only one allowed per order (already in cart)';
        }
        return 'Click to increase quantity';
      }

      return '';
    },

    AddToCart(id) {
      // First check if product already exists in cart
      const existingCartItem = this.carts.find(item => item.pro_id === id);

      if (existingCartItem) {
        // Product already in cart
        const category = this.categories.find(c => c.id === existingCartItem.category_id);
        const rule = this.getCategoryRule(category?.name);

        // For categories with max 1, don't allow adding again
        if (category && rule && rule.max === 1) {
          notification.customNoti(`Only one ${category.name} product allowed per order. You cannot add more.`);
          return;
        }

        // For non-restricted, increase quantity
        this.inc(existingCartItem.cart_id);
        return;
      }

      // If not in cart, proceed with normal add
      axios.get(`/api/product/${id}`)
        .then(res => {
          const product = res.data;

          // Find the category name
          const category = this.categories.find(c => c.id === product.category_id);
          if (!category) {
            this.addToCartAPI(id);
            return;
          }

          const categoryName = category.name;
          const rule = this.getCategoryRule(categoryName);

          if (!rule) {
            this.addToCartAPI(id);
            return;
          }

          // Check maximum limit
          const categoryItems = this.carts.filter(item => item.category_name === categoryName);
          const currentQty = categoryItems.reduce((sum, item) => sum + parseInt(item.pro_qty), 0);

          if (rule.max === 1 && currentQty >= 1) {
            notification.customNoti(`Maximum ${rule.max} ${categoryName} product(s) allowed per order.`);
            return;
          }

          // Check mutual exclusivity
          if (rule.exclusiveWith && rule.exclusiveWith.length > 0) {
            for (const exclusiveCat of rule.exclusiveWith) {
              const exclusiveQty = this.carts.filter(item => item.category_name === exclusiveCat)
                                            .reduce((sum, item) => sum + parseInt(item.pro_qty), 0);
              if (exclusiveQty > 0) {
                notification.customNoti(`Cannot add ${categoryName} when ${exclusiveCat} is present.`);
                return;
              }
            }
          }

          // Check dependencies
          if (categoryName === 'MBD') {
            const cpuQty = this.carts.filter(item => item.category_name === 'CPU')
                                    .reduce((sum, item) => sum + parseInt(item.pro_qty), 0);
            if (cpuQty === 0) {
              notification.customNoti('Cannot add Motherboard without CPU');
              return;
            }
          }

          // All checks passed, add to cart
          this.addToCartAPI(id);
        })
        .catch(err => {
          console.error('Error fetching product details:', err);
          notification.customNoti('Error checking product details');
        });
    },

    addToCartAPI(id) {
      axios.get(`/api/addCart/${id}`)
        .then(res => {
          notification.customNoti(res.data);
          this.getCarts();
        })
        .catch(err => {
          console.error('Error adding to cart:', err);
          notification.customNoti('Error adding to cart');
        });
    },

    inc(id) {
      // Find the cart item
      const cartItem = this.carts.find(c => c.cart_id === id);
      if (!cartItem) return;

      const categoryName = cartItem.category_name;
      const rule = this.getCategoryRule(categoryName);

      // Check if this is a restricted category with max 1
      if (rule && rule.max === 1) {
        if (cartItem.pro_qty >= 1) {
          notification.customNoti(`Only one ${categoryName} product allowed per order.`);
          return;
        }
      }

      // Check if increasing would violate mutual exclusivity
      if (rule && rule.exclusiveWith && rule.exclusiveWith.length > 0) {
        for (const exclusiveCat of rule.exclusiveWith) {
          const exclusiveQty = this.carts.filter(item => item.category_name === exclusiveCat)
                                        .reduce((sum, item) => sum + parseInt(item.pro_qty), 0);
          if (exclusiveQty > 0) {
            notification.customNoti(`Cannot increase quantity when ${exclusiveCat} is present`);
            return;
          }
        }
      }

      axios.get(`/api/cart/cartInc/${id}`)
        .then(() => this.getCarts())
        .catch(err => {
          console.error('Error increasing quantity:', err);
          notification.customNoti('Error updating cart');
        });
    },

    dec(id) {
      axios.get(`/api/cart/cartDec/${id}`)
        .then(() => this.getCarts())
        .catch(err => {
          console.error('Error decreasing quantity:', err);
          notification.customNoti('Error updating cart');
        });
    },

    removeItem(id) {
      axios.get(`/api/cart/remove/${id}`)
        .then(() => {
          this.carts = this.carts.filter(c => c.cart_id != id);
          notification.customNoti('Item removed from cart');
        })
        .catch(err => {
          console.error('Error removing item:', err);
          notification.customNoti('Error removing item');
        });
    },

    getCarts() {
      axios.get('/api/carts/get')
        .then(res => {
          this.carts = res.data;
        })
        .catch(err => {
          console.error('Error loading cart:', err);
        });
    },

    getCustomer() {
      axios.get('/api/customer')
        .then(res => {
          this.Customers = res.data;
        })
        .catch(err => {
          console.error('Error loading customers:', err);
        });
    },

    getEmp() {
      axios.get('/api/product')
        .then(res => {
          this.suppliers = res.data;
          this.CatProduct = res.data;
        })
        .catch(err => {
          console.error('Error loading products:', err);
        });
    },

    getCat() {
      axios.get('/api/categories')
        .then(res => {
          this.categories = res.data;
        })
        .catch(err => {
          console.error('Error loading categories:', err);
        });
    },

    getSubCategoriesOptions() {
      axios.get('/api/sub-categories')
        .then(res => {
          this.subCategoriesOptions = res.data;
        })
        .catch(err => {
          console.error('Error loading subcategories:', err);
        });
    },

    orderdone() {
      if (!this.customer_id) {
        notification.customNoti('Please select a customer');
        return;
      }

      if (this.carts.length === 0) {
        notification.customNoti('Cart is empty');
        return;
      }

      // Check for validation errors
      if (this.cartValidationErrors.length > 0) {
        const errorMessage = 'Cart validation failed:\n' +
          this.cartValidationErrors.map(err => `• ${err}`).join('\n');
        notification.customNoti(errorMessage);
        return;
      }

      const data = {
        customer_id: this.customer_id,
        total_amount: this.totalSub,
        total_qty: this.totalCart,
        cart_items: this.carts,
        is_reason: this.build_type
      };

      axios.post('/api/orderdone', data)
        .then(res => {
          notification.customNoti(res.data.message || 'Order placed successfully!');
          this.carts = [];
          this.customer_id = '';
          this.build_type = null;
          this.getCarts();
        })
        .catch(err => {
          console.error('Error placing order:', err);
          notification.customNoti('Error placing order');
        });
    }
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
/* Product image styling */
img#em_photo {
  width: 100% !important;
  height: 120px !important;
  object-fit: contain;
  margin: auto;
  display: block;
  padding: 5px;
}

/* Fix product card to be consistent */
.product-card {
  width: 100% !important;
  height: 260px !important;
  margin-bottom: 10px;
  display: flex;
  flex-direction: column;
  border: 1px solid #e3e6f0;
  border-radius: 0.35rem;
  transition: all 0.2s;
}

.product-card:hover {
  box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
}

/* Image container */
.image-container {
  position: relative;
  width: 100%;
  height: 130px;
  overflow: visible;
  background: #f8f9fc;
  display: flex;
  align-items: center;
  justify-content: center;
  border-bottom: 1px solid #e3e6f0;
}

/* Card body */
.product-card .card-body {
  padding: 10px 8px !important;
  width: 100%;
  flex: 1;
  display: flex;
  flex-direction: column;
  background: white;
  border-bottom-left-radius: 0.35rem;
  border-bottom-right-radius: 0.35rem;
}

/* Product title */
.product-card .card-title {
  font-size: 12px !important;
  font-weight: 600;
  margin-bottom: 8px;
  line-height: 1.3;
  height: 30px;
  overflow: hidden;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  color: #333;
}

/* Badges container */
.product-card .badge-container {
  display: flex;
  flex-direction: column;
  gap: 4px;
  margin-top: auto;
}

/* Badge styling - LARGER FONTS */
.product-card .badge {
  font-size: 11px !important;
  padding: 5px 8px !important;
  font-weight: 600;
  white-space: normal;
  text-align: left;
  width: 100%;
  margin-bottom: 2px;
  border-radius: 4px;
  letter-spacing: 0.3px;
}

.product-card .badge-success {
  background-color: #1cc88a;
  color: white;
  font-size: 12px !important;
  font-weight: 700;
}

.product-card .badge-primary {
  background-color: #4e73df;
  color: white;
  font-size: 10px !important;
  font-weight: 600;
}

.product-card .badge-danger {
  background-color: #e74a3b;
  color: white;
  font-size: 11px !important;
  font-weight: 600;
}

.product-card .badge-warning {
  background-color: #f6c23e;
  color: #333;
  font-size: 11px !important;
  font-weight: 600;
  margin-top: 4px !important;
}

/* Price badge specific */
.price-badge {
  font-size: 13px !important;
  padding: 6px 8px !important;
}

/* Date badge specific */
.date-badge {
  font-size: 10px !important;
  background-color: #4e73df;
  color: white;
}

/* Stock badge specific */
.stock-badge {
  font-size: 11px !important;
  background-color: #e74a3b;
  color: white;
}

/* Restriction badge specific */
.restriction-badge {
  font-size: 11px !important;
  background-color: #f6c23e;
  color: #333;
}

/* Product button */
.product-btn {
  padding: 0;
  background: none;
  border: none;
  width: 100%;
  margin-bottom: 5px;
}

.product-btn:disabled {
  cursor: not-allowed;
  opacity: 0.8;
}

.product-btn:disabled .product-card {
  background-color: #f8f9fc;
}

/* Disabled product styling */
.product-card.disabled-product {
  opacity: 0.7;
  filter: grayscale(30%);
  border: 1px solid #f6c23e;
}

.product-card.disabled-product .image-container {
  background-color: #f8f9fa;
}

/* Sub-category buttons */
.sub-category-wrapper .btn {
  border-radius: 50px;
  padding: 4px 18px;
  font-weight: 600;
  font-size: 0.85rem;
}

/* HOVER POPUP STYLING - UPDATED TO SHOW ONLY ON IMAGE HOVER */
.hover-popup {
  position: absolute;
  top: 50%;
  left: 100%;
  transform: translateY(-50%) translateX(10px);
  z-index: 10000;
  display: none;
  background: white;
  border-radius: 12px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.2);
  padding: 15px;
  min-width: 350px;
  max-width: 400px;
  width: max-content;
  pointer-events: auto; /* Ensure popup can be interacted with */
}

/* Show popup ONLY when hovering over the image */
.image-container img:hover + .hover-popup {
  display: block;
  animation: fadeIn 0.2s ease-out;
}

/* Keep popup visible when hovering over it (allows interaction) */
.hover-popup:hover {
  display: block;
}

/* Position adjustment for items near the right edge */
.image-container:nth-child(4n) img:hover + .hover-popup,
.image-container:last-child img:hover + .hover-popup,
.image-container:nth-child(4n) .hover-popup:hover,
.image-container:last-child .hover-popup:hover {
  left: auto;
  right: 100%;
  transform: translateY(-50%) translateX(-10px);
}

.popup-image-wrapper {
  width: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
  background: #f8f9fa;
  border-radius: 8px;
  padding: 15px;
  margin-bottom: 15px;
}

.hover-popup img {
  width: 100%;
  height: auto;
  max-height: 300px;
  min-height: 200px;
  object-fit: contain;
  border-radius: 8px;
}

.popup-info {
  margin-top: 10px;
  text-align: left;
  padding: 0 5px;
}

.popup-info h6 {
  font-size: 18px;
  font-weight: bold;
  margin-bottom: 8px;
  color: #333;
  border-bottom: 1px solid #eee;
  padding-bottom: 5px;
}

.popup-info p {
  font-size: 14px;
  margin: 8px 0;
  color: #555;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.popup-info strong {
  color: #333;
  font-weight: 600;
}

.popup-info .restriction-warning {
  background-color: #fff3cd;
  color: #856404;
  padding: 8px;
  border-radius: 4px;
  margin-top: 10px;
  font-size: 13px;
  border-left: 3px solid #ffc107;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(-50%) translateX(10px) scale(0.9);
  }
  to {
    opacity: 1;
    transform: translateY(-50%) translateX(10px) scale(1);
  }
}

/* Animation for items showing on the left */
.image-container:nth-child(4n) img:hover + .hover-popup,
.image-container:last-child img:hover + .hover-popup {
  animation: fadeInLeft 0.2s ease-out;
}

@keyframes fadeInLeft {
  from {
    opacity: 0;
    transform: translateY(-50%) translateX(-10px) scale(0.9);
  }
  to {
    opacity: 1;
    transform: translateY(-50%) translateX(-10px) scale(1);
  }
}

/* Gap utility */
.gap-1 {
  gap: 0.25rem;
}
.gap-2 {
  gap: 0.5rem;
}

/* Cart table enhancements */
.table td {
  vertical-align: middle;
}

.table .btn-sm {
  padding: 3px 8px;
  font-size: 11px;
}

/* Alert enhancements */
.alert-light {
  background-color: #f8f9fa;
  border-color: #e9ecef;
}

.alert-light hr {
  border-top-color: #dee2e6;
}

/* Row spacing */
.row {
  margin-right: -5px;
  margin-left: -5px;
}

.row > [class*="col-"] {
  padding-right: 5px;
  padding-left: 5px;
}

/* Responsive design for larger screens */
@media (min-width: 1400px) {
  .hover-popup {
    min-width: 400px;
    max-width: 450px;
  }

  .hover-popup img {
    max-height: 350px;
  }

  .product-card {
    height: 270px !important;
  }

  .product-card .badge {
    font-size: 12px !important;
  }

  .product-card .badge-success {
    font-size: 13px !important;
  }
}

/* Tablet responsive */
@media (max-width: 992px) {
  .product-card {
    height: 250px !important;
  }

  .product-card .badge {
    font-size: 10px !important;
    padding: 4px 6px !important;
  }

  .product-card .badge-success {
    font-size: 11px !important;
  }
}

/* Mobile responsive - UPDATED */
@media (max-width: 768px) {
  .product-card {
    width: 100% !important;
    height: auto !important;
    min-height: 240px;
  }

  img#em_photo {
    height: 100px !important;
  }

  .image-container {
    height: 110px;
  }

  .product-card .card-title {
    font-size: 11px !important;
    height: 28px;
  }

  .product-card .badge {
    font-size: 10px !important;
    padding: 4px 6px !important;
  }

  .product-card .badge-success {
    font-size: 11px !important;
  }

  .product-card .badge-primary {
    font-size: 9px !important;
  }

  /* Mobile popup styling */
  .hover-popup {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    min-width: 90%;
    max-width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    z-index: 100000;
  }

  /* Show popup on image hover for mobile */
  .image-container img:hover + .hover-popup,
  .hover-popup:hover {
    display: block;
  }

  .popup-image-wrapper {
    max-height: 50vh;
  }

  .hover-popup img {
    max-height: 40vh;
    min-height: auto;
  }

  /* Center popup for all items on mobile */
  .image-container:nth-child(4n) img:hover + .hover-popup,
  .image-container:last-child img:hover + .hover-popup,
  .image-container:nth-child(4n) .hover-popup:hover,
  .image-container:last-child .hover-popup:hover {
    left: 50%;
    right: auto;
    transform: translate(-50%, -50%);
  }

  .image-container:nth-child(4n) img:hover + .hover-popup,
  .image-container:last-child img:hover + .hover-popup {
    animation: fadeInMobile 0.2s ease-out;
  }

  @keyframes fadeInMobile {
    from {
      opacity: 0;
      transform: translate(-50%, -45%) scale(0.9);
    }
    to {
      opacity: 1;
      transform: translate(-50%, -50%) scale(1);
    }
  }
}

/* Small mobile devices */
@media (max-width: 480px) {
  .hover-popup {
    min-width: 95%;
    max-width: 95%;
    padding: 10px;
  }

  .popup-info h6 {
    font-size: 16px;
  }

  .popup-info p {
    font-size: 13px;
  }

  .product-card .card-title {
    font-size: 10px !important;
  }

  .product-card .badge {
    font-size: 9px !important;
    padding: 3px 5px !important;
  }

  .product-card .badge-success {
    font-size: 10px !important;
  }
}
</style>
