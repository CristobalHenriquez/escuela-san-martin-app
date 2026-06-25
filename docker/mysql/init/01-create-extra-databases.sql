CREATE DATABASE IF NOT EXISTS `kiosco` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'kiosco'@'%' IDENTIFIED BY 'kiosco';
GRANT ALL PRIVILEGES ON `kiosco`.* TO 'kiosco'@'%';
GRANT ALL PRIVILEGES ON `escuela_san_martin`.* TO 'escuela'@'%';
FLUSH PRIVILEGES;
