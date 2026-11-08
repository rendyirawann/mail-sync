# Database Mail Sync

Driver default: PostgreSQL (`DB_CONNECTION=pgsql`, database `mail_sync`). Primary key `users.id` bertipe **UUID**.

## Tabel

| Tabel | Isi | Catatan |
| --- | --- | --- |
| `users` | Akun pengguna | `id` UUID, `username`, `email`, `no_wa`, `avatar`, `is_active`, `banned_at`, `last_ip`, `last_login`, `social_id`, `social_type`, `nik`, `phone` |
| `password_reset_tokens` | Token reset password | Kedaluwarsa 60 menit (`config/auth.php`) |
| `sessions` | Sesi (SESSION_DRIVER=database) | `user_id` UUID, dipakai fitur riwayat sesi login |
| `cache`, `cache_locks` | Cache database | |
| `jobs`, `job_batches`, `failed_jobs` | Queue | |
| `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` | Spatie Permission | `permissions.category` tambahan untuk pengelompokan |
| `activity_log` | Spatie Activitylog | `causer`/`subject` morph UUID |
| `settings` | Pengaturan key–value | Secret OAuth disimpan terenkripsi (APP_KEY) |

## ERD

```mermaid
erDiagram
    users ||--o{ sessions : "memiliki"
    users ||--o{ model_has_roles : "model_id"
    users ||--o{ model_has_permissions : "model_id"
    roles ||--o{ model_has_roles : ""
    permissions ||--o{ model_has_permissions : ""
    roles ||--o{ role_has_permissions : ""
    permissions ||--o{ role_has_permissions : ""
    users ||--o{ activity_log : "causer"
    users ||--o{ password_reset_tokens : "email"

    users {
        uuid id PK
        string name
        string username
        string email
        string no_wa
        string avatar
        boolean is_active
        timestamp banned_at
        timestamp last_login
        string social_type
    }
    roles {
        bigint id PK
        string name
        string guard_name
    }
    permissions {
        bigint id PK
        string name
        string category
        string guard_name
    }
    activity_log {
        bigint id PK
        string log_name
        string event
        uuid causer_id
        uuid subject_id
        json properties
    }
    settings {
        bigint id PK
        string key
        text value
    }
    sessions {
        string id PK
        uuid user_id
        string ip_address
        int last_activity
    }
```

## Konvensi migration

- Tabel plural snake_case, pivot alfabetis (`order_product`).
- Foreign key eksplisit dengan `->constrained()->cascadeOnDelete()` / `nullOnDelete()` sesuai kebutuhan.
- Index untuk kolom yang sering difilter/diurutkan.
- Selalu cek dengan `php artisan migrate --pretend` sebelum menjalankan di production.
