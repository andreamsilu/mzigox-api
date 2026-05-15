# MzigoX API — System Architecture

MzigoX is a **multi-vehicle cargo mobility platform** (not parcel delivery). Customers request vehicles to move cargo; drivers operate with **prepaid wallet commission**, **trip lifecycle management**, and **realtime tracking** via Firebase.

**Stack:** Laravel · PostgreSQL/MySQL · Sanctum · Redis queues · Firebase RTDB (realtime only) · FCM · Scramble API docs.

---

## 1. System context

```mermaid
flowchart TB
    subgraph Clients
        CA[Customer App]
        DA[Driver App]
        AA[Admin App]
    end

    subgraph MzigoX["MzigoX API — Laravel"]
        API["/api/v1/*"]
        DOCS["/docs/api — Scramble"]
    end

    subgraph Truth["Source of truth"]
        PG[(PostgreSQL / MySQL)]
    end

    subgraph Async["Async workers — Redis queues"]
        Q1[matching]
        Q2[firebase]
        Q3[sms]
        Q4[notifications]
    end

    subgraph Realtime["Realtime layer only"]
        FB[(Firebase RTDB)]
        FCM[FCM]
    end

    CA --> API
    DA --> API
    AA --> API
    API --> PG
    API --> Async
    Async --> FB
    Async --> FCM
    DA -. live GPS / presence .-> FB
    CA -. live trip status .-> FB
```

### Source-of-truth rule

| Store | Holds |
|-------|--------|
| **Laravel + DB** | Users, drivers, vehicles, trips, wallets, ledger, commissions, audits, notifications |
| **Firebase RTDB** | Live GPS, online presence, trip status sync, ETA only |
| **Never in Firebase** | Wallet balances, prices, financial records, permanent business data |

---

## 2. Application layers

```mermaid
flowchart LR
    subgraph HTTP["HTTP edge"]
        R["routes/api.php"]
        MW["Middleware\nForceJson · Sanctum · role · throttle:otp"]
    end

    subgraph Modules["app/Modules/* — feature modules"]
        Auth[Auth]
        Users[Users]
        Drivers[Drivers]
        Vehicles[Vehicles]
        Trips[Trips]
        Wallets[Wallets]
        Admin[Admin]
        FirebaseMod[Firebase]
    end

    subgraph Core["Shared core"]
        SVC["app/Services/*"]
        REPO["app/Repositories/*"]
        ENUM["app/Enums/*"]
        POL["Policies · Jobs"]
    end

    subgraph Data["Persistence"]
        Eloquent["Eloquent models"]
        DB[(Database)]
    end

    R --> MW --> Modules
    Modules --> SVC
    Modules --> REPO
    SVC --> Eloquent
    REPO --> Eloquent
    Eloquent --> DB
    SVC --> POL
```

| Layer | Responsibility |
|-------|----------------|
| **Controllers** | Thin; validate via Form Requests, return `ApiResponse` + API Resources |
| **Services** | Business logic: trips, wallets, OTP, matching, commission |
| **Repositories** | Query and matching data access |
| **Jobs** | Side effects: Firebase sync, matching, SMS, FCM, analytics |

---

## 3. Module map (routes)

Base path: **`/api/v1`**. Defined in `routes/api.php`.

```mermaid
flowchart TB
    subgraph Public["Public"]
        OTP["POST auth/otp/*"]
        VT["GET vehicle-types"]
    end

    subgraph AuthZ["auth:sanctum"]
        ME["auth/me · logout"]
        PROF["users/me · devices"]
        WAL["wallets/me · topups"]
        TRIP["trips CRUD + status + cancel"]
        DRV["drivers/me · online · location"]
    end

    subgraph AdminOnly["auth:sanctum + role:admin"]
        ADM["admin/trips · drivers · wallets · reports"]
    end

    OTP --> AuthM[Auth module]
    VT --> VehM[Vehicles module]
    ME --> AuthM
    PROF --> UsersM[Users module]
    WAL --> WalM[Wallets module]
    TRIP --> TripM[Trips module]
    DRV --> DrvM[Drivers module]
    ADM --> AdmM[Admin module]

    TripM --> TripSvc[TripService]
    WalM --> WalSvc[WalletService]
    AuthM --> OtpSvc[OtpService]
    DrvM --> FbJob[SyncDriverPresenceToFirebaseJob]
    TripM --> FbTrip[SyncTripToFirebaseJob]
    FbJob --> FbSvc[FirebaseRealtimeSyncService]
    FbTrip --> FbSvc
```

### Roles

- `customer` — create trips, cancel, wallet
- `driver` — accept trips, update status/location, wallet
- `admin` — approvals, monitoring, reports

### Endpoint summary

| Area | Routes |
|------|--------|
| **Auth** | `POST auth/otp/request`, `POST auth/otp/verify`, `GET auth/me`, `POST auth/logout` |
| **Users** | `PATCH users/me`, `POST users/me/devices` |
| **Wallets** | `GET wallets/me`, `POST wallets/me/topups` |
| **Trips** | `POST/GET trips`, `GET trips/{id}`, `POST accept`, `PATCH status`, `POST cancel` |
| **Drivers** | `GET drivers/me`, `PATCH online`, `POST location` |
| **Vehicles** | `GET vehicle-types` |
| **Admin** | `GET admin/trips/active`, `POST admin/drivers/{id}/approve`, `GET admin/wallets`, `GET admin/reports/commission`, `GET admin/disputes` |

---

## 4. Trip lifecycle

```mermaid
stateDiagram-v2
    [*] --> REQUESTED: customer creates trip
    REQUESTED --> ACCEPTED: driver accepts\n(wallet headroom checked)
    ACCEPTED --> DRIVER_ARRIVING
    DRIVER_ARRIVING --> CARGO_LOADED
    CARGO_LOADED --> TRIP_STARTED: commission RESERVED\nfrom driver wallet
    TRIP_STARTED --> IN_TRANSIT
    IN_TRANSIT --> DELIVERED: commission FINALIZED
    REQUESTED --> CANCELLED: no commission
    ACCEPTED --> CANCELLED: no commission
    CARGO_LOADED --> CANCELLED: release if reserved
    TRIP_STARTED --> CANCELLED: partial commission
    IN_TRANSIT --> CANCELLED: partial commission
```

### Trip flow sequence

```mermaid
sequenceDiagram
    participant C as Customer
    participant API as TripController
    participant TS as TripService
    participant WS as WalletService
    participant DB as Database
    participant Q as Queue matching
    participant FB as Firebase RTDB

    C->>API: POST /trips
    API->>TS: createTrip()
    TS->>DB: trip REQUESTED + status log
    TS->>Q: FindDriversForTripJob

    Note over API,WS: Driver accepts — no commission yet
    API->>TS: acceptTrip()
    TS->>WS: assertAvailableMinor()
    TS->>DB: ACCEPTED

    API->>TS: PATCH status TRIP_STARTED
    TS->>WS: reserveCommissionMinor()
    TS->>DB: commission_state = reserved
    TS->>Q: SyncTripToFirebaseJob
    Q->>FB: trips/{id} status + location

    API->>TS: PATCH status DELIVERED
    TS->>WS: finalizeCommissionMinor()
    TS->>DB: payment_status = paid
```

**Validation:** `TripStateValidator` enforces allowed transitions. Every change is logged in `trip_status_logs`.

---

## 5. Prepaid commission & wallet

Drivers preload operational balance. Commission is **not** taken on accept — only reserved when the trip **starts**.

```mermaid
flowchart LR
    subgraph Wallet["Driver / customer wallet"]
        B["balance_minor\n(spendable)"]
        R["reserved_balance_minor\n(commission holds)"]
    end

    subgraph Ledger
        WT[wallet_transactions]
        AUD[wallet_transaction_audits]
    end

    B -->|reserve on TRIP_STARTED| R
    R -->|finalize on DELIVERED| Platform[Platform commission]
    R -->|release on early cancel| B
    R -->|partial on late cancel| Platform

    WS[WalletService] -->|DB transaction + lockForUpdate| Wallet
    WS --> WT
    WS --> AUD
```

| Transaction type | Purpose |
|------------------|---------|
| `topup` | Add spendable balance |
| `withdrawal` | Cash out |
| `trip_payment` | Customer trip payment |
| `commission` | Driver platform fee (reserve → finalize) |
| `refund` | Reversal |
| `bonus` | Promotional credit |

**Services:** `WalletService` (atomic ops), `WalletBalanceService` (available vs reserved), `CommissionCalculator` (rate + cancel rules).

---

## 6. Driver matching engine

Triggered by `FindDriversForTripJob` on queue `matching`.

```mermaid
flowchart TB
    T[Trip created REQUESTED]
    J[FindDriversForTripJob]
    MS[DriverMatchingService]
    DR[DriverMatchingRepository]

    T --> J --> MS --> DR

    DR --> F1["is_online = true"]
    DR --> F2["driver status = approved"]
    DR --> F3["vehicle type matches trip"]
    DR --> F4["wallet available ≥ commission"]
    DR --> F5["Haversine distance from pickup"]
```

---

## 7. Firebase — live radar layer

**Full specification:** [FIREBASE.md](./FIREBASE.md)

```text
Laravel + PostgreSQL  =  Brain + Bank (permanent truth)
Firebase RTDB         =  Live Radar (transient operations)
```

```mermaid
flowchart TB
    subgraph Apps
        CA[Customer App]
        DA[Driver App]
    end

    subgraph Laravel["Laravel API + PostgreSQL"]
        Trip[Trip / wallet / matching]
    end

    subgraph FB["Firebase RTDB"]
        FD["drivers/{id}\nGPS · presence"]
        FT["trips/{id}\nstatus · ETA · progress"]
    end

    CA -->|create trip, pay, auth| Laravel
    DA -->|accept, status, auth| Laravel
    DA -->|GPS every 3–5s| FD
    CA -->|subscribe| FT
    CA -->|subscribe| FD
    Laravel -->|trip status, BUSY presence| FB
```

| Data | Primary writer | Store of record |
|------|----------------|-----------------|
| Live GPS | **Driver app → Firebase** | Firebase (transient) |
| Trip status / commission | **Laravel API** | PostgreSQL |
| Presence ONLINE/OFFLINE/BUSY | Driver app + Laravel | PostgreSQL + Firebase sync |
| Location snapshot for matching | Laravel `drivers/me/location` | PostgreSQL |

**Never in Firebase:** wallet balances, commissions, payments, pricing, permanent history.

**Jobs:** `SyncTripToFirebaseJob`, `SyncDriverPresenceToFirebaseJob` (queue: `firebase`).

**Security rules template:** `firebase/database.rules.json`

---

## 8. Authentication & security

```mermaid
flowchart LR
    Phone[Phone number] --> OTP[OTP request]
    OTP --> SMS[SendSmsJob — queue sms]
    Verify[OTP verify] --> User[User record]
    User --> Token[Sanctum Bearer token]
    Token --> API[Protected /api/v1 routes]
    API --> Role[role middleware\ncustomer · driver · admin]
```

- **OTP:** `OtpService` + `phone_otps` table (hashed codes, TTL, attempt limits)
- **Tokens:** Laravel Sanctum (`personal_access_tokens`, UUID morph map includes `user`)
- **Rate limiting:** `throttle:otp` on OTP endpoints
- **Policies:** `TripPolicy` (view, accept, cancel, updateStatus)
- **Mass assignment:** guarded models + Form Request validation

---

## 9. Queues & notifications

| Queue | Job | Purpose |
|-------|-----|---------|
| `matching` | `FindDriversForTripJob` | Candidate driver search |
| `firebase` | `SyncTripToFirebaseJob`, `SyncDriverPresenceToFirebaseJob` | RTDB sync |
| `sms` | `SendSmsJob` | OTP / fallback SMS |
| default | `SendFcmNotificationJob` | Push notifications (FCM stub) |
| default | `AnalyticsEventJob` | Analytics hook |

Production: set `QUEUE_CONNECTION=redis` and run workers per queue.

---

## 10. API response contract

All responses use:

```json
{
  "success": true,
  "message": "Operation successful",
  "data": {}
}
```

Failures return `success: false` with appropriate HTTP status (401, 403, 422, etc.). Implemented in `App\Helpers\ApiResponse`.

---

## 11. Project structure

```
app/
├── Modules/
│   ├── Auth/          OTP + Sanctum login
│   ├── Users/         profile, devices (FCM tokens)
│   ├── Drivers/       KYC state, online, GPS
│   ├── Vehicles/      types + vehicles
│   ├── Trips/         lifecycle + status logs
│   ├── Wallets/       balance + ledger
│   ├── Firebase/      realtime sync service
│   └── Admin/         ops + reports
├── Services/          Trip, Wallet, OTP, Matching, Commission
├── Repositories/      Trip, DriverMatching
├── Jobs/              matching, firebase, sms, fcm, analytics
├── Enums/             statuses & transaction types
├── Policies/          TripPolicy
├── Http/Middleware/   ForceJsonResponse, EnsureUserRole
└── Helpers/           ApiResponse

routes/api.php         → all /api/v1 endpoints
bootstrap/app.php      → API routes, exception handling, middleware
config/scramble.php    → OpenAPI at /docs/api
database/migrations/   → UUID schema, FKs, indexes
tests/Feature/         → OTP, wallet/trip, Sanctum flows
```

---

## 12. Documentation & local run

| Resource | URL / command |
|----------|----------------|
| API docs | `/docs/api` (Scramble) |
| OpenAPI JSON | `/docs/api.json` |
| Health | `/up` |
| Migrate + seed | `php artisan migrate --seed` |
| Tests | `php artisan test` (uses `mzigox_testing` DB per `phpunit.xml`) |
| Serve | `php artisan serve` |

---

## 13. Design principles (from PROJECT.MD)

1. **Thin controllers** — logic lives in services.
2. **DB transactions** for all wallet mutations (`lockForUpdate`).
3. **Enums** for trip, driver, wallet, and payment states.
4. **UUID primary keys** across domain tables.
5. **Firebase is a sync layer**, not a datastore for business or financial data.
6. **Modular features** under `app/Modules/` for scalable team ownership.
