# Product Requirements Document (PRD)

# Rekap Nomor Marketing

Version: 1.0
Status: Active Development
Project Type: Internal Operational System

---

# 1. Project Overview

## 1.1 Project Name

Rekap Nomor Marketing

## 1.2 Project Description

Rekap Nomor Marketing adalah aplikasi internal berbasis web yang digunakan untuk mengelola, merekap, memvalidasi, mendistribusikan, dan memantau nomor WhatsApp tim marketing berdasarkan struktur tim wilayah.

Aplikasi ini digunakan oleh:

* Superadmin
* Marketing Utama
* Asisten Marketing

Sistem berfungsi sebagai pusat data nomor WhatsApp untuk operasional marketing.

---

## 1.3 Main Goals

Tujuan utama aplikasi:

* Menghindari duplicate nomor dalam periode aktif
* Mempermudah input nomor oleh Asisten Marketing
* Mempermudah Marketing Utama menghubungi nomor
* Mempermudah monitoring performa tim
* Menyediakan sistem distribusi nomor antar tim
* Menyediakan histori nomor berdasarkan periode
* Menyediakan audit dan tracking aktivitas user

---

## 1.4 Core Concepts

### Team-Based System

Sistem berbasis tim.

Dalam 1 tim dapat terdapat:

* beberapa Marketing Utama
* beberapa Asisten Marketing

Asisten Marketing menginput nomor untuk tim.

Marketing Utama dapat melihat seluruh nomor yang diinput oleh seluruh Asisten Marketing dalam tim yang sama.

---

# 2. User Roles

## 2.1 Superadmin

### Capabilities

* Melihat seluruh data sistem
* Mengelola tim
* Mengelola user
* Membuat akun user
* Mengubah role user
* Mengatur user ke tim tertentu
* Import nomor untuk tim tertentu
* Melihat histori import
* Memantau perpindahan nomor antar tim
* Memantau request nomor
* Melihat seluruh statistik sistem
* Melihat seluruh nomor lintas tim
* Filter seluruh data
* Export data

### Restrictions

* Tidak digunakan untuk operasional harian marketing

---

## 2.2 Marketing Utama

### Capabilities

* Melihat seluruh nomor dalam tim sendiri
* Menghubungi nomor melalui WhatsApp
* Mengubah status nomor
* Filter data nomor
* Export data nomor
* Menerima notifikasi nomor baru
* Mengambil nomor dari tim lain
* Melihat histori nomor tim

### Restrictions

* Tidak dapat melihat data tim lain secara penuh
* Tidak dapat mengelola user
* Tidak dapat menghapus data sistem global

---

## 2.3 Asisten Marketing

### Capabilities

* Input nomor manual
* Import nomor
* Melihat nomor yang diinput sendiri
* Melihat histori input sendiri

### Restrictions

* Tidak dapat melihat seluruh nomor tim
* Tidak dapat melihat data tim lain
* Tidak dapat mengelola user
* Tidak dapat export data global

---

# 3. Team Structure

## 3.1 Team Structure Rules

Dalam 1 tim dapat terdapat:

* beberapa Marketing Utama
* beberapa Asisten Marketing

Setiap user hanya boleh berada di 1 tim.

Semua nomor yang diinput Asisten Marketing dianggap milik tim.

Marketing Utama dapat melihat seluruh nomor dalam tim yang sama.

---

## 3.2 Team Ownership Rules

* Nomor dimiliki oleh tim
* Nomor tidak dimiliki individu
* Nomor dapat dipindahkan antar tim
* Perpindahan nomor harus tercatat dalam sistem

---

# 4. Business Rules

## 4.1 Duplicate Rules

### Main Rule

Duplicate nomor hanya dicek dalam periode bulan yang sama.

### Example

#### Allowed

* Nomor 628123 masuk di periode 2026-05
* Nomor 628123 masuk lagi di periode 2026-06

#### Rejected

* Nomor 628123 masuk dua kali di periode 2026-05

---

## 4.2 Duplicate Scope

Duplicate berlaku secara global lintas seluruh sistem.

Contoh:

* Tim A input 628123 di periode 2026-05
* Tim B tidak boleh input 628123 di periode 2026-05

---

## 4.3 Phone Number Validation Rules

Nomor yang diterima:

* 08
* 62
* +62

### Invalid Examples

* 12345
* abcde
* 00999
* +abc

---

## 4.4 Phone Number Normalization Rules

Semua nomor harus disimpan dalam format normalize.

### Examples

Input:

* 08123456789
* +628123456789
* 628123456789

Stored Value:

* 628123456789

---

## 4.5 Phone Number Length Rules

* Minimal 10 digit
* Maksimal 15 digit

---

## 4.6 Contact Status Rules

Available statuses:

* Belum Dihubungi
* Sudah Dihubungi

Default status:

* Belum Dihubungi

---

## 4.7 Team Transfer Rules

Marketing Utama dapat mengambil nomor dari tim lain.

### Rules

* Limit pengambilan: 100 nomor per hari
* Setelah limit habis, pengambilan harus melalui request
* Semua perpindahan nomor harus tercatat
* Ownership nomor berpindah ke tim baru

---

## 4.8 Notification Rules

Saat Asisten Marketing menginput nomor:

* Marketing Utama dalam tim yang sama menerima notifikasi

---

## 4.9 Period Rules

Sistem menggunakan periode bulanan.

Format periode:

* YYYY-MM

### Examples

* 2026-05
* 2026-06

---

# 5. Features

# 5.1 Current Features

## Authentication

* Login
* Logout
* Session management

## Role System

* Superadmin
* Marketing Utama
* Asisten Marketing

## Team Management

* Create team
* Assign user to team
* Reassign team

## Contact Input

* Manual input
* Bulk import
* Multiple number input

## Contact Management

* Contact list
* Search
* Pagination
* Status filter
* Period filter

## WhatsApp Integration

* Open WhatsApp
* Auto status update

## Notification

* Notification badge
* Notification list

## Export

* CSV export

---

# 5.2 Future Features

## Voice Notification

Belum menjadi prioritas implementasi.

## Ototop Integration

Belum menjadi prioritas implementasi.

## Copy Mass Contact

Masih dalam tahap pertimbangan.

## Full Realtime System

Masih tahap pengembangan lanjutan.

---

# 6. Detailed Feature Flows

# 6.1 Flow - Manual Contact Input

## Actor

Asisten Marketing

## Steps

1. User membuka halaman input nomor
2. User mengisi:

   * nama kontak
   * nomor kontak
3. User submit form
4. Sistem validasi format nomor
5. Sistem normalize nomor
6. Sistem menentukan periode aktif
7. Sistem cek duplicate:

   * berdasarkan normalized_phone
   * berdasarkan period aktif
8. Jika duplicate ditemukan:

   * data ditolak
   * tampil pesan error
9. Jika valid:

   * data disimpan
   * status default = Belum Dihubungi
   * ownership mengikuti tim user
10. Sistem mengirim notifikasi ke Marketing Utama dalam tim yang sama

---

# 6.2 Flow - Bulk Import

## Actor

* Asisten Marketing
* Superadmin

## Supported File Types

* csv
* txt
* xlsx
* xls

## Steps

1. User upload file
2. Sistem validasi tipe file
3. Sistem membaca file
4. Sistem mapping kolom
5. Sistem loop setiap nomor
6. Sistem normalize nomor
7. Sistem validasi nomor
8. Sistem cek duplicate berdasarkan periode aktif
9. Duplicate di-skip
10. Nomor valid disimpan
11. Sistem menampilkan summary:

* total data
* berhasil
* duplicate
* invalid

---

# 6.3 Flow - Contact Status Update

## Actor

Marketing Utama

## Methods

* Dropdown status
* Tombol Hubungi

---

## Flow A - Dropdown

1. User memilih status baru
2. Sistem update status menggunakan AJAX
3. Halaman tidak reload
4. Status langsung berubah

---

## Flow B - Tombol Hubungi

1. User klik tombol Hubungi
2. Sistem membuka WhatsApp
3. Sistem otomatis mengubah status menjadi Sudah Dihubungi
4. contacted_at diisi

---

# 6.4 Flow - Team Transfer

## Actor

Marketing Utama

## Steps

1. User memilih nomor
2. User memilih tim tujuan
3. Sistem cek limit harian
4. Jika limit belum habis:

   * transfer diproses
5. Jika limit habis:

   * transfer harus menggunakan request
6. Ownership nomor berpindah ke tim tujuan
7. Sistem menyimpan transfer log

---

# 6.5 Flow - Notification

## Trigger

Asisten Marketing berhasil input nomor

## Steps

1. Contact berhasil disimpan
2. Sistem mencari Marketing Utama dalam tim yang sama
3. Sistem membuat notifikasi
4. Notifikasi muncul di dashboard Marketing Utama

---

# 6.6 Flow - Superadmin Import

## Actor

Superadmin

## Steps

1. Superadmin memilih tim tujuan
2. Superadmin upload/import nomor
3. Sistem menggunakan logic validasi yang sama seperti Asisten Marketing
4. Ownership nomor mengikuti tim tujuan
5. Duplicate tetap dicek

---

# 7. Database Rules

## 7.1 General Rules

* Semua nomor menggunakan normalized_phone
* Semua nomor memiliki period_key
* Semua nomor memiliki team_id
* Semua nomor memiliki input_by

---

## 7.2 Duplicate Constraint

Duplicate checker menggunakan:

* normalized_phone
* period_key

Duplicate checker bersifat global lintas seluruh sistem.

---

## 7.3 Recommended Main Tables

## users

* id
* name
* email
* password
* role
* team_id
* created_at
* updated_at

---

## teams

* id
* name
* created_at
* updated_at

---

## contacts

* id
* name
* phone
* normalized_phone
* status
* team_id
* input_by
* period_key
* contacted_at
* contacted_by
* created_at
* updated_at

---

## notifications

* id
* user_id
* title
* message
* is_read
* created_at
* updated_at

---

## contact_transfers

* id
* contact_id
* from_team_id
* to_team_id
* transferred_by
* created_at

---

# 8. UI/UX Rules

## General Rules

* Gunakan pagination
* Gunakan responsive layout
* Hindari reload halaman jika tidak diperlukan
* Gunakan search dan filter konsisten

---

## Contact List Rules

Contact list harus memiliki:

* Search
* Status filter
* Period filter
* Pagination
* Export
* Action button

---

## Status Rules

* Status menggunakan dropdown
* Perubahan status menggunakan AJAX
* Tombol Hubungi tetap tersedia

---

## Notification Rules

Notification harus:

* tampil di navbar
* memiliki unread badge
* dapat ditandai read

---

# 9. Technical Requirements

## Backend

* Laravel 12+
* PHP 8.3+

---

## Frontend

* Blade
* Tailwind CSS
* Alpine.js
* Vite

---

## Database

* MySQL
* MariaDB compatible

---

## File Processing

* phpoffice/phpspreadsheet

---

# 10. Technical Constraints

## Important Rules For Developers And AI

* Jangan mengubah flow existing yang tidak diminta
* Jangan menghapus fitur existing tanpa instruksi
* Jangan mengubah struktur auth existing
* Semua fitur baru harus mengikuti business rules di PRD ini
* Semua fitur baru harus reuse duplicate checker existing
* Semua fitur baru harus reuse normalization logic existing
* Semua fitur baru harus mengikuti role permission existing
* Implementasi harus dilakukan bertahap
* Hindari refactor besar tanpa instruksi eksplisit

---

# 11. Import Rules

## Max Upload Size

5 MB

---

## Allowed File Types

* csv
* txt
* xlsx
* xls

---

## Supported Header Aliases

### Phone

* phone
* nomor
* no hp
* nohp
* number

### Name

* name
* nama
* contact_name
* kontak

---

# 12. Route Overview

## Superadmin

* /superadmin/users
* /superadmin/contacts
* /superadmin/import

---

## Marketing Utama

* /leader/contacts
* /leader/contacts/export
* /leader/contacts/{contact}/whatsapp

---

## Asisten Marketing

* /sub-leader/contacts
* /sub-leader/contacts/import

---

# 13. Audit & Logging

## Required Logs

Sistem harus dapat mencatat:

* input nomor
* update status
* transfer nomor
* import nomor
* request nomor

---

# 14. Security Rules

## Access Rules

* User hanya dapat mengakses data sesuai role
* User hanya dapat mengakses data tim sendiri
* Semua route harus menggunakan middleware role

---

## Validation Rules

* Semua input harus tervalidasi
* Semua upload file harus tervalidasi
* Semua nomor harus dinormalisasi

---

# 15. Future Development Notes

## Planned Improvements

* Websocket realtime notification
* Voice notification
* Ototop integration
* API layer
* Mobile integration
* Advanced analytics
* Audit dashboard
* Performance dashboard

---

# 16. Development Workflow Recommendation

## Recommended Development Order

### Phase 1

* Team structure
* Validation
* Duplicate system
* Dropdown status

### Phase 2

* Notification
* Import improvement
* Transfer system

### Phase 3

* Audit system
* Analytics
* Optimization

### Phase 4

* Realtime websocket
* External integrations
* Advanced automation

---

# 17. AI Development Instructions

## Mandatory Rules For AI Assistants

When processing future prompts:

* Always use this PRD as source of truth
* Do not assume undocumented business logic
* Do not refactor unrelated code
* Do not rename tables/columns without instruction
* Do not remove backward compatibility unless requested
* Reuse existing logic whenever possible
* Keep implementation modular
* Preserve existing routes unless instructed otherwise
* Preserve existing authentication flow
* Preserve existing role middleware structure

---

# End Of Document