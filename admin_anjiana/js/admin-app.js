/**
 * Sage Anjiana Admin Application Client
 * Modern Vanilla JavaScript connected with PHP RESTful APIs
 */

// Global App State
let salesChartInstance = null;
let detailedChartInstance = null;
let currentCurrency = 'Rs.';
let activeEditingProductId = null;
let activeProductImageFiles = []; // URLs or uploaded file paths
let cachedCategoriesList = [];
let currentVerifyingOrderData = null;

// Helper to resolve image paths whether running in /admin_anjiana/ or on root domain
function resolveAdminImageUrl(url) {
  if (!url) return '../images/placeholder.png';
  url = String(url).trim();
  if (!url) return '../images/placeholder.png';
  if (url.startsWith('http://') || url.startsWith('https://') || url.startsWith('data:')) {
    return url;
  }
  if (url.startsWith('../')) {
    return url;
  }
  if (url.startsWith('/')) {
    return '..' + url;
  }
  return '../' + url;
}

// ----------------------------------------------------
// 1. ROUTING & TAB NAVIGATION ENGINE
// ----------------------------------------------------

const VIEWS = {
  dashboard: { title: "Dashboard Overview", desc: "Real-time snapshot of your store's sales and performance." },
  products: { title: "Product Catalog", desc: "Manage clothing listings, stock levels, and store visibility." },
  "add-product": { title: "Add / Edit Clothing Item", desc: "Configure product details, images, and variant stock matrices." },
  categories: { title: "Category Management", desc: "Organize clothing by departments, subcategories, and targets." },
  inventory: { title: "Inventory Management", desc: "Track variant quantities, log restocks, and review adjustment audit trails." },
  payments: { title: "Payment Ledger", desc: "Monitor transactions, verify bank deposit slips, and manage COD collections." },
  orders: { title: "Orders Management", desc: "Review, verify, approve, and dispatch storefront orders." },
  shipping: { title: "Orders & Shipping", desc: "Review orders, configure shipping fees, and track couriers." },
  discounts: { title: "Discounts & Campaigns", desc: "Manage promotional coupons and homepage hero banner slides." },
  reviews: { title: "Customer Reviews", desc: "Moderate customer ratings, approve feedback, and publish admin replies." },
  reports: { title: "Reports & Analytics", desc: "Analyze revenue trends, stock valuations, and product view counts." },
  staff: { title: "Staff & Permissions", desc: "Manage authorized staff accounts, permission roles, and audit access logs." },
  settings: { title: "Store Settings", desc: "Configure store parameters, currency, tax rates, and SMS notifications." }
};

function switchView(hash) {
  let cleanHash = hash.replace(/^#/, '');
  let queryParams = {};

  if (cleanHash.includes('?')) {
    const parts = cleanHash.split('?');
    cleanHash = parts[0];
    const queryStr = parts[1];
    const sp = new URLSearchParams(queryStr);
    for (const [k, v] of sp.entries()) {
      queryParams[k] = v;
    }
  }

  if (!cleanHash || !VIEWS[cleanHash]) {
    cleanHash = 'dashboard';
  }

  // Hide all panes
  document.querySelectorAll('.tab-pane').forEach(el => el.classList.remove('active'));

  // Show target pane
  const targetPane = document.getElementById(cleanHash);
  if (targetPane) {
    targetPane.classList.add('active');
  }

  // Update header text
  const viewInfo = VIEWS[cleanHash];
  if (viewInfo) {
    document.getElementById('viewTitle').textContent = viewInfo.title;
    document.getElementById('viewDesc').textContent = viewInfo.desc;
  }

  // Highlight active sidebar item
  document.querySelectorAll('.admin-nav .nav-item').forEach(el => {
    if (el.getAttribute('data-tab') === cleanHash) {
      el.classList.add('active');
    } else {
      el.classList.remove('active');
    }
  });

  // Trigger view data loader
  triggerViewData(cleanHash, queryParams);
}

function triggerViewData(viewName, params) {
  switch (viewName) {
    case 'dashboard':
      loadDashboardKPIs();
      break;
    case 'products':
      loadProductsList();
      break;
    case 'add-product':
      initAddProductForm(params.id);
      break;
    case 'categories':
      loadCategoriesList();
      break;
    case 'inventory':
      loadInventoryView();
      break;
    case 'payments':
      loadPaymentsLedger();
      break;
    case 'orders':
    case 'shipping':
      loadOrdersView();
      break;
    case 'discounts':
      loadDiscountsView();
      break;
    case 'reviews':
      loadReviewsList();
      break;
    case 'reports':
      loadReportsView();
      break;
    case 'staff':
      loadStaffView();
      break;
    case 'settings':
      loadSettingsView();
      break;
  }
}

// ----------------------------------------------------
// 2. TOAST NOTIFICATIONS & FEEDBACK UTILITIES
// ----------------------------------------------------

function showToast(message, isError = false) {
  const toast = document.getElementById('toastNotification');
  const toastIcon = document.getElementById('toastIcon');
  const toastTitle = document.getElementById('toastTitle');
  const toastMessage = document.getElementById('toastMessage');

  if (!toast) return;

  toastTitle.textContent = isError ? 'Error' : 'Success';
  toastMessage.textContent = message;
  toastIcon.textContent = isError ? '✕' : '✓';
  toastIcon.style.background = isError ? '#FFEBEE' : '#E8F5E9';
  toastIcon.style.color = isError ? '#C62828' : '#2E7D32';

  toast.classList.add('show');
  setTimeout(() => {
    toast.classList.remove('show');
  }, 4000);
}

function triggerSmsSimulator(messageText) {
  const widget = document.getElementById('smsSimulatorWidget');
  const body = document.getElementById('smsSimulatorBody');
  if (!widget || !body) return;

  body.textContent = messageText;
  widget.classList.add('active');

  setTimeout(() => {
    widget.classList.remove('active');
  }, 6000);
}

// ----------------------------------------------------
// 3. DASHBOARD MODULE
// ----------------------------------------------------

async function loadDashboardKPIs() {
  try {
    const res = await fetch('api/dashboard.php');
    const data = await res.json();

    if (!data) return;
    currentCurrency = data.currency || 'Rs.';

    document.getElementById('kpiSales').textContent = data.totalSalesFmt || (currentCurrency + ' 0.00');
    document.getElementById('kpiOrders').textContent = data.totalOrders || '0';
    document.getElementById('kpiCustomers').textContent = data.totalCustomers || '0';
    document.getElementById('kpiLowStock').textContent = data.lowStockCount || '0';

    const pndText = document.getElementById('kpiPendingText');
    if (pndText) pndText.textContent = `${data.pendingOrders || 0} pending fulfillment`;

    const lowStockFooter = document.getElementById('kpiLowStockFooter');
    if (lowStockFooter) {
      if (data.lowStockCount > 0) {
        lowStockFooter.textContent = `⚠️ ${data.lowStockCount} items need restocking`;
        lowStockFooter.style.color = 'var(--error-color)';
      } else {
        lowStockFooter.textContent = 'All variant stocks healthy';
        lowStockFooter.style.color = 'var(--success-color)';
      }
    }

    // Render Sales Chart
    renderSalesChart(data.chartLabels, data.chartData);

    // Render Best Sellers
    renderBestSellers(data.bestSellers);

    // Render Recent Orders
    renderRecentOrders(data.recentOrders);

  } catch (err) {
    console.error('Error loading dashboard data:', err);
  }
}

function renderSalesChart(labels, values) {
  const ctx = document.getElementById('salesChart');
  if (!ctx) return;

  if (salesChartInstance) {
    salesChartInstance.destroy();
  }

  salesChartInstance = new Chart(ctx, {
    type: 'line',
    data: {
      labels: labels && labels.length ? labels : ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
      datasets: [{
        label: `Monthly Revenue (${currentCurrency})`,
        data: values && values.length ? values : [0, 0, 0, 0, 0, 0],
        borderColor: '#D4AF37',
        backgroundColor: 'rgba(212, 175, 55, 0.1)',
        borderWidth: 3,
        fill: true,
        tension: 0.35,
        pointBackgroundColor: '#D4AF37',
        pointRadius: 5
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false }
      },
      scales: {
        y: {
          beginAtZero: true,
          grid: { color: 'rgba(255,255,255,0.05)' }
        },
        x: {
          grid: { display: false }
        }
      }
    }
  });
}

function renderBestSellers(products) {
  const container = document.getElementById('bestSellersContainer');
  if (!container) return;

  if (!products || products.length === 0) {
    container.innerHTML = `<p class="text-muted" style="text-align: center; padding: 2rem 0;">No sales recorded yet.</p>`;
    return;
  }

  container.innerHTML = products.map((p, idx) => `
    <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid var(--border-color);">
      <div style="display: flex; align-items: center; gap: 12px;">
        <span style="font-weight: 700; color: var(--accent-color); font-size: 0.9rem;">#${idx + 1}</span>
        <img src="${resolveAdminImageUrl(p.image_url)}" onerror="this.onerror=null; this.src='../images/placeholder.png';" style="width: 40px; height: 40px; object-fit: cover; border-radius: 6px;">
        <div>
          <div style="font-weight: 600; font-size: 0.9rem; color: var(--primary-color);">${escapeHtml(p.product_name)}</div>
          <div style="font-size: 0.75rem; color: var(--text-muted);">${p.units_sold ? `${p.units_sold} units sold` : 'Featured item'}</div>
        </div>
      </div>
      <strong style="color: var(--accent-color); font-size: 0.9rem;">${currentCurrency} ${parseFloat(p.total_revenue || 0).toFixed(2)}</strong>
    </div>
  `).join('');
}

function renderRecentOrders(orders) {
  const tbody = document.getElementById('recentOrdersTableBody');
  if (!tbody) return;

  if (!orders || orders.length === 0) {
    tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">No recent orders placed.</td></tr>`;
    return;
  }

  tbody.innerHTML = orders.map(o => {
    let badgeClass = 'status-pending';
    if (o.order_status === 'Verified' || o.order_status === 'Delivered') badgeClass = 'status-verified';
    if (o.order_status === 'Rejected' || o.order_status === 'Cancelled') badgeClass = 'status-rejected';
    if (o.payment_status === 'Verifying Slip') badgeClass = 'status-verifying';

    return `
      <tr>
        <td><strong>${escapeHtml(o.order_number)}</strong></td>
        <td>
          <div style="font-weight: 500;">${escapeHtml(o.first_name + ' ' + o.last_name)}</div>
          <small class="text-muted">${escapeHtml(o.email)}</small>
        </td>
        <td><span class="pos-chip" style="font-size: 0.75rem;">${escapeHtml(o.payment_method)}</span></td>
        <td>${escapeHtml(o.city || 'Standard')}</td>
        <td><strong>${currentCurrency} ${parseFloat(o.total_amount).toFixed(2)}</strong></td>
        <td><span class="pos-chip ${badgeClass}" style="font-size: 0.75rem;">${escapeHtml(o.order_status)}</span></td>
        <td>
          <button class="btn btn-secondary" onclick="viewOrderDetail(${o.id})" style="padding: 4px 10px; font-size: 0.8rem;">View</button>
        </td>
      </tr>
    `;
  }).join('');
}

// ----------------------------------------------------
// 4. PRODUCTS MANAGEMENT MODULE
// ----------------------------------------------------

async function loadProductsList() {
  const q = document.getElementById('productSearchInput')?.value || '';
  const gender = document.getElementById('productFilterGender')?.value || '';
  const tbody = document.getElementById('productsTableBody');
  if (!tbody) return;

  try {
    const res = await fetch(`api/products.php?q=${encodeURIComponent(q)}&gender=${encodeURIComponent(gender)}`);
    const data = await res.json();

    if (!data.success || !data.products || data.products.length === 0) {
      tbody.innerHTML = `<tr><td colspan="8" style="text-align: center; color: var(--text-muted); padding: 2rem 0;">No products found in catalog.</td></tr>`;
      return;
    }

    tbody.innerHTML = data.products.map(p => {
      const isAvailable = p.status === 'active';
      const discountVal = parseFloat(p.discount || 0);

      return `
        <tr>
          <td><img src="${resolveAdminImageUrl(p.image_url)}" onerror="this.onerror=null; this.src='../images/placeholder.png';" style="width: 44px; height: 44px; object-fit: cover; border-radius: 6px;"></td>
          <td>
            <div style="font-weight: 600; color: var(--primary-color);">${escapeHtml(p.name)}</div>
            <small class="text-muted">SKU: ${escapeHtml(p.sku || 'ANJ-' + p.id)}</small>
          </td>
          <td>${escapeHtml(p.brand || 'Sage Anjiana')}</td>
          <td><span class="pos-chip" style="font-size: 0.75rem;">${escapeHtml(p.category)}</span></td>
          <td>
            <strong>${currentCurrency} ${parseFloat(p.price).toFixed(2)}</strong>
            ${discountVal > 0 ? `<br><small style="color: var(--accent-color); font-weight: 600;">-${discountVal}% Off</small>` : ''}
          </td>
          <td>
            <strong style="color: ${p.stock <= 5 ? 'var(--error-color)' : 'var(--primary-color)'};">${p.stock} units</strong>
          </td>
          <td>
            <span class="pos-chip ${isAvailable ? 'status-verified' : 'status-rejected'}" style="font-size: 0.75rem;">
              ${isAvailable ? 'Available' : 'Out of Stock'}
            </span>
          </td>
          <td>
            <div style="display: flex; gap: 6px;">
              <a href="#add-product?id=${p.id}" class="btn btn-secondary" style="padding: 4px 10px; font-size: 0.8rem;">Edit</a>
              <button class="btn btn-secondary" onclick="deleteProduct(${p.id}, '${escapeHtml(p.name)}')" style="padding: 4px 8px; font-size: 0.8rem; color: var(--error-color);">🗑️</button>
            </div>
          </td>
        </tr>
      `;
    }).join('');

  } catch (err) {
    console.error('Error loading products:', err);
    tbody.innerHTML = `<tr><td colspan="8" style="text-align: center; color: var(--error-color); padding: 2rem;">Error loading products database.</td></tr>`;
  }
}

async function deleteProduct(id, name) {
  if (!confirm(`Are you sure you want to delete product "${name}"?`)) return;

  try {
    const res = await fetch(`api/products.php?id=${id}`, { method: 'DELETE' });
    const data = await res.json();
    if (data.success) {
      showToast('Product deleted successfully.');
      loadProductsList();
    } else {
      showToast(data.error || 'Failed to delete product.', true);
    }
  } catch (err) {
    showToast('Network error during deletion.', true);
  }
}

// ----------------------------------------------------
// 5. ADD / EDIT PRODUCT FORM MODULE
// ----------------------------------------------------

async function initAddProductForm(productId) {
  activeEditingProductId = productId || null;
  activeProductImageFiles = [];

  // Populate category options
  await populateCategoriesDropdown();

  const titleEl = document.getElementById('addProductTitle');
  const editIdInput = document.getElementById('editProductId');
  const form = document.getElementById('productForm');
  const previewsContainer = document.getElementById('multiImagePreviews');

  if (previewsContainer) previewsContainer.innerHTML = '';
  if (editIdInput) editIdInput.value = productId || '';

  if (productId) {
    titleEl.textContent = 'Edit Clothing Item #' + productId;
    try {
      const res = await fetch(`api/products.php?id=${productId}`);
      const data = await res.json();
      if (data.success && data.product) {
        const p = data.product;
        document.getElementById('prodName').value = p.name || '';
        document.getElementById('prodBrand').value = p.brand || 'Sage Anjiana';
        document.getElementById('prodCategory').value = p.category || '';
        document.getElementById('prodGender').value = p.gender || 'Women';
        document.getElementById('prodMaterial').value = p.material || '';
        document.getElementById('prodPrice').value = p.price || '';
        document.getElementById('prodDiscount').value = p.discount || '';
        document.getElementById('prodSku').value = p.sku || '';
        document.getElementById('prodDesc').value = p.description || '';
        document.getElementById('prodSingleStock').value = p.stock || '0';

        // Select Status
        const statusRadios = document.getElementsByName('prodStatus');
        statusRadios.forEach(r => {
          if (p.status === 'active' && r.value === 'Available') r.checked = true;
          if (p.status === 'inactive' && r.value === 'Out of Stock') r.checked = true;
        });

        // Parse Sizes & Colors
        if (p.sizes) {
          const szs = p.sizes.split(',');
          document.querySelectorAll('#sizeSelectGroup input[type=checkbox]').forEach(cb => {
            cb.checked = szs.includes(cb.value);
          });
        }
        if (p.colors) {
          const cls = p.colors.split(',');
          document.querySelectorAll('#colorSelectGroup input[type=checkbox]').forEach(cb => {
            cb.checked = cls.includes(cb.value);
          });
        }

        // Image previews
        if (p.image_url) {
          activeProductImageFiles.push(p.image_url);
        }
        if (p.additional_images) {
          try {
            const addl = JSON.parse(p.additional_images);
            if (Array.isArray(addl)) {
              addl.forEach(img => activeProductImageFiles.push(img));
            }
          } catch(e) {}
        }
        renderImagePreviews();
        renderVariantMatrix();
      }
    } catch(err) {
      showToast('Error loading product details for editing.', true);
    }
  } else {
    titleEl.textContent = 'Add New Clothing Item';
    if (form) form.reset();
    renderVariantMatrix();
  }
}

async function populateCategoriesDropdown() {
  const select = document.getElementById('prodCategory');
  if (!select) return;

  try {
    const res = await fetch('api/categories.php');
    const data = await res.json();
    if (data.success && data.categories) {
      cachedCategoriesList = data.categories;
      select.innerHTML = data.categories.map(c => `
        <option value="${escapeHtml(c.name)}">${escapeHtml(c.name)} (${escapeHtml(c.gender)})</option>
      `).join('');
    }
  } catch(err) {}
}

function renderVariantMatrix() {
  const selectedSizes = Array.from(document.querySelectorAll('#sizeSelectGroup input:checked')).map(cb => cb.value);
  const selectedColors = Array.from(document.querySelectorAll('#colorSelectGroup input:checked')).map(cb => cb.value);

  const matrixContainer = document.getElementById('variantMatrixContainer');
  const singleContainer = document.getElementById('singleStockContainer');
  const matrixBody = document.getElementById('variantMatrixBody');

  if (selectedSizes.length > 0 && selectedColors.length > 0) {
    matrixContainer.style.display = 'block';
    singleContainer.style.display = 'none';

    let html = '';
    selectedSizes.forEach(sz => {
      selectedColors.forEach(col => {
        const skuPrefix = document.getElementById('prodSku')?.value || 'ANJ';
        const vSku = `${skuPrefix}-${sz}-${col.substring(0, 3).toUpperCase()}`;
        html += `
          <tr data-size="${sz}" data-color="${col}">
            <td><strong>${sz}</strong></td>
            <td>${col}</td>
            <td><input type="number" class="form-control matrix-qty-input" style="width: 90px; padding: 4px 8px; margin: 0;" value="5" min="0"></td>
            <td><input type="text" class="form-control matrix-sku-input" style="width: 140px; padding: 4px 8px; margin: 0;" value="${vSku}"></td>
          </tr>
        `;
      });
    });
    matrixBody.innerHTML = html;
  } else {
    matrixContainer.style.display = 'none';
    singleContainer.style.display = 'block';
  }
}

// Bulk stock button
document.addEventListener('click', (e) => {
  if (e.target && e.target.id === 'bulkStockApplyBtn') {
    const val = parseInt(document.getElementById('bulkStockInput')?.value || 0);
    document.querySelectorAll('.matrix-qty-input').forEach(input => {
      input.value = val;
    });
  }
});

// Image previews
function renderImagePreviews() {
  const container = document.getElementById('multiImagePreviews');
  if (!container) return;

  container.innerHTML = activeProductImageFiles.map((url, idx) => `
    <div style="position: relative; display: inline-block; margin-right: 10px; margin-top: 10px;">
      <img src="${resolveAdminImageUrl(url)}" onerror="this.onerror=null; this.src='../images/placeholder.png';" style="width: 70px; height: 70px; object-fit: cover; border-radius: 6px; border: 1px solid var(--border-color);">
      <button type="button" onclick="removeProductImage(${idx})" style="position: absolute; top: -6px; right: -6px; background: #C62828; color: #fff; border: none; border-radius: 50%; width: 20px; height: 20px; cursor: pointer; font-size: 11px; line-height: 1;">×</button>
      ${idx === 0 ? '<span style="position: absolute; bottom: 2px; left: 2px; background: rgba(0,0,0,0.6); color: #fff; font-size: 9px; padding: 1px 4px; border-radius: 3px;">Primary</span>' : ''}
    </div>
  `).join('');
}

function removeProductImage(index) {
  activeProductImageFiles.splice(index, 1);
  renderImagePreviews();
}

// Multi-image upload dropzone click & change
document.addEventListener('DOMContentLoaded', () => {
  const dropzone = document.getElementById('multiImageDropzone');
  const fileInput = document.getElementById('prodImagesInput');

  if (dropzone && fileInput) {
    dropzone.addEventListener('click', () => fileInput.click());

    fileInput.addEventListener('change', async () => {
      if (!fileInput.files || fileInput.files.length === 0) return;

      const formData = new FormData();
      for (let i = 0; i < fileInput.files.length; i++) {
        formData.append('images[]', fileInput.files[i]);
      }

      showToast('Uploading images to server...');
      try {
        const res = await fetch('api/upload.php?folder=products', {
          method: 'POST',
          body: formData
        });
        const data = await res.json();
        if (data.success && data.urls) {
          data.urls.forEach(url => activeProductImageFiles.push(url));
          renderImagePreviews();
          showToast('Images uploaded successfully.');
        } else {
          showToast(data.error || 'Upload error.', true);
        }
      } catch (err) {
        showToast('Network error during image upload.', true);
      }
    });
  }

  // Size/Color change matrix triggers
  document.querySelectorAll('#sizeSelectGroup input, #colorSelectGroup input').forEach(cb => {
    cb.addEventListener('change', renderVariantMatrix);
  });

  // Product Form Submit
  const prodForm = document.getElementById('productForm');
  if (prodForm) {
    prodForm.addEventListener('submit', async (e) => {
      e.preventDefault();

      const selectedSizes = Array.from(document.querySelectorAll('#sizeSelectGroup input:checked')).map(cb => cb.value);
      const selectedColors = Array.from(document.querySelectorAll('#colorSelectGroup input:checked')).map(cb => cb.value);

      // Collect variant matrix
      let variantMatrix = [];
      document.querySelectorAll('#variantMatrixBody tr').forEach(tr => {
        variantMatrix.push({
          size: tr.getAttribute('data-size'),
          color: tr.getAttribute('data-color'),
          qty: parseInt(tr.querySelector('.matrix-qty-input')?.value || 0),
          sku: tr.querySelector('.matrix-sku-input')?.value || ''
        });
      });

      const payload = {
        editProductId: document.getElementById('editProductId')?.value || '',
        name: document.getElementById('prodName')?.value.trim(),
        brand: document.getElementById('prodBrand')?.value.trim(),
        category: document.getElementById('prodCategory')?.value,
        gender: document.getElementById('prodGender')?.value,
        material: document.getElementById('prodMaterial')?.value.trim(),
        price: parseFloat(document.getElementById('prodPrice')?.value || 0),
        discount: parseFloat(document.getElementById('prodDiscount')?.value || 0),
        sku: document.getElementById('prodSku')?.value.trim(),
        status: document.querySelector('input[name="prodStatus"]:checked')?.value || 'Available',
        singleStock: parseInt(document.getElementById('prodSingleStock')?.value || 0),
        description: document.getElementById('prodDesc')?.value.trim(),
        sizes: selectedSizes,
        colors: selectedColors,
        variantMatrix: variantMatrix.length ? variantMatrix : null,
        imageUrl: activeProductImageFiles[0] || 'images/placeholder.png',
        additionalImages: activeProductImageFiles.slice(1)
      };

      try {
        const res = await fetch('api/products.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) {
          showToast(data.message || 'Product saved successfully!');
          window.location.hash = 'products';
        } else {
          showToast(data.error || 'Failed to save product.', true);
        }
      } catch (err) {
        showToast('Network error while saving product.', true);
      }
    });
  }
});

// ----------------------------------------------------
// 6. CATEGORIES MODULE
// ----------------------------------------------------

async function loadCategoriesList() {
  const tbody = document.getElementById('categoriesTableBody');
  const q = document.getElementById('catSearchInput')?.value || '';
  const hierarchy = document.getElementById('catHierarchyFilter')?.value || '';
  const gender = document.getElementById('catGenderFilter')?.value || '';

  if (!tbody) return;

  try {
    const res = await fetch(`api/categories.php?q=${encodeURIComponent(q)}&hierarchy=${encodeURIComponent(hierarchy)}&gender=${encodeURIComponent(gender)}`);
    const data = await res.json();

    if (!data.success || !data.categories || data.categories.length === 0) {
      tbody.innerHTML = `<tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 2rem;">No categories found.</td></tr>`;
      return;
    }

    tbody.innerHTML = data.categories.map(c => `
      <tr>
        <td><img src="${resolveAdminImageUrl(c.image || 'images/women_new.png')}" onerror="this.onerror=null; this.src='../images/women_new.png';" style="width: 36px; height: 36px; object-fit: cover; border-radius: 6px;"></td>
        <td><strong>${escapeHtml(c.name)}</strong></td>
        <td><span class="pos-chip" style="font-size: 0.75rem;">${escapeHtml(c.gender)}</span></td>
        <td>${c.parent_category ? `<span class="text-muted">Sub of <strong>${escapeHtml(c.parent_category)}</strong></span>` : '<span class="text-muted">Top-Level</span>'}</td>
        <td>
          <button class="btn btn-secondary" onclick="editCategory(${c.id}, '${escapeHtml(c.name)}', '${escapeHtml(c.gender)}', '${escapeHtml(c.parent_category || '')}', '${escapeHtml(c.image || '')}')" style="padding: 4px 8px; font-size: 0.8rem;">Edit</button>
          <button class="btn btn-secondary" onclick="deleteCategory(${c.id})" style="padding: 4px 8px; font-size: 0.8rem; color: var(--error-color);">🗑️</button>
        </td>
      </tr>
    `).join('');

  } catch(err) {
    console.error('Error loading categories:', err);
  }
}

function editCategory(id, name, gender, parent, image) {
  document.getElementById('editCategoryId').value = id;
  document.getElementById('catName').value = name;
  document.getElementById('catGender').value = gender;
  document.getElementById('catParent').value = parent;
  document.getElementById('catImage').value = image;
  document.getElementById('categoryFormTitle').textContent = 'Edit Category #' + id;
  document.getElementById('resetCategoryFormBtn').style.display = 'inline-block';
}

function resetCategoryForm() {
  document.getElementById('categoryForm').reset();
  document.getElementById('editCategoryId').value = '';
  document.getElementById('categoryFormTitle').textContent = 'Add Category';
  document.getElementById('resetCategoryFormBtn').style.display = 'none';
}

async function deleteCategory(id) {
  if (!confirm('Are you sure you want to delete this category?')) return;
  try {
    const res = await fetch(`api/categories.php?id=${id}`, { method: 'DELETE' });
    const data = await res.json();
    if (data.success) {
      showToast('Category deleted.');
      loadCategoriesList();
    } else {
      showToast(data.error || 'Failed to delete category.', true);
    }
  } catch(err) {
    showToast('Network error.', true);
  }
}

// Category form listener
document.addEventListener('DOMContentLoaded', () => {
  const catForm = document.getElementById('categoryForm');
  if (catForm) {
    catForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const payload = {
        id: document.getElementById('editCategoryId').value,
        name: document.getElementById('catName').value.trim(),
        gender: document.getElementById('catGender').value,
        parent: document.getElementById('catParent').value,
        image: document.getElementById('catImage').value.trim()
      };

      try {
        const res = await fetch('api/categories.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) {
          showToast(data.message);
          resetCategoryForm();
          loadCategoriesList();
        } else {
          showToast(data.error, true);
        }
      } catch(err) {
        showToast('Network error while saving category.', true);
      }
    });
  }

  const resetCatBtn = document.getElementById('resetCategoryFormBtn');
  if (resetCatBtn) resetCatBtn.addEventListener('click', resetCategoryForm);

  // Category filter triggers
  ['catSearchInput', 'catHierarchyFilter', 'catGenderFilter'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('input', loadCategoriesList);
  });
});

// ----------------------------------------------------
// 7. INVENTORY MODULE & RESTOCK MODAL
// ----------------------------------------------------

async function loadInventoryView() {
  const tbody = document.getElementById('inventoryTableBody');
  const timeline = document.getElementById('stockLogsTimeline');
  const q = document.getElementById('inventorySearch')?.value || '';
  const filter = document.getElementById('inventoryStockFilter')?.value || 'all';

  if (!tbody) return;

  try {
    const res = await fetch(`api/inventory.php?q=${encodeURIComponent(q)}&filter=${encodeURIComponent(filter)}`);
    const data = await res.json();

    if (!data.success || !data.inventory || data.inventory.length === 0) {
      tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2rem;">No inventory rows matched filters.</td></tr>`;
    } else {
      tbody.innerHTML = data.inventory.map(row => {
        let stockStyle = 'color: var(--primary-color); font-weight: 700;';
        if (row.stock <= 5 && row.stock > 0) stockStyle = 'color: #F57F17; font-weight: 800;';
        if (row.stock === 0) stockStyle = 'color: #C62828; font-weight: 800;';

        return `
          <tr>
            <td>
              <div style="display: flex; align-items: center; gap: 10px;">
                <img src="${resolveAdminImageUrl(row.imageUrl)}" onerror="this.onerror=null; this.src='../images/placeholder.png';" style="width: 36px; height: 36px; object-fit: cover; border-radius: 4px;">
                <span style="font-weight: 600;">${escapeHtml(row.name)}</span>
              </div>
            </td>
            <td>${escapeHtml(row.size)}</td>
            <td>${escapeHtml(row.color)}</td>
            <td><code style="font-size: 0.8rem;">${escapeHtml(row.sku)}</code></td>
            <td><span style="${stockStyle}">${row.stock} units</span></td>
            <td>
              <button class="btn btn-secondary" onclick="openRestockModal(${row.productId}, '${escapeHtml(row.name)}', '${escapeHtml(row.size)}', '${escapeHtml(row.color)}', ${row.stock})" style="padding: 4px 10px; font-size: 0.8rem;">+ Restock</button>
            </td>
          </tr>
        `;
      }).join('');
    }

    // Render History Timeline
    if (timeline && data.history) {
      if (data.history.length === 0) {
        timeline.innerHTML = `<p class="text-muted" style="text-align: center; padding: 1.5rem;">No stock adjustments logged yet.</p>`;
      } else {
        timeline.innerHTML = data.history.map(h => `
          <div style="padding: 0.75rem 0; border-bottom: 1px dashed var(--border-color); font-size: 0.85rem;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 0.2rem;">
              <strong style="color: var(--primary-color);">${escapeHtml(h.product_name)} (${escapeHtml(h.variant_size)} / ${escapeHtml(h.variant_color)})</strong>
              <span style="color: var(--success-color); font-weight: 700;">+${h.change_qty} units</span>
            </div>
            <div style="color: var(--text-muted); font-size: 0.78rem;">
              By: ${escapeHtml(h.created_by)} • New total: <strong>${h.new_stock}</strong> • ${h.created_at}
            </div>
          </div>
        `).join('');
      }
    }

  } catch(err) {
    console.error('Error loading inventory:', err);
  }
}

function openRestockModal(productId, name, size, color, currentStock) {
  document.getElementById('restockProductId').value = productId;
  document.getElementById('restockSize').value = size;
  document.getElementById('restockColor').value = color;
  document.getElementById('restockDetailsText').innerHTML = `Restocking: <strong>${name}</strong><br>Variant: ${size} - ${color} (Current stock: ${currentStock})`;
  document.getElementById('restockModal').classList.add('active');
}

// Restock form submit
document.addEventListener('DOMContentLoaded', () => {
  const restockForm = document.getElementById('restockForm');
  if (restockForm) {
    restockForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const payload = {
        productId: document.getElementById('restockProductId').value,
        size: document.getElementById('restockSize').value,
        color: document.getElementById('restockColor').value,
        quantity: parseInt(document.getElementById('restockQuantityInput').value || 10)
      };

      try {
        const res = await fetch('api/inventory.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) {
          showToast(data.message);
          document.getElementById('restockModal').classList.remove('active');
          loadInventoryView();
        } else {
          showToast(data.error, true);
        }
      } catch(err) {
        showToast('Network error during restock.', true);
      }
    });
  }

  const closeRestockBtn = document.getElementById('closeRestockModal');
  if (closeRestockBtn) closeRestockBtn.addEventListener('click', () => {
    document.getElementById('restockModal').classList.remove('active');
  });

  // Filter triggers
  ['inventorySearch', 'inventoryStockFilter'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('input', loadInventoryView);
  });
});

// ----------------------------------------------------
// 8. PAYMENTS LEDGER & BANK DEPOSIT VERIFICATION
// ----------------------------------------------------

async function loadPaymentsLedger() {
  try {
    const res = await fetch('api/payments.php');
    const data = await res.json();
    if (!data.success) return;

    // Update KPI summaries
    document.getElementById('paymentsPaidSum').textContent = `${currentCurrency} ${parseFloat(data.grossPaid || 0).toFixed(2)}`;
    document.getElementById('paymentsRefundedSum').textContent = `${currentCurrency} ${parseFloat(data.refundsProcessed || 0).toFixed(2)}`;
    document.getElementById('paymentsNetSum').textContent = `${currentCurrency} ${parseFloat(data.netRevenue || 0).toFixed(2)}`;

    const bankBadge = document.getElementById('bankDepositBadge');
    if (bankBadge) {
      if (data.pendingDeposits > 0) {
        bankBadge.textContent = data.pendingDeposits;
        bankBadge.style.display = 'inline-block';
      } else {
        bankBadge.style.display = 'none';
      }
    }

    // 1. Transaction Ledger Table
    const ledgerBody = document.getElementById('paymentsTableBody');
    if (ledgerBody) {
      if (!data.transactions || data.transactions.length === 0) {
        ledgerBody.innerHTML = `<tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">No transaction history found.</td></tr>`;
      } else {
        ledgerBody.innerHTML = data.transactions.map(t => {
          let statusStyle = 'status-pending';
          if (t.payment_status === 'Paid' || t.payment_status === 'Verified') statusStyle = 'status-verified';
          if (t.payment_status === 'Refunded' || t.payment_status === 'Rejected') statusStyle = 'status-rejected';

          return `
            <tr>
              <td><strong>${escapeHtml(t.order_number)}</strong></td>
              <td>${escapeHtml(t.first_name + ' ' + t.last_name)}</td>
              <td><span class="pos-chip" style="font-size: 0.75rem;">${escapeHtml(t.payment_method)}</span></td>
              <td><strong>${currentCurrency} ${parseFloat(t.total_amount).toFixed(2)}</strong></td>
              <td>${t.created_at}</td>
              <td><span class="pos-chip ${statusStyle}" style="font-size: 0.75rem;">${escapeHtml(t.payment_status)}</span></td>
              <td>
                ${t.payment_status !== 'Refunded' ? `<button class="btn btn-secondary" onclick="processRefund(${t.id})" style="padding: 3px 8px; font-size: 0.75rem; color: #C62828;">Refund</button>` : '<span class="text-muted" style="font-size: 0.8rem;">Refunded</span>'}
              </td>
            </tr>
          `;
        }).join('');
      }
    }

    // 2. Bank Deposit Slip Verification Table
    const bankBody = document.getElementById('bankDepositTableBody');
    if (bankBody) {
      if (!data.bankDeposits || data.bankDeposits.length === 0) {
        bankBody.innerHTML = `<tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">No bank deposit orders requiring verification.</td></tr>`;
      } else {
        bankBody.innerHTML = data.bankDeposits.map(b => `
          <tr>
            <td><strong>${escapeHtml(b.order_number)}</strong></td>
            <td>${escapeHtml(b.first_name + ' ' + b.last_name)}</td>
            <td>${escapeHtml(b.email)}</td>
            <td><strong>${currentCurrency} ${parseFloat(b.total_amount).toFixed(2)}</strong></td>
            <td>${b.created_at}</td>
            <td>
              ${b.payment_proof_url ? `<button class="btn btn-secondary" onclick="openBankDepositVerifyModal(${b.id})" style="padding: 4px 10px; font-size: 0.8rem;">🔍 View Slip</button>` : '<span style="color: var(--error-color); font-size: 0.8rem;">No Slip Uploaded</span>'}
            </td>
            <td>
              <span class="pos-chip ${b.payment_status === 'Verified' ? 'status-verified' : 'status-pending'}" style="font-size: 0.75rem;">
                ${escapeHtml(b.payment_status)}
              </span>
            </td>
          </tr>
        `).join('');
      }
    }

    // 3. COD Queue Table
    const codBody = document.getElementById('codTableBody');
    if (codBody) {
      if (!data.codList || data.codList.length === 0) {
        codBody.innerHTML = `<tr><td colspan="8" style="text-align: center; color: var(--text-muted); padding: 2rem;">No pending COD orders.</td></tr>`;
      } else {
        codBody.innerHTML = data.codList.map(c => `
          <tr>
            <td><strong>${escapeHtml(c.order_number)}</strong></td>
            <td>${escapeHtml(c.first_name + ' ' + c.last_name)}</td>
            <td>${escapeHtml(c.phone)}</td>
            <td>${escapeHtml(c.address + ', ' + c.city)}</td>
            <td>${escapeHtml(c.courier || 'Local Logistics')}</td>
            <td><strong>${currentCurrency} ${parseFloat(c.total_amount).toFixed(2)}</strong></td>
            <td><span class="pos-chip ${c.payment_status === 'Paid' ? 'status-verified' : 'status-pending'}" style="font-size: 0.75rem;">${escapeHtml(c.payment_status)}</span></td>
            <td>
              ${c.payment_status !== 'Paid' ? `<button class="btn btn-primary" onclick="markCodPaid(${c.id})" style="padding: 3px 8px; font-size: 0.75rem;">Mark Paid</button>` : '<span style="color: var(--success-color); font-size: 0.8rem;">✓ Remitted</span>'}
            </td>
          </tr>
        `).join('');
      }
    }

  } catch(err) {
    console.error('Error loading payments:', err);
  }
}

// Payments Sub-Tab Switching
document.addEventListener('DOMContentLoaded', () => {
  const btnLedger = document.getElementById('btnPaymentsLedger');
  const btnBank = document.getElementById('btnBankDepositVerification');
  const btnCod = document.getElementById('btnCODManagement');

  const subLedger = document.getElementById('subViewLedger');
  const subBank = document.getElementById('subViewBankDeposit');
  const subCod = document.getElementById('subViewCOD');

  if (btnLedger && btnBank && btnCod) {
    btnLedger.addEventListener('click', () => {
      btnLedger.classList.add('active');
      btnBank.classList.remove('active');
      btnCod.classList.remove('active');
      subLedger.style.display = 'block';
      subBank.style.display = 'none';
      subCod.style.display = 'none';
    });

    btnBank.addEventListener('click', () => {
      btnBank.classList.add('active');
      btnLedger.classList.remove('active');
      btnCod.classList.remove('active');
      subBank.style.display = 'block';
      subLedger.style.display = 'none';
      subCod.style.display = 'none';
    });

    btnCod.addEventListener('click', () => {
      btnCod.classList.add('active');
      btnLedger.classList.remove('active');
      btnBank.classList.remove('active');
      subCod.style.display = 'block';
      subLedger.style.display = 'none';
      subBank.style.display = 'none';
    });
  }
});

async function openBankDepositVerifyModal(orderId) {
  try {
    const res = await fetch(`api/orders.php?id=${orderId}`);
    const data = await res.json();
    if (!data.success || !data.order) return;

    currentVerifyingOrderData = data.order;
    const o = data.order;

    document.getElementById('bankDepositDetailsText').innerHTML = `
      <strong>Order Number:</strong> ${escapeHtml(o.order_number)}<br>
      <strong>Customer Name:</strong> ${escapeHtml(o.first_name + ' ' + o.last_name)} (${escapeHtml(o.phone)})<br>
      <strong>Total Order Amount:</strong> <span style="font-size: 1.1rem; font-weight: 700; color: var(--accent-color);">${currentCurrency} ${parseFloat(o.total_amount).toFixed(2)}</span><br>
      <strong>Payment Method:</strong> ${escapeHtml(o.payment_method)}
    `;

    const img = document.getElementById('bankDepositSlipImg');
    const warn = document.getElementById('noSlipWarning');
    if (o.payment_proof_url) {
      img.src = resolveAdminImageUrl(o.payment_proof_url);
      img.style.display = 'block';
      warn.style.display = 'none';
    } else {
      img.style.display = 'none';
      warn.style.display = 'block';
    }

    document.getElementById('bankDepositVerifyModal').classList.add('active');
  } catch(err) {
    showToast('Error loading bank deposit details.', true);
  }
}

// Approve / Reject bank deposit buttons
document.addEventListener('DOMContentLoaded', () => {
  const approveBtn = document.getElementById('bankDepositApproveBtn');
  const rejectBtn = document.getElementById('bankDepositRejectBtn');
  const closeBankBtn = document.getElementById('closeBankDepositModal');

  if (closeBankBtn) closeBankBtn.addEventListener('click', () => {
    document.getElementById('bankDepositVerifyModal').classList.remove('active');
  });

  if (approveBtn) {
    approveBtn.addEventListener('click', async () => {
      if (!currentVerifyingOrderData) return;
      try {
        const res = await fetch('api/payments.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            action: 'approve_bank_deposit',
            orderId: currentVerifyingOrderData.id
          })
        });
        const data = await res.json();
        if (data.success) {
          showToast(data.message);
          document.getElementById('bankDepositVerifyModal').classList.remove('active');
          if (data.smsBody) triggerSmsSimulator(data.smsBody);
          loadPaymentsLedger();
        } else {
          showToast(data.error, true);
        }
      } catch(err) {
        showToast('Network error during payment verification.', true);
      }
    });
  }

  if (rejectBtn) {
    rejectBtn.addEventListener('click', async () => {
      if (!currentVerifyingOrderData) return;
      if (!confirm('Reject this payment deposit slip?')) return;
      try {
        const res = await fetch('api/payments.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            action: 'reject_bank_deposit',
            orderId: currentVerifyingOrderData.id
          })
        });
        const data = await res.json();
        if (data.success) {
          showToast(data.message);
          document.getElementById('bankDepositVerifyModal').classList.remove('active');
          loadPaymentsLedger();
        }
      } catch(err) {
        showToast('Network error.', true);
      }
    });
  }
});

async function processRefund(orderId) {
  if (!confirm('Are you sure you want to process a refund for this order?')) return;
  try {
    const res = await fetch('api/payments.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'refund', orderId: orderId })
    });
    const data = await res.json();
    if (data.success) {
      showToast(data.message);
      loadPaymentsLedger();
    }
  } catch(err) {
    showToast('Network error.', true);
  }
}

async function markCodPaid(orderId) {
  try {
    const res = await fetch('api/payments.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'mark_cod_paid', orderId: orderId })
    });
    const data = await res.json();
    if (data.success) {
      showToast(data.message);
      loadPaymentsLedger();
    }
  } catch(err) {
    showToast('Network error.', true);
  }
}

// ----------------------------------------------------
// 9. ORDERS MODULE & ORDER DETAIL MODAL
// ----------------------------------------------------

let activeOrdersFilter = 'ALL';

async function loadOrdersView() {
  const tbody = document.getElementById('ordersTableBody');
  const q = document.getElementById('ordersSearchInput')?.value || '';
  if (!tbody) return;

  try {
    const res = await fetch(`api/orders.php?filter=${encodeURIComponent(activeOrdersFilter)}&q=${encodeURIComponent(q)}`);
    const data = await res.json();

    if (!data.success) return;

    // Update KPI counts
    document.getElementById('kpiTotalOrders').textContent = data.kpiTotalOrders || 0;
    document.getElementById('kpiPendingOrders').textContent = data.kpiPendingOrders || 0;
    document.getElementById('kpiVerifiedOrders').textContent = data.kpiVerifiedOrders || 0;
    document.getElementById('kpiRejectedOrders').textContent = data.kpiRejectedOrders || 0;

    if (!data.orders || data.orders.length === 0) {
      tbody.innerHTML = `<tr><td colspan="8" style="text-align: center; color: var(--text-muted); padding: 2rem;">No orders found matching filter "${activeOrdersFilter}".</td></tr>`;
      return;
    }

    tbody.innerHTML = data.orders.map(o => {
      let statusStyle = 'status-pending';
      if (o.order_status === 'Verified' || o.order_status === 'Delivered') statusStyle = 'status-verified';
      if (o.order_status === 'Rejected' || o.order_status === 'Cancelled') statusStyle = 'status-rejected';
      if (o.payment_status === 'Verifying Slip') statusStyle = 'status-verifying';

      return `
        <tr>
          <td><strong>${escapeHtml(o.order_number)}</strong></td>
          <td style="font-size: 0.85rem; color: var(--text-muted);">${o.created_at}</td>
          <td>
            <div style="font-weight: 600;">${escapeHtml(o.first_name + ' ' + o.last_name)}</div>
            <small class="text-muted">${escapeHtml(o.phone)} • ${escapeHtml(o.email)}</small>
          </td>
          <td><span class="pos-chip" style="font-size: 0.75rem;">${escapeHtml(o.payment_method)}</span></td>
          <td><strong>${currentCurrency} ${parseFloat(o.total_amount).toFixed(2)}</strong></td>
          <td>
            ${o.payment_proof_url ? `<button class="btn btn-secondary" onclick="openBankDepositVerifyModal(${o.id})" style="padding: 2px 6px; font-size: 0.75rem;">Receipt 🖼️</button>` : '<span class="text-muted" style="font-size: 0.8rem;">None</span>'}
          </td>
          <td><span class="pos-chip ${statusStyle}" style="font-size: 0.75rem;">${escapeHtml(o.order_status)}</span></td>
          <td>
            <div style="display: flex; gap: 6px;">
              <button class="btn btn-secondary" onclick="viewOrderDetail(${o.id})" style="padding: 4px 10px; font-size: 0.8rem;">Details</button>
            </div>
          </td>
        </tr>
      `;
    }).join('');

  } catch(err) {
    console.error('Error loading orders:', err);
  }
}

// Order filter chips
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('#ordersFilterTabs button').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('#ordersFilterTabs button').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      activeOrdersFilter = btn.getAttribute('data-filter') || 'ALL';
      loadOrdersView();
    });
  });

  const searchInput = document.getElementById('ordersSearchInput');
  if (searchInput) searchInput.addEventListener('input', loadOrdersView);

  // Shipping Rules Form Submit
  const shipForm = document.getElementById('shippingSettingsForm');
  if (shipForm) {
    shipForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const payload = {
        type: 'shipping',
        standard_fee: document.getElementById('shipStandardFee').value,
        express_fee: document.getElementById('shipExpressFee').value,
        free_threshold: document.getElementById('shipFreeThreshold').value,
        couriers: document.getElementById('shipCouriers').value
      };
      try {
        const res = await fetch('api/settings.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) showToast(data.message);
      } catch(err) {
        showToast('Network error saving shipping rules.', true);
      }
    });
  }
});

async function viewOrderDetail(orderId) {
  try {
    const res = await fetch(`api/orders.php?id=${orderId}`);
    const data = await res.json();
    if (!data.success || !data.order) return;

    const o = data.order;
    document.getElementById('modalOrderRef').textContent = `Order #${o.order_number}`;

    let itemsHtml = '';
    if (o.items && o.items.length) {
      itemsHtml = o.items.map(item => `
        <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid var(--border-color);">
          <div style="display: flex; align-items: center; gap: 10px;">
            <img src="${resolveAdminImageUrl(item.image_url)}" onerror="this.onerror=null; this.src='../images/placeholder.png';" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">
            <div>
              <div style="font-weight: 600;">${escapeHtml(item.product_name)}</div>
              <small class="text-muted">Size: ${escapeHtml(item.size)} | Color: ${escapeHtml(item.color)} | Qty: ${item.quantity}</small>
            </div>
          </div>
          <strong>${currentCurrency} ${parseFloat(item.total).toFixed(2)}</strong>
        </div>
      `).join('');
    } else {
      itemsHtml = '<p class="text-muted">No items list recorded.</p>';
    }

    document.getElementById('modalOrderContent').innerHTML = `
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
        <div>
          <h4 style="margin-bottom: 0.5rem;">Customer Information</h4>
          <p style="font-size: 0.9rem; line-height: 1.6; margin: 0;">
            <strong>${escapeHtml(o.first_name + ' ' + o.last_name)}</strong><br>
            Email: ${escapeHtml(o.email)}<br>
            Phone: ${escapeHtml(o.phone)}<br>
            Address: ${escapeHtml(o.address)}, ${escapeHtml(o.city)}, ${escapeHtml(o.district)} ${escapeHtml(o.postal_code)}
          </p>
        </div>
        <div>
          <h4 style="margin-bottom: 0.5rem;">Payment & Delivery</h4>
          <p style="font-size: 0.9rem; line-height: 1.6; margin: 0;">
            Method: <strong>${escapeHtml(o.payment_method)}</strong><br>
            Payment Status: <span class="pos-chip">${escapeHtml(o.payment_status)}</span><br>
            Order Status: <strong>${escapeHtml(o.order_status)}</strong><br>
            Courier Assigned: ${escapeHtml(o.courier || 'None')} (${escapeHtml(o.tracking_number || 'No tracking')})
          </p>
        </div>
      </div>

      <h4 style="margin-bottom: 0.75rem;">Purchased Items</h4>
      <div style="max-height: 200px; overflow-y: auto; margin-bottom: 1.5rem;">
        ${itemsHtml}
      </div>

      <div style="display: flex; justify-content: space-between; border-top: 2px solid var(--border-color); padding-top: 1rem; margin-bottom: 1.5rem;">
        <div>
          <span class="text-muted">Subtotal:</span> ${currentCurrency} ${parseFloat(o.subtotal).toFixed(2)}<br>
          <span class="text-muted">Shipping:</span> ${currentCurrency} ${parseFloat(o.shipping).toFixed(2)}
        </div>
        <div style="text-align: right;">
          <div style="font-size: 0.85rem; color: var(--text-muted);">Grand Total</div>
          <div style="font-size: 1.4rem; font-weight: 800; color: var(--accent-color);">${currentCurrency} ${parseFloat(o.total_amount).toFixed(2)}</div>
        </div>
      </div>

      <!-- Quick Status & Courier Dispatch Update -->
      <div style="background: rgba(0,0,0,0.03); padding: 1rem; border-radius: var(--radius-sm); margin-bottom: 1rem;">
        <h5 style="margin-bottom: 0.5rem;">Dispatch Courier & Update Tracking</h5>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
          <input type="text" id="modalCourierInput" class="form-control" placeholder="Courier Name (e.g. DHL)" value="${escapeHtml(o.courier || 'FedEx')}" style="flex: 1; margin: 0;">
          <input type="text" id="modalTrackingInput" class="form-control" placeholder="Tracking Number" value="${escapeHtml(o.tracking_number || '')}" style="flex: 1; margin: 0;">
          <button type="button" class="btn btn-primary" onclick="dispatchOrderTracking(${o.id})" style="padding: 8px 16px;">Save & Dispatch</button>
        </div>
      </div>

      <div style="display: flex; justify-content: space-between; gap: 10px;">
        <button class="btn btn-secondary" onclick="printOrderInvoice(${o.id})">🖨️ Print Invoice Receipt</button>
        <button class="btn btn-secondary" onclick="document.getElementById('orderDetailModal').classList.remove('active')">Close</button>
      </div>
    `;

    document.getElementById('orderDetailModal').classList.add('active');

  } catch(err) {
    showToast('Error loading order details.', true);
  }
}

async function dispatchOrderTracking(orderId) {
  const courier = document.getElementById('modalCourierInput')?.value.trim();
  const trackingNumber = document.getElementById('modalTrackingInput')?.value.trim();

  try {
    const res = await fetch('api/orders.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        action: 'update_tracking',
        orderId,
        courier,
        trackingNumber
      })
    });
    const data = await res.json();
    if (data.success) {
      showToast(data.message);
      document.getElementById('orderDetailModal').classList.remove('active');
      loadOrdersView();
    }
  } catch(err) {
    showToast('Error dispatching tracking details.', true);
  }
}

function printOrderInvoice(orderId) {
  window.print();
}

// ----------------------------------------------------
// 10. DISCOUNTS & HOMEPAGE BANNERS MODULE
// ----------------------------------------------------

async function loadDiscountsView() {
  try {
    const res = await fetch('api/discounts.php');
    const data = await res.json();
    if (!data.success) return;

    // 1. Coupons Table
    const cBody = document.getElementById('couponsTableBody');
    if (cBody) {
      if (!data.coupons || data.coupons.length === 0) {
        cBody.innerHTML = `<tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">No active coupons.</td></tr>`;
      } else {
        cBody.innerHTML = data.coupons.map(c => `
          <tr>
            <td><strong>${escapeHtml(c.code)}</strong></td>
            <td>${escapeHtml(c.type)}</td>
            <td><strong>${c.type === 'Percentage' ? c.value + '%' : currentCurrency + ' ' + parseFloat(c.value).toFixed(2)}</strong></td>
            <td>${c.expiry_date || 'No Expiry'}</td>
            <td><span class="pos-chip status-verified" style="font-size: 0.75rem;">Active</span></td>
            <td><button class="btn btn-secondary" onclick="deleteCoupon(${c.id})" style="padding: 2px 6px; font-size: 0.75rem; color: var(--error-color);">Delete</button></td>
          </tr>
        `).join('');
      }
    }

    // 2. Banners List
    const bContainer = document.getElementById('bannersContainer');
    if (bContainer) {
      if (!data.banners || data.banners.length === 0) {
        bContainer.innerHTML = `<p class="text-muted" style="text-align: center; padding: 1rem;">No banner campaigns.</p>`;
      } else {
        bContainer.innerHTML = data.banners.map(b => `
          <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid var(--border-color);">
            <div style="display: flex; align-items: center; gap: 10px;">
              <img src="${resolveAdminImageUrl(b.image_url || 'images/banner1.png')}" onerror="this.onerror=null; this.src='../images/banner1.png';" style="width: 60px; height: 35px; object-fit: cover; border-radius: 4px;">
              <div>
                <strong style="font-size: 0.85rem;">${escapeHtml(b.title)}</strong>
                <div style="font-size: 0.75rem; color: var(--text-muted);">${escapeHtml(b.section_type)} • ${escapeHtml(b.link_url || 'No Link')}</div>
              </div>
            </div>
            <button class="btn btn-secondary" onclick="deleteBanner(${b.id})" style="padding: 2px 6px; font-size: 0.75rem; color: var(--error-color);">Delete</button>
          </div>
        `).join('');
      }
    }

  } catch(err) {
    console.error('Error loading discounts & banners:', err);
  }
}

async function deleteCoupon(id) {
  if (!confirm('Delete this coupon?')) return;
  try {
    const res = await fetch(`api/discounts.php?entity=coupon&id=${id}`, { method: 'DELETE' });
    const data = await res.json();
    if (data.success) {
      showToast(data.message);
      loadDiscountsView();
    }
  } catch(err) {}
}

async function deleteBanner(id) {
  if (!confirm('Delete this banner campaign?')) return;
  try {
    const res = await fetch(`api/discounts.php?entity=banner&id=${id}`, { method: 'DELETE' });
    const data = await res.json();
    if (data.success) {
      showToast(data.message);
      loadDiscountsView();
    }
  } catch(err) {}
}

// Forms
document.addEventListener('DOMContentLoaded', () => {
  const couponForm = document.getElementById('couponForm');
  if (couponForm) {
    couponForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const payload = {
        entity: 'coupon',
        code: document.getElementById('cCode').value.trim(),
        type: document.getElementById('cType').value,
        value: parseFloat(document.getElementById('cValue').value || 0),
        minAmount: parseFloat(document.getElementById('cMinAmount').value || 0),
        expiry: document.getElementById('cExpiry').value
      };
      try {
        const res = await fetch('api/discounts.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) {
          showToast(data.message);
          couponForm.reset();
          loadDiscountsView();
        } else {
          showToast(data.error, true);
        }
      } catch(err) {
        showToast('Network error.', true);
      }
    });
  }

  const bannerForm = document.getElementById('bannerForm');
  if (bannerForm) {
    bannerForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const payload = {
        entity: 'banner',
        title: document.getElementById('bannerTitle').value.trim(),
        image: document.getElementById('bannerImage').value.trim(),
        link: document.getElementById('bannerLink').value.trim(),
        section: document.getElementById('bannerType').value
      };
      try {
        const res = await fetch('api/discounts.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) {
          showToast(data.message);
          bannerForm.reset();
          loadDiscountsView();
        } else {
          showToast(data.error, true);
        }
      } catch(err) {
        showToast('Network error.', true);
      }
    });
  }
});

// ----------------------------------------------------
// 11. REVIEWS MODERATION MODULE
// ----------------------------------------------------

async function loadReviewsList() {
  const tbody = document.getElementById('reviewsTableBody');
  if (!tbody) return;

  try {
    const res = await fetch('api/reviews.php');
    const data = await res.json();

    if (!data.success || !data.reviews || data.reviews.length === 0) {
      tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">No customer reviews submitted yet.</td></tr>`;
      return;
    }

    tbody.innerHTML = data.reviews.map(r => `
      <tr>
        <td>
          <div style="display: flex; align-items: center; gap: 8px;">
            <img src="${resolveAdminImageUrl(r.product_image)}" onerror="this.onerror=null; this.src='../images/placeholder.png';" style="width: 32px; height: 32px; object-fit: cover; border-radius: 4px;">
            <span style="font-weight: 600;">${escapeHtml(r.product_name || 'Product #' + r.product_id)}</span>
          </div>
        </td>
        <td>
          <div>${escapeHtml(r.customer_name)}</div>
          <small class="text-muted">${escapeHtml(r.customer_email)}</small>
        </td>
        <td style="color: #D4AF37; font-weight: 700;">${'★'.repeat(r.rating)}${'☆'.repeat(5 - r.rating)}</td>
        <td style="max-width: 250px; font-size: 0.85rem;">${escapeHtml(r.comment)}</td>
        <td><span class="pos-chip ${r.status === 'Approved' ? 'status-verified' : (r.status === 'Rejected' ? 'status-rejected' : 'status-pending')}" style="font-size: 0.75rem;">${escapeHtml(r.status)}</span></td>
        <td style="font-size: 0.8rem; color: var(--text-muted);">${r.admin_reply ? `<strong>Admin:</strong> ${escapeHtml(r.admin_reply)}` : 'None'}</td>
        <td>
          <div style="display: flex; gap: 4px;">
            <button class="btn btn-secondary" onclick="openReplyModal(${r.id}, '${escapeHtml(r.comment)}')" style="padding: 2px 6px; font-size: 0.75rem;">Reply</button>
            <button class="btn btn-secondary" onclick="updateReviewStatus(${r.id}, 'approve')" style="padding: 2px 6px; font-size: 0.75rem; color: var(--success-color);">✓</button>
            <button class="btn btn-secondary" onclick="updateReviewStatus(${r.id}, 'reject')" style="padding: 2px 6px; font-size: 0.75rem; color: var(--error-color);">✕</button>
          </div>
        </td>
      </tr>
    `).join('');

  } catch(err) {
    console.error('Error loading reviews:', err);
  }
}

function openReplyModal(reviewId, commentPreview) {
  document.getElementById('replyReviewId').value = reviewId;
  document.getElementById('reviewTextPreview').textContent = `"${commentPreview}"`;
  document.getElementById('replyModal').classList.add('active');
}

async function updateReviewStatus(id, action) {
  try {
    const res = await fetch('api/reviews.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id, action })
    });
    const data = await res.json();
    if (data.success) {
      showToast(data.message);
      loadReviewsList();
    }
  } catch(err) {
    showToast('Network error.', true);
  }
}

// Reply form listener
document.addEventListener('DOMContentLoaded', () => {
  const replyForm = document.getElementById('replyForm');
  if (replyForm) {
    replyForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const payload = {
        action: 'reply',
        id: document.getElementById('replyReviewId').value,
        reply: document.getElementById('replyTextInput').value.trim()
      };
      try {
        const res = await fetch('api/reviews.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) {
          showToast(data.message);
          document.getElementById('replyModal').classList.remove('active');
          loadReviewsList();
        }
      } catch(err) {
        showToast('Network error.', true);
      }
    });
  }

  const closeReplyBtn = document.getElementById('closeReplyModal');
  if (closeReplyBtn) closeReplyBtn.addEventListener('click', () => {
    document.getElementById('replyModal').classList.remove('active');
  });
});

// ----------------------------------------------------
// 12. REPORTS & ANALYTICS MODULE
// ----------------------------------------------------

async function loadReportsView() {
  const scope = document.getElementById('reportScope')?.value || 'Monthly';

  try {
    const res = await fetch(`api/reports.php?scope=${encodeURIComponent(scope)}`);
    const data = await res.json();
    if (!data.success) return;

    document.getElementById('analyticsStockValue').textContent = data.stockValuation;
    document.getElementById('analyticsReturnRate').textContent = data.returnRate;
    document.getElementById('analyticsExchangeRate').textContent = data.exchangeRate;
    document.getElementById('analyticsCustomerGrowth').textContent = data.customerGrowth;

    // Top viewed products list
    const topViewContainer = document.getElementById('topViewedContainer');
    if (topViewContainer && data.topViewed) {
      topViewContainer.innerHTML = data.topViewed.map(p => `
        <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.6rem 0; border-bottom: 1px solid var(--border-color); font-size: 0.85rem;">
          <div style="display: flex; align-items: center; gap: 10px;">
            <img src="${resolveAdminImageUrl(p.image_url)}" onerror="this.onerror=null; this.src='../images/placeholder.png';" style="width: 32px; height: 32px; object-fit: cover; border-radius: 4px;">
            <div>
              <strong>${escapeHtml(p.name)}</strong>
              <div style="font-size: 0.75rem; color: var(--text-muted);">${escapeHtml(p.category)}</div>
            </div>
          </div>
          <span class="pos-chip" style="font-size: 0.75rem;">👁️ ${p.views_count || 0} views</span>
        </div>
      `).join('');
    }

    // Render Detailed Chart
    renderDetailedChart(data.chartLabels, data.chartData, scope);

  } catch(err) {
    console.error('Error loading reports:', err);
  }
}

function renderDetailedChart(labels, values, scope) {
  const ctx = document.getElementById('reportsDetailedChart');
  if (!ctx) return;

  if (detailedChartInstance) detailedChartInstance.destroy();

  detailedChartInstance = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [{
        label: `${scope} Sales Volume (${currentCurrency})`,
        data: values,
        backgroundColor: 'rgba(212, 175, 55, 0.7)',
        borderColor: '#D4AF37',
        borderWidth: 1.5,
        borderRadius: 4
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' } },
        x: { grid: { display: false } }
      }
    }
  });
}

document.addEventListener('DOMContentLoaded', () => {
  const scopeEl = document.getElementById('reportScope');
  if (scopeEl) scopeEl.addEventListener('change', loadReportsView);
});

// ----------------------------------------------------
// 13. STAFF MANAGEMENT MODULE
// ----------------------------------------------------

async function loadStaffView() {
  const tbody = document.getElementById('staffTableBody');
  const logBody = document.getElementById('staffHistoryTableBody');
  if (!tbody) return;

  try {
    const res = await fetch('api/staff.php');
    const data = await res.json();
    if (!data.success) return;

    tbody.innerHTML = data.staffList.map(s => `
      <tr>
        <td><strong>${escapeHtml(s.full_name)}</strong></td>
        <td>${escapeHtml(s.email)}</td>
        <td><span class="pos-chip" style="font-size: 0.75rem;">${escapeHtml(s.role)}</span></td>
        <td><span class="pos-chip ${s.status === 'Approved' ? 'status-verified' : (s.status === 'Denied' ? 'status-rejected' : 'status-pending')}" style="font-size: 0.75rem;">${escapeHtml(s.status)}</span></td>
        <td>
          <button class="btn btn-secondary" onclick="editStaffMember(${s.id}, '${escapeHtml(s.full_name)}', '${escapeHtml(s.email)}', '${escapeHtml(s.role)}', '${escapeHtml(s.status)}')" style="padding: 2px 6px; font-size: 0.75rem;">Edit</button>
          <button class="btn btn-secondary" onclick="deleteStaffMember(${s.id})" style="padding: 2px 6px; font-size: 0.75rem; color: var(--error-color);">🗑️</button>
        </td>
      </tr>
    `).join('');

    if (logBody && data.logs) {
      logBody.innerHTML = data.logs.map(l => `
        <tr>
          <td><strong>${escapeHtml(l.staff_email)}</strong></td>
          <td style="font-size: 0.8rem; color: var(--text-muted);">${escapeHtml(l.user_agent || 'Browser')} (${escapeHtml(l.ip_address || 'Local')})</td>
          <td style="font-size: 0.85rem;">${l.created_at}</td>
        </tr>
      `).join('');
    }

  } catch(err) {
    console.error('Error loading staff:', err);
  }
}

function editStaffMember(id, name, email, role, status) {
  document.getElementById('editStaffId').value = id;
  document.getElementById('staffNameInput').value = name;
  document.getElementById('staffEmailInput').value = email;
  document.getElementById('staffRoleInput').value = role;
  document.getElementById('staffStatusInput').value = status;
  document.getElementById('staffFormTitle').textContent = 'Edit Staff Member #' + id;
  document.getElementById('staffFormSubmitBtn').textContent = 'Save Changes';
  document.getElementById('cancelStaffEditBtn').style.display = 'block';
}

function cancelStaffEdit() {
  document.getElementById('staffForm').reset();
  document.getElementById('editStaffId').value = '';
  document.getElementById('staffFormTitle').textContent = 'Add Authorized Staff';
  document.getElementById('staffFormSubmitBtn').textContent = 'Add Team Member';
  document.getElementById('cancelStaffEditBtn').style.display = 'none';
}

async function deleteStaffMember(id) {
  if (!confirm('Remove this staff member?')) return;
  try {
    const res = await fetch(`api/staff.php?id=${id}`, { method: 'DELETE' });
    const data = await res.json();
    if (data.success) {
      showToast(data.message);
      loadStaffView();
    } else {
      showToast(data.error, true);
    }
  } catch(err) {
    showToast('Network error.', true);
  }
}

document.addEventListener('DOMContentLoaded', () => {
  const staffForm = document.getElementById('staffForm');
  if (staffForm) {
    staffForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const payload = {
        id: document.getElementById('editStaffId').value,
        name: document.getElementById('staffNameInput').value.trim(),
        email: document.getElementById('staffEmailInput').value.trim(),
        role: document.getElementById('staffRoleInput').value,
        status: document.getElementById('staffStatusInput').value
      };
      try {
        const res = await fetch('api/staff.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) {
          showToast(data.message);
          cancelStaffEdit();
          loadStaffView();
        } else {
          showToast(data.error, true);
        }
      } catch(err) {
        showToast('Network error.', true);
      }
    });
  }

  const cancelBtn = document.getElementById('cancelStaffEditBtn');
  if (cancelBtn) cancelBtn.addEventListener('click', cancelStaffEdit);
});

// ----------------------------------------------------
// 14. STORE SETTINGS & SMS LOGS MODULE
// ----------------------------------------------------

async function loadSettingsView() {
  try {
    const res = await fetch('api/settings.php');
    const data = await res.json();
    if (!data.success) return;

    const s = data.settings;
    if (s) {
      if (document.getElementById('setStoreName')) document.getElementById('setStoreName').value = s.site_name || '';
      if (document.getElementById('setStoreEmail')) document.getElementById('setStoreEmail').value = s.contact_email || '';
      if (document.getElementById('setStorePhone')) document.getElementById('setStorePhone').value = s.contact_phone || '';
      if (document.getElementById('setStoreAddress')) document.getElementById('setStoreAddress').value = s.store_address || '';
      if (document.getElementById('setCurrency')) document.getElementById('setCurrency').value = s.currency_symbol || 'Rs.';
      if (document.getElementById('setTaxRate')) document.getElementById('setTaxRate').value = s.tax_rate || '10';
      if (document.getElementById('setFb')) document.getElementById('setFb').value = s.facebook_url || '';
      if (document.getElementById('setIg')) document.getElementById('setIg').value = s.instagram_url || '';
      if (document.getElementById('setTw')) document.getElementById('setTw').value = s.twitter_url || '';

      if (document.getElementById('setNotifNewOrder')) document.getElementById('setNotifNewOrder').checked = s.notif_new_order === '1';
      if (document.getElementById('setNotifLowStock')) document.getElementById('setNotifLowStock').checked = s.notif_low_stock === '1';
      if (document.getElementById('setNotifReturns')) document.getElementById('setNotifReturns').checked = s.notif_returns === '1';

      if (document.getElementById('setSmsOnApproval')) document.getElementById('setSmsOnApproval').checked = s.sms_on_approval === '1';
      if (document.getElementById('setSmsGateway')) document.getElementById('setSmsGateway').value = s.sms_gateway || 'mock';
      if (document.getElementById('setSmsTemplate')) document.getElementById('setSmsTemplate').value = s.sms_template || '';
    }

    // SMS Logs Table
    const smsTbody = document.getElementById('smsLogsTableBody');
    if (smsTbody && data.smsLogs) {
      if (data.smsLogs.length === 0) {
        smsTbody.innerHTML = `<tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 1.5rem 0;">No SMS messages logged yet.</td></tr>`;
      } else {
        smsTbody.innerHTML = data.smsLogs.map(l => `
          <tr>
            <td><strong>${escapeHtml(l.order_id)}</strong></td>
            <td>${escapeHtml(l.customer_name)}</td>
            <td>${escapeHtml(l.phone)}</td>
            <td style="font-size: 0.8rem; max-width: 250px;">${escapeHtml(l.message)}</td>
            <td><span class="pos-chip" style="font-size: 0.75rem;">${escapeHtml(l.gateway)}</span></td>
            <td><span class="pos-chip status-verified" style="font-size: 0.75rem;">${escapeHtml(l.status)}</span></td>
            <td style="font-size: 0.8rem; color: var(--text-muted);">${l.created_at}</td>
          </tr>
        `).join('');
      }
    }

  } catch(err) {
    console.error('Error loading settings:', err);
  }
}

document.addEventListener('DOMContentLoaded', () => {
  const settingsForm = document.getElementById('storeSettingsForm');
  if (settingsForm) {
    settingsForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const payload = {
        type: 'store',
        store_name: document.getElementById('setStoreName')?.value.trim(),
        store_email: document.getElementById('setStoreEmail')?.value.trim(),
        store_phone: document.getElementById('setStorePhone')?.value.trim(),
        store_address: document.getElementById('setStoreAddress')?.value.trim(),
        currency: document.getElementById('setCurrency')?.value,
        tax_rate: document.getElementById('setTaxRate')?.value,
        fb: document.getElementById('setFb')?.value.trim(),
        ig: document.getElementById('setIg')?.value.trim(),
        tw: document.getElementById('setTw')?.value.trim(),
        notif_new_order: document.getElementById('setNotifNewOrder')?.checked,
        notif_low_stock: document.getElementById('setNotifLowStock')?.checked,
        notif_returns: document.getElementById('setNotifReturns')?.checked,
        sms_on_approval: document.getElementById('setSmsOnApproval')?.checked,
        sms_gateway: document.getElementById('setSmsGateway')?.value,
        sms_template: document.getElementById('setSmsTemplate')?.value.trim()
      };
      try {
        const res = await fetch('api/settings.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) {
          showToast(data.message);
          currentCurrency = payload.currency || 'Rs.';
        }
      } catch(err) {
        showToast('Network error saving settings.', true);
      }
    });
  }
});

// ----------------------------------------------------
// 15. INITIALIZATION ON WINDOW LOAD & HASH ROUTING
// ----------------------------------------------------

window.addEventListener('hashchange', () => {
  switchView(window.location.hash);
});

window.addEventListener('DOMContentLoaded', () => {
  // Hash Routing init
  const initialHash = window.location.hash || '#dashboard';
  switchView(initialHash);
});

// Helper for escaping HTML strings
function escapeHtml(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}
