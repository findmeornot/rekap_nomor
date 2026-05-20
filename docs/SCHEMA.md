# Skema Database Rekap Nomor

## Tabel `teams`
- `id` (bigint, PK)
- `name` (varchar)
- `created_at`, `updated_at` (timestamp)

## Tabel `users`
- `id` (bigint, PK)
- `name` (varchar)
- `email` (varchar, unique)
- `email_verified_at` (timestamp, nullable)
- `password` (varchar)
- `role` (varchar) nilai: `superadmin`, `main_marketing`, `assistant_marketing`
- `team_id` (bigint, nullable, FK -> `teams.id`, nullOnDelete)
- `main_marketing_id` (bigint, nullable, FK -> `users.id`, nullOnDelete)
- `remember_token` (varchar, nullable)
- `created_at`, `updated_at` (timestamp)

Catatan relasi:
- `main_marketing` punya banyak `assistant_marketing` lewat `users.main_marketing_id`
- `users` boleh berada dalam satu tim lewat `users.team_id`
- `superadmin` tidak butuh `main_marketing_id`

## Tabel `contacts`
- `id` (bigint, PK)
- `contact_name` (varchar, nullable)
- `phone` (varchar, unique)
- `assistant_marketing_id` (bigint, nullable, FK -> `users.id`, nullOnDelete)
- `main_marketing_id` (bigint, nullable, FK -> `users.id`, nullOnDelete)
- `contacted_at` (timestamp, nullable)
- `contacted_by_main_marketing_id` (bigint, nullable, FK -> `users.id`, nullOnDelete)
- `created_at`, `updated_at` (timestamp)

Aturan penting:
- `phone` bersifat unik global, sehingga nomor tidak bisa sama antar assistant marketing.

## Ringkasan Relasi
- `users (main_marketing)` 1 --- N `users (assistant_marketing)`
- `users (assistant_marketing)` 1 --- N `contacts`
- `users (main_marketing)` 1 --- N `contacts`
