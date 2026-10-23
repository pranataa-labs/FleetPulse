# FleetPulse: Multi-Tenant SaaS Delivery & Real-Time Fleet Tracking System

[![Laravel Framework](https://img.shields.io/badge/Laravel-13.x-red.svg)](https://laravel.com)
[![WebSockets](https://img.shields.io/badge/Laravel_Reverb-WebSockets-blue.svg)](https://laravel.com/docs/reverb)
[![GIS Mapping](https://img.shields.io/badge/GIS-Leaflet.js_&_OpenStreetMap-green.svg)](https://leafletjs.com)
[![Architecture Discipline](https://img.shields.io/badge/Discipline-Workflow_Master-purple.svg)](https://github.com/pranataa-labs/Workflow-Master)
[![Test Suite](https://img.shields.io/badge/Tests-22_Passed_100%25-brightgreen.svg)](#comprehensive-three-phase-verification)

---

## Executive Overview & Architectural Vision

**FleetPulse** is a production-grade, multi-tenant Software-as-a-Service (SaaS) logistics and live fleet tracking platform engineered for commercial courier services, last-mile parcel distributors, freight haulers, and food delivery networks.

Unlike traditional single-tenant applications, FleetPulse is architected with **strict single-database tenant scoping** (powered by Eloquent Global Scopes and subdomain/header resolvers), sub-second **real-time WebSocket GPS telemetry** via **Laravel Reverb**, spatial **Haversine GIS routing engines**, and **pessimistic database locking** to prevent race conditions during concurrent order claiming.

---

## Architectural Highlights & Novel Engineering Features

### 1. Multi-Tenant Scoped Architecture
- **Tenant Context Isolation:** Each tenant company (`SwiftExpress`, `MetroCargo`) possesses completely isolated fleets, couriers, orders, rate cards, and audit records.
- **`BelongsToTenant` Eloquent Trait:** Injects global query scopes (`WHERE tenant_id = ?`) across read/write operations and auto-binds tenant attributes during model instantiation.
- **Multi-Level Resolver:** Seamlessly resolves tenant contexts through subdomains (`{slug}.fleetpulse.test`), custom domain headers (`X-Tenant-Slug`, `X-Tenant-ID`), or active user authentication state.

### 2. Real-Time WebSockets Telemetry (Laravel Reverb)
- **Sub-Second GPS Broadcasting:** Mobile courier devices push geolocation packets (lat, lng, heading, speed) every 3 to 5 seconds to `/api/driver/location`.
- **Event Streaming:** Dispatches `DriverLocationUpdated` implementing `ShouldBroadcastNow` directly to Reverb WebSocket channels:
  - `order.{tracking_token}`: Real-time public tracking map for the end recipient.
  - `tenant.{tenant_id}.dispatch`: Dispatcher operational radar overview.
- **Smooth Map Interpolation:** Frontend Leaflet.js rendering with animated marker rotation, auto-fitting bounding boxes, and resilient polling fallback.

### 3. Spatial GIS Routing & Dynamic Logistics Pricing Matrix
- **Haversine Great-Circle Formula:** Accurately computes geographic spherical distance between pickup and dropoff coordinates in pure PHP:
  $$\text{Distance (km)} = 2R \arcsin\left(\sqrt{\sin^2\left(\frac{\Delta \phi}{2}\right) + \cos(\phi_1)\cos(\phi_2)\sin^2\left(\frac{\Delta \lambda}{2}\right)}\right)$$
- **Dynamic Rate Card Calculator:** Calculates transparent logistics tariffs based on vehicle type, road distance, cargo mass, and real-time surge multipliers:
  $$\text{Total Fee} = \text{Base Fare} + (\text{Distance (km)} \times \text{Rate/km}) + (\text{Cargo Weight (kg)} \times \text{Rate/kg}) \times \text{Surge Multiplier}$$

### 4. Pessimistic Concurrency Locking (`lockForUpdate`)
- When multiple couriers attempt to claim an incoming dispatched shipment simultaneously, the dispatch controller executes a transactional pessimistic database lock:
  ```php
  DB::transaction(function () use ($orderId, $driverId) {
      $order = Order::where('id', $orderId)->lockForUpdate()->firstOrFail();
      if ($order->delivery_status !== 'DISPATCHED') {
          return null; // Prevents race condition
      }
      $order->update(['driver_id' => $driverId, 'delivery_status' => 'ACCEPTED']);
  });
  ```
- Subsequent concurrent claim attempts receive an immediate HTTP 409 Conflict error without duplicate order allocation.

### 5. Obfuscated Administrative Suite & Security Hardening
- **Route Obfuscation:** The administration operations center is dynamically routed via `ADMIN_PREFIX` from `.env` (e.g. `/fleetpulse-ops-mgmt/dashboard`), rendering default `/admin` probe scanners useless.
- **Honeypot Trap:** Any request targeting standard `/admin` or `/admin/*` is immediately redirected with a flash security alert: `"Akses ditolak. Anda bukan admin."`.
- **IDOR Defense:** Public shipment tracking exposes only cryptographically secure UUIDv4 tokens (`/track/{uuid}`) rather than auto-incrementing integer identifiers.

### 6. Daily Rotating Telemetry Logs (Pak Eko PZN Standard)
- Standardized logging configuration across all environments:
  - `LOG_CHANNEL=daily`
  - `LOG_DAILY_DAYS=14`
- Daily log files are structured under `storage/logs/laravel-YYYY-MM-DD.log` with dedicated event streams for GPS pings, order assignments, and security audit warnings.

---

## Relational Database Entity Schema

```mermaid
erDiagram
    TENANTS ||--o{ USERS : "employs"
    TENANTS ||--o{ FLEETS : "owns"
    TENANTS ||--o{ DRIVERS : "manages"
    TENANTS ||--o{ PRICING_RULES : "configures"
    TENANTS ||--o{ ORDERS : "manages"

    FLEETS ||--o{ DRIVERS : "assigned to"
    USERS ||--o| DRIVERS : "operates as"

    ORDERS ||--o{ TRACKING_CHECKPOINTS : "logs"
    DRIVERS ||--o{ ORDERS : "fulfills"

    TENANTS {
        bigint id PK
        uuid uuid UK
        string name
        string slug UK
        string domain UK
        string plan_tier
        string subscription_status
        json settings
        timestamps created_at
    }

    FLEETS {
        bigint id PK
        bigint tenant_id FK
        string vehicle_type
        string license_plate
        decimal max_weight_kg
        decimal max_volume_cbm
        string status
    }

    DRIVERS {
        bigint id PK
        bigint tenant_id FK
        bigint user_id FK
        bigint fleet_id FK
        string license_number
        boolean is_online
        boolean is_on_duty
        decimal last_lat
        decimal last_lng
        decimal last_heading
        timestamp last_ping_at
    }

    PRICING_RULES {
        bigint id PK
        bigint tenant_id FK
        string vehicle_type
        decimal base_fare
        decimal rate_per_km
        decimal rate_per_kg
        decimal surge_multiplier
        boolean is_active
    }

    ORDERS {
        bigint id PK
        uuid tracking_token UK
        bigint tenant_id FK
        bigint driver_id FK
        string order_number UK
        string sender_name
        string sender_phone
        text pickup_address
        decimal pickup_lat
        decimal pickup_lng
        string recipient_name
        string recipient_phone
        text dropoff_address
        decimal dropoff_lat
        decimal dropoff_lng
        string vehicle_type
        decimal weight_kg
        decimal distance_km
        decimal total_fee
        string payment_status
        string delivery_status
        timestamps created_at
    }

    TRACKING_CHECKPOINTS {
        bigint id PK
        bigint order_id FK
        string status
        decimal lat
        decimal lng
        string note
        timestamp recorded_at
    }
```

---

## Comprehensive Three-Phase Verification

In adherence with **Workflow Master** engineering guidelines, the application implements a strict 3-phase automated verification test suite:

```bash
php artisan test
```

### Verification Matrix Summary

| Verification Phase | Test Suite Class | Tests | Assertions | Result |
| :--- | :--- | :---: | :---: | :---: |
| **Phase 1: Database Architecture** | `DatabaseArchitectureTest` | 4 | 19 | **PASSED** (100%) |
| **Phase 2: Backend Logic & Security** | `BackendLogicAndSecurityTest` | 8 | 29 | **PASSED** (100%) |
| **Phase 3: Frontend UI & Workflow** | `FrontendUiAndWorkflowTest` | 8 | 42 | **PASSED** (100%) |
| **Framework Baseline** | `ExampleTest` | 2 | 2 | **PASSED** (100%) |
| **Total Test Coverage** | **All Automated Test Suites** | **22** | **92** | **100% PASS** |

---

## Local Installation & Quick Start Guide

### Prerequisites
- **PHP:** >= 8.2 (Tested on PHP 8.5)
- **Composer:** >= 2.x
- **Node.js:** >= 20.x & NPM >= 10.x
- **SQLite / MySQL:** Compatible

### Installation Steps

1. **Clone Repository & Navigate to Directory:**
   ```bash
   git clone https://github.com/pranataa-labs/FleetPulse.git
   cd FleetPulse
   ```

2. **Install Composer & NPM Dependencies:**
   ```bash
   composer install
   npm install
   ```

3. **Configure Environment Variables:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Verify Logging & Route Prefix in `.env`:**
   ```env
   LOG_CHANNEL=daily
   LOG_DAILY_DAYS=14
   ADMIN_PREFIX=fleetpulse-ops-mgmt
   BROADCAST_CONNECTION=reverb
   ```

5. **Run Migrations & Seed Sample Tenants:**
   ```bash
   php artisan migrate:fresh --seed
   ```

6. **Build Frontend Assets:**
   ```bash
   npm run build
   ```

7. **Start Application & Reverb Server:**
   ```bash
   # Terminal 1: HTTP Web Server
   php artisan serve

   # Terminal 2: Real-Time Reverb WebSocket Server
   php artisan reverb:start
   ```

---

## Default Seeded Accounts & Testing Credentials

| Role | Email | Password | Primary Surface |
| :--- | :--- | :--- | :--- |
| **Platform Superadmin** | `superadmin@fleetpulse.io` | `password123` | `/{ADMIN_PREFIX}/dashboard` |
| **SwiftExpress Admin** | `admin@swiftexpress.com` | `password123` | `/{ADMIN_PREFIX}/dashboard` |
| **Fleet Dispatcher** | `dispatcher@swiftexpress.com` | `password123` | `/dispatch` |
| **Courier Driver (Budi)** | `budi.driver@swiftexpress.com` | `password123` | `/driver` |
| **MetroCargo Admin** | `admin@metrocargo.com` | `password123` | Multi-Tenant Scoped Isolation |

---

## License & Engineering Ownership

Developed under **Pranataa Labs Architecture Standards** and synchronized with [Workflow Master](https://github.com/pranataa-labs/Workflow-Master). Open-source released under the **MIT License**.
