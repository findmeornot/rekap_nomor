# Skema Database Rekap Nomor

## Tabel `users`
- `id` (bigint, PK)
- `name` (varchar)
- `email` (varchar, unique)
- `email_verified_at` (timestamp, nullable)
- `password` (varchar)
- `role` (varchar, default `sub_leader`) nilai: `superadmin`, `leader`, `sub_leader`
- `leader_id` (bigint, nullable, FK -> `users.id`, nullOnDelete)
- `remember_token` (varchar, nullable)
- `created_at`, `updated_at` (timestamp)

Catatan relasi:
- `leader` punya banyak `sub_leader` lewat `users.leader_id`
- `superadmin` tidak butuh `leader_id`

## Tabel `contacts`
- `id` (bigint, PK)
- `contact_name` (varchar, nullable)
- `phone` (varchar, unique)
- `sub_leader_id` (bigint, FK -> `users.id`, cascadeOnDelete)
- `leader_id` (bigint, FK -> `users.id`, cascadeOnDelete)
- `created_at`, `updated_at` (timestamp)

Aturan penting:
- `phone` bersifat unik global, sehingga nomor tidak bisa sama antar sub leader.

## Ringkasan Relasi
- `users (leader)` 1 --- N `users (sub_leader)`
- `users (sub_leader)` 1 --- N `contacts`
- `users (leader)` 1 --- N `contacts`
