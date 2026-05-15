# Firebase in MzigoX

Firebase Realtime Database is the **realtime communication layer** for live logistics operations. It is **not** the main backend, financial system, or permanent database.

> **One-line rule:** Firebase is the live radar; Laravel + PostgreSQL are the brain and bank.

See also: [ARCHITECTURE.md](./ARCHITECTURE.md)

---

## System responsibility split

```text
Laravel + PostgreSQL  =  Business truth (permanent)
Firebase RTDB         =  Live operational updates (transient)
```

| Laravel controls | Firebase controls |
|------------------|-------------------|
| Authentication | Live GPS streams |
| Wallet balances | Realtime trip updates |
| Commissions & payments | Driver ONLINE / OFFLINE / BUSY |
| Trip creation & pricing | Live ETA |
| Driver matching | Instant UI sync |
| Analytics & business rules | Trip movement visibility |
| Trip history | |

---

## What must never be in Firebase

Never store:

- Wallet balances
- Commissions or commission state
- Trip payments or pricing
- Financial records or ledger entries
- Permanent trip history
- Business rule outcomes

Firebase is not transactional enough for finance. All money flows through `WalletService` in Laravel.

---

## Final architecture

```text
Customer App  ↔  Firebase RTDB  ↔  Driver App
                      ↑
              (live GPS, presence,
               trip status, ETA)

Customer App  →  Laravel API  →  PostgreSQL
Driver App    →  Laravel API  →  PostgreSQL
                      ↑
              (auth, trips, wallets,
               matching, commissions)
```

---

## Example flows

### 1. Customer creates a trip

```text
Flutter App  →  Laravel API  →  PostgreSQL stores trip
                                      ↓
                              SyncTripToFirebaseJob
                                      ↓
                              trips/{trip_id} (status only)
```

### 2. Driver moves (live tracking)

```text
Driver App  →  Firebase RTDB  drivers/{driver_id}
                    ↓
              Customer App subscribes instantly
```

**High-frequency GPS is written by the driver app directly to Firebase** every **3–5 seconds** (config: `FIREBASE_DRIVER_GPS_INTERVAL` in `.env` / `config/firebase-realtime.php`).

The Laravel endpoint `POST /api/v1/drivers/me/location` stores a **periodic snapshot** in PostgreSQL for the **matching engine** (Haversine). It is not the primary realtime path.

### 3. Trip status changes

```text
Driver/Admin  →  Laravel API  →  PostgreSQL (authoritative status)
                                      ↓
                              SyncTripToFirebaseJob
                                      ↓
                              trips/{trip_id} updated
```

---

## RTDB node schemas

### A. Driver location & presence

**Path:** `drivers/{driver_id}`

```json
{
  "lat": -6.7924,
  "lng": 39.2083,
  "presence": "ONLINE",
  "vehicle_type": "boda",
  "updated_at": "2026-05-14T12:00:00+00:00"
}
```

| Field | Writer | Notes |
|-------|--------|-------|
| `lat`, `lng` | **Driver app** (primary) | Every 3–5s while online |
| `presence` | Driver app + Laravel | `ONLINE` · `OFFLINE` · `BUSY` |
| `vehicle_type` | Laravel (on sync) | Slug from assigned vehicle |
| `updated_at` | Writer | ISO 8601 |

**Presence semantics**

| Value | Meaning |
|-------|---------|
| `ONLINE` | Available for matching |
| `OFFLINE` | Not operating |
| `BUSY` | On active trip (set by Laravel on accept) |

Matching in Laravel only considers drivers with `presence = ONLINE`.

### B. Live trip status

**Path:** `trips/{trip_id}`

```json
{
  "status": "IN_TRANSIT",
  "progress": "in_transit",
  "eta": null,
  "driver_location": { "lat": -6.79, "lng": 39.21 },
  "updated_at": "2026-05-14T12:05:00+00:00"
}
```

| Field | Writer | Notes |
|-------|--------|-------|
| `status` | Laravel | Mirrors `trip_status` enum |
| `progress` | Laravel | UI-friendly label |
| `eta` | Driver app or Laravel | Seconds or ISO duration |
| `driver_location` | Driver app (live) / Laravel (snapshot) | Last known point |
| `updated_at` | Writer | ISO 8601 |

---

## Mobile app responsibilities

### Driver app

1. Authenticate via Laravel (OTP + Sanctum).
2. Set `presence` to `ONLINE` via API or Firebase (per security rules).
3. **Write `lat` / `lng` to `drivers/{driver_id}` every 3–5 seconds.**
4. Optionally POST location snapshot to Laravel for matching.
5. Trip lifecycle actions (accept, status) go through **Laravel API only**.

### Customer app

1. Create trips via Laravel API.
2. **Subscribe to `trips/{trip_id}`** for status, ETA, progress.
3. **Subscribe to `drivers/{driver_id}`** during active trip for live map.
4. Never read wallet or payment data from Firebase.

---

## Laravel implementation

| Component | Role |
|-----------|------|
| `FirebaseRealtimeSyncService` | Server-side trip status + presence sync |
| `SyncTripToFirebaseJob` | Queue `firebase` |
| `SyncDriverPresenceToFirebaseJob` | Queue `firebase` |
| `DriverPresenceService` | `ONLINE` / `OFFLINE` / `BUSY` in PostgreSQL |
| `config/firebase-realtime.php` | Node paths, allowed fields, GPS interval |
| `firebase/database.rules.json` | Security rules template |

Laravel uses **`.update()`** on RTDB nodes to avoid wiping driver-written GPS when syncing presence.

---

## Firebase security rules

Template: [`firebase/database.rules.json`](./firebase/database.rules.json)

| Role | Permissions |
|------|-------------|
| **Driver** | Read/write **own** `drivers/{driver_id}` only |
| **Customer** | Read **own** `trips/{trip_id}` only |
| **Admin** | Read/write monitoring paths (custom claims: `role: admin`) |

Deploy rules:

```bash
firebase deploy --only database
```

Custom claims (set via Firebase Admin / Laravel) should include:

- `role`: `customer` | `driver` | `admin`
- `driver_id`: UUID for drivers
- `trip_id` or `customer_trip_ids`: for trip read access

---

## Why Firebase fits MzigoX

- Low latency for mobile realtime sync
- Native Flutter / Dart integration
- WebSocket abstraction without operating socket servers
- Scales live subscriptions independently of Laravel

Avoids: self-hosted WebSocket clusters, sticky sessions, and realtime infra overhead for **operational** data only.

---

## GPS update strategy

| Interval | Recommendation |
|----------|----------------|
| **3–5 seconds** | Recommended (battery, cost, bandwidth) |
| **1 second** | Avoid — higher Firebase cost and battery drain |

Configure: `FIREBASE_DRIVER_GPS_INTERVAL=4` in `.env`.

---

## Environment variables

```env
FIREBASE_CREDENTIALS=/path/to/service-account.json
FIREBASE_DATABASE_URL=https://your-project.firebaseio.com
FIREBASE_DRIVER_GPS_INTERVAL=4
```

---

## Conclusion

Firebase’s job in MzigoX:

- Live tracking and trip movement visibility
- Realtime synchronization between customer and driver UIs
- Operational presence (`ONLINE` / `OFFLINE` / `BUSY`)
- Low-latency updates without polling Laravel

Firebase is **not** business authority, financial storage, or core system logic. That remains **Laravel + PostgreSQL**.
