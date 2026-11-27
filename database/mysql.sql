CREATE DATABASE IF NOT EXISTS hotel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hotel;
CREATE TABLE IF NOT EXISTS users (id INT AUTO_INCREMENT PRIMARY KEY, login VARCHAR(60) UNIQUE NOT NULL, password_hash VARCHAR(255) NOT NULL, role ENUM('admin','viewer') NOT NULL) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS rooms (id INT AUTO_INCREMENT PRIMARY KEY, number VARCHAR(10) UNIQUE NOT NULL, category VARCHAR(60) NOT NULL, capacity INT NOT NULL CHECK(capacity>0), price INT NOT NULL CHECK(price>0)) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS bookings (id INT AUTO_INCREMENT PRIMARY KEY, room_id INT NOT NULL, guest VARCHAR(120) NOT NULL, phone VARCHAR(30) NOT NULL, guests INT NOT NULL CHECK(guests>0), arrival DATE NOT NULL, departure DATE NOT NULL, status VARCHAR(20) NOT NULL DEFAULT 'confirmed', price INT NOT NULL, CONSTRAINT fk_room FOREIGN KEY(room_id) REFERENCES rooms(id), CHECK(departure>arrival), CHECK(status IN ('confirmed','cancelled')), INDEX idx_period(room_id,status,arrival,departure)) ENGINE=InnoDB;
INSERT IGNORE INTO rooms(id,number,category,capacity,price) VALUES (1,'101','Стандарт',2,2500),(2,'102','Стандарт',2,2500),(3,'201','Комфорт',3,3500),(4,'202','Люкс',4,5000);
