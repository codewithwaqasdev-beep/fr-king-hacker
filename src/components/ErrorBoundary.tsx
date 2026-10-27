import React, { Component, ErrorInfo, ReactNode } from 'react';
import { AlertTriangle, RotateCcw, ShieldAlert } from 'lucide-react';

interface Props {
  children: ReactNode;
}

interface State {
  hasError: boolean;
  error: Error | null;
}

export class ErrorBoundary extends Component<Props, State> {
  public state: State = {
    hasError: false,
    error: null,
  };

  public static getDerivedStateFromError(error: Error): State {
    return { hasError: true, error };
  }

  public componentDidCatch(error: Error, errorInfo: ErrorInfo) {
    console.error('FR-KING Terminal Uncaught Error:', error, errorInfo);
  }

  private handleReset = () => {
    try {
      localStorage.removeItem('frk_license');
      sessionStorage.clear();
    } catch {
      // ignore
    }
    window.location.reload();
  };

  public render() {
    if (this.state.hasError) {
      return (
        <main className="min-h-screen bg-[#050505] text-white flex items-center justify-center p-6">
          <div className="w-full max-w-md bg-[#101010] border border-[#FFD000]/30 rounded-2xl p-6 text-center shadow-[0_0_30px_rgba(255,208,0,0.15)]">
            <div className="w-14 h-14 rounded-full bg-[#FF4D6D]/15 border border-[#FF4D6D]/40 flex items-center justify-center mx-auto mb-4 text-[#FF4D6D]">
              <AlertTriangle size={28} />
            </div>

            <h2 className="font-['Oxanium'] font-bold text-lg text-white mb-2">
              TERMINAL INTERFACE RECOVERY
            </h2>

            <p className="text-xs text-[#B8B8B8] font-mono mb-4 leading-relaxed">
              A runtime initialization error occurred. Your database connection and settings are safe.
            </p>

            {this.state.error && (
              <div className="bg-[#050505] border border-[#252525] rounded-lg p-3 text-left font-mono text-[11px] text-[#FF4D6D] mb-5 overflow-x-auto max-h-24">
                {this.state.error.message || 'Unknown render exception'}
              </div>
            )}

            <div className="flex flex-col gap-2">
              <button
                onClick={this.handleReset}
                className="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-[#FFD000] to-[#D9AD00] text-black font-['Oxanium'] font-bold text-xs tracking-wider flex items-center justify-center gap-2 cursor-pointer shadow-[0_0_15px_rgba(255,208,0,0.3)] hover:brightness-110 active:scale-[0.99] transition-all"
              >
                <RotateCcw size={14} />
                <span>RESET TERMINAL & RELOAD</span>
              </button>

              <button
                onClick={() => {
                  window.location.href = './admin';
                }}
                className="w-full py-2.5 px-4 rounded-xl bg-[#202020] border border-[#34302A] text-[#B8B8B8] hover:text-white font-mono text-xs flex items-center justify-center gap-2 cursor-pointer transition-colors"
              >
                <ShieldAlert size={14} />
                <span>OPEN ADMIN PANEL</span>
              </button>
            </div>
          </div>
        </main>
      );
    }

    return this.props.children;
  }
}
