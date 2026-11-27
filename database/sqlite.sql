PRAGMA foreign_keys=ON;
CREATE TABLE IF NOT EXISTS users(id INTEGER PRIMARY KEY, login TEXT UNIQUE NOT NULL, password_hash TEXT NOT NULL, role TEXT NOT NULL CHECK(role IN ('admin','viewer')));
CREATE TABLE IF NOT EXISTS rooms(id INTEGER PRIMARY KEY, number TEXT UNIQUE NOT NULL, category TEXT NOT NULL, capacity INTEGER NOT NULL CHECK(capacity>0), price INTEGER NOT NULL CHECK(price>0));
CREATE TABLE IF NOT EXISTS bookings(id INTEGER PRIMARY KEY, room_id INTEGER NOT NULL REFERENCES rooms(id), guest TEXT NOT NULL, phone TEXT NOT NULL, guests INTEGER NOT NULL CHECK(guests>0), arrival TEXT NOT NULL, departure TEXT NOT NULL, status TEXT NOT NULL DEFAULT 'confirmed' CHECK(status IN ('confirmed','cancelled')), price INTEGER NOT NULL, CHECK(departure>arrival));
CREATE INDEX IF NOT EXISTS idx_period ON bookings(room_id,status,arrival,departure);
INSERT OR IGNORE INTO rooms VALUES (1,'101','Стандарт',2,2500),(2,'102','Стандарт',2,2500),(3,'201','Комфорт',3,3500),(4,'202','Люкс',4,5000);
