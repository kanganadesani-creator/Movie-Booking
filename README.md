# CineBook - Movie Ticket Booking System

A full-stack movie ticket booking web app built with PHP 8, MySQL, and Bootstrap 5.

## Setup Instructions (XAMPP)

1. **Copy the folder**
   Copy the entire `movie_booking` folder into your XAMPP `htdocs` directory, e.g.
   `C:\xampp\htdocs\movie_booking` (Windows) or `/Applications/XAMPP/htdocs/movie_booking` (Mac).

2. **Start Apache and MySQL** in the XAMPP Control Panel.

3. **Import the database**
   - Open `http://localhost/phpmyadmin`
   - Click "Import" → choose `database.sql` → click "Go"
   - This creates the `cinebook` database with all tables, a default admin account, and sample movies/theatres/shows so the app isn't empty on first run.

4. **Check the config**
   Open `config/database.php` and confirm the DB credentials match your MySQL setup (defaults: user `root`, no password — this is XAMPP's default, so usually no changes needed).

5. **Visit the app**
   - User site: `http://localhost/movie_booking/index.php`
   - Admin panel: `http://localhost/movie_booking/admin/login.php`

## Default Admin Login
```
Email:    admin@cinebook.com
Password: admin123
```

## What's Included

**User side:** Register/login, forgot password (simulated reset link, no SMTP needed),
browse & search/filter movies, movie details with trailer modal, interactive seat map
seat selection, checkout, simulated payment (UPI/Card/Net Banking), PDF e-ticket with
QR code (via FPDF), booking history with cancel option, profile editing.

**Admin side:** Dashboard with live stats, full CRUD for movies/theatres/screens/shows,
booking management (view/cancel/print), payment history, user management (block/unblock/delete),
and reports (daily/monthly revenue, movie-wise and theatre-wise sales).

## Notes & Things You Can Extend for Bonus Marks

- **Seat locking**: Seats are only reserved once a booking reaches checkout (not the instant
  you click them on the seat map). For a stronger implementation, add a `pending` seat lock
  with a short expiry (e.g. 5 minutes) so two people can't fight over the same seat while one
  is mid-checkout.
- **Email confirmations**: Not wired up (no SMTP server assumed). If your college has an SMTP
  relay, this is a good place to add `PHPMailer`.
- **Extra features** mentioned in the original spec but not built (good stretch goals if you
  have time left): dark mode toggle, favorite movies, recently viewed, coupon codes.
- The QR code on tickets is generated via a free public API (`api.qrserver.com`), so it requires
  the server running PHP to have internet access — this is normal on a home/college network.

## Folder Structure

See the project tree — it follows the structure from the original spec (`admin/`, `assets/`,
`config/`, `includes/`, `user/`, `pdf/`, root-level user pages). FPDF library is bundled in `fpdf/`.

## Tested

This project was built and tested end-to-end with a live PHP 8.3 + MariaDB server before
delivery: registration, login, full booking flow (browse → seat select → checkout → pay →
PDF ticket), and the entire admin panel (CRUD on movies/theatres/screens/shows, bookings,
users, reports) — all confirmed working with no PHP errors or warnings.
