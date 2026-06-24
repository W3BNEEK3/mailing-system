# MailFlow — Business Logic & Product Specification
**Version:** 1.0 (MVP)
**Document Type:** Business Logic & Feature Specification
**Status:** Draft

---

## 1. Product Overview

**MailFlow** is a single-tenant, web-based email sending platform designed for small to medium-sized businesses. It provides a clean, mobile-first interface for composing and sending professional branded emails using pre-built or custom templates. The platform is built on top of **Resend** (primary email provider) with optional support for SMTP providers.

### Core Design Principles
- **Single-tenant:** One business, one domain, one login — no multi-user or multi-tenant architecture in MVP.
- **Mobile-first:** All pages are fully responsive with priority given to mobile usability.
- **Brand consistency:** Global branding (logo, colors) applies across all emails, with per-email overrides allowed.
- **Simplicity:** Users should be able to compose and send a professional email in under 2 minutes.

---

## 2. Authentication

### 2.1 Login
- The application has a **single user account** (the business owner or operator).
- Authentication is handled via a username/email + password login form.
- The session persists using a secure token (JWT or session cookie).
- No registration flow in MVP — the account is pre-seeded during setup.
- Failed login attempts display a toast error message.
- Successful login redirects to the **Dashboard / Compose** page.

### 2.2 Access Control
- All pages except the login screen require an authenticated session.
- Unauthenticated requests are redirected to the login page.

---

## 3. Application Pages & Navigation

The application consists of the following top-level pages, accessible via a persistent sidebar or bottom navigation bar (on mobile):

| Page | Route | Description |
|---|---|---|
| Compose Email | `/compose` | Main email composition interface |
| Recipients | `/recipients` | Manage saved recipient contacts |
| Email Logs | `/logs` | History of sent emails and errors |
| Email Templates | `/settings/templates` | Manage, upload, and preview templates |
| Email Credentials | `/settings/credentials` | Configure email providers and API keys |
| General Settings | `/settings/general` | Configure website/platform-level settings |

---

## 4. General Settings Page (`/settings/general`)

This page controls the identity and configuration of the platform itself — separate from any email-specific settings.

### 4.1 Fields & Configuration Options

| Setting | Type | Description |
|---|---|---|
| Website Name | Text | Display name of the platform (e.g., "Acme Mailer") |
| Website URL | URL | The base URL where the app is hosted |
| Website Logo | Image Upload | Logo displayed in the app's own UI (not email logo) |
| Default Sender Name | Text | The "From" name shown to email recipients |
| Default Sender Email | Email | The "From" email address (must match verified domain) |
| Global Email Logo | Image Upload | Logo injected into all email templates by default |
| Global Primary Color | Color Picker | Default brand primary color applied to all templates |
| Global Secondary Color | Color Picker | Default brand secondary/accent color |
| Default Language | Dropdown | Default language for email composition (e.g., English) |
| Timezone | Dropdown | Timezone used for scheduling and log timestamps |

### 4.2 Behavior
- Changes to **Global Email Logo** and **Global Color Theme** cascade to all email templates unless a template or individual email has a local override.
- Settings are saved on explicit "Save Changes" action, with a toast confirmation on success or failure.
- The Website Logo appears in the app's own navigation/header — it does not affect email content.

---

## 5. Email Templates Page (`/settings/templates`)

This page is the central repository for all email templates available in the system.

### 5.1 Template List View
- Displays all available templates as cards with:
  - Template name
  - Thumbnail preview (rendered preview image or iframe)
  - Category/tag (e.g., Newsletter, Transactional, Promotional)
  - Last modified date
  - Actions: Preview, Edit, Duplicate, Delete

### 5.2 Adding a New Template

Users can add templates in two ways:

#### Option A — Upload Template File
- Accepts `.html` files or `.zip` archives (HTML + inline assets).
- Uploaded templates are stored as raw HTML.
- **Color and logo injection** for uploaded templates requires the template to use **CSS custom property placeholders** or **template variable tokens**:
  - `{{PRIMARY_COLOR}}` — replaced at render time with the active primary color.
  - `{{SECONDARY_COLOR}}` — replaced at render time with the active secondary color.
  - `{{LOGO_URL}}` — replaced at render time with the active logo URL.
- If a template does **not** contain these placeholders, the color and logo override fields will be **disabled** for that template (grayed out in the UI), and a tooltip will inform the user that the template does not support dynamic branding.

#### Option B — Paste Raw HTML/CSS
- A code editor input (e.g., Monaco or CodeMirror) allows pasting raw HTML and inline CSS.
- Same placeholder convention applies as Option A.
- Live preview rendered in a sandboxed iframe on the right (split-screen on desktop, toggle on mobile).

### 5.3 Built-in Templates
- The system ships with a set of pre-built, fully branded templates that support all dynamic placeholders.
- Built-in templates cannot be deleted, but can be duplicated and then customized.

### 5.4 Template Preview
- Clicking "Preview" on any template opens a modal or full-screen preview.
- Preview renders with the **currently active global logo and color theme** unless the template has local overrides.
- A toggle allows previewing in **Desktop** and **Mobile** viewport sizes.

---

## 6. Compose Email Page (`/compose`)

This is the primary workspace of the application.

### 6.1 Page Layout

```
┌─────────────────────────────────────────────────────┐
│  TOP TOOLBAR                                         │
│  [Template ▾] [Logo] [Colors] [Language/Translate ▾]│
│  [Save Draft]                    [Preview] [Send →] │
├─────────────────────────────────────────────────────┤
│  EMAIL METADATA                                      │
│  To: [Recipient input or saved contact picker]      │
│  Subject: [Subject line input]                      │
├─────────────────────────────────────────────────────┤
│  EMAIL BODY EDITOR                                   │
│  (Rich text editor or template-bound form fields)   │
│                                                     │
│                                                     │
└─────────────────────────────────────────────────────┘
```

### 6.2 Top Toolbar Controls

| Control | Behavior |
|---|---|
| **Template Selector** | Dropdown to pick from available templates. Selecting one loads the template into the editor. Warns user if current draft will be lost. |
| **Logo** | Opens a modal to upload or select a logo for this specific email. Overrides the global logo for this email only. |
| **Colors** | Opens a color picker panel to set primary/secondary colors for this email. Overrides global theme for this email only. |
| **Translate** | Dropdown or button to translate the composed email body into a selected language (see Section 6.5). |
| **Save Draft** | Saves the current email composition as a draft. Accessible later from a drafts list. |
| **Preview** | Opens a preview modal showing exactly how the email will look to the recipient. |
| **Send** | Triggers the send flow (see Section 6.6). |

### 6.3 Email Metadata Fields

| Field | Behavior |
|---|---|
| **To** | Accepts free-text email addresses or picks from saved recipients. Supports multiple recipients (comma-separated or tag-style chips). |
| **CC / BCC** | Collapsible fields, hidden by default; expandable via a link ("Add CC / BCC"). |
| **Subject** | Plain text input for the email subject line. |
| **Reply-To** | Optional override for the reply-to address (defaults to sender email from General Settings). |

### 6.4 Email Body Editor

- When a **structured template** is selected, the editor surfaces editable **content zones** (text blocks, image slots, CTA buttons) rather than raw HTML. Users fill in the fields without needing HTML knowledge.
- When an **uploaded/raw HTML template** is selected (or no template), the editor may offer a rich text (WYSIWYG) view or a raw HTML/text toggle.
- Editable zones respect the **active color theme and logo** — changes to toolbar color/logo update the preview in real time.

### 6.5 Translation Feature

- A **Translate** button in the toolbar opens a language selector (e.g., Spanish, French, Portuguese, Arabic, etc.).
- On selection, the system sends the current email body text to a translation API (e.g., Claude API, DeepL, or LibreTranslate).
- The translated content replaces the body of the email **in place** — a confirmation prompt warns the user before overwriting.
- The subject line is also translated.
- After translation, the user can freely edit the translated text.
- The original language version is temporarily cached so the user can revert with an "Undo Translation" button that appears immediately after translating.
- Supported languages: English, Spanish, French, Portuguese, Arabic, German, Chinese (Simplified), Yoruba (and others as available from the translation provider).

### 6.6 Send Flow

1. User clicks **Send**.
2. System validates:
   - At least one recipient address.
   - Subject line is not empty.
   - Email body is not empty.
3. If validation fails, inline error messages appear on the relevant fields.
4. A **send confirmation modal** appears showing:
   - Recipient count.
   - Subject line.
   - Sender name and address.
5. User confirms → email is dispatched via the active provider (default: Resend).
6. On success: Toast notification — "Email sent successfully."
7. On failure: Toast notification with error detail — "Send failed: [provider error message]." Error is also logged in Email Logs.

### 6.7 Drafts
- Drafts are auto-saved every 60 seconds while the user is composing.
- Manual "Save Draft" saves immediately with a toast confirmation.
- Drafts are accessible from a **Drafts** list (sub-section of the Compose page or sidebar).
- Drafts store: recipient(s), subject, body, selected template, logo override, color override, language.

---

## 7. Recipients Page (`/recipients`)

### 7.1 Recipient List
- Displays a searchable, sortable list of saved contacts.
- Columns: Name, Email Address, Company (optional), Tags, Date Added, Actions.

### 7.2 Add / Edit Recipient
- Form fields: First Name, Last Name, Email, Company, Tags (for grouping), Notes.
- Recipients can be added individually or bulk-imported via CSV upload.
- CSV format: `first_name, last_name, email, company, tags`.

### 7.3 Recipient Groups / Tags
- Recipients can be tagged for group sending (e.g., "Clients", "Newsletter", "VIPs").
- In the Compose page, the To field accepts a tag/group name to send to all members of that group.

### 7.4 Unsubscribe / Suppression
- Recipients can be manually marked as "unsubscribed" — they will be excluded from all future sends.
- The system maintains a suppression list to prevent re-adding unsubscribed addresses.

---

## 8. Email Logs Page (`/logs`)

### 8.1 Sent Email Logs
- Every send attempt is logged with:
  - Timestamp
  - Recipient(s)
  - Subject
  - Template used
  - Provider used (Resend / SMTP)
  - Status: Sent, Delivered, Failed, Bounced, Opened (if provider supports webhooks)
- Logs are searchable by recipient, subject, date range, and status.
- Clicking a log entry expands it to show full details and a preview of the sent email.

### 8.2 Error Logs
- Failed sends are captured with:
  - Timestamp
  - Error code and message from the provider
  - Recipient(s) affected
  - Retry status (if applicable)
- Error logs appear in a separate "Errors" tab within the Logs page.
- Toast notifications are shown in real-time at the moment of failure (see Section 6.6).

### 8.3 Received Email Logs *(Conditional Feature)*
- Receiving/reading inbound email requires inbound email routing support from the provider.
- **Resend** supports inbound email routing — if configured, inbound messages sent to the domain can be captured via webhook and displayed in a "Received" tab in the Logs page.
- Each received entry shows: Sender, Subject, Timestamp, and a preview of the message body.
- **This feature is dependent on provider capability and domain configuration.** It is flagged as a conditional MVP feature — implemented if the provider supports it with minimal additional complexity.

### 8.4 Log Retention
- Logs are retained indefinitely in MVP (no auto-purge).
- A manual "Clear Logs" option (with confirmation prompt) is available in the Logs page settings.

---

## 9. Email Credentials Page (`/settings/credentials`)

This page manages the connection between MailFlow and the underlying email sending provider.

### 9.1 Supported Providers (MVP)

| Provider | Type | Notes |
|---|---|---|
| **Resend** | API (Primary) | Default provider. Requires API key. |
| **SMTP** | SMTP | Generic SMTP — supports Gmail, Zoho, Mailgun SMTP, Brevo, etc. |
| **PHPMailer-compatible SMTP** | SMTP | Same SMTP fields, labeled for PHP backend compatibility |

> **MVP Note:** Resend is the default and recommended provider. SMTP is available as a fallback for users who prefer free tier providers (Gmail SMTP, Zoho Mail, etc.).

### 9.2 Resend Configuration Fields
- API Key (masked input, reveal toggle)
- Verified From Email Address
- Verified Domain (informational display)
- Test Connection button → sends a test email to the sender address and shows result as toast

### 9.3 SMTP Configuration Fields
- SMTP Host (e.g., `smtp.gmail.com`)
- SMTP Port (e.g., 465, 587)
- Encryption: None / SSL / TLS / STARTTLS
- Username (usually the sending email address)
- Password / App Password (masked input, reveal toggle)
- From Name
- From Email Address
- Test Connection button

### 9.4 Active Provider Selection
- A radio toggle at the top of the page selects which provider is **active**.
- Only one provider is active at a time.
- Switching providers does not delete saved credentials for the inactive provider.

### 9.5 Security
- All API keys and passwords are stored encrypted at rest.
- Keys are never exposed in API responses or logs — only shown in the settings form with a reveal toggle.

---

## 10. Branding & Theming System

### 10.1 Hierarchy (Order of Precedence)

```
Email-level override  >  Template-level override  >  Global settings
```

- **Global settings** (set in General Settings) apply to all emails as a baseline.
- **Per-email overrides** (set in the Compose toolbar) apply only to that draft/send.
- There is no template-level storage of overrides in MVP — overrides live at the compose/draft level.

### 10.2 Logo Handling
- Logos are uploaded as image files (PNG, JPG, SVG — max 2MB).
- Uploaded logos are stored and served from the app's own file storage (not external CDN in MVP).
- The logo URL is injected into templates via the `{{LOGO_URL}}` placeholder at render/send time.
- If a template does not contain `{{LOGO_URL}}`, the logo override field is disabled for that template.

### 10.3 Color Theme
- Two colors are configurable: **Primary** and **Secondary**.
- Injected via `{{PRIMARY_COLOR}}` and `{{SECONDARY_COLOR}}` placeholders.
- If a template does not contain these placeholders, color pickers are disabled for that template with a tooltip explaining why.

---

## 11. Notifications & Feedback (Toast System)

All user actions produce immediate feedback via toast notifications. The toast system follows these rules:

| Action | Toast Type | Message Example |
|---|---|---|
| Email sent successfully | ✅ Success | "Email sent to 3 recipients." |
| Send failed | ❌ Error | "Send failed: Invalid API key." |
| Draft saved | ℹ️ Info | "Draft saved." |
| Settings saved | ✅ Success | "Settings updated." |
| Template uploaded | ✅ Success | "Template 'Invoice' uploaded." |
| Translation complete | ✅ Success | "Email translated to Spanish." |
| Connection test passed | ✅ Success | "SMTP connection successful." |
| Connection test failed | ❌ Error | "Connection failed: Authentication error." |
| Recipient added | ✅ Success | "Contact saved." |
| Invalid form input | ⚠️ Warning | "Please fill in all required fields." |

- Toasts appear in the top-right corner (desktop) or top-center (mobile).
- Auto-dismiss after 4 seconds for success/info; error toasts persist until manually dismissed.

---

## 12. Mobile Responsiveness

### 12.1 Design Priorities
- All pages are fully functional on screens as small as 375px wide (iPhone SE).
- Navigation collapses to a **bottom tab bar** on mobile (icons + labels for: Compose, Recipients, Logs, Settings).
- The Compose page toolbar wraps or collapses into a scrollable horizontal strip or an expandable "options" panel on small screens.

### 12.2 Mobile-Specific Behaviors
- Email preview renders in a mobile viewport by default on mobile devices.
- The code editor (for raw HTML templates) on mobile shows a simplified view with a full-screen toggle.
- Recipient tag chips wrap gracefully; the To field expands vertically as more recipients are added.
- Touch targets are a minimum of 44×44px in compliance with WCAG AA.

---

## 13. Data Storage Requirements (MVP)

| Data | Storage |
|---|---|
| User credentials | Database (hashed password) |
| General settings | Database (key-value) |
| Email provider credentials | Database (encrypted) |
| Email templates (HTML) | Database or file storage |
| Uploaded logos/images | File storage (local or S3-compatible) |
| Recipient contacts | Database |
| Email drafts | Database |
| Email logs (sent) | Database |
| Email logs (errors) | Database |
| Received emails (if enabled) | Database |

---

## 14. Out of Scope for MVP

The following features are explicitly **not** included in the MVP and may be considered for future versions:

- Multi-user accounts or team collaboration
- Custom domain connection (beyond the single pre-configured domain)
- Email scheduling / delayed send
- A/B testing of email content
- Email analytics dashboard (open rates, click rates) — basic delivery status only
- Subscription/unsubscribe management portal for recipients
- Drag-and-drop visual email builder
- API access for third-party integrations
- White-labeling for resale to other businesses

---

## 15. Technology Stack Recommendations (MVP)

| Layer | Recommendation |
|---|---|
| Frontend | Next.js (React) — SSR + mobile-first |
| Backend | Next.js API routes or Node.js/Express |
| Database | PostgreSQL (via Prisma ORM) or SQLite for minimal setup |
| Email (Primary) | Resend SDK |
| Email (Fallback) | Nodemailer (SMTP) |
| Translation | Claude API / DeepL / LibreTranslate |
| File Storage | Local filesystem (MVP) → S3-compatible bucket (production) |
| Auth | NextAuth.js or custom JWT session |
| Styling | Tailwind CSS |

---

*End of MailFlow Business Logic & Product Specification v1.0*
