-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jun 03, 2025 at 03:03 PM
-- Server version: 10.11.6-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `price_calculator`
--

-- --------------------------------------------------------

--
-- Table structure for table `materials`
--

CREATE TABLE `materials` (
  `material_id` int(11) NOT NULL,
  `product_type` varchar(100) NOT NULL,
  `material_name` varchar(255) NOT NULL,
  `price_per_unit` decimal(10,2) NOT NULL,
  `unit` varchar(50) NOT NULL,
  `display_in_calculator` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `materials`
--

INSERT INTO `materials` (`material_id`, `product_type`, `material_name`, `price_per_unit`, `unit`, `display_in_calculator`) VALUES
(1, 'ตัวอักษรโลหะ', 'สังกะสี', 50.00, 'บาท/นิ้ว', 0),
(2, 'ตัวอักษรโลหะ', 'สแตนเลส', 80.00, 'บาท/นิ้ว', 0),
(3, 'ตัวอักษรโลหะ', 'อะคริลิค', 40.00, 'บาท/นิ้ว', 0),
(4, 'กล่องไฟ', 'สี่เหลี่ยม', 7500.00, 'บาท/ตรม.', 0),
(5, 'กล่องไฟ', 'ตู้วงกลม', 14000.00, 'บาท/ตรม.', 0),
(6, 'วัสดุแผ่น', 'พลาสวูด 10 มม.', 1200.00, 'บาท/ตรม.', 1),
(7, 'วัสดุแผ่น', 'อะคริลิค 3 มม.', 2000.00, 'บาท/ตรม.', 1),
(9, 'ตัวอักษรโลหะ', 'อะคริลิค 3mm', 1200.00, 'ตร.ม.', 0),
(10, 'ตัวอักษรโลหะ', 'สแตนเลส 304', 2500.00, 'ตร.ม.', 0),
(11, 'ตัวอักษรโลหะ', 'อลูมิเนียม', 1800.00, 'ตร.ม.', 0),
(12, 'กล่องไฟ', 'กล่องไฟ LED 3D', 3000.00, 'ชุด', 0),
(13, 'กล่องไฟ', 'กล่องไฟ LED แบน', 2800.00, 'ชุด', 0),
(14, 'กล่องไฟ', 'กล่องไฟนีออน', 4200.00, 'ชุด', 0),
(15, 'วัสดุแผ่น', 'PVC 3mm', 450.00, 'แผ่น', 1),
(16, 'วัสดุแผ่น', 'อะคริลิค 5mm', 850.00, 'แผ่น', 0),
(17, 'วัสดุแผ่น', 'โฟมบอร์ด', 180.00, 'แผ่น', 0),
(18, 'ผ้าไวนิล', 'ผ้าไวนิล 340g', 120.00, 'ตร.ม.', 0),
(19, 'ผ้าไวนิล', 'ผ้าไวนิล 440g', 150.00, 'ตร.ม.', 0),
(20, 'ผ้าไวนิล', 'ผ้าไวนิล 510g', 180.00, 'ตร.ม.', 0),
(21, 'สติ๊กเกอร์', 'สติ๊กเกอร์ PVC ทั่วไป', 450.00, 'ตร.ม.', 1),
(22, 'สติ๊กเกอร์', 'สติ๊กเกอร์ 3M (เกรดใช้งานภายนอก)', 650.00, 'ตร.ม.', 1),
(23, 'สติ๊กเกอร์', 'สติ๊กเกอร์ใส (Clear Sticker)', 550.00, 'ตร.ม.', 1),
(24, 'สติ๊กเกอร์', 'สติ๊กเกอร์ซีทรู (See-through)', 700.00, 'ตร.ม.', 1),
(25, 'สติ๊กเกอร์', 'โปสเตอร์', 700.00, 'บาท/ตรม.', 1);

-- --------------------------------------------------------

--
-- Table structure for table `options`
--

CREATE TABLE `options` (
  `option_id` int(11) NOT NULL,
  `option_name` varchar(255) NOT NULL,
  `option_price` decimal(10,2) NOT NULL,
  `category` varchar(100) DEFAULT 'ทั่วไป',
  `display_in_calculator` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `options`
--

INSERT INTO `options` (`option_id`, `option_name`, `option_price`, `category`, `display_in_calculator`) VALUES
(1, 'ค่าแรงติดตั้ง', 1900.00, 'ทั่วไป', 1),
(6, 'ติดตั้ง', 500.00, 'ทั่วไป', 0),
(7, 'ขนส่ง', 300.00, 'ทั่วไป', 0),
(8, 'ออกแบบ', 1000.00, 'ทั่วไป', 0),
(9, 'สติ๊กเกอร์ทนน้ำ', 250.00, 'สติ๊กเกอร์', 1),
(10, 'สติ๊กเกอร์ทนแดด', 300.00, 'สติ๊กเกอร์', 0),
(11, 'สติ๊กเกอร์โปร่งแสง', 350.00, 'สติ๊กเกอร์', 0),
(12, 'เย็บขอบ', 150.00, 'ผ้าไวนิล', 0),
(13, 'เจาะห่วง', 100.00, 'ผ้าไวนิล', 0),
(14, 'ติดตั้งบนโครง', 800.00, 'ผ้าไวนิล', 0),
(15, 'เจาะรูติดตั้ง', 200.00, 'ตัวอักษรโลหะ', 0),
(16, 'ขัดขอบ', 150.00, 'ตัวอักษรโลหะ', 0),
(17, 'พ่นสี', 300.00, 'ตัวอักษรโลหะ', 0),
(18, 'ติดตั้งไฟฟ้า', 800.00, 'กล่องไฟ', 0),
(19, 'รีโมทคอนโทรล', 1200.00, 'กล่องไฟ', 0),
(20, 'เซ็นเซอร์อัตโนมัติ', 1500.00, 'กล่องไฟ', 1);

-- --------------------------------------------------------

--
-- Table structure for table `price_rules`
--

CREATE TABLE `price_rules` (
  `rule_id` int(11) NOT NULL,
  `rule_name` varchar(255) NOT NULL,
  `rule_value` decimal(10,2) NOT NULL,
  `rule_unit` varchar(50) NOT NULL,
  `display_in_calculator` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `price_rules`
--

INSERT INTO `price_rules` (`rule_id`, `rule_name`, `rule_value`, `rule_unit`, `display_in_calculator`) VALUES
(1, 'ติดตั้งนอกเมือง', 12.00, 'บาท/กม.', 1),
(2, 'ติดตั้งในเมือง', 500.00, 'บาท', 1),
(3, 'ราคาสติ๊กเกอร์ต่อตรม.', 700.00, 'บาท/ตร.ม.', 0),
(4, 'สติ๊กเกอร์เคลือบใส', 650.00, 'บาท/ตรม.', 0),
(5, 'สติ๊กเกอร์ถูก', 350.00, 'บาท/ตรม.', 0),
(13, 'ราคาขั้นต่ำ', 1000.00, 'บาท', 0),
(14, 'ค่าออกแบบขั้นต่ำ', 500.00, 'บาท', 0),
(15, 'ค่าติดตั้งขั้นต่ำ', 300.00, 'บาท', 0),
(16, 'ส่วนลดปริมาณ 10%', 10.00, 'เปอร์เซ็นต์', 0),
(17, 'ส่วนลดปริมาณ 20%', 20.00, 'เปอร์เซ็นต์', 0);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `price_per_sqm` decimal(10,2) NOT NULL,
  `unit` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `product_name`, `price_per_sqm`, `unit`) VALUES
(1, 'Sticker', 450.00, 'ตารางเมตร');

-- --------------------------------------------------------

--
-- Table structure for table `stock`
--

CREATE TABLE `stock` (
  `stock_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `product_type` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `unit` varchar(50) NOT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock`
--

INSERT INTO `stock` (`stock_id`, `product_name`, `product_type`, `quantity`, `unit`, `last_updated`) VALUES
(1, 'สติ๊กเกอร์ PVC ขาวเงา', 'สติ๊กเกอร์', 100, 'ตร.ม.', '2025-05-31 08:55:46'),
(2, 'ผ้าไวนิลทึบแสง', 'ผ้าไวนิล', 50, 'ตร.ม.', '2025-05-31 08:55:46'),
(3, 'แผ่นพลาสวูด 3mm', 'วัสดุแผ่น', 25, 'แผ่น', '2025-05-31 08:55:46'),
(4, 'ซิงค์', 'ตัวอักษรโลหะ', 35, 'แผ่น', '2025-06-03 06:59:43'),
(5, 'สติ๊กเกอร์ PVC', 'สติ๊กเกอร์', 100, 'ตร.ม.', '2025-05-31 08:55:46'),
(6, 'ผ้าไวนิลทึบแสง 380', 'ผ้าไวนิล', 50, 'ตร.ม.', '2025-05-31 08:55:46'),
(7, 'แผ่นพลาสวูด 5mm', 'วัสดุแผ่น', 25, 'แผ่น', '2025-05-31 08:55:46'),
(8, 'ซิงค์ 0.7', 'ตัวอักษรโลหะ', 20, 'แผ่น', '2025-06-03 04:34:01'),
(9, 'สติ๊กเกอร์ใส', 'สติ๊กเกอร์', 80, 'ตร.ม.', '2025-05-31 08:55:46'),
(10, 'สติ๊กเกอร์ 3M', 'สติ๊กเกอร์', 60, 'ตร.ม.', '2025-05-31 08:55:46'),
(11, 'สติ๊กเกอร์ฝ้า', 'สติ๊กเกอร์', 40, 'ตร.ม.', '2025-05-31 08:55:46'),
(12, 'สติ๊กเกอร์สูญญากาศ', 'สติ๊กเกอร์', 30, 'ตร.ม.', '2025-05-31 08:55:46'),
(13, 'ผ้าไวนิลโปร่งแสง', 'ผ้าไวนิล', 70, 'ตร.ม.', '2025-05-31 08:55:46'),
(14, 'ผ้าไวนิลทึบแสง', 'ผ้าไวนิล', 90, 'ตร.ม.', '2025-05-31 08:55:46'),
(15, 'ผ้าใบกันน้ำ', 'ผ้าไวนิล', 55, 'ตร.ม.', '2025-05-31 08:55:46'),
(16, 'แผ่นโฟมบอร์ด 5mm', 'วัสดุแผ่น', 20, 'แผ่น', '2025-05-31 08:55:46'),
(17, 'แผ่นโฟมบอร์ด 10mm', 'วัสดุแผ่น', 15, 'แผ่น', '2025-05-31 08:55:46'),
(18, 'แผ่นอะคริลิค 5mm', 'วัสดุแผ่น', 18, 'แผ่น', '2025-05-31 08:55:46'),
(19, 'แผ่นอะคริลิค 10mm', 'วัสดุแผ่น', 12, 'แผ่น', '2025-05-31 08:55:46'),
(20, 'แผ่นอลูมิเนียมคอมโพสิต', 'วัสดุแผ่น', 22, 'แผ่น', '2025-05-31 08:55:46'),
(21, 'ตัวอักษรสแตนเลส 1 นิ้ว', 'ตัวอักษรโลหะ', 40, 'ตัว', '2025-06-03 06:59:43'),
(22, 'ตัวอักษรสแตนเลส 2 นิ้ว', 'ตัวอักษรโลหะ', 35, 'ตัว', '2025-06-03 06:59:43'),
(23, 'ตัวอักษรทองเหลือง 1 นิ้ว', 'ตัวอักษรโลหะ', 25, 'ตัว', '2025-06-03 06:59:43'),
(24, 'ตัวอักษรทองเหลือง 2 นิ้ว', 'ตัวอักษรโลหะ', 20, 'ตัว', '2025-06-03 06:59:43'),
(25, 'กล่องไฟ LED 60x120 ซม.', 'กล่องไฟ', 10, 'กล่อง', '2025-05-31 08:55:46'),
(26, 'กล่องไฟ LED 80x120 ซม.', 'กล่องไฟ', 8, 'กล่อง', '2025-05-31 08:55:46'),
(27, 'กล่องไฟ LED วงกลม 60 ซม.', 'กล่องไฟ', 6, 'กล่อง', '2025-05-31 08:55:46'),
(28, 'กล่องไฟ LED วงกลม 80 ซม.', 'กล่องไฟ', 5, 'กล่อง', '2025-05-31 08:55:46'),
(29, 'สติ๊กเกอร์สะท้อนแสง', 'สติ๊กเกอร์', 25, 'ตร.ม.', '2025-05-31 08:55:46'),
(30, 'สติ๊กเกอร์ลายไม้', 'สติ๊กเกอร์', 30, 'ตร.ม.', '2025-05-31 08:55:46'),
(31, 'สติ๊กเกอร์ลายหินอ่อน', 'สติ๊กเกอร์', 28, 'ตร.ม.', '2025-05-31 08:55:46'),
(32, 'สติ๊กเกอร์ลายคาร์บอน', 'สติ๊กเกอร์', 22, 'ตร.ม.', '2025-05-31 08:55:46'),
(33, 'ผ้าใบลายพราง', 'ผ้าไวนิล', 40, 'ตร.ม.', '2025-05-31 08:55:46'),
(34, 'ผ้าใบลายทแยง', 'ผ้าไวนิล', 35, 'ตร.ม.', '2025-05-31 08:55:46'),
(35, 'แผ่นพีวีซี 2mm', 'วัสดุแผ่น', 16, 'แผ่น', '2025-05-31 08:55:46'),
(36, 'แผ่นพีวีซี 5mm', 'วัสดุแผ่น', 14, 'แผ่น', '2025-05-31 08:55:46'),
(37, 'ตัวอักษรอลูมิเนียม 1 นิ้ว', 'ตัวอักษรโลหะ', 18, 'ตัว', '2025-06-03 06:59:43'),
(38, 'ตัวอักษรอลูมิเนียม 2 นิ้ว', 'ตัวอักษรโลหะ', 15, 'ตัว', '2025-06-03 06:59:43');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `materials`
--
ALTER TABLE `materials`
  ADD PRIMARY KEY (`material_id`);

--
-- Indexes for table `options`
--
ALTER TABLE `options`
  ADD PRIMARY KEY (`option_id`);

--
-- Indexes for table `price_rules`
--
ALTER TABLE `price_rules`
  ADD PRIMARY KEY (`rule_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`);

--
-- Indexes for table `stock`
--
ALTER TABLE `stock`
  ADD PRIMARY KEY (`stock_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `materials`
--
ALTER TABLE `materials`
  MODIFY `material_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `options`
--
ALTER TABLE `options`
  MODIFY `option_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `price_rules`
--
ALTER TABLE `price_rules`
  MODIFY `rule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `stock`
--
ALTER TABLE `stock`
  MODIFY `stock_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
