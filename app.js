const API_BASE = '/api';

const state = {
  activeLicense: null,
  config: null,
  signals: [],
  adminKeys: [],
  currentView: 'activation'
};

const els = {
  activationView: document.getElementById('activation-view'),
  dashboardView: document.getElementById('dashboard-view'),
  adminView: document.getElementById('admin-view'),
  showActivationBtn: document.getElementById('show-activation-btn'),
  showDashboardBtn: document.getElementById('show-dashboard-btn'),
  showAdminBtn: document.getElementById('show-admin-btn'),
  verifyForm: document.getElementById('verify-form'),
  registerForm: document.getElementById('register-form'),
  adminLoginForm: document.getElementById('admin-login-form'),
  signalsList: document.getElementById('signals-list'),
  adminKeys: document.getElementById('admin-keys'),
  adminResults: document.getElementById('admin-results'),
  toast: document.getElementById('toast'),
  logoutBtn: document.getElementById('logout-btn'),
  reloadSignalsBtn: document.getElementById('reload-signals-btn'),
  adminRefreshBtn: document.getElementById('admin-refresh-btn'),
  licenseStatus: document.getElementById('license-status'),
  deviceCount: document.getElementById('device-count'),
  planStatus: document.getElementById('plan-status')
};

function showToast(message, kind = 'info') {
  els.toast.textContent = message;
  els.toast.classList.remove('hidden');
  els.toast.style.borderColor = kind === 'error' ? 'rgba(255,90,90,0.5)' : 'rgba(255,208,0,0.35)';
  setTimeout(() => els.toast.classList.add('hidden'), 2800);
}

async function apiRequest(path, options = {}) {
  const response = await fetch(`${API_BASE}${path}`, {
    headers: { 'Content-Type': 'application/json', ...(options.headers || {}) },
    ...options
  });

  const data = await response.json().catch(() => ({}));
  if (!response.ok) {
    throw new Error(data.error || 'Request failed');
  }

  return data;
}

function setView(name) {
  state.currentView = name;
  const mapping = {
    activation: [els.activationView],
    dashboard: [els.dashboardView],
    admin: [els.adminView]
  };

  Object.values({
    activation: els.activationView,
    dashboard: els.dashboardView,
    admin: els.adminView
  }).forEach((node) => node.classList.add('hidden'));

  const target = mapping[name]?.[0];
  if (target) target.classList.remove('hidden');

  els.showActivationBtn.classList.toggle('active', name === 'activation');
  els.showDashboardBtn.classList.toggle('active', name === 'dashboard');
  els.showAdminBtn.classList.toggle('active', name === 'admin');
}

function renderSignals() {
  if (!state.signals.length) {
    els.signalsList.innerHTML = '<div class="signal-item"><div>No signals available yet.</div></div>';
    return;
  }

  els.signalsList.innerHTML = state.signals.map((signal) => {
    const dirClass = String(signal.direction || 'BUY').toUpperCase() === 'SELL' ? 'sell' : 'buy';
    return `
      <div class="signal-item">
        <div class="pair">${signal.pair || 'N/A'}</div>
        <div>${signal.market || 'Binary Options'}</div>
        <div>${signal.timer || '1M'}</div>
        <div><span class="badge ${dirClass}">${signal.direction || 'BUY'}</span></div>
        <div>${signal.entry || '—'}</div>
      </div>
    `;
  }).join('');
}

function renderAdminKeys() {
  if (!state.adminKeys.length) {
    els.adminKeys.innerHTML = '<div class="signal-item"><div>No licenses found.</div></div>';
    return;
  }

  els.adminKeys.innerHTML = `
    <div class="table-row">
      <strong>Name</strong>
      <strong>Key</strong>
      <strong>Status</strong>
      <strong>Exp.</strong>
      <strong>Devices</strong>
    </div>
    ${state.adminKeys.map((item) => `
      <div class="table-row">
        <span>${item.full_name || 'Customer'}</span>
        <span>${item.license_key || '—'}</span>
        <span>${item.status || 'pending'}</span>
        <span>${item.duration_label || '—'}</span>
        <span>${(item.license_devices || []).length}/${item.max_devices || 1}</span>
      </div>
    `).join('')}
  `;
}

function renderDashboard() {
  const license = state.activeLicense;
  if (!license) {
    els.licenseStatus.textContent = 'Not active';
    els.deviceCount.textContent = '0';
    els.planStatus.textContent = 'Pending';
    return;
  }

  els.licenseStatus.textContent = license.license_key || 'Unknown';
  els.deviceCount.textContent = String((license.license_devices || []).length || 0);
  els.planStatus.textContent = license.status || 'approved';
}

async function loadSignals() {
  try {
    const result = await apiRequest('/signals');
    state.signals = result.signals || [];
    renderSignals();
  } catch (error) {
    showToast(error.message, 'error');
  }
}

async function loadAdminKeys(password) {
  try {
    const result = await apiRequest('/admin/keys', {
      method: 'POST',
      body: JSON.stringify({ password })
    });
    state.adminKeys = result.keys || [];
    renderAdminKeys();
    els.adminResults.classList.remove('hidden');
  } catch (error) {
    showToast(error.message, 'error');
  }
}

async function initializeApp() {
  try {
    state.config = await apiRequest('/config');
  } catch (error) {
    console.warn('Config fetch failed', error);
  }

  await loadSignals();
  setView('activation');
}

function handleActivationSubmit(event) {
  event.preventDefault();
  const key = document.getElementById('verify-key').value.trim();
  const device = document.getElementById('device-name').value.trim() || 'web-client';

  apiRequest('/verify-key', {
    method: 'POST',
    body: JSON.stringify({ key, device })
  })
    .then((result) => {
      state.activeLicense = result.license;
      renderDashboard();
      setView('dashboard');
      showToast('License verified successfully.');
    })
    .catch((error) => showToast(error.message, 'error'));
}

function handleRegisterSubmit(event) {
  event.preventDefault();
  const payload = {
    full_name: document.getElementById('full-name').value.trim(),
    phone: document.getElementById('phone').value.trim(),
    email: document.getElementById('email').value.trim(),
    amount: document.getElementById('amount').value.trim() || 'Pending Payment'
  };

  apiRequest('/register-key', {
    method: 'POST',
    body: JSON.stringify(payload)
  })
    .then((result) => {
      showToast(`License created: ${result.key}`);
      document.getElementById('verify-key').value = result.key;
      setView('activation');
    })
    .catch((error) => showToast(error.message, 'error'));
}

function handleAdminLogin(event) {
  event.preventDefault();
  const password = document.getElementById('admin-password').value.trim();
  if (!password) {
    showToast('Admin password required.', 'error');
    return;
  }

  apiRequest('/admin/login', {
    method: 'POST',
    body: JSON.stringify({ password })
  })
    .then(() => {
      setView('admin');
      loadAdminKeys(password);
      showToast('Admin login successful.');
    })
    .catch((error) => showToast(error.message, 'error'));
}

function handleLogout() {
  state.activeLicense = null;
  renderDashboard();
  setView('activation');
  showToast('Logged out.');
}

els.verifyForm.addEventListener('submit', handleActivationSubmit);
els.registerForm.addEventListener('submit', handleRegisterSubmit);
els.adminLoginForm.addEventListener('submit', handleAdminLogin);
els.logoutBtn.addEventListener('click', handleLogout);
els.reloadSignalsBtn.addEventListener('click', loadSignals);
els.adminRefreshBtn.addEventListener('click', () => {
  const password = document.getElementById('admin-password').value.trim();
  if (password) loadAdminKeys(password);
});
els.showActivationBtn.addEventListener('click', () => setView('activation'));
els.showDashboardBtn.addEventListener('click', () => {
  if (state.activeLicense) {
    setView('dashboard');
  } else {
    showToast('Verify a license first.', 'error');
  }
});
els.showAdminBtn.addEventListener('click', () => setView('admin'));

renderDashboard();
initializeApp();
