-- =====================================================
-- CineBook - Movie Ticket Booking System
-- Database Schema
-- Import this file in phpMyAdmin before running the app
-- =====================================================

CREATE DATABASE IF NOT EXISTS cinebook;
USE cinebook;

-- ---------------------------------------
-- USERS
-- ---------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    status ENUM('active','blocked') DEFAULT 'active',
    reset_token VARCHAR(100) DEFAULT NULL,
    reset_expires DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---------------------------------------
-- ADMINS
-- ---------------------------------------
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Default admin login -> email: admin@cinebook.com | password: admin123
INSERT INTO admins (name, email, password) VALUES
('Super Admin', 'admin@cinebook.com', '$2b$10$wtQEqAwYFYCsie0SmXpEieEBKhPRkjXmp7Bg.wtBPyer7Ep9rgVzm');
-- NOTE: hash above corresponds to "admin123" using PHP password_hash (bcrypt)

-- ---------------------------------------
-- MOVIES
-- ---------------------------------------
CREATE TABLE movies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    genre VARCHAR(100),
    language VARCHAR(50),
    duration_minutes INT,
    rating DECIMAL(3,1) DEFAULT 0,
    description TEXT,
    poster VARCHAR(255),
    banner VARCHAR(255),
    trailer_link VARCHAR(255),
    status ENUM('now_showing','coming_soon','ended') DEFAULT 'now_showing',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---------------------------------------
-- THEATRES
-- ---------------------------------------
CREATE TABLE theatres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    address VARCHAR(255),
    city VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---------------------------------------
-- SCREENS (each theatre can have multiple screens)
-- ---------------------------------------
CREATE TABLE screens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    theatre_id INT NOT NULL,
    screen_name VARCHAR(50) NOT NULL,
    rows_count INT DEFAULT 5,
    seats_per_row INT DEFAULT 8,
    FOREIGN KEY (theatre_id) REFERENCES theatres(id) ON DELETE CASCADE
);

-- ---------------------------------------
-- SEATS (generated per screen - the physical layout)
-- ---------------------------------------
CREATE TABLE seats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    screen_id INT NOT NULL,
    seat_label VARCHAR(10) NOT NULL,   -- e.g. A1, B5
    seat_type ENUM('normal','premium') DEFAULT 'normal',
    FOREIGN KEY (screen_id) REFERENCES screens(id) ON DELETE CASCADE
);

-- ---------------------------------------
-- SHOWS
-- ---------------------------------------
CREATE TABLE shows (
    id INT AUTO_INCREMENT PRIMARY KEY,
    movie_id INT NOT NULL,
    theatre_id INT NOT NULL,
    screen_id INT NOT NULL,
    show_date DATE NOT NULL,
    show_time TIME NOT NULL,
    price DECIMAL(8,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    FOREIGN KEY (theatre_id) REFERENCES theatres(id) ON DELETE CASCADE,
    FOREIGN KEY (screen_id) REFERENCES screens(id) ON DELETE CASCADE
);

-- ---------------------------------------
-- BOOKINGS
-- ---------------------------------------
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_code VARCHAR(20) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    show_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending','confirmed','cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (show_id) REFERENCES shows(id) ON DELETE CASCADE
);

-- ---------------------------------------
-- BOOKING_SEATS (which seats belong to which booking, per show)
-- ---------------------------------------
CREATE TABLE booking_seats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    show_id INT NOT NULL,
    seat_id INT NOT NULL,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (show_id) REFERENCES shows(id) ON DELETE CASCADE,
    FOREIGN KEY (seat_id) REFERENCES seats(id) ON DELETE CASCADE,
    UNIQUE KEY unique_seat_per_show (show_id, seat_id)  -- prevents double booking
);

-- ---------------------------------------
-- PAYMENTS
-- ---------------------------------------
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    transaction_id VARCHAR(50) NOT NULL UNIQUE,
    method ENUM('UPI','Card','Net Banking') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('success','failed') DEFAULT 'success',
    paid_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
);

-- ---------------------------------------
-- REVIEWS (optional extra)
-- ---------------------------------------
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    movie_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ---------------------------------------
-- CONTACT MESSAGES (optional extra)
-- ---------------------------------------
CREATE TABLE contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(150),
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- SAMPLE DATA (so the app isn't empty when you demo it)
-- =====================================================

INSERT INTO movies (title, genre, language, duration_minutes, rating, description, poster, status) VALUES
('Pathaan', 'Action, Thriller', 'Hindi', 146, 4.2, 'A high-octane action thriller following a RAW agent on a mission to stop a rogue mercenary group.', 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?w=400', 'now_showing'),
('Interstellar', 'Sci-Fi, Drama', 'English', 169, 4.8, 'A team of explorers travel through a wormhole in space in an attempt to ensure humanity survival.', 'https://images.unsplash.com/photo-1502134249126-9f3755a50d78?w=400', 'now_showing'),
('The Dark Knight', 'Action, Crime', 'English', 152, 4.9, 'Batman faces the Joker, a criminal mastermind who plunges Gotham into anarchy.', 'https://images.unsplash.com/photo-1509347528160-9a9e33742cdb?w=400', 'now_showing'),
('Jawan', 'Action, Drama', 'Hindi', 169, 4.0, 'A man is driven by a personal vendetta to rectify the wrongs in society.', 'https://images.unsplash.com/photo-1440404653325-ab127d49abc1?w=400', 'now_showing'),
('Dune: Part Two', 'Sci-Fi, Adventure', 'English', 166, 4.7, 'Paul Atreides unites with the Fremen to take revenge against the conspirators who destroyed his family.', 'https://images.unsplash.com/photo-1440404653325-ab127d49abc1?w=400', 'coming_soon');

INSERT INTO theatres (name, address, city) VALUES
('PVR Cinemas - City Center', 'Ring Road, Near City Mall', 'Ahmedabad'),
('INOX - Alpha Mall', 'SG Highway', 'Ahmedabad'),
('Cinepolis - Elite Square', 'CG Road', 'Ahmedabad');

INSERT INTO screens (theatre_id, screen_name, rows_count, seats_per_row) VALUES
(1, 'Screen 1', 5, 8),
(1, 'Screen 2', 5, 8),
(2, 'Screen 1', 6, 10),
(3, 'Screen 1', 5, 8);

-- Generate seats for each screen (A-E rows, 8 seats each, for example)
-- This is a small procedure-free approach: insert manually for demo screens
INSERT INTO seats (screen_id, seat_label, seat_type)
SELECT s.id, CONCAT(r.row_letter, n.num), IF(r.row_letter IN ('A','B'), 'premium','normal')
FROM screens s
JOIN (SELECT 'A' AS row_letter UNION SELECT 'B' UNION SELECT 'C' UNION SELECT 'D' UNION SELECT 'E' UNION SELECT 'F') r
JOIN (SELECT 1 AS num UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10) n
WHERE n.num <= s.seats_per_row
AND (
    (r.row_letter = 'A' AND s.rows_count >= 1) OR
    (r.row_letter = 'B' AND s.rows_count >= 2) OR
    (r.row_letter = 'C' AND s.rows_count >= 3) OR
    (r.row_letter = 'D' AND s.rows_count >= 4) OR
    (r.row_letter = 'E' AND s.rows_count >= 5) OR
    (r.row_letter = 'F' AND s.rows_count >= 6)
);

-- Sample shows (today and tomorrow, adjust dates as needed after import)
INSERT INTO shows (movie_id, theatre_id, screen_id, show_date, show_time, price) VALUES
(1, 1, 1, CURDATE(), '14:00:00', 220.00),
(1, 1, 1, CURDATE(), '18:00:00', 250.00),
(2, 2, 3, CURDATE(), '15:30:00', 280.00),
(3, 3, 4, CURDATE(), '19:00:00', 260.00),
(4, 1, 2, CURDATE() + INTERVAL 1 DAY, '17:00:00', 230.00);
