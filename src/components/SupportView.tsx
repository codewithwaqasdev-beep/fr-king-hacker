import React from 'react';
import { Shell } from './Shell.tsx';
import { useBot } from '../context/BotContext.tsx';
import { WHATSAPP_URL, TELEGRAM_URL, PHONE_NUMBER } from '../constants.ts';
import { MessageSquare, Send, Phone, Clock, Download, Server } from 'lucide-react';

export const SupportView: React.FC = () => {
  const { appConfig } = useBot();

  const whatsapp = appConfig?.whatsappUrl || WHATSAPP_URL;
  const telegram = appConfig?.telegramUrl || TELEGRAM_URL;
  const phone = appConfig?.phone || PHONE_NUMBER;

  return (
    <Shell>
      <section className="terminal">
        <h2 className="terminal-heading">
          SUPPORT <span>CENTER</span>
        </h2>
        <p className="terminal-subtitle">Our team is available 24/7 to help you</p>

        <div className="support-list">
          <a
            className="link-row"
            href={whatsapp}
            target="_blank"
            rel="noreferrer"
          >
            <MessageSquare className="text-[#00D68F]" />
            <div>
              <strong>WhatsApp Group</strong>
              <small>Join the FR-KINGs community & updates</small>
            </div>
          </a>

          <a
            className="link-row"
            href={telegram}
            target="_blank"
            rel="noreferrer"
          >
            <Send className="text-[#FFD000]" />
            <div>
              <strong>Telegram Channel</strong>
              <small>@frking786 — live updates & market alerts</small>
            </div>
          </a>

          <a
            className="link-row"
            href={`tel:${phone}`}
          >
            <Phone className="text-[#FFC247]" />
            <div>
              <strong>Contact Number</strong>
              <small>{phone}</small>
            </div>
          </a>

          <a
            className="link-row border border-[#FFD000]/30 bg-[#FFD000]/10 hover:bg-[#FFD000]/20"
            href="/fr-king-aura-live-server.zip"
            download="fr-king-aura-live-server.zip"
          >
            <Download className="text-[#FFD000]" />
            <div>
              <strong className="text-[#FFD000]">Download Live Server ZIP</strong>
              <small className="text-[#CCCCCC]">Production bundle for cPanel / Apache hosting (PHP + MySQL)</small>
            </div>
          </a>
        </div>

        <p className="signal-note flex items-center justify-center gap-1.5 mt-4">
          <Clock size={12} className="text-[#FFD000]" />
          <span>Average response time: under 5 minutes</span>
        </p>
      </section>
    </Shell>
  );
};
