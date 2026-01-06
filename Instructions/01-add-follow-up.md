# Add Follow-up Modal Flow

This document describes the Add Follow-up modal/page flow and all scenarios in this app.

Source files:
- UI: `app/Views/agent/lead_followup.php`
- Behavior: `assets/js/agent_followup.js`
- Server validation & persistence: `app/Controllers/AgentLeadsController.php`

## Entry/Context
- Page: `agent/lead?id={leadId}`
- The form always shows Attempt `#N`, where `N` is the next attempt number.
- Sequence enforced: if a previous attempt is missing, a warning appears and the next attempt should not proceed.
- Max progress UI shows completion out of 3 attempts (extra attempts are still allowed).

## Always-visible Fields (Required unless noted)
- Contact Date & Time: `contact_datetime` (required)
- Call Status: `call_status` (required)
  - Values: `NO_RESPONSE`, `RESPONDED`, `ASK_CONTACT_LATER`
- Call Screenshot: `call_screenshot` (required)
  - Allowed types: jpg/png/webp; max 3MB
- Notes: `notes` (required, min 50 characters)
- WhatsApp Contacted: `whatsapp_contacted` checkbox (optional)
  - If checked, WhatsApp Screenshot becomes required

## Conditional Sections

### 1) Call Status != RESPONDED
Applies to:
- `NO_RESPONSE`
- `ASK_CONTACT_LATER`

Behavior:
- `interested_status`, `intent`, `buy_property_type`, and all unit/buy/rent fields are hidden/disabled.
- Only the core fields apply (contact datetime, call status, call screenshot, notes, WhatsApp if checked).

Server rules:
- `interested_status`, `intent`, `buy_property_type`, `unit_type`, and all numeric fields are nulled.

### 2) Call Status = RESPONDED
The UI shows the Interested Status dropdown.

#### 2.1) Interested Status = NOT_INTERESTED
Behavior:
- No extra sections shown (explicit requirement).
- Only core fields and `interested_status` apply.

Server rules:
- `intent`, `buy_property_type`, `unit_type`, and all numeric fields are nulled.

#### 2.2) Interested Status = INTERESTED
Behavior:
- The Intent dropdown is required.
- Intent choices: `RENT` or `BUY`.

##### 2.2.a) Intent = BUY
Requires:
- Buy Property Type: `READY_TO_MOVE` or `OFF_PLAN`.

###### BUY + READY_TO_MOVE
Required:
- Unit Type: `VILLA` or `APARTMENT`

Optional:
- Size (sqft)
- Location
- Building
- Beds
- Budget
- Down payment

###### BUY + OFF_PLAN
Optional:
- Location
- Size (sqft)
- Budget
- Down payment

Notes:
- Unit type is not required for OFF_PLAN.

##### 2.2.b) Intent = RENT
Required:
- Unit Type: `VILLA` or `APARTMENT`

Optional:
- Size (sqft)
- Beds
- Number of cheques
- Rent per month
- Yearly budget

## Server-side Validation Summary
The controller enforces the same rules as the UI and rejects invalid combinations:
- `contact_datetime` is required.
- `call_status` must be one of: `NO_RESPONSE`, `RESPONDED`, `ASK_CONTACT_LATER`.
- `notes` must be at least 50 characters.
- If `call_status` != `RESPONDED`: no interested/intent/buy/rent fields allowed.
- If `call_status` = `RESPONDED`:
  - `interested_status` must be `INTERESTED` or `NOT_INTERESTED`.
  - If `NOT_INTERESTED`: no extra fields allowed.
  - If `INTERESTED`:
    - `intent` must be `RENT` or `BUY`.
    - If `BUY`: `buy_property_type` must be `READY_TO_MOVE` or `OFF_PLAN`.
      - If `READY_TO_MOVE`: `unit_type` required (`VILLA` or `APARTMENT`).
      - If `OFF_PLAN`: no unit type required.
    - If `RENT`: `unit_type` required (`VILLA` or `APARTMENT`).
- Optional numeric fields must be >= 0 when provided.
- Call screenshot required.
- WhatsApp screenshot required only when WhatsApp is checked.

## Data Saved
Fields persisted in `Followup::create`:
- Core: `lead_id`, `agent_user_id`, `attempt_no`, `contact_datetime`, `call_status`, `notes`
- Interest: `interested_status`, `intent`, `buy_property_type`
- Property data: `unit_type`, `size_sqft`, `location`, `building`, `beds`, `budget`, `downpayment`
- Rent data: `cheques`, `rent_per_month`, `rent_per_year_budget`
- Proofs: `call_screenshot_path`, `whatsapp_contacted`, `whatsapp_screenshot_path`

## Client-side Behavior (JS)
The UI toggles visibility/required states based on:
- `call_status`
- `interested_status`
- `intent`
- `buy_property_type`
- WhatsApp checkbox
- Notes length counter for min 50 chars

