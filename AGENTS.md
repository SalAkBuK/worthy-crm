# Project Context for Agents

This file summarizes the project so agents can quickly onboard and work safely.

## Overview
- Project: Real Estate Agent Performance Check System
- Stack: PHP 8 + MySQL (PDO), Apache (XAMPP/WAMP/LAMP)
- Roles: Admin (lead entry), CEO (dashboard), Agent (follow-ups)
- DB: Uses existing `wortuckd_attendance` database and `employees` table without modifying it

## Quick Start
1) Put project in web root (example: `C:\xampp\htdocs\agent_performance_app\`)
2) Set web document root to `public/`
3) Copy `.env.example` to `.env`, set DB credentials
4) Import `database/schema.sql` and `database/seed.sql`
5) Ensure writable folders:
   - `public/uploads/`
   - `storage/logs/`

Login routes: `http://localhost/agent_performance_app/public/login`

## Demo Credentials
- Admin: `kiran / admin1234`
- CEO: `abbas / ceo1234`
- Agents: `afi, altaleb, aurangzeb, benyam, mohamed, raunak, sanaya / agent123`

## Behavior Summary
Admin:
- Bulk add leads in a table, save all rows in one transaction
- View/search/filter leads; view lead details + follow-ups (read-only)
- Lead email/phone can be left blank (bulk + individual)

Agent:
- Sees only assigned leads
- Adds follow-ups with required fields: contact datetime, screenshot, call status, interested status (Interested / 50/50 / Not interested)
- Notes: minimum 50 characters (live counter)
- WhatsApp checkbox makes WhatsApp screenshot mandatory
- Attempts are sequential (attempt #3 requires #1 and #2)
- Lead status auto-updates: NEW (0), IN_PROGRESS (1-2), CLOSED (3+)
- Lead email/phone can be left blank when adding leads
- Follow-up inbox with Due Soon / Due Today / Next Follow-up tabs
- Follow-up scheduling happens in the main follow-up form (no separate quick action flow)

CEO:
- Dashboard with date filter, KPIs, and charts
- Agent performance detail and CSV export

## UI Theme
Use Lahomes admin template as UI source of truth.
- Source: `Lahomes_v1.0/Admin/dist`
- App assets: `public/assets/lahomes/`
- Layout partials: `app/Views/layouts/lahomes/`

## Lead Follow-up UI Notes
- Page view: `app/Views/agent/lead_followup.php` (two-column layout)
- Header summary (left column): lead name, email/phone, status badge, attempt count
- Follow-up form: contact datetime, call status, interested status, screenshots, notes + conditional fields (driven by `public/assets/js/agent_followup.js`)
- Follow-up history table shows past attempts and next follow-up timestamp
- Scenario flow:
  - `NO_RESPONSE`: requires notes with channel mention (whatsapp/sms/email) or WhatsApp checkbox; next follow-up optional.
  - `RESPONDED` + `Interested`: requires intent + buy/rent details; sets lead status to `CLOSED`.
  - `RESPONDED` + `50/50`: allowed; set a next follow-up date/time to keep it in the inbox.
  - `RESPONDED` + `Not interested`: no intent fields; sets lead status to `CLOSED`.
  - `ASK_CONTACT_LATER`: next follow-up date/time required; lead stays in progress until next action.
  - If a follow-up is saved with no next date, a non-blocking nudge is shown.

## Security & Reliability Notes
- PDO prepared statements
- CSRF tokens on POST forms
- Session-based auth + role checks + 403 pages
- Basic brute-force lockout: 5 failures, 10-minute lock (per username + IP)
- File uploads validated (mime/type/size), random filenames
- Logs: `storage/logs/app.log`
- DB migration notes:
  - Leads: add `next_followup_at`, `next_followup_note`, `next_followup_set_by_user_id`, `next_followup_status`, index on `next_followup_at`
  - New table: `lead_followup_remarks` for remark history

## Notifications (Phase 1: must-have)
Lead lifecycle:
- Lead assigned/reassigned (agent)
- Lead status changed (admin + CEO summary)
- Lead idle for X days (agent + admin)
- Follow-up due soon (agent)
- Follow-up overdue (agent, escalates to admin after grace period)

Follow-ups:
- Follow-up created/rescheduled (agent)
- Missed follow-up auto-flagged (agent, admin)

Admin/CEO oversight:
- Bulk import failed/partially failed (admin)
- Agent deactivated/reactivated (admin + CEO)
- Sensitive actions audit (delete lead, reopen lead) for admin + CEO

## Notifications (Phase 2: nice-to-have)
- Agent closed a deal (agent + admin)
- Agent inactive for X days (admin only)
- KPI threshold reached (weekly digest only, no real-time spam)

## Who Gets What (rules)
Agent:
- Assigned/reassigned lead
- Follow-up due/overdue
- Missed follow-up
- Lead won (their own)

Admin:
- All agent-critical + idle leads + bulk import/export status
- Agent activation/deactivation
- Sensitive actions (delete/reopen)

CEO:
- No real-time spam
- Only sensitive actions, agent status changes, weekly summary

## Key Paths
- Controllers: `app/Controllers/`
- Views: `app/Views/`
- Public assets: `public/assets/`
- Uploads: `public/uploads/`
- Logs: `storage/logs/`
- SQL: `database/schema.sql`, `database/seed.sql`

## Current Modules (from routes)
Auth & Profile:
- Login/logout
- Profile view/update + change password

Notifications:
- Notifications list
- Mark all as read
- Live notifications stream

Admin - Leads:
- Leads list
- Individual lead entry
- Bulk lead entry
- Assigned leads list
- View lead detail
- CSV import/export
- Bulk assign (all/selected)
- Clear bulk draft
- Reopen lead

Admin - Agents:
- Agents list
- Agent detail
- Add/create agent
- Update/delete agent
- Bulk import + bulk reset passwords
- Single agent reset password
- Export agents

Agent - Leads & Follow-ups:
- Leads list (full + partial)
- Add lead (create)
- Open lead detail
- Add follow-up

CEO - Reports:
- Dashboard
- Summary
- Agent performance detail
- Export CSV

System Tasks:
- Notifications cron task
- Daily follow-up summary notification (configurable hour/timezone)

## Optional Build
Compiled JS already in `public/assets/js/`.
If needed:
```
npm install
npm run build
```
