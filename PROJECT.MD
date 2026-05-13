You are a senior backend architect and Laravel expert.

Build a production-grade RESTful API backend for a logistics platform called "MzigoX".

MzigoX is NOT a parcel delivery app or shopping app.

It is a multi-vehicle cargo mobility platform where customers request vehicles to move any type of cargo (mzigo).

Supported transport types:
- Bodaboda
- Bajaji / Guta / Toyo
- Pickup trucks
- Canter trucks
- Lorries / Mende

The system must support:
- realtime logistics operations
- trip lifecycle management
- driver/customer matching
- wallet systems
- prepaid commission logic
- Firebase realtime tracking integration
- scalable marketplace operations

---

# 🎯 TECH STACK

Use:
- Laravel 12
- PostgreSQL
- Laravel Sanctum
- Redis queues
- Firebase Realtime Database (ONLY for realtime updates)
- Firebase Cloud Messaging (FCM)
- REST API architecture
- Modular feature-based architecture

DO NOT use:
- Blade frontend
- Livewire
- direct database business logic from frontend

API only.

---

# ⚠️ VERY IMPORTANT ARCHITECTURE RULE

Laravel + PostgreSQL is the PRIMARY source of truth.

Firebase Realtime Database is ONLY for:
- live GPS updates
- realtime trip status synchronization
- online/offline presence
- live ETA updates

DO NOT store:
- wallet balances
- financial data
- permanent business records
- core transactions

inside Firebase.

All critical business logic must remain inside Laravel + PostgreSQL.

---

# 🏗️ REQUIRED ARCHITECTURE

Use:
- Clean architecture principles
- Thin controllers
- Service classes
- Repository pattern where useful
- Form Request validation
- API Resources
- SOLID principles
- UUID primary keys
- typed PHP

---

# 📁 PROJECT STRUCTURE

app/
 ├── Modules/
 │    ├── Auth/
 │    ├── Users/
 │    ├── Drivers/
 │    ├── Vehicles/
 │    ├── Trips/
 │    ├── Wallets/
 │    ├── Payments/
 │    ├── Notifications/
 │    ├── Firebase/
 │    └── Admin/
 │
 ├── Services/
 ├── Repositories/
 ├── Actions/
 ├── Enums/
 ├── Traits/
 ├── Helpers/
 └── Exceptions/

---

# 🔐 AUTHENTICATION SYSTEM

Implement:
- phone number authentication
- OTP login flow
- Sanctum token authentication
- role-based access control

Roles:
- customer
- driver
- admin

---

# 👤 USER MODULE

Users table:
- UUID id
- full_name
- phone
- email nullable
- role
- status
- profile_photo
- created_at

Features:
- profile management
- account status
- device tracking

---

# 🚚 DRIVER MODULE

Driver features:
- KYC verification
- vehicle assignment
- ratings
- operational status
- wallet relation
- online/offline state

Driver statuses:
- pending
- approved
- suspended

---

# 🚛 VEHICLE MODULE

Dynamic vehicle system.

Vehicle types:
- boda
- bajaji
- pickup
- canter
- lorry

Tables:
- vehicle_types
- vehicles

Vehicle fields:
- plate_number
- capacity
- vehicle_type_id
- owner_id
- status

DO NOT hardcode vehicle logic.

---

# 💰 WALLET SYSTEM (VERY IMPORTANT)

Implement:
- customer wallet
- driver wallet
- wallet transaction ledger
- transaction audit logs
- balance calculation service

Transaction types:
- topup
- withdrawal
- trip_payment
- commission
- refund
- bonus

Use:
- database transactions
- financial consistency
- atomic operations

---

# 💡 PREPAID COMMISSION LOGIC

CRITICAL BUSINESS RULE:

Drivers preload operational balance into wallet.

Flow:
1. Driver accepts trip
2. No commission deducted yet
3. Trip officially starts
4. Commission reserved
5. Trip completes
6. Commission finalized

If cancelled BEFORE trip start:
- no commission charged

If cancelled AFTER trip start:
- commission rules apply

Implement:
- commission reservation
- release logic
- deduction logic
- transaction safety

Use database transactions everywhere.

---

# 📦 TRIP MANAGEMENT SYSTEM

Trip lifecycle states:

REQUESTED
ACCEPTED
DRIVER_ARRIVING
CARGO_LOADED
TRIP_STARTED
IN_TRANSIT
DELIVERED
CANCELLED

Implement:
- create trip
- accept trip
- cancel trip
- complete trip
- trip state validation
- trip status logs

Trip fields:
- customer_id
- driver_id
- vehicle_id
- pickup_location
- destination_location
- cargo_description
- cargo_photo
- estimated_price
- final_price
- trip_status
- payment_status

---

# 🧠 DRIVER MATCHING ENGINE

Implement matching logic using:
- nearest drivers
- vehicle type compatibility
- online status
- wallet balance threshold
- driver eligibility

Use:
- Haversine distance calculations

Prepare scalable service architecture.

---

# 📍 FIREBASE REALTIME INTEGRATION

Firebase Realtime Database should handle ONLY:

1. Driver live GPS
2. Live trip tracking
3. Driver online presence
4. Live ETA updates
5. Active trip synchronization

---

# 🔥 FIREBASE STRUCTURE

Prepare services for syncing:

drivers/{driver_id}
- lat
- lng
- online
- vehicle_type
- updated_at

trips/{trip_id}
- status
- driver_location
- eta
- updated_at

---

# ⚠️ IMPORTANT FIREBASE RULES

DO NOT:
- store wallet balances
- store trip financial records
- store permanent business data

Firebase is ONLY:
- realtime synchronization layer

Laravel/PostgreSQL remain system truth.

---

# 🔔 NOTIFICATION SYSTEM

Implement:
- Firebase Cloud Messaging
- queued notifications
- SMS fallback structure

Triggers:
- trip accepted
- driver arrived
- trip started
- trip completed
- wallet updates

Use Redis queues.

---

# ⚡ QUEUE SYSTEM

Use Redis queues for:
- driver matching
- push notifications
- SMS sending
- analytics jobs
- Firebase sync jobs

---

# 🧾 API RESPONSE FORMAT

All responses must follow:

{
  "success": true,
  "message": "Operation successful",
  "data": {}
}

Use proper:
- HTTP status codes
- validation responses
- exception handling

---

# 🛡️ SECURITY REQUIREMENTS

Implement:
- rate limiting
- role middleware
- transaction protection
- audit logs
- mass assignment protection
- secure validations
- UUID identifiers

---

# 📘 API DOCUMENTATION

Install and configure:
- Scramble OR Swagger

Generate:
- endpoint documentation
- authentication examples
- request/response samples

---

# 🧱 DATABASE REQUIREMENTS

Use:
- UUID primary keys
- foreign key constraints
- indexes for trips & locations
- soft deletes where needed

Create migrations for:
- users
- drivers
- vehicles
- vehicle_types
- trips
- wallets
- wallet_transactions
- trip_status_logs
- notifications

DO NOT store realtime GPS history in PostgreSQL initially.

---

# 🧪 TESTING REQUIREMENTS

Implement:
- Feature tests
- API tests
- wallet consistency tests
- trip lifecycle tests

Critical:
Wallet and trip logic must be thoroughly tested.

---

# 📊 ADMIN API FEATURES

Prepare APIs for:
- active trips
- driver approvals
- wallet monitoring
- analytics
- dispute handling
- commission reports

---

# ⚙️ CODE QUALITY RULES

- Thin controllers
- Business logic in Services
- Use Form Requests
- Use API Resources
- Use enums for statuses
- Use transactions for wallet operations
- Use typed PHP
- Clean reusable services
- Proper exception handling

---

# 🚀 FINAL SYSTEM GOAL

The backend should function like:

"A scalable logistics operating system managing vehicle transport, wallets, trip operations, and realtime cargo movement."

The architecture must be:
- scalable
- maintainable
- production-grade
- financially safe
- realtime-capable
- operationally reliable

Generate:
- migrations
- models
- services
- repositories
- requests
- controllers
- resources
- enums
- jobs
- middleware
- policies
- Firebase services
- tests
- API docs setup
- complete modular backend structure