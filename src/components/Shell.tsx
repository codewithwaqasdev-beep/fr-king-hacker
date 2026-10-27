import React, { ReactNode, useRef } from 'react';
import { useBot } from '../context/BotContext.tsx';
import { DEV_HANDLE } from '../constants.ts';
import {
  House,
  History,
  ChartNoAxesColumn,
  Headphones,
  Ellipsis,
  ShieldCheck,
  Brain,
  Users,
  Crown,
  KeyRound,
  ShieldAlert,
} from 'lucide-react';

interface ShellProps {
  children: ReactNode;
  showDashboardHeader?: boolean;
}

export const Shell: React.FC<ShellProps> = ({ children, showDashboardHeader = false }) => {
  const { currentRoute, navigateTo, setAuthSettingsOpen, activeLicense, isAdmin } = useBot();
  const logoClicksRef = useRef<number>(0);
  const clickTimeoutRef = useRef<NodeJS.Timeout | null>(null);

  const handleTerminalClick = () => {
    logoClicksRef.current += 1;
    if (clickTimeoutRef.current) clearTimeout(clickTimeoutRef.current);
    clickTimeoutRef.current = setTimeout(() => {
      logoClicksRef.current = 0;
    }, 2500);

    if (logoClicksRef.current >= 5) {
      logoClicksRef.current = 0;
      navigateTo('/admin');
    }
  };

  const navItems = [
    { route: '/dashboard', label: 'Dashboard', icon: House },
    { route: '/history', label: 'History', icon: History },
    { route: '/performance', label: 'Performance', icon: ChartNoAxesColumn },
    { route: '/support', label: 'Support', icon: Headphones },
    { route: '/more', label: 'More', icon: Ellipsis },
  ];

  return (
    <main className="bot-page">
      <div className="bot-shell">
        {/* Top utility row */}
        <div className="flex items-center justify-between mb-3 px-1 text-xs">
          <div
            onClick={handleTerminalClick}
            className="flex items-center gap-1.5 text-[#B8B8B8] font-mono text-[11px] select-none cursor-pointer hover:text-white transition-colors"
            title="FR-KING Terminal (Click 5 times for Admin)"
          >
            <span className="w-2 h-2 rounded-full bg-[#00D68F] animate-pulse" />
            <span>FR-KING TERMINAL</span>
          </div>

          <div className="flex items-center gap-2">
            {isAdmin && (
              <button
                onClick={() => setAuthSettingsOpen(true)}
                className="flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-[#2A2208] border border-[#8D7210]/50 text-[#FFD000] hover:bg-[#3A2F0C] transition-colors text-[11px] font-semibold cursor-pointer"
                title="Change API Key & Auth Logic"
              >
                <KeyRound size={12} />
                <span>API Key & Auth</span>
              </button>
            )}

            <button
              onClick={() => navigateTo('/admin')}
              className="flex items-center gap-1 px-2.5 py-1 rounded-lg bg-[#202020]/80 border border-[#34302A] text-[#B8B8B8] hover:text-white hover:bg-[#202020]/60 transition-colors text-[11px] cursor-pointer"
              title="Admin Panel"
            >
              <ShieldAlert size={12} />
              <span>Admin</span>
            </button>
          </div>
        </div>

        {/* Dashboard Header */}
        {showDashboardHeader && (
          <>
            <header className="bot-header">
              <img
                className="bot-logo cursor-pointer select-none"
                src="/fr-kings-logo.jpg"
                alt="FR-KINGs logo"
                onClick={handleTerminalClick}
                title="FR-KINGs AI Bot Pro"
                onError={(e) => {
                  (e.target as HTMLElement).style.display = 'none';
                }}
              />
              <div>
                <h1>FR-KING AURA</h1>
                <div className="brand-subtitle">
                  AI BOT{' '}
                  <span className="tier-badge">
                    <Crown size={11} /> PRO MAX
                  </span>{' '}
                  NEW VERSION <span className="tier-badge">2.0</span>
                </div>
              </div>
            </header>

            {/* Status strip */}
            <div className="status-strip">
              <div className="status-item">
                <div className="status-icon">
                  <ShieldCheck size={20} />
                </div>
                <div>
                  <small>LICENSE STATUS</small>
                  <strong className="online">ACTIVE</strong>
                </div>
              </div>

              <div className="status-item">
                <div className="status-icon">
                  <Brain size={20} />
                </div>
                <div>
                  <small>AI ENGINE STATUS</small>
                  <strong className="online">ONLINE</strong>
                </div>
              </div>

              <div className="status-item">
                <div className="status-icon">
                  <Users size={20} />
                </div>
                <div>
                  <small>ACTIVE USERS</small>
                  <strong>1M+</strong>
                </div>
              </div>
            </div>

            {/* Unlocked banner */}
            <div className="unlocked">
              <Crown size={17} />
              <span>PRO MAX UNLOCKED ✅</span>
            </div>
          </>
        )}

        {/* Main Content View */}
        {children}

        {/* Developer Credit */}
        <p className="dev-credit">Developer {DEV_HANDLE}</p>
      </div>

      {/* Floating Bottom Navigation */}
      {activeLicense && (
        <nav className="bottom-nav" aria-label="Main navigation">
          {navItems.map(({ route, label, icon: Icon }) => {
            const isActive = currentRoute === route;
            return (
              <button
                key={route}
                onClick={() => navigateTo(route)}
                data-active={isActive}
                aria-current={isActive ? 'page' : undefined}
              >
                <Icon size={20} />
                <span>{label}</span>
              </button>
            );
          })}
        </nav>
      )}
    </main>
  );
};
