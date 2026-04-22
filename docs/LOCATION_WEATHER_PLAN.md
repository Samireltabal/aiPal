# Location + Weather — Implementation Plan (v1)

**Status:** Planned
**Branch:** `feat/location-and-weather` (base: `main`)
**Estimated effort:** ~2 days
**Phase:** 14 (High Impact) — Quality-of-life add

---

## Goal

1. Every user has a **saved location** (lat/lon + display name + timezone) that aiPal uses across the web, WhatsApp, Telegram, scheduled workflows, and the daily briefing.
2. Location is captured in 3 ways: **browser geolocation** (primary), **share-location message** from WhatsApp/Telegram, or a **Google/Apple Maps URL** pasted into any text channel.
3. A **weather tool** (`GetWeatherTool`) uses that location to answer weather questions.
4. A **dashboard card** shows today's weather + short forecast at a glance.

---

## Core Design Decisions

| Decision | Rationale |
|---|---|
| Open-Meteo for weather + geocoding | Free, no API key, commercial-friendly, global |
| Silent browser auto-save on every page load | One source of truth for all channels; "last known good" location works offline |
| Always update `location_updated_at`; throttle writes to 10-min min | Prevents tab-spam writes, preserves staleness signal |
| **Keep `briefing_timezone` as the de-facto general tz** (don't rename) | Rename is a scope bomb — defer to separate PR |
| Maps URL parser with **strict host allowlist** after redirect resolution | SSRF protection (key security invariant) |
| Dashboard card uses `wire:poll.visible.900s` | Pauses polling when tab is backgrounded |
| **Show a confirmation toast** after every silent location save | Transparent UX — user knows it's happening, can opt out |

---

## Data Model

### Migration: add location fields to `users`

```php
Schema::table('users', function (Blueprint $table): void {
    $table->decimal('latitude', 9, 6)->nullable();
    $table->decimal('longitude', 9, 6)->nullable();
    $table->string('location_name')->nullable();
    $table->timestamp('location_updated_at')->nullable();
    $table->string('location_source', 20)->nullable(); // 'browser' | 'whatsapp' | 'telegram' | 'manual' | 'maps_url'
});
```

**NOT renaming** `briefing_timezone` → `timezone` in this PR. Treat the existing column as the general-purpose timezone. (The column name is cosmetic; behavior is already general.)

---

## New Files

| # | File | Purpose |
|---|------|---------|
| 1 | `database/migrations/XXXX_add_location_to_users_table.php` | Schema |
| 2 | `app/Services/Location/GeocodingService.php` | Open-Meteo forward + reverse geocoding |
| 3 | `app/Services/Location/MapsUrlParser.php` | Extract lat/lon from Google/Apple Maps URLs, with **SSRF-safe** short-link resolution |
| 4 | `app/Services/Location/LocationUpdater.php` | Central write path: validate coords, reverse-geocode, throttle, save |
| 5 | `app/Services/Weather/WeatherService.php` | Open-Meteo forecast API with per-coord 10-min cache |
| 6 | `app/Http/Controllers/Api/LocationController.php` | `POST /api/v1/location` endpoint for browser auto-save |
| 7 | `app/Ai/Tools/GetWeatherTool.php` | AI tool reading user's saved location |
| 8 | `app/Livewire/WeatherCard.php` + blade | Dashboard card (livewire component) |
| 9 | `resources/js/location.js` | Alpine snippet — permission query, auto-save, toast on success |
| 10 | `tests/Feature/Location/LocationUpdateTest.php` | Endpoint + throttle + user scoping |
| 11 | `tests/Feature/Location/MapsUrlParserTest.php` | URL format parsing + SSRF protection |
| 12 | `tests/Feature/Location/MessageLocationHandlerTest.php` | WhatsApp/Telegram native + URL-in-text |
| 13 | `tests/Feature/Weather/GetWeatherToolTest.php` | Tool with saved and missing location (mocked API) |
| 14 | `tests/Feature/Weather/WeatherCardTest.php` | Dashboard card render |

**Modified files:** `ProcessWhatsAppMessageJob`, `ProcessTelegramMessageJob`, `Settings` (Livewire + view), `Dashboard` (mount weather card), `routes/api.php` (location endpoint), app layout (include location.js), nav-sidebar / dashboard view.

---

## Flow Details

### 1. Browser auto-save (primary)

```
Page mount (any authenticated page)
  ↓
navigator.permissions.query({name: 'geolocation'})
  ↓
┌──────────────────────┬──────────────────────┬────────────────────────┐
│ granted              │ prompt               │ denied                 │
├──────────────────────┼──────────────────────┼────────────────────────┤
│ silent               │ show dismissible     │ do nothing (use saved  │
│ getCurrentPosition() │ banner asking user   │ if any, else prompt    │
│                      │ to click "Enable     │ manual entry in        │
│                      │ location"            │ Settings)              │
└──────────────────────┴──────────────────────┴────────────────────────┘
  ↓ (on success)
POST /api/v1/location {lat, lon, accuracy}
  ↓
LocationController
  ↓
LocationUpdater->updateFromCoordinates($user, lat, lon, 'browser')
  ├─ 10-min throttle check (skip if recent)
  ├─ GeocodingService->reverseLookup(lat, lon) → {name, timezone}
  └─ $user->update(lat, lon, name, tz, location_updated_at, source)
  ↓
response {name: "Riyadh", updated: true}
  ↓
browser shows toast: "Saved your location as Riyadh — change in Settings"
```

### 2. WhatsApp/Telegram native location share

```
Webhook receives message type=location with lat/lon
  ↓
ProcessWhatsAppMessageJob / ProcessTelegramMessageJob (modified)
  ├─ Is message type 'location'? → LocationUpdater->updateFromCoordinates($user, lat, lon, 'whatsapp'|'telegram')
  │     → reply "Got it — saved as Riyadh. I'll use this for weather and time."
  │     → STOP (don't pass to workflow matcher or ChatAgent)
  ├─ Is text a maps URL? → MapsUrlParser extracts lat/lon → same save flow → reply
  ├─ Does it match a workflow trigger? → run workflow
  └─ Otherwise → ChatAgent as usual
```

### 3. Maps URL parser

**Supported patterns:**
```
https://www.google.com/maps/@LAT,LON,15z
https://www.google.com/maps/place/.../@LAT,LON,...
https://maps.google.com/?q=LAT,LON
https://maps.google.com/?ll=LAT,LON
https://maps.apple.com/?ll=LAT,LON
https://maps.apple.com/?q=LAT,LON
https://goo.gl/maps/XXXX                    ← short link (needs redirect follow)
https://maps.app.goo.gl/XXXX                ← short link
```

**SSRF protection (critical):**
- `Http::timeout(5)->maxRedirects(3)->withoutRedirecting()->get($url)` to inspect Location header manually
- Enforce **host allowlist** at every redirect hop: `google.com`, `www.google.com`, `maps.google.com`, `goo.gl`, `maps.app.goo.gl`, `maps.apple.com`
- Reject any redirect to internal IPs (check resolved host is not `127.0.0.1`, `169.254.*`, `10.*`, `172.16-31.*`, `192.168.*`, `::1`)
- If any hop fails allowlist or IP check → return null, log warning
- Test: `https://goo.gl/maps/evil → redirects to http://localhost:6379` must return null

### 4. Manual Settings UI

A new "Location" card in Settings:
- Current saved location (name + coords + "updated X ago")
- "Detect now" button → browser geolocation flow
- "Enter city manually" input → forward geocode via Open-Meteo → save
- "Clear location" button
- Timezone shown as read-only derived; override dropdown if user wants to force it

---

## Weather Tool

```php
class GetWeatherTool extends AiTool
{
    public function description(): string
    {
        return 'Get current weather and forecast for the user\'s saved location or a specified city. Use when the user asks "what\'s the weather", "will it rain", "how hot is it", etc.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'location' => $schema->string()
                ->description('Optional city name. Defaults to user\'s saved location.')
                ->nullable()->required(),
            'when' => $schema->string()
                ->description('Time range: "now", "today", "tomorrow", or "week".')
                ->enum(['now', 'today', 'tomorrow', 'week'])
                ->nullable()->required(),
        ];
    }
}
```

Returns formatted text like:
*"In Riyadh right now: 34°C, clear skies, humidity 28%, wind 15 km/h NE. Today: high 38°C, low 24°C. No rain expected."*

---

## Dashboard Weather Card

**Placement:** top-right of dashboard, below the "new chat" row.

**Content:**
- Header: location name + "updated X ago"
- Current: large temp, conditions icon, feels-like, humidity, wind
- Today: high/low, precipitation chance
- 12-hour strip (compact rows): time, temp, icon
- 5-day mini forecast

**Refresh:** `wire:poll.visible.900s` (15 min, only when tab is visible)

**Degraded state:** if no saved location → *"Set your location to see weather"* + button to trigger browser geolocation.

---

## Security

| Surface | Control |
|---|---|
| Location endpoint `POST /api/v1/location` | `auth:sanctum`, user-scoped update, 60/min throttle |
| Payload validation | `latitude`: -90..90, `longitude`: -180..180, `accuracy`: numeric optional |
| Reverse-geocode output | Truncate name at 120 chars |
| Maps URL short-link resolution | **Strict host allowlist + private-IP block at every hop**, max 3 redirects, 5s timeout |
| Open-Meteo calls | Server-side only, no user-controlled URLs |
| Browser permission | Respect denial — never re-prompt after decline |

---

## Tests (16 target)

### Location
1. Browser POST saves location and reverse-geocodes
2. Throttle skips writes within 10 min
3. User can't update another user's location
4. Invalid coords rejected
5. Manual city input forward-geocodes and saves

### Maps URL Parser
6. Parses `google.com/maps/@lat,lon,zoom`
7. Parses `maps.google.com/?q=lat,lon`
8. Parses `maps.apple.com/?ll=lat,lon`
9. Resolves `goo.gl/maps/XXX` short link
10. **SSRF: short link redirecting to `localhost` is rejected**
11. **SSRF: short link redirecting to `169.254.169.254` is rejected**
12. **SSRF: redirect to non-allowlist host (`evil.com`) is rejected**

### Message Handler
13. WhatsApp location message saves location + replies
14. Telegram location message saves location + replies
15. Maps URL in text message extracts and saves

### Weather
16. `GetWeatherTool` with no saved location returns helpful error
17. `GetWeatherTool` with saved location hits Open-Meteo (mocked) and formats response
18. Dashboard card renders with saved location and shows degraded state without

---

## Build Phases (day-by-day)

### Day 1 — Backend core
1. Migration + `User` model fillable/casts
2. `GeocodingService` (mocked in tests)
3. `LocationUpdater` with throttle
4. `LocationController` + route
5. `MapsUrlParser` **with SSRF tests first (TDD)**
6. Hook `LocationUpdater` into `ProcessWhatsAppMessageJob` and `ProcessTelegramMessageJob` (detect native + URL)
7. Hook URL parser into `Chat` Livewire/chat message handler

### Day 2 — Weather + UI
8. `WeatherService` (mocked in tests)
9. `GetWeatherTool`
10. `WeatherCard` Livewire component + blade
11. Dashboard card placement
12. Settings "Location" card + "Detect now" + manual entry + clear
13. `resources/js/location.js` — permission query + silent save + toast
14. App layout includes the JS
15. Run Pint, full test pass, `npm run build`
16. Commit, PR

---

## Out of Scope (v2 candidates)

- Per-hour notifications ("storm warning for your area")
- Historical weather data
- Map picker (click on a map to set location)
- Multiple saved locations (home / work)
- Air quality, UV index, sunrise/sunset beyond what Open-Meteo returns
- IP geolocation fallback (can add later if demand)
- Weather-triggered workflow events (can layer on Agent Workflows v2 event triggers)

---

## Risk Register

| Risk | Likelihood | Mitigation |
|---|---|---|
| SSRF via short-link redirects | Medium | Host allowlist + private-IP block + redirect cap + test coverage |
| Open-Meteo rate limits | Low | 10k/day commercial tier + per-coord cache |
| Browser geolocation denial rate | Medium | Graceful manual fallback, nudge banner (not modal) |
| Stale location confusion | Low | Visible "updated X ago" on card + toast on update |
| Timezone mismatch if user travels and never opens web | Low | WhatsApp location share updates tz too |

---

## Success Criteria

1. On first browser visit after granting permission, location auto-saves and toast confirms
2. User can text their WhatsApp bot a dropped pin or Google Maps link and it saves the location with a reply
3. `what's the weather?` from any channel returns current conditions for the saved location
4. Dashboard shows weather card with live-ish data, pauses polling when tab is hidden
5. Malicious short link pointing to internal IP is rejected (test proves it)
6. All 16+ tests green, Pint clean, full suite still at 167+ passed

---

## Decisions Log

| Decision | Reason |
|---|---|
| Open-Meteo over OpenWeatherMap | No API key, free commercial tier, simpler ops |
| Silent browser auto-save (not opt-in each time) | Reduces friction for the 95% case; toast keeps it transparent |
| Save on every GET (throttled 10 min) | Last-known location works cross-channel; 10-min throttle prevents tab storms |
| Keep `briefing_timezone` name | Rename is a scope bomb best done solo |
| Host allowlist, not just HTTP-success check | SSRF is the actual threat |
| `wire:poll.visible.900s` | Halves API traffic from idle tabs |
| Reverse-geocode on save (not display-time) | Name is stable, we want it persisted for offline channels |
| Don't expose lat/lon directly in tool responses | Privacy — show "Riyadh", not "24.71, 46.68" |
