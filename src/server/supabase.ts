import { License, AppConfig } from './db.ts';

export async function fetchSupabaseKeys(config: AppConfig): Promise<License[] | null> {
  if (!config.supabaseUrl || !config.supabaseAnonKey) return null;
  const cleanUrl = config.supabaseUrl.replace(/\/+$/, '');

  try {
    const res = await fetch(`${cleanUrl}/rest/v1/licenses?select=*&order=created_at.desc`, {
      method: 'GET',
      headers: {
        'apikey': config.supabaseAnonKey,
        'Authorization': `Bearer ${config.supabaseAnonKey}`,
        'Content-Type': 'application/json',
      },
    });

    if (!res.ok) {
      console.warn(`Supabase fetch failed: HTTP ${res.status}`);
      return null;
    }

    const data = await res.json();
    if (Array.isArray(data)) {
      return data.map((item: any) => ({
        id: item.id || `lic_${Date.now()}`,
        license_key: item.license_key || item.key,
        full_name: item.full_name || 'Customer',
        phone: item.phone || '',
        email: item.email || '',
        amount: item.amount || 'Paid',
        status: item.status || 'pending',
        duration_label: item.duration_label || '1 Month',
        expires_at: item.expires_at || null,
        lifetime: Boolean(item.lifetime),
        max_devices: item.max_devices || 1,
        license_devices: Array.isArray(item.license_devices)
          ? item.license_devices
          : typeof item.license_devices === 'string'
          ? JSON.parse(item.license_devices || '[]')
          : [],
        created_at: item.created_at || new Date().toISOString(),
        updated_at: item.updated_at || new Date().toISOString(),
      }));
    }
  } catch (err) {
    console.warn('Error querying Supabase licenses:', err);
  }
  return null;
}

export async function insertSupabaseKey(config: AppConfig, license: License): Promise<boolean> {
  if (!config.supabaseUrl || !config.supabaseAnonKey) return false;
  const cleanUrl = config.supabaseUrl.replace(/\/+$/, '');

  try {
    const res = await fetch(`${cleanUrl}/rest/v1/licenses`, {
      method: 'POST',
      headers: {
        'apikey': config.supabaseAnonKey,
        'Authorization': `Bearer ${config.supabaseAnonKey}`,
        'Content-Type': 'application/json',
        'Prefer': 'return=minimal',
      },
      body: JSON.stringify({
        id: license.id,
        license_key: license.license_key,
        full_name: license.full_name,
        phone: license.phone,
        email: license.email,
        amount: license.amount,
        status: license.status,
        duration_label: license.duration_label,
        expires_at: license.expires_at,
        lifetime: license.lifetime,
        max_devices: license.max_devices,
        license_devices: license.license_devices,
        created_at: license.created_at,
        updated_at: license.updated_at,
      }),
    });

    return res.ok || res.status === 201;
  } catch (err) {
    console.warn('Error inserting license into Supabase:', err);
    return false;
  }
}

export async function patchSupabaseKey(
  config: AppConfig,
  keyOrId: string,
  patch: Partial<License>
): Promise<boolean> {
  if (!config.supabaseUrl || !config.supabaseAnonKey) return false;
  const cleanUrl = config.supabaseUrl.replace(/\/+$/, '');

  try {
    const res = await fetch(
      `${cleanUrl}/rest/v1/licenses?or=(id.eq.${keyOrId},license_key.eq.${keyOrId})`,
      {
        method: 'PATCH',
        headers: {
          'apikey': config.supabaseAnonKey,
          'Authorization': `Bearer ${config.supabaseAnonKey}`,
          'Content-Type': 'application/json',
          'Prefer': 'return=minimal',
        },
        body: JSON.stringify({
          ...patch,
          updated_at: new Date().toISOString(),
        }),
      }
    );

    return res.ok;
  } catch (err) {
    console.warn('Error patching Supabase license:', err);
    return false;
  }
}
