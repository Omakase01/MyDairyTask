# Daily Report App

ระบบรายงานการทำงานประจำวัน (PHP + PDO)

## โครงสร้างหลัก

- `pages/login.php` — เข้าสู่ระบบ
- `pages/insert_report.php` — บันทึกรายงาน
- `list_reports.php` — รายการรายงาน
- `view_report.php` — ดูรายละเอียดรายงาน
- `save_report.php` — บันทึกรายงานลงฐานข้อมูล
- `auth.php` — session/login และสิทธิ์ `admin` / `user`
- `db.php` — PDO database connection
- `assets/` — CSS/JS

## ความปลอดภัย

- **ห้าม commit `config.php`** เพราะไฟล์นี้ใช้เก็บค่าการเชื่อมต่อฐานข้อมูล
- ใช้ `config.example.php` เป็นตัวอย่าง แล้วสร้าง `config.php` บนเครื่องที่ deploy
- หากใช้ Supabase/PostgreSQL ให้เปลี่ยน `db.php` และ `config.php` ให้เป็น PostgreSQL ก่อน deploy

## สิทธิ์ผู้ใช้

- `role = user` เห็นเฉพาะรายงานของตัวเอง
- `role = admin` เห็นรายงานของทุกคน

## เตรียมขึ้น GitHub

```bash
cd C:\xampp\htdocs\dailyreportapp
git init
git add .
git commit -m "Initial commit"
git branch -M main
git remote add origin https://github.com/YOUR_USERNAME/YOUR_REPOSITORY.git
git push -u origin main
```
