# Deploying FR-KING AURA on Vercel with Live Database

This project is fully configured for **1-click / Git deployment on Vercel** with a live database and serverless API endpoints.

---

## 🏗 Architecture on Vercel

| Component | Technology | Role |
|---|---|---|
| **Frontend** | Vite + React 19 + Tailwind CSS | High-speed trading terminal UI served globally via Vercel Edge CDN |
| **Backend API** | Vercel Serverless Function (`api/index.ts`) + Express | Handles `/api/verify-key`, `/api/register-key`, `/api/admin/*`, `/api/signals`, `/api/config` |
| **Live Database** | **Firebase Firestore** + **Supabase (Optional)** | Persistent cloud storage for license keys, device bindings, and trading signals |
| **Local Cache** | Serverless `/tmp` storage | Fast memory fallback seeded with initial VIP master keys |

---

## 🚀 Quick Deployment Steps

### 1. Push to GitHub
Commit and push your files to your GitHub repository:
```bash
git add .
git commit -m "Configure Vercel serverless deployment and live database"
git push origin main
```

### 2. Import into Vercel
1. Go to [vercel.com/new](https://vercel.com/new).
2. Connect your GitHub account and click **Import** next to your `King-Hacker` repository.
3. Vercel will automatically detect the **Vite** framework from `vercel.json`.
4. Keep the default build settings:
   - **Build Command**: `npm run build`
   - **Output Directory**: `dist`
   - **Install Command**: `npm install`

### 3. Add Environment Variables (Optional but Recommended)
In the Vercel deployment screen under **Environment Variables**, configure:

| Key | Example Value | Description |
|---|---|---|
| `ADMIN_PASSWORD` | `FRK@2026#WaqasKing!Secure9x` | Password required to unlock `/admin` control panel |
| `API_KEY` | `FRK-PRO-MASTER-KEY-2026` | Master bypass key for VIP instant access |
| `ALLOW_MASTER_KEY_BYPASS` | `true` | Allows instant unlock using master key |
| `SUPABASE_URL` | *(optional)* `https://xyz.supabase.co` | If using Supabase as your primary cloud SQL database |
| `SUPABASE_ANON_KEY` | *(optional)* `eyJhbGci...` | Supabase public anon key |
| `AUTH_MODE` | `local` or `supabase_sync` | Authentication sync mode |

### 4. Click **Deploy**
Vercel will build the frontend assets and deploy the Serverless API in ~30 seconds.

---

## ✅ Verifying Your Live Deployment

Once deployed:

1. **Test the API Health Check**:
   Open:
   ```
   https://<your-project>.vercel.app/api/health
   ```
   It should return:
   ```json
   {
     "ok": true,
     "service": "FR-KING AURA Terminal Backend",
     "status": "online",
     "isServerless": true
   }
   ```

2. **Access the Terminal**:
   Open `https://<your-project>.vercel.app/`.
   - Activate immediately using the master key: `FRK-PRO-MASTER-KEY-2026`.
   - Or register a new client key from the "Register Key" tab.

3. **Open the Admin Panel**:
   Click **Admin Panel** at the top right of the activation screen (or navigate to `/admin`).
   - Password: `FRK@2026#WaqasKing!Secure9x` (or whatever you set in `ADMIN_PASSWORD`).
   - From here you can mint keys, approve pending payments, set expiration dates, and reset device locks.

---

## 🗄️ Database Options on Vercel

### Option 1: Firebase Firestore (Pre-configured)
- Firebase Firestore is pre-wired through `firebase-applet-config.json` and `src/services/firebaseDb.ts`.
- All registered keys, approvals, and signals sync directly with Firestore.
- Works natively on Vercel with zero additional configuration!

### Option 2: Supabase (PostgreSQL)
If you prefer Supabase for relational SQL storage:
1. Create a free project at [supabase.com](https://supabase.com).
2. Run this SQL in your Supabase SQL Editor:
```sql
create table if not exists licenses (
  id text primary key,
  license_key text unique not null,
  full_name text not null,
  phone text,
  email text,
  amount text,
  status text default 'pending',
  duration_label text default '1 Month',
  expires_at timestamptz,
  lifetime boolean default false,
  max_devices int default 1,
  license_devices jsonb default '[]'::jsonb,
  created_at timestamptz default now(),
  updated_at timestamptz default now()
);

-- Enable Row Level Security (optional)
alter table licenses enable row level security;
create policy "Allow anon access" on licenses for all using (true) with check (true);
```
3. Add `SUPABASE_URL` and `SUPABASE_ANON_KEY` to your Vercel Environment Variables (or set them via the Admin Panel -> Auth Settings modal).
