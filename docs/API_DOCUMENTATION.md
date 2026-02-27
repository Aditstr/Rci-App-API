# 📘 RCI API Documentation

**Base URL:** `http://localhost:8000/api`
**Content-Type:** `application/json`
**Accept:** `application/json`

---

## 🔑 Authentication

All protected endpoints require the following header:

```
Authorization: Bearer {token}
```

Token diperoleh dari endpoint **Register** atau **Login**.

---

## 1. Authentication Endpoints

### 1.1 Register

Mendaftarkan user baru, otomatis membuat Wallet dengan saldo 0, dan mengembalikan token.

| | |
|---|---|
| **URL** | `POST /api/auth/register` |
| **Auth** | ❌ Public |

**Request Body:**

```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "role": "client"
}
```

| Field | Type | Required | Rules |
|---|---|---|---|
| `name` | string | ✅ | max 255 |
| `email` | string | ✅ | valid email, unique |
| `password` | string | ✅ | min 8, confirmed |
| `password_confirmation` | string | ✅ | must match password |
| `role` | string | ✅ | `client`, `paralegal`, `lawyer`, `corporate` |

**Success Response (201):**

```json
{
  "success": true,
  "message": "Registration successful.",
  "token": "1|abc123...",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "client",
    "created_at": "2026-02-18T07:00:00.000000Z",
    "updated_at": "2026-02-18T07:00:00.000000Z"
  }
}
```

**Error Response (422 — Validation):**

```json
{
  "message": "The email has already been taken.",
  "errors": {
    "email": ["The email has already been taken."]
  }
}
```

---

### 1.2 Login

Mengautentikasi user dan mengembalikan token beserta data user.

| | |
|---|---|
| **URL** | `POST /api/auth/login` |
| **Auth** | ❌ Public |

**Request Body:**

```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

| Field | Type | Required |
|---|---|---|
| `email` | string | ✅ |
| `password` | string | ✅ |

**Success Response (200):**

```json
{
  "success": true,
  "message": "Login successful.",
  "token": "2|xyz789...",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "client",
    "created_at": "2026-02-18T07:00:00.000000Z",
    "updated_at": "2026-02-18T07:00:00.000000Z"
  }
}
```

**Error Response (422 — Invalid Credentials):**

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The provided credentials are incorrect."]
  }
}
```

---

### 1.3 Logout

Mencabut (revoke) token akses saat ini.

| | |
|---|---|
| **URL** | `POST /api/auth/logout` |
| **Auth** | 🔒 Bearer Token |

**Request Body:** _none_

**Success Response (200):**

```json
{
  "success": true,
  "message": "Logged out successfully."
}
```

---

### 1.4 Me (Profile)

Mengembalikan data user yang sedang login beserta saldo wallet.

| | |
|---|---|
| **URL** | `GET /api/auth/me` |
| **Auth** | 🔒 Bearer Token |

**Request Body:** _none_

**Success Response (200):**

```json
{
  "success": true,
  "message": "User profile retrieved.",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "client"
  },
  "wallet": {
    "balance": "150000.00"
  }
}
```

---

## 2. AI Chat Endpoints

### 2.1 Chat Send (Freemium)

Mengirim pesan ke AI assistant. Bisa digunakan tanpa login (guest).
Guest & free user memiliki **batas 3 pertanyaan/hari**. Pro member **unlimited**.

| | |
|---|---|
| **URL** | `POST /api/chat/send` |
| **Auth** | ⚪ Optional (supports guest) |

**Request Body:**

```json
{
  "message": "Apa itu hukum perdata?",
  "session_id": "optional-uuid-for-guests",
  "user_id": 1
}
```

| Field | Type | Required | Notes |
|---|---|---|---|
| `message` | string | ✅ | max 2000 |
| `session_id` | string | ❌ | Untuk tracking guest (opsional) |
| `user_id` | integer | ❌ | Untuk testing tanpa auth token |

**Success Response — Free/Guest (200):**

```json
{
  "status": "success",
  "tier": "free",
  "data": {
    "answer": "Hukum perdata adalah cabang hukum yang mengatur hubungan...",
    "topic": "hukum_perdata",
    "confidence": 0.85,
    "system_prompt": "...",
    "disclaimer": "..."
  },
  "usage": {
    "limit": 3,
    "used": 1,
    "remaining": 2
  },
  "upgrade_cta": null
}
```

**Success Response — Pro (200):**

```json
{
  "status": "success",
  "tier": "pro",
  "data": {
    "answer": "Berdasarkan Pasal 1320 KUHPerdata, syarat sahnya perjanjian...",
    "topic": "hukum_perdata",
    "confidence": 0.95,
    "system_prompt": "...",
    "disclaimer": "..."
  },
  "usage": {
    "limit": null,
    "used": null,
    "remaining": "unlimited"
  },
  "escalation": {
    "can_escalate": true,
    "message": "Sebagai member Pro, Anda dapat terhubung langsung dengan Paralegal/Advokat.",
    "escalate_url": "http://localhost:8000/api/chat/escalate"
  }
}
```

**Rate Limited Response (429):**

```json
{
  "status": "forbidden",
  "tier": "free",
  "message": "Anda telah mencapai batas 3 pertanyaan gratis hari ini...",
  "upgrade_url": "http://localhost:8000/pricing",
  "usage": {
    "limit": 3,
    "used": 3,
    "remaining": 0
  }
}
```

---

### 2.2 Chat (Authenticated)

Sama seperti Chat Send, tetapi hanya untuk user yang sudah login (via Sanctum).

| | |
|---|---|
| **URL** | `POST /api/rci/chat` |
| **Auth** | 🔒 Bearer Token |

**Request Body:**

```json
{
  "message": "Bagaimana proses cerai di Indonesia?"
}
```

| Field | Type | Required | Rules |
|---|---|---|---|
| `message` | string | ✅ | max 2000 |

**Success Response (200):**

```json
{
  "success": true,
  "message": "AI response generated successfully.",
  "data": {
    "answer": "...",
    "topic": "perceraian",
    "confidence": 0.9,
    "system_prompt": "...",
    "disclaimer": "..."
  }
}
```

---

## 3. Wallet & Escrow Endpoints

### 3.1 Top Up Wallet

Menambahkan saldo ke wallet user.

| | |
|---|---|
| **URL** | `POST /api/rci/topup` |
| **Auth** | 🔒 Bearer Token |

**Request Body:**

```json
{
  "amount": 100000
}
```

| Field | Type | Required | Rules |
|---|---|---|---|
| `amount` | numeric | ✅ | min 1 |

**Success Response (200):**

```json
{
  "success": true,
  "message": "Wallet topped up successfully.",
  "data": {
    "id": 1,
    "wallet_id": 1,
    "amount": "100000.00",
    "type": "deposit",
    "status": "success",
    "description": "Top-up saldo sebesar Rp 100.000",
    "created_at": "2026-02-18T07:30:00.000000Z"
  }
}
```

**Error Response (422):**

```json
{
  "success": false,
  "message": "Jumlah top-up harus lebih dari 0.",
  "data": null
}
```

---

### 3.2 Upgrade Membership

Upgrade user ke Pro dengan memotong saldo wallet.

| | |
|---|---|
| **URL** | `POST /api/rci/upgrade` |
| **Auth** | 🔒 Bearer Token |

**Request Body:** _none_

**Success Response (200):**

```json
{
  "success": true,
  "message": "Membership upgraded to Pro successfully.",
  "data": null
}
```

**Error Response (422 — Saldo tidak cukup):**

```json
{
  "success": false,
  "message": "Saldo tidak mencukupi untuk berlangganan Pro. ...",
  "data": null
}
```

---

### 3.3 Start Escrow Case

Mengunci dana di escrow untuk sebuah kasus hukum.

| | |
|---|---|
| **URL** | `POST /api/rci/escrow/start` |
| **Auth** | 🔒 Bearer Token |

**Request Body:**

```json
{
  "case_id": 1,
  "amount": 500000
}
```

| Field | Type | Required | Rules |
|---|---|---|---|
| `case_id` | integer | ✅ | must exist in `legal_cases` |
| `amount` | numeric | ✅ | min 1 |

**Success Response (200):**

```json
{
  "success": true,
  "message": "Funds locked in escrow successfully.",
  "data": {
    "id": 2,
    "wallet_id": 1,
    "amount": "500000.00",
    "type": "escrow_hold",
    "status": "pending",
    "reference_id": 1,
    "reference_type": "App\\Models\\LegalCase",
    "description": "Escrow hold untuk kasus #CASE-001 sebesar Rp 500.000",
    "created_at": "2026-02-18T08:00:00.000000Z"
  }
}
```

**Error Response (422 — Saldo tidak cukup):**

```json
{
  "success": false,
  "message": "Saldo tidak mencukupi. Saldo saat ini: Rp 50.000, dibutuhkan: Rp 500.000.",
  "data": null
}
```

---

## 📋 Route Summary

| # | Method | Endpoint | Auth | Description |
|---|---|---|---|---|
| 1 | `POST` | `/api/auth/register` | ❌ Public | Register user baru |
| 2 | `POST` | `/api/auth/login` | ❌ Public | Login & dapat token |
| 3 | `POST` | `/api/auth/logout` | 🔒 Sanctum | Revoke token |
| 4 | `GET` | `/api/auth/me` | 🔒 Sanctum | Get profile + wallet |
| 5 | `POST` | `/api/chat/send` | ⚪ Optional | AI Chat (freemium) |
| 6 | `POST` | `/api/rci/chat` | 🔒 Sanctum | AI Chat (authenticated) |
| 7 | `POST` | `/api/rci/topup` | 🔒 Sanctum | Top up wallet |
| 8 | `POST` | `/api/rci/upgrade` | 🔒 Sanctum | Upgrade ke Pro |
| 9 | `POST` | `/api/rci/escrow/start` | 🔒 Sanctum | Lock dana escrow |

---

## ⚠️ Global Error Responses

**401 Unauthorized** — Token tidak valid atau tidak ada:

```json
{
  "message": "Unauthenticated."
}
```

**422 Validation Error** — Input tidak sesuai:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field_name": ["Error message."]
  }
}
```

**500 Internal Server Error** — Kesalahan server:

```json
{
  "success": false,
  "message": "An unexpected error occurred.",
  "data": null
}
```

---

## 🧪 Postman Quick Start

1. **Register** → simpan `token` dari response
2. Set header pada semua request protected:
   ```
   Authorization: Bearer {token}
   Content-Type: application/json
   Accept: application/json
   ```
3. **Login** → gunakan token baru jika token sebelumnya sudah di-logout
4. **Me** → cek profil dan saldo wallet
5. **Top Up** → isi saldo sebelum upgrade atau escrow
6. **Upgrade** → upgrade membership ke Pro
7. **Escrow Start** → kunci dana untuk kasus hukum
