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
- `role` (varchar) nilai: `superadmin`, `leader`, `sub_leader`
- `team_id` (bigint, nullable, FK -> `teams.id`, nullOnDelete)
- `leader_id` (bigint, nullable, FK -> `users.id`, nullOnDelete)
- `remember_token` (varchar, nullable)
- `created_at`, `updated_at` (timestamp)

Catatan relasi:
- `leader` punya banyak `sub_leader` lewat `users.leader_id`
- `users` boleh berada dalam satu tim lewat `users.team_id`
- `superadmin` tidak butuh `leader_id`

## Tabel `contacts`
- `id` (bigint, PK)
- `contact_name` (varchar, nullable)
- `phone` (varchar, unique)
- `sub_leader_id` (bigint, nullable, FK -> `users.id`, nullOnDelete)
- `leader_id` (bigint, nullable, FK -> `users.id`, nullOnDelete)
- `contacted_at` (timestamp, nullable)
- `contacted_by_leader_id` (bigint, nullable, FK -> `users.id`, nullOnDelete)
- `created_at`, `updated_at` (timestamp)

Aturan penting:
- `phone` bersifat unik global, sehingga nomor tidak bisa sama antar asisten marketing.

## Ringkasan Relasi
- `users (leader)` 1 --- N `users (sub_leader)`
- `users (sub_leader)` 1 --- N `contacts`
- `users (leader)` 1 --- N `contacts`
