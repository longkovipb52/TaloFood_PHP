-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1:3307
-- Thời gian đã tạo: Th4 13, 2025 lúc 10:03 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `talofood`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `account`
--

CREATE TABLE `account` (
  `account_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` text NOT NULL,
  `email` varchar(50) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `id_role` int(11) NOT NULL,
  `address` varchar(200) DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `login_attempts` int(11) DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `reputation_points` int(11) DEFAULT 100
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `account`
--

INSERT INTO `account` (`account_id`, `username`, `password`, `email`, `phone`, `id_role`, `address`, `status`, `login_attempts`, `locked_until`, `profile_image`, `reputation_points`) VALUES
(1, 'admin', '$2y$10$gGivE.efOVynsfpS/kcT1Of9EPBSwzUM514afhnThLLUmRbvN6Ws6', 'admin@gmail.com', '0000000000', 1, 'TaLoFood Restaurant', 1, 0, NULL, NULL, 100),
(4, 'longkovipb52', '$2y$10$SwSWmI3lkJSntFrS8bT8m.7YxWG53m.FXtTELIEhXXTAizpfYVBbm', 'longpro03@gmail.com', '0367547809', 2, 'Duc Phong, Bu Dang, Binh Phuoc', 1, 0, NULL, '67f249862d306.jpg', 20),
(8, 'longpro03', '$2y$10$H1XjbDmJm4jK356E6N/uKuO/Cj7uxbAHDcpoH9QXTKFW41o8wCkYW', 'longkovipb52@gmail.com', '0367547809', 2, 'Duc Hoa, Duc Phong, Bu Dang, Binh Phuoc', 1, 0, NULL, '67fb448aaacde.jpg', 70),
(30, 'longkovip357', '$2y$10$Bfb7q/jWUZPvq030mskvbOAEqizfl6N1EZkTJSlK2KDYSXHtNc2SO', 'longkovip357@gmail.com', '0867197692', 2, 'Bu Dang, Binh Phuoc', 1, 0, NULL, '67fa6e6c82668.png', 90);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `bill`
--

CREATE TABLE `bill` (
  `bill_id` int(11) NOT NULL,
  `ngaydat` date NOT NULL,
  `ngaygiao` date NOT NULL,
  `id_account` int(11) NOT NULL,
  `status` varchar(50) DEFAULT NULL,
  `address` varchar(250) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `payment_method` varchar(50) DEFAULT 'COD',
  `created_at` datetime DEFAULT current_timestamp(),
  `phone` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `bill`
--

INSERT INTO `bill` (`bill_id`, `ngaydat`, `ngaygiao`, `id_account`, `status`, `address`, `total_amount`, `payment_method`, `created_at`, `phone`) VALUES
(1, '2025-03-15', '2025-03-16', 4, 'Đã giao', '123 Đường ABC, TP. HCM', 250000.00, 'COD', '2025-03-21 12:44:34', ''),
(2, '2025-03-16', '2025-03-18', 8, 'Đã giao', '456 Đường XYZ, Hà Nội', 180000.00, 'Chuyển khoản', '2025-03-21 12:44:34', ''),
(3, '2025-03-17', '2025-03-19', 4, 'Đã giao', '789 Đường DEF, Đà Nẵng', 320000.00, 'COD', '2025-03-21 12:44:34', ''),
(4, '2025-02-10', '2025-02-11', 4, 'Đã giao', '12 Nguyễn Trãi, Hà Nội', 300000.00, 'Chuyển khoản', '2025-03-21 12:48:04', ''),
(5, '2025-02-12', '2025-02-13', 8, 'Đã hủy', '45 Lê Lợi, TP. HCM', 275000.00, 'COD', '2025-03-21 12:48:04', ''),
(6, '2025-02-14', '2025-02-15', 4, 'Đã giao', '78 Hoàng Văn Thụ, Đà Nẵng', 220000.00, 'Momo', '2025-03-21 12:48:04', ''),
(7, '2025-02-16', '2025-02-18', 8, 'Đã hủy', '90 Trần Hưng Đạo, Hải Phòng', 350000.00, 'Chuyển khoản', '2025-03-21 12:48:04', ''),
(8, '2025-03-19', '2025-03-20', 4, 'Đã giao', 'Bu Dang, Binh Phuoc', 120000.00, 'COD', '2025-03-24 11:20:00', ''),
(9, '2025-03-19', '2025-03-20', 8, 'Đã giao', 'Duc Hoa, Binh Phuoc', 90000.00, 'Momo', '2025-03-24 11:20:00', ''),
(10, '2025-03-20', '2025-03-21', 4, 'Đã giao', 'Bu Dang, Binh Phuoc', 75000.00, 'Momo', '2025-03-24 11:20:00', ''),
(17, '2025-04-02', '2025-04-04', 8, 'Đã hủy', 'Duc Hoa, Duc Phong, Bu Dang, Binh Phuoc', 140000.00, 'cod', '2025-04-02 13:34:46', ''),
(18, '2025-04-02', '2025-04-04', 8, 'Chờ xác nhận', 'Duc Hoa, Duc Phong, Bu Dang, Binh Phuoc', 105000.00, 'cod', '2025-04-02 13:53:04', ''),
(19, '2025-04-02', '2025-04-04', 8, 'Chờ xác nhận', 'Duc Hoa, Duc Phong, Bu Dang, Binh Phuoc', 105000.00, 'paypal', '2025-04-02 14:25:10', ''),
(20, '2025-04-02', '2025-04-04', 8, 'Chờ xác nhận', 'Duc Hoa, Duc Phong, Bu Dang, Binh Phuoc', 105000.00, 'paypal', '2025-04-02 14:34:02', ''),
(21, '2025-04-02', '2025-04-05', 8, 'Chờ xác nhận', 'Duc Hoa, Duc Phong, Bu Dang, Binh Phuoc', 138000.00, 'cod', '2025-04-02 17:29:07', '0367547809'),
(22, '2025-04-02', '2025-04-05', 8, 'Chờ xác nhận', 'Duc Hoa, Duc Phong, Bu Dang, Binh Phuoc', 90000.00, 'cod', '2025-04-02 17:31:00', '0367547809'),
(23, '2025-04-02', '2025-04-05', 8, 'Chờ xác nhận', 'Duc Hoa, Duc Phong, Bu Dang, Binh Phuoc', 90000.00, 'cod', '2025-04-02 17:35:08', '0367547809'),
(24, '2025-04-02', '2025-04-05', 8, 'Chờ xác nhận', '\r\nBình Dương', 90000.00, 'cod', '2025-04-02 17:40:38', '0367547809'),
(25, '2025-04-02', '2025-04-05', 8, 'Chờ xác nhận', '\r\nBình Dương', 90000.00, 'cod', '2025-04-02 17:44:16', '0367547809'),
(26, '2025-04-02', '2025-04-05', 8, 'Chờ xác nhận', '\r\nBình Dương', 138000.00, 'COD', '2025-04-02 17:49:33', '0367547809'),
(27, '2025-04-02', '2025-04-05', 8, 'Chờ xác nhận', 'Bu Dang, Binh Phuoc\r\nPhu Loi, Thu Dau Mot, Binh Duong', 63000.00, 'COD', '2025-04-02 17:50:36', '0367547809'),
(28, '2025-04-02', '2025-04-05', 8, 'Chờ xác nhận', 'Bu Dang, Binh Phuoc\r\nPhu Loi, Thu Dau Mot, Binh Duong', 255000.00, 'COD', '2025-04-02 17:53:57', '0367547809'),
(29, '2025-04-02', '2025-04-05', 8, 'Chờ xác nhận', 'Bu Dang, Binh Phuoc\r\nPhu Loi, Thu Dau Mot, Binh Duong', 255000.00, 'COD', '2025-04-02 17:56:35', '0367547809'),
(30, '2025-04-02', '2025-04-05', 8, 'Chờ xác nhận', 'Bu Dang, Binh Phuoc\r\nPhu Loi, Thu Dau Mot, Binh Duong', 485000.00, 'COD', '2025-04-02 18:00:23', '0367547809'),
(31, '2025-04-02', '2025-04-05', 8, 'Chờ xác nhận', 'Bu Dang, Binh Phuoc\r\nPhu Loi, Thu Dau Mot, Binh Duong', 691000.00, 'COD', '2025-04-02 18:04:48', '0367547809'),
(32, '2025-04-02', '2025-04-05', 8, 'Chờ xác nhận', 'Bu Dang, Binh Phuoc\r\nPhu Loi, Thu Dau Mot, Binh Duong', 669000.00, 'COD', '2025-04-02 18:07:42', '0367547809'),
(33, '2025-04-02', '2025-04-05', 8, 'Chờ xác nhận', 'Bu Dang, Binh Phuoc\r\nPhu Loi, Thu Dau Mot, Binh Duong', 390000.00, 'COD', '2025-04-02 18:08:45', '0367547809'),
(34, '2025-04-02', '2025-04-05', 8, 'Chờ xác nhận', 'Bu Dang, Binh Phuoc\r\nPhu Loi, Thu Dau Mot, Binh Duong', 669000.00, 'COD', '2025-04-02 18:10:34', '0367547809'),
(35, '2025-04-02', '2025-04-05', 8, 'Chờ xác nhận', 'Bu Dang, Binh Phuoc\r\nPhu Loi, Thu Dau Mot, Binh Duong', 765000.00, 'COD', '2025-04-02 18:11:52', '0367547809'),
(36, '2025-04-02', '2025-04-05', 8, 'Chờ xác nhận', 'Bu Dang, Binh Phuoc\r\nPhu Loi, Thu Dau Mot, Binh Duong', 158000.00, 'cod', '2025-04-02 18:14:37', '0367547809'),
(37, '2025-04-02', '2025-04-05', 8, 'Chờ xác nhận', 'Bu Dang, Binh Phuoc\r\nPhu Loi, Thu Dau Mot, Binh Duong', 235000.00, 'cod', '2025-04-02 18:16:01', '0367547809'),
(38, '2025-04-02', '2025-04-05', 8, 'Chờ xác nhận', 'Bu Dang, Binh Phuoc\r\nPhu Loi, Thu Dau Mot, Binh Duong', 165000.00, 'cod', '2025-04-02 18:24:09', '0367547809'),
(39, '2025-04-02', '2025-04-05', 8, 'Chờ xác nhận', 'Bu Dang, Binh Phuoc\r\nPhu Loi, Thu Dau Mot, Binh Duong', 90000.00, 'cod', '2025-04-02 18:33:04', '0367547809'),
(40, '2025-04-02', '2025-04-05', 8, 'Chờ xác nhận', 'Bu Dang, Binh Phuoc\r\nPhu Loi, Thu Dau Mot, Binh Duong', 90000.00, 'cod', '2025-04-02 18:37:04', '0367547809'),
(41, '2025-04-02', '2025-04-05', 8, 'Chờ xác nhận', 'Bu Dang, Binh Phuoc\r\nPhu Loi, Thu Dau Mot, Binh Duong', 90000.00, 'cod', '2025-04-02 18:44:38', '0367547809'),
(42, '2025-04-02', '2025-04-05', 8, 'Đã xác nhận', 'Bu Dang, Binh Phuoc\r\nPhu Loi, Thu Dau Mot, Binh Duong', 84000.00, 'cod', '2025-04-02 19:25:16', '0367547809'),
(43, '2025-04-02', '2025-04-05', 8, 'Đã xác nhận', 'Bu Dang, Binh Phuoc\r\nPhu Loi, Thu Dau Mot, Binh Duong', 230000.00, 'cod', '2025-04-02 19:36:45', '0367547809'),
(44, '2025-04-02', '2025-04-05', 4, 'Đã hủy', 'Bu Dang, Binh Phuoc\r\nPhu Loi, Thu Dau Mot, Binh Duong', 390000.00, 'cod', '2025-04-02 20:51:30', '0367547809'),
(45, '2025-04-03', '2025-04-06', 8, 'Chờ xác nhận', 'Bu Dang, Binh Phuoc\r\nPhu Loi, Thu Dau Mot, Binh Duong', 240000.00, 'cod', '2025-04-03 11:42:26', '0367547809'),
(46, '2025-04-03', '2025-04-06', 8, 'Chờ xác nhận', 'Bu Dang, Binh Phuoc\r\nPhu Loi, Thu Dau Mot, Binh Duong', 1140000.00, 'cod', '2025-04-03 11:47:47', '0367547809'),
(47, '2025-04-03', '2025-04-06', 8, 'Chờ xác nhận', 'Bu Dang, Binh Phuoc\r\nPhu Loi, Thu Dau Mot, Binh Duong', 355000.00, 'paypal', '2025-04-03 12:24:08', '0367547809'),
(48, '2025-04-03', '2025-04-06', 8, 'Chờ xác nhận', 'Duc Hoa, Duc Phong, Bu Dang, Binh Phuoc\r\nDuc Hoa, Duc Phong, Bu Dang, Binh Phuoc', 158000.00, 'paypal', '2025-04-03 12:28:56', '0367547809'),
(49, '2025-04-03', '2025-04-06', 4, 'Chờ xác nhận', 'Bu Dang, Binh Phuoc\r\nPhu Loi, Thu Dau Mot, Binh Duong', 444000.00, 'paypal', '2025-04-03 12:31:38', '0367547809'),
(50, '2025-04-03', '2025-04-06', 8, 'Chờ xác nhận', 'Bu Dang, Binh Phuoc\r\nPhu Loi, Thu Dau Mot, Binh Duong', 444000.00, 'paypal', '2025-04-03 12:35:23', '0367547809'),
(51, '2025-04-03', '2025-04-06', 8, 'Chờ xác nhận', 'Bu Dang, Binh Phuoc\r\nPhu Loi, Thu Dau Mot, Binh Duong', 444000.00, 'paypal', '2025-04-03 12:36:05', '0367547809'),
(52, '2025-04-03', '2025-04-06', 4, 'Đã hủy', 'Bu Dang, Binh Phuoc\r\nPhu Loi, Thu Dau Mot, Binh Duong', 444000.00, 'paypal', '2025-04-03 12:43:59', '0367547809'),
(53, '2025-04-03', '2025-04-06', 8, 'Chờ xác nhận', 'Bu Dang, Binh Phuoc\r\nPhu Loi, Thu Dau Mot, Binh Duong', 90000.00, 'paypal', '2025-04-03 13:00:02', '0367547809'),
(54, '2025-04-03', '2025-04-06', 8, 'Chờ xác nhận', 'Bu Dang, Binh Phuoc\r\nPhu Loi, Thu Dau Mot, Binh Duong', 90000.00, 'cod', '2025-04-03 13:01:30', '0367547809'),
(55, '2025-04-03', '2025-04-06', 8, 'Chờ xác nhận', 'Bu Dang, Binh Phuoc\r\nPhu Loi, Thu Dau Mot, Binh Duong', 83000.00, 'paypal', '2025-04-03 13:01:48', '0367547809'),
(56, '2025-04-03', '2025-04-06', 8, 'Chờ xác nhận', 'Bu Dang, Binh Phuoc\r\nPhu Loi, Thu Dau Mot, Binh Duong', 90000.00, 'paypal', '2025-04-03 13:11:30', '0367547809'),
(57, '2025-04-03', '2025-04-06', 8, 'Chờ xác nhận', 'Bu Dang, Binh Phuoc\r\nPhu Loi, Thu Dau Mot, Binh Duong', 90000.00, 'paypal', '2025-04-03 13:14:29', '0367547809'),
(58, '2025-04-03', '2025-04-06', 8, 'Chờ xác nhận', 'Bu Dang, Binh Phuoc\r\nPhu Loi, Thu Dau Mot, Binh Duong', 83000.00, 'paypal', '2025-04-03 13:15:26', '0367547809'),
(59, '2025-04-03', '2025-04-06', 8, 'Chờ xác nhận', 'Bu Dang, Binh Phuoc\r\nPhu Loi, Thu Dau Mot, Binh Duong', 83000.00, 'paypal', '2025-04-03 13:33:28', '0367547809'),
(60, '2025-04-03', '2025-04-06', 8, 'Chờ xác nhận', 'Bu Dang, Binh Phuoc\r\nPhu Loi, Thu Dau Mot, Binh Duong', 63000.00, 'paypal', '2025-04-03 13:38:20', '0367547809'),
(61, '2025-04-03', '2025-04-06', 8, 'Chờ xác nhận', 'Bu Dang, Binh Phuoc\r\nPhu Loi, Thu Dau Mot, Binh Duong', 90000.00, 'paypal', '2025-04-03 13:38:37', '0367547809'),
(62, '2025-04-03', '2025-04-06', 8, 'Chờ xác nhận', 'Bu Dang, Binh Phuoc\r\nPhu Loi, Thu Dau Mot, Binh Duong', 63000.00, 'paypal', '2025-04-03 13:40:10', '0367547809'),
(63, '2025-04-03', '2025-04-06', 8, 'Chờ xác nhận', 'Bu Dang, Binh Phuoc, Phu Loi, Thu Dau Mot, Binh Duong', 48000.00, 'paypal', '2025-04-03 13:42:10', '0367547809'),
(64, '2025-04-03', '2025-04-06', 8, 'Chờ xác nhận', 'Bu Dang, Binh Phuoc, Phu Loi, Thu Dau Mot, Binh Duong', 48000.00, 'paypal', '2025-04-03 13:42:37', '0367547809'),
(65, '2025-04-03', '2025-04-06', 8, 'Chờ xác nhận', 'Bu Dang, Binh Phuoc, Phu Loi, Thu Dau Mot, Binh Duong', 48000.00, 'paypal', '2025-04-03 13:44:23', '0367547809'),
(66, '2025-04-03', '2025-04-06', 8, 'Chờ xác nhận', 'Bu Dang, Binh Phuoc, Phu Loi, Thu Dau Mot, Binh Duong', 190000.00, 'paypal', '2025-04-03 13:48:15', '0367547809'),
(67, '2025-04-03', '2025-04-06', 8, 'Chờ xác nhận', 'Bu Dang, Binh Phuoc, Phu Loi, Thu Dau Mot, Binh Duong', 190000.00, 'paypal', '2025-04-03 13:48:52', '0367547809'),
(68, '2025-04-03', '2025-04-06', 8, 'Đã thanh toán', 'Bu Dang, Binh Phuoc, Phu Loi, Thu Dau Mot, Binh Duong', 190000.00, 'paypal', '2025-04-03 13:52:04', '0367547809'),
(69, '2025-04-03', '2025-04-06', 8, 'Đã thanh toán', 'Bu Dang, Binh Phuoc, Phu Loi, Thu Dau Mot, Binh Duong', 340000.00, 'paypal', '2025-04-03 13:55:43', '0367547809'),
(70, '2025-04-03', '2025-04-06', 8, 'Đã thanh toán', 'Bu Dang, Binh Phuoc, Phu Loi, Thu Dau Mot, Binh Duong', 680000.00, 'paypal', '2025-04-03 13:57:00', '0367547809'),
(71, '2025-04-03', '2025-04-06', 8, 'Chờ xác nhận', 'Duc Hoa, Duc Phong, Bu Dang, Binh Phuoc, Duc Hoa, Duc Phong, Bu Dang, Binh Phuoc', 200000.00, 'cod', '2025-04-03 14:02:56', '0367547809'),
(72, '2025-04-03', '2025-04-06', 8, 'Chờ xác nhận', 'Bu Dang, Binh Phuoc, Phu Loi, Thu Dau Mot, Binh Duong', 272000.00, 'paypal', '2025-04-03 14:07:46', '0367547809'),
(73, '2025-04-03', '2025-04-06', 8, 'Đã thanh toán', 'Bu Dang, Binh Phuoc, Phu Loi, Thu Dau Mot, Binh Duong', 450000.00, 'paypal', '2025-04-03 14:09:44', '0367547809'),
(74, '2025-04-03', '2025-04-06', 8, 'Chờ xác nhận', 'Bu Dang, Binh Phuoc, Phu Loi, Thu Dau Mot, Binh Duong', 225000.00, 'paypal', '2025-04-03 14:12:35', '0367547809'),
(75, '2025-04-03', '2025-04-06', 8, 'Đã thanh toán', 'Bu Dang, Binh Phuoc, Phu Loi, Thu Dau Mot, Binh Duong', 300000.00, 'paypal', '2025-04-03 14:16:31', '0367547809'),
(76, '2025-04-03', '2025-04-06', 8, 'Đã thanh toán', 'Bu Dang, Binh Phuoc, Phu Loi, Thu Dau Mot, Binh Duong', 150000.00, 'paypal', '2025-04-03 14:19:24', '0367547809'),
(77, '2025-04-03', '2025-04-06', 8, 'Chờ xác nhận', 'Bu Dang, Binh Phuoc, Phu Loi, Thu Dau Mot, Binh Duong', 75000.00, 'cod', '2025-04-03 14:42:36', '0367547809'),
(78, '2025-04-03', '2025-04-06', 8, 'Chờ xác nhận', 'Bu Dang, Binh Phuoc, Phu Loi, Thu Dau Mot, Binh Duong', 150000.00, 'cod', '2025-04-03 15:25:20', '0367547809'),
(79, '2025-04-03', '2025-04-06', 8, 'Đã thanh toán', 'Bu Dang, Binh Phuoc, Phu Loi, Thu Dau Mot, Binh Duong', 300000.00, 'paypal', '2025-04-03 15:32:13', '0367547809'),
(80, '2025-04-03', '2025-04-06', 8, 'Chờ xác nhận', 'Bu Dang, Binh Phuoc, Phu Loi, Thu Dau Mot, Binh Duong', 313000.00, 'cod', '2025-04-03 15:42:51', '0367547809'),
(81, '2025-04-05', '2025-04-08', 4, 'Đã thanh toán', 'Bu Dang, Binh Phuoc, Phu Loi, Thu Dau Mot, Binh Duong', 450000.00, 'paypal', '2025-04-05 15:26:42', '0367547809'),
(82, '2025-04-05', '2025-04-08', 8, 'Đã giao', 'Bu Dang, Binh Phuoc, Phu Loi, Thu Dau Mot, Binh Duong', 75000.00, 'cod', '2025-04-05 17:40:10', '0367547809'),
(83, '2025-04-05', '2025-04-08', 8, 'Đã thanh toán', 'Bu Dang, Binh Phuoc, Phu Loi, Thu Dau Mot, Binh Duong', 150000.00, 'paypal', '2025-04-05 17:51:34', '0367547809'),
(84, '2025-04-06', '2025-04-09', 4, 'Đã thanh toán', 'Duc Phong, Bu Dang, Binh Phuoc', 75000.00, 'paypal', '2025-04-06 16:32:11', '0367547809'),
(85, '2025-04-07', '2025-04-10', 8, 'Đã xác nhận', 'Duc Hoa, Duc Phong, Bu Dang, Binh Phuoc', 375000.00, 'cod', '2025-04-07 13:57:27', '0367547809'),
(86, '2025-04-07', '2025-04-07', 8, 'Đã thanh toán', 'Duc Hoa, Duc Phong, Bu Dang, Binh Phuoc', 750000.00, 'paypal', '2025-04-07 14:01:34', '0367547809'),
(88, '2025-04-11', '2025-04-11', 4, 'Đã hủy', 'Duc Phong, Bu Dang, Binh Phuoc', 798000.00, 'cod', '2025-04-11 13:34:51', '0367547809'),
(89, '2025-04-11', '2025-04-11', 4, 'Đã thanh toán', 'Duc Phong, Bu Dang, Binh Phuoc', 1107000.00, 'paypal', '2025-04-11 14:48:36', '0367547809'),
(90, '2025-04-11', '2025-04-11', 8, 'Đã hủy', 'Duc Hoa, Duc Phong, Bu Dang, Binh Phuoc', 300000.00, 'cod', '2025-04-11 16:05:28', '0367547809'),
(91, '2025-04-11', '2025-04-11', 4, 'Đã hủy', 'Duc Phong, Bu Dang, Binh Phuoc', 48000.00, 'cod', '2025-04-11 16:45:02', '0367547809'),
(92, '2025-04-11', '2025-04-11', 8, 'Đã thanh toán', 'Duc Hoa, Duc Phong, Bu Dang, Binh Phuoc', 48000.00, 'paypal', '2025-04-11 17:00:31', '0367547809'),
(93, '2025-04-12', '2025-04-12', 8, 'Đã giao', 'Duc Hoa, Duc Phong, Bu Dang, Binh Phuoc', 95000.00, 'cod', '2025-04-12 16:14:01', '0367547809'),
(96, '2025-04-13', '2025-04-13', 8, 'Đã hủy', 'Duc Hoa, Duc Phong, Bu Dang, Binh Phuoc', 56000.00, 'cod', '2025-04-13 11:57:44', '0367547809');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `bill_info`
--

CREATE TABLE `bill_info` (
  `billinfo_id` int(11) NOT NULL,
  `id_bill` int(11) NOT NULL,
  `id_food` int(11) NOT NULL,
  `id_account` int(11) NOT NULL,
  `count` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `bill_info`
--

INSERT INTO `bill_info` (`billinfo_id`, `id_bill`, `id_food`, `id_account`, `count`, `price`) VALUES
(45, 25, 3, 8, 0, 75000.00),
(46, 26, 3, 8, 1, 75000.00),
(47, 26, 2, 8, 1, 48000.00),
(48, 27, 2, 8, 1, 48000.00),
(49, 28, 2, 8, 1, 48000.00),
(50, 29, 2, 8, 1, 48000.00),
(51, 30, 2, 8, 3, 48000.00),
(52, 30, 1, 8, 2, 95000.00),
(53, 30, 4, 8, 2, 68000.00),
(54, 31, 1, 8, 3, 95000.00),
(55, 31, 2, 8, 3, 48000.00),
(56, 31, 5, 8, 4, 43000.00),
(57, 31, 3, 8, 1, 75000.00),
(58, 32, 1, 8, 3, 95000.00),
(59, 32, 2, 8, 3, 48000.00),
(60, 32, 3, 8, 3, 75000.00),
(61, 33, 3, 8, 5, 75000.00),
(62, 34, 3, 8, 3, 75000.00),
(63, 34, 2, 8, 3, 48000.00),
(64, 34, 1, 8, 3, 95000.00),
(65, 35, 3, 8, 10, 75000.00),
(66, 36, 1, 8, 1, 95000.00),
(67, 36, 2, 8, 1, 48000.00),
(68, 37, 7, 8, 2, 110000.00),
(69, 38, 3, 8, 2, 75000.00),
(70, 39, 3, 8, 1, 75000.00),
(71, 40, 3, 8, 1, 75000.00),
(72, 41, 3, 8, 1, 75000.00),
(73, 42, 27, 8, 1, 69000.00),
(74, 43, 26, 8, 1, 215000.00),
(75, 44, 3, 4, 5, 75000.00),
(76, 45, 3, 8, 3, 75000.00),
(77, 46, 3, 8, 15, 75000.00),
(78, 47, 4, 8, 5, 68000.00),
(79, 48, 4, 8, 1, 68000.00),
(80, 48, 3, 8, 1, 75000.00),
(81, 49, 3, 4, 3, 75000.00),
(82, 49, 4, 4, 3, 68000.00),
(83, 50, 3, 8, 3, 75000.00),
(84, 50, 4, 8, 3, 68000.00),
(85, 51, 3, 8, 3, 75000.00),
(86, 51, 4, 8, 3, 68000.00),
(87, 52, 3, 4, 3, 75000.00),
(88, 52, 4, 4, 3, 68000.00),
(89, 53, 3, 8, 1, 75000.00),
(90, 54, 3, 8, 1, 75000.00),
(91, 55, 4, 8, 1, 68000.00),
(92, 56, 3, 8, 1, 75000.00),
(93, 57, 3, 8, 1, 75000.00),
(94, 58, 4, 8, 1, 68000.00),
(95, 59, 4, 8, 1, 68000.00),
(96, 60, 2, 8, 1, 48000.00),
(97, 61, 3, 8, 1, 75000.00),
(98, 62, 2, 8, 1, 48000.00),
(99, 63, 2, 8, 1, 48000.00),
(100, 64, 2, 8, 1, 48000.00),
(101, 65, 2, 8, 1, 48000.00),
(102, 66, 1, 8, 2, 95000.00),
(103, 67, 1, 8, 2, 95000.00),
(104, 68, 1, 8, 2, 95000.00),
(105, 69, 4, 8, 5, 68000.00),
(106, 70, 4, 8, 10, 68000.00),
(107, 71, 8, 8, 5, 40000.00),
(108, 72, 4, 8, 4, 68000.00),
(109, 73, 3, 8, 6, 75000.00),
(110, 74, 3, 8, 3, 75000.00),
(111, 75, 3, 8, 4, 75000.00),
(112, 76, 3, 8, 2, 75000.00),
(113, 77, 3, 8, 1, 75000.00),
(114, 78, 3, 8, 2, 75000.00),
(115, 79, 3, 8, 4, 75000.00),
(116, 80, 1, 8, 2, 95000.00),
(117, 80, 2, 8, 1, 48000.00),
(118, 80, 3, 8, 1, 75000.00),
(119, 81, 3, 4, 6, 75000.00),
(120, 82, 3, 8, 1, 75000.00),
(121, 83, 3, 8, 2, 75000.00),
(122, 84, 3, 4, 1, 75000.00),
(123, 85, 3, 8, 5, 75000.00),
(124, 86, 3, 8, 10, 75000.00),
(126, 88, 28, 4, 2, 399000.00),
(127, 89, 2, 4, 9, 48000.00),
(128, 89, 3, 4, 9, 75000.00),
(129, 90, 3, 8, 4, 75000.00),
(130, 91, 2, 4, 1, 48000.00),
(131, 92, 2, 8, 1, 48000.00),
(132, 93, 1, 8, 1, 95000.00),
(135, 96, 19, 8, 2, 28000.00);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `blog`
--

CREATE TABLE `blog` (
  `blog_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `author_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('draft','published') DEFAULT 'draft'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `blog`
--

INSERT INTO `blog` (`blog_id`, `title`, `content`, `image`, `author_id`, `created_at`, `updated_at`, `status`) VALUES
(11, 'Công thức làm bánh ngon tại nhà', 'Học cách nấu ăn ngon với những mẹo đơn giản nhưng hiệu quảssssssssssssssssssssssssssssss', '67e4fab35e2f2.jpg', 1, '2025-03-27 12:50:12', '2025-03-27 14:13:55', 'published'),
(12, '10 công thức nấu ăn ngon, đơn giản tại nhà', 'Bạn không cần phải là một đầu bếp chuyên nghiệp để có thể nấu những món ăn ngon. Dưới đây là 10 công thức nấu ăn đơn giản, dễ thực hiện:  \r\n1. Gà chiên nước mắm – giòn rụm, đậm đà hương vị.  \r\n2. Canh chua cá lóc – món ăn truyền thống đậm đà.  \r\n3. Bò kho – thơm ngon, thích hợp cho bữa sáng.  \r\n4. Mì xào hải sản – nhanh gọn nhưng vẫn đủ chất dinh dưỡng.  \r\n5. Cháo gà – giúp bồi bổ sức khỏe sau những ngày mệt mỏi.  \r\n6. Cơm chiên dương châu – hấp dẫn và dễ làm.  \r\n7. Bún bò Huế – tinh túy của ẩm thực miền Trung.  \r\n8. Lẩu Thái chua cay – thích hợp cho những buổi sum họp.  \r\n9. Nem rán – món ăn truyền thống không thể thiếu trong các dịp lễ.  \r\n10. Chè trôi nước – ngọt ngào, dễ làm cho những ngày se lạnh.', '67e4fa20b58f8.jpg', 1, '2025-03-27 14:11:28', '2025-03-27 14:11:28', 'published');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `contact`
--

CREATE TABLE `contact` (
  `contact_id` int(11) NOT NULL,
  `Message` text NOT NULL,
  `id_account` int(11) NOT NULL,
  `status` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `contact`
--

INSERT INTO `contact` (`contact_id`, `Message`, `id_account`, `status`) VALUES
(1, 'Món Burger Bò rất ngon, thịt mềm và nước sốt đậm đà. Tôi sẽ giới thiệu cho bạn bè!', 4, 'Đã xử lý'),
(2, 'Pizza Hải Sản rất ngon, nhưng giá hơi cao. Có thể giảm giá một chút không?', 8, 'Chưa xử lý'),
(3, 'Món Gà Rán giòn ngon, đúng vị. Nhưng phần ăn hơi ít, có thể tăng khẩu phần không?', 4, 'Đã xử lý'),
(4, 'Website dễ sử dụng, nhưng có thể cải thiện phần tìm kiếm không?', 8, 'Chưa xử lý'),
(5, 'Nên thêm tính năng đánh giá sao cho món ăn. Điều này sẽ giúp người dùng dễ chọn hơn.', 4, 'Đã xử lý'),
(6, 'Có thể thêm tính năng lưu địa chỉ giao hàng không?', 8, 'Chưa xử lý'),
(7, 'Đơn hàng của tôi bị thiếu một món Pepsi, mong được hỗ trợ.', 4, 'Đã xử lý'),
(8, 'Thời gian giao hàng hơi lâu, mong được cải thiện.', 8, 'Chưa xử lý'),
(9, 'Đơn hàng #123 bị sai địa chỉ giao hàng.', 4, 'Đã xử lý'),
(10, 'Tôi muốn đề xuất thêm các món ăn chay vào menu.', 8, 'Chưa xử lý'),
(11, 'Có thể thêm món tráng miệng không?', 4, 'Đã xử lý'),
(12, 'Nên thêm các món ăn vặt cho trẻ em.', 8, 'Chưa xử lý'),
(13, 'Nhân viên giao hàng rất thân thiện và chuyên nghiệp.', 4, 'Đã xử lý'),
(14, 'Dịch vụ giao hàng nhanh, đúng giờ.', 8, 'Đã xử lý'),
(15, 'Nhân viên tư vấn nhiệt tình, giúp tôi chọn được món ngon.', 4, 'Đã xử lý'),
(16, 'Có thể thêm nhiều chương trình khuyến mãi hơn không?', 8, 'Chưa xử lý'),
(17, 'Nên có chương trình tích điểm đổi quà.', 4, 'Chưa xử lý'),
(18, 'Có thể giảm giá cho đơn hàng lớn không?', 8, 'Đã xử lý'),
(19, 'Tôi gặp vấn đề khi thanh toán online.', 8, 'Đã xử lý'),
(20, 'Có thể thêm phương thức thanh toán Momo không?', 4, 'Đã xử lý'),
(21, 'Gặp lỗi khi thanh toán qua thẻ tín dụng.', 8, 'Đã xử lý'),
(22, 'Dịch vụ rất tốt, món ăn ngon, giá cả hợp lý.', 4, 'Đã xử lý'),
(23, 'Website dễ sử dụng, giao hàng nhanh.', 8, 'Đã xử lý'),
(24, 'Tôi rất hài lòng với dịch vụ của nhà hàng.', 4, 'Đã xử lý'),
(25, 'Món ăn rất ngon, nhưng giao hàng hơi lâu.', 4, 'Chưa xử lý'),
(26, 'Tôi muốn thêm vào menu món Salad Trộn.', 8, 'Chưa xử lý'),
(27, 'Thức ăn nguội khi giao tới, mong được cải thiện.', 4, 'Đã xử lý'),
(28, 'gà ngon vãi nồi', 8, 'Đã xử lý'),
(31, 'Alo kip', 8, 'Chưa xử lý'),
(32, 'Alokio', 4, 'Chưa xử lý'),
(33, 'Alibaba', 4, 'Chưa xử lý'),
(34, 'sss', 4, 'Chưa xử lý'),
(35, 'sss', 4, 'Chưa xử lý'),
(36, 'sss', 4, 'Chưa xử lý'),
(37, 'Daley Blind\r\n', 4, 'Chưa xử lý'),
(38, 'Daley Blind\r\n', 4, 'Chưa xử lý'),
(39, 'Daley Blind\r\n', 4, 'Chưa xử lý'),
(40, 'Dal', 4, 'Chưa xử lý');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `food`
--

CREATE TABLE `food` (
  `food_id` int(11) NOT NULL,
  `food_name` varchar(50) NOT NULL,
  `id_category` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` tinyint(1) DEFAULT NULL,
  `new_price` decimal(10,2) NOT NULL,
  `total_sold` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `food`
--

INSERT INTO `food` (`food_id`, `food_name`, `id_category`, `price`, `image`, `description`, `status`, `new_price`, `total_sold`) VALUES
(1, 'Pizza Margherita', 1, 100000.00, 'pizza.jpg', 'Pizza truyền thống Ý với phô mai mozzarella và sốt cà chua.', 1, 95000.00, 0),
(2, 'Bún Bò Huế', 1, 50000.00, 'bunbohue.jpg', 'Món bún nổi tiếng của miền Trung với nước dùng đậm đà.', 1, 48000.00, 11),
(3, 'Hamburger Bò', 1, 80000.00, '67dcf9938d24c.png', 'Bánh hamburger kẹp bò với rau và phô mai.', 1, 75000.00, 51),
(4, 'Gà Rán KFC', 3, 70000.00, 'garan.jpg', 'Gà rán giòn tan với công thức đặc biệt.', 1, 68000.00, 0),
(5, 'Mì Quảng', 1, 45000.00, 'miquang.jpg', 'Đặc sản Quảng Nam với sợi mì vàng và nước lèo thơm ngon.', 1, 43000.00, 0),
(6, 'Burger Bò Phô Mai', 1, 75000.00, 'burger.jpg', 'Burger bò với phô mai cheddar, rau tươi và sốt đặc biệt.', 1, 70000.00, 1),
(7, 'Pizza Hải Sản', 1, 120000.00, 'pizza.jpg', 'Pizza với tôm, mực, phô mai và sốt cà chua.', 1, 110000.00, 0),
(8, 'Gà Rán Giòn', 1, 45000.00, 'fried_chicken.jpg', 'Gà rán giòn với lớp vỏ cay nồng.', 1, 40000.00, 0),
(9, 'Coca-Cola 500ml', 2, 15000.00, 'coca.jpg', 'Nước ngọt có ga Coca-Cola chai 500ml.', 1, 14000.00, 0),
(10, 'Combo Burger + Pepsi', 3, 90000.00, '67e4d3a2854b0.png', 'Combo gồm 1 Burger Bò Phô Mai và 1 Pepsi 500ml.', 1, 85000.00, 0),
(17, 'Trà sữa trân châu', 2, 35000.00, 'bubble_tea.jpg', 'Trà sữa thơm béo kết hợp với trân châu dẻo dai, thơm ngon.', 1, 32000.00, 15),
(18, 'Cà phê sữa đá', 2, 25000.00, 'ice_coffee.jpg', 'Cà phê đậm đà kết hợp với sữa đặc, phục vụ với đá lạnh.', 1, 25000.00, 25),
(19, 'Nước cam ép', 2, 30000.00, 'orange_juice.jpg', 'Nước cam ép tươi 100%, giàu vitamin C, không thêm đường.', 1, 28000.00, 10),
(20, 'Sinh tố xoài', 2, 38000.00, 'mango_smoothie.jpg', 'Sinh tố xoài nguyên chất, thơm ngọt tự nhiên kết hợp với sữa tươi.', 1, 36000.00, 12),
(21, 'Trà đào', 2, 29000.00, 'peach_tea.jpg', 'Trà đào thơm mát với lát đào tươi và đường phèn.', 1, 28000.00, 18),
(22, 'Nước ép dưa hấu', 2, 28000.00, 'watermelon_juice.jpg', 'Nước ép dưa hấu tươi mát, giải khát tức thì.', 1, 26000.00, 8),
(23, 'Matcha đá xay', 2, 42000.00, 'matcha.jpg', 'Matcha Nhật Bản xay với đá, sữa tươi và kem tươi béo ngậy bên trên.', 1, 39000.00, 14),
(24, 'Combo gà rán + khoai tây', 3, 95000.00, 'combo_chicken.jpg', 'Combo gồm 2 miếng gà rán, 1 khoai tây chiên vừa và 1 nước ngọt.', 1, 89000.00, 30),
(25, 'Combo burger + gà rán', 3, 110000.00, 'combo_burger_chicken.jpg', '1 Burger bò phô mai, 1 miếng gà rán, khoai tây chiên vừa và nước ngọt tùy chọn.', 1, 99000.00, 25),
(26, 'Combo pizza gia đình', 3, 230000.00, 'combo_pizza.jpg', '1 Pizza cỡ lớn (tùy chọn), 1 phần salad, 2 ly nước ngọt và 2 món tráng miệng.', 1, 215000.00, 19),
(27, 'Combo ăn sáng', 3, 75000.00, 'combo_breakfast.jpg', '1 Sandwich trứng thịt, 1 hash brown, 1 cà phê sữa đá hoặc trà.', 1, 69000.00, 23),
(28, 'Combo sinh nhật', 3, 450000.00, '67eb3b191eb2e.png', '2 Pizza cỡ vừa, 6 miếng gà rán, 3 phần khoai tây lớn, 6 nước ngọt và 1 bánh cupcake.', 1, 399000.00, 8);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `food_category`
--

CREATE TABLE `food_category` (
  `foodcategory_id` int(11) NOT NULL,
  `foodcategory_name` varchar(50) NOT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `food_category`
--

INSERT INTO `food_category` (`foodcategory_id`, `foodcategory_name`, `image`) VALUES
(1, 'Đồ ăn', '67d640439d419.png'),
(2, 'Đồ uống', NULL),
(3, 'Combo', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `id_account` int(11) NOT NULL,
  `id_food` int(11) NOT NULL,
  `star_rating` int(11) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `reviews`
--

INSERT INTO `reviews` (`review_id`, `id_account`, `id_food`, `star_rating`, `comment`, `created_at`) VALUES
(1, 4, 1, 5, 'Burger rất ngon, phô mai béo ngậy và bánh mềm.', '2025-03-18 17:00:00'),
(3, 4, 3, 3, 'Gà rán giòn nhưng hơi khô.', '2025-03-19 17:00:00'),
(5, 4, 5, 4, 'Combo rất đáng tiền, phần ăn nhiều.', '2025-03-20 17:00:00'),
(6, 4, 17, 5, 'Trà sữa trân châu cực kỳ ngon, trân châu dẻo vừa phải.', '2025-03-25 07:30:00'),
(8, 4, 19, 4, 'Nước cam ép tươi mát, cam ngọt vừa phải, không quá chua.', '2025-03-26 02:20:00'),
(10, 4, 21, 4, 'Trà đào thơm mát, nhiều lát đào tươi. Sẽ order lại!', '2025-03-27 03:10:00'),
(12, 4, 23, 5, 'Matcha đá xay thơm ngon, vị đắng nhẹ hòa quyện với vị ngọt của sữa.', '2025-03-28 09:40:00'),
(13, 8, 24, 5, 'Combo gà rán và khoai tây rất ngon, phần ăn đủ lớn, sẽ quay lại.', '2025-04-12 09:21:26'),
(14, 4, 25, 5, 'Combo burger và gà rán tuyệt vời, đáng đồng tiền bát gạo!', '2025-03-29 05:45:00'),
(15, 8, 26, 4, 'Combo pizza gia đình rất hợp lý cho buổi họp mặt tối qua. Pizza ngon, salad tươi.', '2025-03-29 13:30:00'),
(16, 4, 27, 3, 'Combo ăn sáng khá ổn, sandwich ngon nhưng hash brown hơi ít.', '2025-03-30 01:20:00'),
(18, 4, 6, 5, 'Burger bò phô mai thịt mềm, phô mai béo ngậy, rất ngon!', '2025-03-30 14:30:00'),
(19, 8, 7, 4, 'Pizza hải sản nhiều nhân, phô mai kéo sợi, tuy nhiên giá hơi cao.', '2025-03-30 15:15:00'),
(20, 4, 8, 5, 'Gà rán giòn rụm, gia vị thấm đều, rất đáng thử!', '2025-03-31 04:10:00'),
(21, 8, 9, 5, 'Coca lạnh ngon, giải khát tức thì.', '2025-04-12 09:34:12'),
(22, 4, 1, 1, 'Combo burger và pepsi tiện lợi và tiết kiệm, rất hài lòng.', '2025-03-31 06:20:00'),
(23, 8, 3, 5, 'Ăn ngon vãi cả chưởng luôn', '2025-04-12 09:35:40'),
(25, 8, 1, 5, 'Pizza ăn rất là ngon', '2025-04-12 09:43:00');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `role`
--

CREATE TABLE `role` (
  `role_id` int(11) NOT NULL,
  `rolename` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `role`
--

INSERT INTO `role` (`role_id`, `rolename`) VALUES
(1, 'Admin'),
(2, 'User');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `account`
--
ALTER TABLE `account`
  ADD PRIMARY KEY (`account_id`),
  ADD KEY `id_role` (`id_role`);

--
-- Chỉ mục cho bảng `bill`
--
ALTER TABLE `bill`
  ADD PRIMARY KEY (`bill_id`),
  ADD KEY `id_account` (`id_account`);

--
-- Chỉ mục cho bảng `bill_info`
--
ALTER TABLE `bill_info`
  ADD PRIMARY KEY (`billinfo_id`),
  ADD KEY `id_bill` (`id_bill`),
  ADD KEY `id_food` (`id_food`),
  ADD KEY `id_account` (`id_account`);

--
-- Chỉ mục cho bảng `blog`
--
ALTER TABLE `blog`
  ADD PRIMARY KEY (`blog_id`);

--
-- Chỉ mục cho bảng `contact`
--
ALTER TABLE `contact`
  ADD PRIMARY KEY (`contact_id`),
  ADD KEY `id_account` (`id_account`);

--
-- Chỉ mục cho bảng `food`
--
ALTER TABLE `food`
  ADD PRIMARY KEY (`food_id`),
  ADD KEY `id_category` (`id_category`);

--
-- Chỉ mục cho bảng `food_category`
--
ALTER TABLE `food_category`
  ADD PRIMARY KEY (`foodcategory_id`);

--
-- Chỉ mục cho bảng `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `id_account` (`id_account`),
  ADD KEY `id_food` (`id_food`);

--
-- Chỉ mục cho bảng `role`
--
ALTER TABLE `role`
  ADD PRIMARY KEY (`role_id`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `account`
--
ALTER TABLE `account`
  MODIFY `account_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT cho bảng `bill`
--
ALTER TABLE `bill`
  MODIFY `bill_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=97;

--
-- AUTO_INCREMENT cho bảng `bill_info`
--
ALTER TABLE `bill_info`
  MODIFY `billinfo_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=136;

--
-- AUTO_INCREMENT cho bảng `blog`
--
ALTER TABLE `blog`
  MODIFY `blog_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT cho bảng `contact`
--
ALTER TABLE `contact`
  MODIFY `contact_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT cho bảng `food`
--
ALTER TABLE `food`
  MODIFY `food_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT cho bảng `food_category`
--
ALTER TABLE `food_category`
  MODIFY `foodcategory_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT cho bảng `role`
--
ALTER TABLE `role`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `account`
--
ALTER TABLE `account`
  ADD CONSTRAINT `account_ibfk_1` FOREIGN KEY (`id_role`) REFERENCES `role` (`role_id`);

--
-- Các ràng buộc cho bảng `bill`
--
ALTER TABLE `bill`
  ADD CONSTRAINT `bill_ibfk_1` FOREIGN KEY (`id_account`) REFERENCES `account` (`account_id`);

--
-- Các ràng buộc cho bảng `bill_info`
--
ALTER TABLE `bill_info`
  ADD CONSTRAINT `bill_info_ibfk_1` FOREIGN KEY (`id_bill`) REFERENCES `bill` (`bill_id`),
  ADD CONSTRAINT `bill_info_ibfk_2` FOREIGN KEY (`id_food`) REFERENCES `food` (`food_id`),
  ADD CONSTRAINT `bill_info_ibfk_3` FOREIGN KEY (`id_account`) REFERENCES `account` (`account_id`);

--
-- Các ràng buộc cho bảng `contact`
--
ALTER TABLE `contact`
  ADD CONSTRAINT `contact_ibfk_1` FOREIGN KEY (`id_account`) REFERENCES `account` (`account_id`);

--
-- Các ràng buộc cho bảng `food`
--
ALTER TABLE `food`
  ADD CONSTRAINT `food_ibfk_1` FOREIGN KEY (`id_category`) REFERENCES `food_category` (`foodcategory_id`);

--
-- Các ràng buộc cho bảng `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`id_account`) REFERENCES `account` (`account_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`id_food`) REFERENCES `food` (`food_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
