import express from 'express';
import path from 'path';
import { fileURLToPath } from 'url';
import dotenv from 'dotenv';
import { db } from './src/server/db.ts';
import { fetchSupabaseKeys, insertSupabaseKey, patchSupabaseKey } from './src/server/supabase.ts';

dotenv.config();

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const app = express();
const PORT = process.env.PORT || 3000;
const isProduction = process.env.NODE_ENV === 'production' || Boolean(process.env.VERCEL);

// Standard Middlewares
app.use(express.json());

// CORS headers for Vercel and cross-origin access
app.use((req, res, next) => {
  res.header('Access-Control-Allow-Origin', '*');
  res.header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
  res.header('Access-Control-Allow-Headers', 'Origin, X-Requested-With, Content-Type, Accept, Authorization, apikey');
  if (req.method === 'OPTIONS') {
    return res.sendStatus(200);
  }
  next();
});

// --- API Router (supports both /api/* and direct /* routing on Vercel) ---
const apiRouter = express.Router();

// Health check endpoint
apiRouter.get('/health', (_req, res) => {
  res.json({
    ok: true,
    service: 'FR-KING AURA Terminal Backend',
    status: 'online',
    timestamp: new Date().toISOString(),
    isServerless: Boolean(process.env.VERCEL),
  });
});

// 1. App Configuration
apiRouter.get('/config', (_req, res) => {
  const config = db.getConfig();
  // Return config without revealing adminPassword
  const { adminPassword, ...safeConfig } = config;
  res.json(safeConfig);
});

// Update App Configuration (Admin Auth Required)
apiRouter.post('/config', (req, res) => {
  const { password, apiKey, adminPassword, supabaseUrl, supabaseAnonKey, authMode, allowMasterApiKeyBypass } = req.body;
  const currentConfig = db.getConfig();

  const validPassword = currentConfig.adminPassword || 'FRK@2026#WaqasKing!Secure9x';
  if (!password || password !== validPassword) {
    return res.status(401).json({ ok: false, error: 'Unauthorized: Invalid admin password.' });
  }

  const updates: Record<string, any> = {};
  if (apiKey !== undefined) updates.apiKey = apiKey;
  if (adminPassword !== undefined && adminPassword.trim() !== '') updates.adminPassword = adminPassword.trim();
  if (supabaseUrl !== undefined) updates.supabaseUrl = supabaseUrl;
  if (supabaseAnonKey !== undefined) updates.supabaseAnonKey = supabaseAnonKey;
  if (authMode !== undefined) updates.authMode = authMode;
  if (allowMasterApiKeyBypass !== undefined) updates.allowMasterApiKeyBypass = Boolean(allowMasterApiKeyBypass);

  const updated = db.updateConfig(updates);
  const { adminPassword: _, ...safeUpdated } = updated;
  res.json({ ok: true, config: safeUpdated });
});

// 2. Verify License Key (Device-locked authentication)
apiRouter.post('/verify-key', async (req, res) => {
  const { key, device } = req.body;
  if (!key || typeof key !== 'string') {
    return res.status(400).json({ ok: false, error: 'License key is required.' });
  }

  const cleanKey = key.trim().toUpperCase();
  const deviceId = (device && typeof device === 'string' ? device.trim() : 'device-unknown');
  const config = db.getConfig();

  // Master API Key bypass check
  if (config.allowMasterApiKeyBypass && cleanKey === config.apiKey.toUpperCase()) {
    return res.json({
      ok: true,
      license: {
        id: 'master_api_key',
        license_key: cleanKey,
        full_name: 'VIP Master Admin',
        status: 'approved',
        lifetime: true,
        duration_label: 'Master API Key',
        max_devices: 999,
        license_devices: [deviceId],
        created_at: new Date().toISOString(),
      },
    });
  }

  // Check in database cache
  let lic = db.findLicenseByKey(cleanKey);

  // If not found in cache and Supabase is configured, check Supabase
  if (!lic && config.supabaseUrl) {
    try {
      const remoteKeys = await fetchSupabaseKeys(config);
      if (remoteKeys && remoteKeys.length > 0) {
        const found = remoteKeys.find(
          (k) => k.license_key.trim().toUpperCase() === cleanKey
        );
        if (found) {
          db.addOrUpdateLicense(found);
          lic = found;
        }
      }
    } catch (e) {
      console.warn('Supabase remote key lookup warning:', e);
    }
  }

  if (!lic) {
    return res.json({
      ok: false,
      error: 'License key not found. Please register or contact @codetrader to purchase.',
    });
  }

  if (lic.status === 'blocked') {
    return res.json({
      ok: false,
      error: 'This license has been blocked by administrator.',
    });
  }

  if (lic.status === 'pending') {
    return res.json({
      ok: false,
      error: 'Payment verification pending. Please wait for admin approval in the Admin Panel.',
    });
  }

  if (!lic.lifetime && lic.expires_at && new Date(lic.expires_at) < new Date()) {
    return res.json({
      ok: false,
      error: `License key expired on ${new Date(lic.expires_at).toLocaleDateString()}. Please renew with admin.`,
    });
  }

  // Device locking verification
  const bindResult = db.bindDevice(cleanKey, deviceId);
  if (!bindResult.ok) {
    return res.json({
      ok: false,
      error: bindResult.error || 'Device verification failed.',
    });
  }

  // Sync device binding to Supabase if enabled
  if (config.authMode === 'supabase_sync' && config.supabaseUrl && bindResult.license) {
    patchSupabaseKey(config, bindResult.license.id, {
      license_devices: bindResult.license.license_devices,
    }).catch(() => {});
  }

  return res.json({
    ok: true,
    license: bindResult.license,
  });
});

// 3. Register New License Request
apiRouter.post('/register-key', async (req, res) => {
  const { full_name, phone, email, amount } = req.body;
  if (!full_name || !phone || !email) {
    return res.status(400).json({ ok: false, error: 'Full name, phone, and Gmail are required.' });
  }

  try {
    const lic = db.createLicense({
      full_name,
      phone,
      email,
      amount: amount || 'Pending Payment',
      duration: '1 Month',
      max_devices: 1,
      status: 'pending',
    });

    const config = db.getConfig();
    if (config.authMode === 'supabase_sync' && config.supabaseUrl) {
      await insertSupabaseKey(config, lic);
    }

    return res.json({
      ok: true,
      key: lic.license_key,
      license: lic,
    });
  } catch (err: any) {
    return res.status(500).json({ ok: false, error: err?.message || 'Registration failed.' });
  }
});

// 4. Admin Login
apiRouter.post('/admin/login', (req, res) => {
  const { password } = req.body;
  const config = db.getConfig();
  const validPassword = config.adminPassword || 'FRK@2026#WaqasKing!Secure9x';

  if (password && password === validPassword) {
    return res.json({ ok: true });
  }

  return res.status(401).json({ ok: false, error: 'Invalid admin password.' });
});

// 5. Get All License Keys (Admin)
apiRouter.post('/admin/keys', async (req, res) => {
  const { password } = req.body;
  const config = db.getConfig();
  const validPassword = config.adminPassword || 'FRK@2026#WaqasKing!Secure9x';

  if (!password || password !== validPassword) {
    return res.status(401).json({ ok: false, error: 'Invalid admin credentials.' });
  }

  if (config.authMode === 'supabase_sync' && config.supabaseUrl) {
    const remoteKeys = await fetchSupabaseKeys(config);
    if (remoteKeys && remoteKeys.length > 0) {
      // Merge remote keys into memory cache
      for (const k of remoteKeys) {
        db.addOrUpdateLicense(k);
      }
      return res.json({ ok: true, keys: remoteKeys });
    }
  }

  const keys = db.getLicenses();
  return res.json({ ok: true, keys });
});

// 6. Admin Actions (Approve, Block, Delete, Reset Devices, Remove Device)
apiRouter.post('/admin/action', async (req, res) => {
  const { password, id, action, duration, max_devices, deviceId } = req.body;
  const config = db.getConfig();
  const validPassword = config.adminPassword || 'FRK@2026#WaqasKing!Secure9x';

  if (!password || password !== validPassword) {
    return res.status(401).json({ ok: false, error: 'Invalid admin credentials.' });
  }

  if (!id || !action) {
    return res.status(400).json({ ok: false, error: 'License ID and action are required.' });
  }

  const result = db.updateLicenseAction(id, action, duration, max_devices, deviceId);
  if (!result.ok) {
    return res.status(400).json({ ok: false, error: result.error });
  }

  if (config.authMode === 'supabase_sync' && config.supabaseUrl && result.license) {
    await patchSupabaseKey(config, id, {
      status: result.license.status,
      duration_label: result.license.duration_label,
      expires_at: result.license.expires_at,
      max_devices: result.license.max_devices,
      license_devices: result.license.license_devices,
    });
  }

  return res.json({ ok: true, license: result.license });
});

// 7. Admin Mint Pre-Approved Key
apiRouter.post('/admin/create-key', async (req, res) => {
  const { password, custom_key, full_name, phone, email, duration, max_devices } = req.body;
  const config = db.getConfig();
  const validPassword = config.adminPassword || 'FRK@2026#WaqasKing!Secure9x';

  if (!password || password !== validPassword) {
    return res.status(401).json({ ok: false, error: 'Invalid admin credentials.' });
  }

  try {
    const lic = db.createLicense({
      custom_key,
      full_name: full_name || 'VIP Client',
      phone: phone || '+966554573612',
      email: email || 'client@frkings.pro',
      amount: 'Admin Minted',
      duration: duration || '1 Month',
      max_devices: max_devices || 1,
      status: 'approved',
    });

    if (config.authMode === 'supabase_sync' && config.supabaseUrl) {
      await insertSupabaseKey(config, lic);
    }

    return res.json({ ok: true, license: lic });
  } catch (err: any) {
    return res.status(500).json({ ok: false, error: err?.message || 'Failed to mint key.' });
  }
});

// 8. Test Supabase connection
apiRouter.post('/supabase/test', async (req, res) => {
  const { url, key } = req.body;
  if (!url) {
    return res.status(400).json({ ok: false, error: 'Supabase URL is required.' });
  }

  try {
    const cleanUrl = url.replace(/\/+$/, '');
    const headers: Record<string, string> = {
      'Content-Type': 'application/json',
    };
    if (key) {
      headers['apikey'] = key;
      headers['Authorization'] = `Bearer ${key}`;
    }

    const testRes = await fetch(`${cleanUrl}/rest/v1/`, {
      method: 'GET',
      headers,
    });

    if (testRes.ok || testRes.status === 200 || testRes.status === 401 || testRes.status === 404) {
      return res.json({
        ok: true,
        message: 'Successfully reached Supabase URL endpoint.',
        tableStatus: 'Active',
      });
    }

    return res.json({
      ok: false,
      error: `Supabase returned HTTP status ${testRes.status}`,
    });
  } catch (err: any) {
    return res.json({
      ok: false,
      error: `Could not reach ${url}: ${err.message || 'Network error'}`,
    });
  }
});

// 9. Store and retrieve signals
apiRouter.post('/signals', (req, res) => {
  const { signal } = req.body;
  if (!signal || !signal.pair || !signal.direction) {
    return res.status(400).json({ ok: false, error: 'Invalid signal payload.' });
  }

  const saved = db.addSignal(signal);
  return res.json({ ok: true, signal: saved });
});

apiRouter.get('/signals', (_req, res) => {
  return res.json({ ok: true, signals: db.getSignals() });
});

// Mount the API Router under both /api and root (handles Vercel rewrite URL variations)
app.use('/api', apiRouter);
app.use(apiRouter);

// --- Vite Middleware in Dev or Static Files in Production ---
async function startServer() {
  if (!isProduction) {
    const { createServer: createViteServer } = await import('vite');
    const vite = await createViteServer({
      server: { middlewareMode: true },
      appType: 'spa',
    });
    app.use(vite.middlewares);
  } else {
    const distPath = path.resolve(__dirname, 'dist');
    app.use(express.static(distPath));
    app.get('*', (_req, res) => {
      res.sendFile(path.resolve(distPath, 'index.html'));
    });
  }

  app.listen(Number(PORT), '0.0.0.0', () => {
    console.log(`🚀 FR-KING AURA Server running on http://0.0.0.0:${PORT}`);
    console.log(`📁 Database storage active (serverless=${Boolean(process.env.VERCEL)})`);
  });
}

// Only start the HTTP listener if not running in a serverless environment (like Vercel)
if (!process.env.VERCEL) {
  startServer();
}

export default app;
