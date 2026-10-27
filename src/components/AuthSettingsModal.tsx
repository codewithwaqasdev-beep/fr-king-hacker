import React, { useState, useEffect } from 'react';
import { useBot } from '../context/BotContext.tsx';
import {
  KeyRound,
  SlidersVertical,
  Database,
  Lock,
  Save,
  X,
  RefreshCw,
  CircleCheck,
  CircleAlert,
} from 'lucide-react';

export const AuthSettingsModal: React.FC = () => {
  const {
    isAuthSettingsOpen,
    setAuthSettingsOpen,
    appConfig,
    refreshConfig,
    activateKey,
  } = useBot();

  const [apiKey, setApiKey] = useState<string>('');
  const [newAdminPassword, setNewAdminPassword] = useState<string>('');
  const [authAdminPassword, setAuthAdminPassword] = useState<string>('');
  const [supabaseUrl, setSupabaseUrl] = useState<string>('');
  const [supabaseAnonKey, setSupabaseAnonKey] = useState<string>('');
  const [authMode, setAuthMode] = useState<'local' | 'api_key_only' | 'supabase_sync'>('local');
  const [allowBypass, setAllowBypass] = useState<boolean>(true);

  const [alert, setAlert] = useState<{ type: 'success' | 'error'; text: string } | null>(null);
  const [isSaving, setIsSaving] = useState<boolean>(false);
  const [isTestingSupabase, setIsTestingSupabase] = useState<boolean>(false);
  const [supabaseTestMsg, setSupabaseTestMsg] = useState<string | null>(null);

  useEffect(() => {
    if (appConfig) {
      setApiKey(appConfig.apiKey || '');
      setSupabaseUrl(appConfig.supabaseUrl || '');
      setSupabaseAnonKey(appConfig.supabaseAnonKey || '');
      setAuthMode(appConfig.authMode || 'local');
      setAllowBypass(appConfig.allowMasterApiKeyBypass ?? true);

      const savedAdmin = sessionStorage.getItem('frk_admin');
      if (savedAdmin) {
        setAuthAdminPassword(savedAdmin);
      } else {
        setAuthAdminPassword('');
      }
    }
  }, [appConfig, isAuthSettingsOpen]);

  if (!isAuthSettingsOpen) return null;

  const handleSave = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!authAdminPassword.trim()) {
      setAlert({
        type: 'error',
        text: 'Enter your Admin Password to authorize database configuration changes.',
      });
      return;
    }

    setIsSaving(true);
    setAlert(null);

    try {
      const res = await fetch('/api/config', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          password: authAdminPassword.trim(),
          apiKey: apiKey.trim(),
          adminPassword: newAdminPassword.trim() ? newAdminPassword.trim() : undefined,
          supabaseUrl: supabaseUrl.trim(),
          supabaseAnonKey: supabaseAnonKey.trim(),
          authMode: authMode,
          allowMasterApiKeyBypass: allowBypass,
        }),
      });

      const data = await res.json();
      if (data.ok) {
        setAlert({
          type: 'success',
          text: 'API Key & Database Authentication settings updated successfully!',
        });
        if (newAdminPassword.trim()) {
          sessionStorage.setItem('frk_admin', newAdminPassword.trim());
          setAuthAdminPassword(newAdminPassword.trim());
          setNewAdminPassword('');
        }
        await refreshConfig();
      } else {
        setAlert({
          type: 'error',
          text: data.error || 'Failed to update settings. Check admin password.',
        });
      }
    } catch {
      setAlert({
        type: 'error',
        text: 'Network error updating configuration.',
      });
    } finally {
      setIsSaving(false);
    }
  };

  const handleTestSupabase = async () => {
    if (!supabaseUrl.trim()) {
      setSupabaseTestMsg('Please enter a Supabase URL first.');
      return;
    }

    setIsTestingSupabase(true);
    setSupabaseTestMsg(null);

    try {
      const res = await fetch('/api/supabase/test', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          url: supabaseUrl.trim(),
          key: supabaseAnonKey.trim(),
        }),
      });
      const data = await res.json();
      if (data.ok) {
        setSupabaseTestMsg(
          `✅ ${data.message} ${data.tableStatus ? `(${data.tableStatus})` : ''}`
        );
      } else {
        setSupabaseTestMsg(`❌ ${data.error || 'Connection failed'}`);
      }
    } catch {
      setSupabaseTestMsg('Connection failed. Verify URL reachability.');
    } finally {
      setIsTestingSupabase(false);
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md overflow-y-auto">
      <div className="relative w-full max-w-lg bg-[#151515] border border-[#8D7210]/60 rounded-2xl shadow-2xl p-6 text-[#FFFFFF] my-8">
        {/* Header */}
        <div className="flex items-center justify-between pb-4 border-b border-[#34302A]">
          <div className="flex items-center gap-2.5">
            <div className="p-2 rounded-xl bg-[#2A2208] border border-[#8D7210]/60 text-[#FFD000]">
              <KeyRound size={20} />
            </div>
            <div>
              <h2 className="text-lg font-bold text-white font-mono flex items-center gap-2">
                API Key & Auth Configuration
              </h2>
              <p className="text-xs text-[#B8B8B8]">
                Customize API keys, database modes, and authentication logic
              </p>
            </div>
          </div>

          <button
            onClick={() => setAuthSettingsOpen(false)}
            className="p-1 rounded-lg text-[#B8B8B8] hover:text-white hover:bg-[#202020] transition-colors cursor-pointer"
          >
            <X size={20} />
          </button>
        </div>

        {/* Alert message */}
        {alert && (
          <div
            className={`mt-4 p-3 rounded-xl flex items-center gap-2 text-xs font-medium ${
              alert.type === 'success'
                ? 'bg-[#00D68F]/10 border border-[#00D68F]/30 text-[#00D68F]'
                : 'bg-[#FF4D6D]/10 border border-[#FF4D6D]/30 text-[#FF4D6D]'
            }`}
          >
            {alert.type === 'success' ? (
              <CircleCheck size={16} />
            ) : (
              <CircleAlert size={16} />
            )}
            <span>{alert.text}</span>
          </div>
        )}

        <form onSubmit={handleSave} className="mt-4 space-y-4 text-xs">
          {/* Master API Key section */}
          <div className="p-3.5 bg-[#151515]/80 rounded-xl border border-[#34302A]">
            <div className="flex items-center justify-between mb-1.5">
              <label className="font-semibold text-[#FFD000] flex items-center gap-1.5 uppercase tracking-wide">
                <KeyRound size={13} />
                <span>Master API Key</span>
              </label>
              <button
                type="button"
                onClick={() => {
                  if (apiKey.trim()) {
                    activateKey(apiKey.trim(), {
                      id: 'master_key',
                      license_key: apiKey.trim(),
                      full_name: 'Master VIP Admin',
                      duration_label: 'Master API Key',
                      lifetime: true,
                      status: 'approved',
                      max_devices: 999,
                    });
                    setAuthSettingsOpen(false);
                  }
                }}
                className="text-[11px] text-[#FFE166] underline hover:text-white cursor-pointer"
              >
                Use as Active License
              </button>
            </div>

            <p className="text-[11px] text-[#B8B8B8] mb-2">
              Any user entering this API key on the activation page receives instant unlocked access.
            </p>

            <input
              type="text"
              value={apiKey}
              onChange={(e) => setApiKey(e.target.value)}
              placeholder="e.g. FRK-PRO-MASTER-KEY-2026"
              className="w-full px-3 py-2 bg-[#080808] border border-[#34302A] rounded-lg text-white font-mono text-xs focus:outline-none focus:border-[#FFD000] tracking-wider"
            />

            <div className="mt-2 flex items-center gap-2">
              <input
                type="checkbox"
                id="bypassCheck"
                checked={allowBypass}
                onChange={(e) => setAllowBypass(e.target.checked)}
                className="rounded bg-[#151515] border-[#34302A] text-[#FFD000] focus:ring-0 cursor-pointer"
              />
              <label
                htmlFor="bypassCheck"
                className="text-[11px] text-[#B8B8B8] cursor-pointer"
              >
                Allow master API key bypass (
                <span className="font-mono text-[#FFD000]">
                  {apiKey || 'FRK-PRO-MASTER-KEY-2026'}
                </span>
                )
              </label>
            </div>
          </div>

          {/* Auth mode selector */}
          <div className="p-3.5 bg-[#151515]/80 rounded-xl border border-[#34302A]">
            <label className="font-semibold text-white flex items-center gap-1.5 uppercase tracking-wide mb-1.5">
              <SlidersVertical size={13} className="text-[#FFD000]" />
              <span>Authentication Logic Mode</span>
            </label>

            <div className="grid grid-cols-1 sm:grid-cols-3 gap-2 mt-2">
              <button
                type="button"
                onClick={() => setAuthMode('local')}
                className={`p-2.5 rounded-lg border text-left transition-all cursor-pointer ${
                  authMode === 'local'
                    ? 'border-[#FFD000] bg-[#2A2208] text-white font-bold shadow-[0_0_12px_rgba(255,208,0,0.2)]'
                    : 'border-[#34302A] bg-[#080808]/60 text-[#B8B8B8] hover:border-[#34302A]'
                }`}
              >
                <div className="text-[11px] font-bold">Local DB Locked</div>
                <div className="text-[10px] text-[#B8B8B8] mt-0.5">
                  Admin approval + device binding
                </div>
              </button>

              <button
                type="button"
                onClick={() => setAuthMode('api_key_only')}
                className={`p-2.5 rounded-lg border text-left transition-all cursor-pointer ${
                  authMode === 'api_key_only'
                    ? 'border-[#FFD000] bg-[#2A2208] text-white font-bold shadow-[0_0_12px_rgba(255,208,0,0.2)]'
                    : 'border-[#34302A] bg-[#080808]/60 text-[#B8B8B8] hover:border-[#34302A]'
                }`}
              >
                <div className="text-[11px] font-bold">API Key Only</div>
                <div className="text-[10px] text-[#B8B8B8] mt-0.5">
                  Direct master key verification
                </div>
              </button>

              <button
                type="button"
                onClick={() => setAuthMode('supabase_sync')}
                className={`p-2.5 rounded-lg border text-left transition-all cursor-pointer ${
                  authMode === 'supabase_sync'
                    ? 'border-[#FFD000] bg-[#2A2208] text-white font-bold shadow-[0_0_12px_rgba(255,208,0,0.2)]'
                    : 'border-[#34302A] bg-[#080808]/60 text-[#B8B8B8] hover:border-[#34302A]'
                }`}
              >
                <div className="text-[11px] font-bold">Supabase Cloud</div>
                <div className="text-[10px] text-[#B8B8B8] mt-0.5">
                  Sync with external Supabase
                </div>
              </button>
            </div>
          </div>

          {/* Optional Supabase section */}
          <div className="p-3.5 bg-[#151515]/80 rounded-xl border border-[#34302A] space-y-2">
            <div className="flex items-center justify-between">
              <label className="font-semibold text-white flex items-center gap-1.5 uppercase tracking-wide">
                <Database size={13} className="text-[#FFD000]" />
                <span>Supabase Credentials (Optional)</span>
              </label>

              <button
                type="button"
                onClick={handleTestSupabase}
                disabled={isTestingSupabase}
                className="flex items-center gap-1 px-2 py-0.5 rounded bg-[#202020] text-[#FFD000] hover:bg-[#2A2A2A] text-[11px] cursor-pointer"
              >
                <RefreshCw
                  size={10}
                  className={isTestingSupabase ? 'animate-spin' : ''}
                />
                <span>Test Connection</span>
              </button>
            </div>

            <div>
              <label className="text-[10px] text-[#B8B8B8]">SUPABASE URL</label>
              <input
                type="text"
                value={supabaseUrl}
                onChange={(e) => setSupabaseUrl(e.target.value)}
                placeholder="https://your-project.supabase.co"
                className="w-full mt-1 px-3 py-1.5 bg-[#080808] border border-[#34302A] rounded-lg text-white font-mono text-xs focus:outline-none focus:border-[#FFD000]"
              />
            </div>

            <div>
              <label className="text-[10px] text-[#B8B8B8]">
                SUPABASE PUBLISHABLE / ANON API KEY
              </label>
              <input
                type="text"
                value={supabaseAnonKey}
                onChange={(e) => setSupabaseAnonKey(e.target.value)}
                placeholder="sb_publishable_... or eyJhbGciOi..."
                className="w-full mt-1 px-3 py-1.5 bg-[#080808] border border-[#34302A] rounded-lg text-white font-mono text-xs focus:outline-none focus:border-[#FFD000]"
              />
            </div>

            {supabaseTestMsg && (
              <p className="text-[11px] text-[#FFC247] font-mono mt-1">
                {supabaseTestMsg}
              </p>
            )}
          </div>

          {/* Admin authorization input */}
          <div className="p-3.5 bg-[#080808] rounded-xl border border-[#FFC247]/30 space-y-2">
            <div className="flex items-center gap-1.5 text-[#FFC247] font-semibold uppercase tracking-wide">
              <Lock size={13} />
              <span>Admin Authorization</span>
            </div>

            <p className="text-[11px] text-[#B8B8B8]">
              Enter current password to authorize changes, and optionally specify a new password.
            </p>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
              <div>
                <label className="text-[10px] text-[#B8B8B8]">
                  CURRENT ADMIN PASSWORD *
                </label>
                <input
                  type="password"
                  value={authAdminPassword}
                  onChange={(e) => setAuthAdminPassword(e.target.value)}
                  placeholder="Required"
                  className="w-full mt-1 px-3 py-1.5 bg-[#151515] border border-[#34302A] rounded-lg text-white text-xs focus:outline-none focus:border-[#FFD000]"
                  required
                />
              </div>

              <div>
                <label className="text-[10px] text-[#B8B8B8]">
                  NEW ADMIN PASSWORD (OPTIONAL)
                </label>
                <input
                  type="password"
                  value={newAdminPassword}
                  onChange={(e) => setNewAdminPassword(e.target.value)}
                  placeholder="Leave blank to keep current"
                  className="w-full mt-1 px-3 py-1.5 bg-[#151515] border border-[#34302A] rounded-lg text-white text-xs focus:outline-none focus:border-[#FFD000]"
                />
              </div>
            </div>
          </div>

          {/* Buttons */}
          <div className="flex items-center justify-end gap-2 pt-2">
            <button
              type="button"
              onClick={() => setAuthSettingsOpen(false)}
              className="px-4 py-2 rounded-xl border border-[#34302A] text-[#B8B8B8] hover:text-white cursor-pointer transition-colors"
            >
              Cancel
            </button>

            <button
              type="submit"
              disabled={isSaving}
              className="bot-button bot-button-neon flex items-center gap-1.5 px-5 py-2 cursor-pointer"
            >
              <Save size={14} />
              <span>{isSaving ? 'Saving...' : 'Save & Apply Settings'}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};
