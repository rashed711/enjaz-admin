-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 30, 2026 at 12:47 PM
-- Server version: 8.0.46-0ubuntu0.22.04.4
-- PHP Version: 8.3.31

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `enjaz_admin`
--

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'اسم العميل',
  `company_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'اسم الشركة',
  `mobile` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'رقم الموبايل',
  `mobile_2` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'رقم موبايل إضافي',
  `activity` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'النشاط التجاري',
  `country` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'EG',
  `domain` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `domain_provider` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `username_note` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'اسم المستخدم (للإشارة)',
  `server_panel` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cp.enjaz.cloud',
  `email` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1=نشط, 0=موقوف',
  `created_by` int UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`id`, `name`, `company_name`, `mobile`, `mobile_2`, `activity`, `country`, `domain`, `domain_provider`, `username_note`, `server_panel`, `email`, `address`, `notes`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'م / باسم', 'مكتب مشعل بادغيش', '010', '966598012129', 'مكتب محاماة', 'SA', 'mishal-lawfirm.com', 'GoDaddy', 'mishal-lawfirm', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-04 13:13:44', '2026-09-01 09:21:02'),
(2, 'م / مصعب نبيل', 'Kentro TFM', '201112957899', '', 'interior design', 'EG', 'kentrotfm.com', 'Namecheap', 'kentrotfm', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-04 13:35:02', '2026-10-12 14:26:23'),
(3, 'م / محمد', 'servot', '201277795959', '201015308608', 'interior design', 'EG', 'servot.co', 'GoDaddy', 'servot', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-04 13:45:56', '2026-10-12 14:26:35'),
(4, 'م / اشرف', 'nexforges', '201220003177', '', '', 'EG', 'nexforges.com', 'GoDaddy', 'nexforges', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-04 14:17:11', '2026-06-04 14:17:11'),
(5, 'م / محمود', 'Flourish Landscaping', '201000008145', '', 'interior design', 'EG', 'flourish-landscaping.com', 'Namecheap', 'flourish', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-04 14:19:02', '2026-06-04 14:19:02'),
(6, 'م / احمد سعيد', 'Smart Source', '201019826096', '', 'Electronics shop', 'EG', 'smartsource-eg.com', 'GoDaddy', 'smartsource', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-04 14:23:20', '2026-06-04 14:38:22'),
(7, 'م / الطيب', 'Royal Smart', '201097488085', '', 'عمالة خارجية', 'EG', 'royalsmart-rs.com', 'GoDaddy', 'royalsmart', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-04 14:37:23', '2026-06-04 14:39:36'),
(8, 'م / محمد مروان', 'Stars Services', '201012692176', '', 'خدمات النظافة', 'EG', 'stars-smcs.com', 'GoDaddy', 'stars-smcs', 'cp.enjaz.cloud', '', '', 'stars-smcs.com', 1, 1, '2026-06-04 14:44:51', '2026-06-05 01:07:51'),
(9, 'م / اسلام كامل', 'Namaa invest', '201099000850', '', 'Contracting', 'EG', 'namaa-invests.com', 'GoDaddy', 'namaainvests', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-04 14:50:29', '2026-06-04 14:50:29'),
(10, 'م / احمد حماد', 'Micro Serial', '201023399311', '', 'online shop', 'EG', 'microserial.com', 'GoDaddy', 'microserial', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-04 14:54:47', '2026-06-04 14:54:47'),
(11, 'م / رفعت كريم', 'Alauma Contracting', '201111130655', '', 'Contracting', 'EG', 'alauma.com', 'GoDaddy', 'alauma', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-04 15:11:18', '2026-06-04 15:11:18'),
(12, 'د / محمد الجميعي', 'Onboarding 4u', '201003939499', '', 'قطاع الصيدليات', 'EG', 'onboarding4u.com', 'GoDaddy', 'onboarding4u', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-04 15:14:13', '2026-06-04 15:14:13'),
(13, 'م / اسلام الشريف', 'Alsherif Group', '201119589121', '', 'Contracting', 'EG', 'alsherifgroup.net', 'GoDaddy', 'alsherifgroup', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-04 15:18:47', '2026-06-04 15:18:47'),
(14, 'م / عاطف صلاح', 'التحدي تريد', '201000002267', '', 'استيراد وتصدير', 'EG', 'eltahady-trade.com', 'GoDaddy', 'eltahadytrade', 'cp.enjaz.cloud', '', '', '', 0, 1, '2026-06-04 15:22:39', '2026-06-06 14:07:22'),
(15, 'م / عمار بهنس', 'Arab House Vent', '201143638874', '', 'Mep - HVAC', 'EG', 'arabhousevent.com', 'GoDaddy', 'arabhousevent', 'cp.enjaz.cloud', '', '', '', 0, 1, '2026-06-04 15:24:54', '2026-06-06 13:09:55'),
(16, 'م / علي محمد', 'Blue Sea Metal', '201013982131', '', 'تشكيل المعان', 'EG', 'bluesea-metal.com', 'GoDaddy', 'blueseametal', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-04 15:37:02', '2026-06-04 15:37:02'),
(17, 'م / بيشوي رأفت', 'innova Egypt', '201288333348', '', 'برمجيات', 'EG', 'innova-egypt.com', 'GoDaddy', 'innova-egypt', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-04 15:42:10', '2026-06-04 15:42:10'),
(18, 'DR. Aya A Ahmed', 'Admissions Office', '201038239100', '201044929476', 'قطاع التعليم', 'EG', 'admissions-office-eg.com', 'GoDaddy', 'admissions-office', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-04 15:44:49', '2026-09-01 10:08:21'),
(19, 'د / طه منصور', 'TM Medical', '201016225666', '', 'القطاع الطبي', 'EG', 'tm-medical.net', 'GoDaddy', 'tm-medical', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-04 15:48:14', '2026-06-04 15:48:14'),
(20, 'م / بيتر فيكتور', 'AMD Consttructions', '201001479363', '', 'Contracting', 'EG', 'amdconst.com', 'GoDaddy', 'amdconst', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-04 15:53:52', '2026-06-04 15:53:52'),
(21, 'م / محمد نصر', 'همة وطن', '201128478217', '', 'Contracting', 'EG', 'hemmatwatn.com', 'GoDaddy', 'hemmatwatn', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-04 15:56:14', '2026-06-04 15:56:14'),
(22, 'م / مازن', 'مازن كو', '201055144517', '', 'القطاع المالي', 'EG', 'mazenco.info', 'GoDaddy', 'mazenco', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-04 15:57:53', '2026-06-04 15:57:53'),
(23, 'م / اسلام البلتاجي', 'Gladious Scape', '201207575483', '', 'Landscaping', 'EG', 'gladiousscape.com', 'GoDaddy', 'gladiousscape', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-04 15:59:19', '2026-06-14 12:37:18'),
(24, 'م / احمد يوسف', 'Dimec', '201028071308', '', 'التوريدات', 'EG', 'dimec-eg.com', 'GoDaddy', 'dimec-eg', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-04 16:01:46', '2026-06-04 16:01:46'),
(25, 'م / نانسي', 'Vera Agency', '201206671454', '', 'Agency', 'EG', 'veraagency-eg.com', 'GoDaddy', 'veraagency', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-04 16:05:19', '2026-06-04 16:05:19'),
(26, 'د / عبد الرحمن', 'Advanced Tech', '201274432551', '', 'Tech', 'EG', 'advancedtech-eg.com', 'GoDaddy', 'advancedtech', 'cp.enjaz.cloud', '', '', '', 0, 1, '2026-06-04 16:08:40', '2026-06-06 13:08:55'),
(27, 'م / محمود نور الدين', 'Valve Link', '201018067511', '', 'PLUMBING', 'EG', 'valvelink.co', 'Hostinger', 'valvelink', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-04 16:12:11', '2026-06-04 22:33:50'),
(28, 'م / عمرو محمد', 'Alex Air', '201226178167', '', 'HVAC', 'EG', 'alexair-eg.com', 'GoDaddy', 'alexair', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-04 16:16:15', '2026-06-04 16:16:15'),
(29, 'م / رامي عبد اللطيف', 'First Air', '201067996402', '', 'Mep - HVAC', 'EG', 'firstair-eg.com', 'GoDaddy', 'firstair', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-04 16:19:46', '2026-06-05 20:05:39'),
(30, 'م / صابر مكي', 'shariha', '966561036181', '', 'telecom', 'SA', 'shariha.net', 'GoDaddy', 'shariha', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-04 16:34:00', '2026-08-21 14:49:23'),
(31, 'م / محمد', 'Metal Power', '201090416807', '', 'Contracting', 'EG', 'metalpwr.com', 'GoDaddy', 'metalpwr', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-04 18:40:00', '2026-06-04 18:40:00'),
(32, 'م / احمد نجم', 'Oxyva Capital', '201555115296', '', 'consulting learning', 'EG', 'oxyva-eg.com', 'GoDaddy', 'oxyva', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-04 18:44:16', '2026-06-04 18:44:16'),
(33, 'م / محمود محمد', 'Ovo Way', '201025500011', '', '', 'EG', 'ovo-way.com', 'Hostinger', 'ovoway', 'cp.enjaz.cloud', '', '', '', 0, 1, '2026-06-04 18:46:52', '2026-06-06 13:11:19'),
(34, 'م / احمد عبد العال', 'Sedra Oman', '201005151818', '', 'cosmetics', 'EG', 'sedra-om.com', 'GoDaddy', 'sedraom', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-04 18:53:18', '2026-06-04 18:53:18'),
(35, 'م / عبد الرحمن فرغلي', 'Exception CO', '201006829244', '', 'construction', 'EG', 'exception-co.com', 'GoDaddy', 'exceptionco', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-04 18:56:03', '2026-06-04 18:56:03'),
(36, 'م / احمد ابراهيم', 'Elhassan P S', '201068500268', '', 'Printing Supply', 'EG', 'elhassan-ps.com', 'GoDaddy', 'elhassan', 'cp.enjaz.cloud', '', '', '', 0, 1, '2026-06-04 19:03:55', '2026-07-20 12:08:19'),
(37, 'م / احمد ابراهيم', 'Makkah EST', '201068500268', '', 'construction', 'EG', 'makkahest.com', 'GoDaddy', 'makkahest', 'cp.enjaz.cloud', '', '', '', 0, 1, '2026-06-04 19:05:57', '2026-07-20 12:08:32'),
(38, 'م / يوسف هشام', 'reelify', '201505588416', '', 'videos Converter', 'EG', 'reelify.cc', 'dynadot', 'reelify', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-04 19:09:16', '2026-06-04 19:10:08'),
(39, 'م / احمد رؤوف', 'Nour Tiba', '201028085860', '', 'مكتب محاسبة', 'EG', 'nourtiba-eg.com', 'GoDaddy', 'nourtiba', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-04 19:11:56', '2026-06-04 19:11:56'),
(40, 'م / عمرو عبد الموجود', 'AB Pro Medical', '201091166448', '', 'Medical', 'EG', 'abpromedical.com', 'GoDaddy', 'abpromedical', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-04 19:15:39', '2026-06-04 19:15:39'),
(41, 'م / جمال محمد', 'Burhank', '201210400738', '', 'Contracting', 'EG', 'burhank.com', 'Hostinger', 'burhank', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-04 19:18:26', '2026-06-04 19:18:26'),
(42, 'م / احمد سرور', 'Prime Gate Tech', '201227187286', '', '', 'EG', 'primegatetech.com', 'GoDaddy', 'primegatetech', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-04 19:30:05', '2026-06-04 19:30:05'),
(43, 'م / اسلام اسامة', 'Almotaheda Engineering', '201012173608', '', 'Engineering', 'EG', 'almotaheda-eng.com', 'GoDaddy', 'almotaheda', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-04 19:36:48', '2026-06-04 19:36:48'),
(44, 'عميد يحي المراسي', 'International CP', '201018920639', '', '', 'OM', 'international-cp.com', 'GoDaddy', 'internationalcp', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-04 19:39:03', '2026-08-22 10:10:28'),
(45, 'م / تامر', 'Alkayan Transport', '201501400887', '', 'Transportation', 'EG', 'alkayan-trans.com', 'GoDaddy', 'alkayantrans', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-04 19:42:26', '2026-06-04 19:42:26'),
(46, 'م / محمد فتاح', 'Fattah Foundation', '201130713582', '', 'Contracting', 'EG', 'fattahfoundation.com', 'GoDaddy', 'fattahfoundation', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-04 19:45:08', '2026-06-04 19:45:08'),
(47, 'م / محمد سعيد', 'Enbridge', '201220404582', '', 'Petroleum Services', 'EG', 'enbridge-eg.com', 'GoDaddy', 'enbridge', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-04 19:48:28', '2026-06-04 19:48:28'),
(48, 'م / عبد الحميد عرابي', 'Oujaj Salt', '201003738806', '', '', 'EG', 'oujajsalt.com', 'GoDaddy', 'oujajsalt', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-04 19:51:27', '2026-06-04 19:51:27'),
(49, 'م / هيثم', 'White Group', '201050207906', '', 'Contracting', 'EG', 'whitegroup-eng.com', 'GoDaddy', 'whitegroup', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-04 19:53:49', '2026-07-23 12:21:54'),
(50, 'م / محمد عبد الله', 'Pro Creativity', '010', '', 'Contracting', 'EG', 'procreativity.net', 'GoDaddy', 'procreativity', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-04 19:55:58', '2026-06-04 19:55:58'),
(51, 'م / ابو العيبد', 'Egy Fruitex', '201028240959', '', 'Fruitex', 'EG', 'egy-fruitex.com', 'GoDaddy', 'egyfruitex', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-04 19:59:13', '2026-06-04 19:59:13'),
(52, 'م / محمد علي', 'Ellevate Marketing', '201150695780', NULL, 'Marketing', 'EG', 'ellevatemarketing.com', 'Netlify', 'ellevatemarketing', 'cp.enjaz.cloud', NULL, NULL, NULL, 1, 1, '2026-06-04 20:15:15', '2026-06-04 20:15:15'),
(53, 'م / يوسف', 'G-Tech Solution', '201210622000', NULL, 'Tech', 'EG', 'g-tech-solution.com', 'Godady', 'g-tech-solution', 'cp.enjaz.cloud', NULL, NULL, NULL, 1, 1, '2026-06-04 20:15:15', '2026-06-04 20:15:15'),
(54, 'م / عمرو خالد', 'Construct Code', '201012468796', NULL, 'Contracting', 'EG', 'Construct-code.com', 'Godady', 'Construct-code', 'cp.enjaz.cloud', NULL, NULL, NULL, 1, 1, '2026-06-04 20:15:15', '2026-06-04 20:15:15'),
(55, 'م / نبيل رضا', 'Future Green', '201113416722', NULL, 'تجارة العدد اليدوية وانظمة الحريق', 'EG', 'future-green.net', 'Godady', 'future-green', 'cp.enjaz.cloud', NULL, NULL, NULL, 1, 1, '2026-06-04 20:15:15', '2026-06-04 20:15:15'),
(56, 'م / محمود مجدي', 'snoby', '201018766588', '', 'cosmetics', 'EG', 'snoby-eg.com', 'Godady', 'snoby-eg', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-04 20:15:15', '2026-07-23 12:21:34'),
(57, 'م / خالد منصور', 'Omega', '201117013024', NULL, 'supplies', 'EG', 'omega-supplies.com', 'Godady', 'omega-supplies', 'cp.enjaz.cloud', NULL, NULL, NULL, 1, 1, '2026-06-04 20:15:15', '2026-06-04 20:15:15'),
(58, 'م / ريم', 'myriad', '201157184292', NULL, NULL, 'EG', 'myriad-co.com', 'Godady', 'myriad-co', 'cp.enjaz.cloud', NULL, NULL, NULL, 1, 1, '2026-06-04 20:15:15', '2026-06-04 20:15:15'),
(59, 'م / شريف مجدي', 'Senior Constructions', '201017571005', NULL, 'Constructions', 'EG', 'seniorconstructions.com', 'Godady', 'seniorconstructions', 'cp.enjaz.cloud', NULL, NULL, NULL, 1, 1, '2026-06-04 20:15:15', '2026-06-04 20:15:15'),
(60, 'م / السيد هاني', 'Alamana', '201280265841', '', 'Transport', 'EG', 'alamanatransport.com', 'Godady', 'alamanatransport', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-04 20:15:15', '2026-06-04 23:54:14'),
(61, 'م / فهمي جاد', 'Alnasem', '201100691874', NULL, NULL, 'EG', 'alnasem-eg.com', 'Godady', 'alnasem-eg', 'cp.enjaz.cloud', NULL, NULL, NULL, 1, 1, '2026-06-04 20:15:15', '2026-06-04 20:15:15'),
(62, 'د / مصطفي', 'Medx Hub', '201003939499', '', 'Medical', 'EG', 'medx-hub.com', 'Godady', 'medx-hub', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-04 20:15:15', '2026-07-01 08:30:35'),
(63, 'م / تامر خليل', 'Smart Partner', '201225770713', NULL, 'agency', 'EG', 'smartpartner.agency', 'Godady', 'smartpartner', 'cp.enjaz.cloud', NULL, NULL, NULL, 1, 1, '2026-06-04 20:15:15', '2026-06-04 20:15:15'),
(64, 'م / مروان هشام', 'Wijha Express', '201143594429', NULL, 'Express', 'EG', 'wijha.express', 'Godady', 'wijha', 'cp.enjaz.cloud', NULL, NULL, NULL, 1, 1, '2026-06-04 20:15:15', '2026-06-04 20:15:15'),
(65, 'م / ت', 'Transmission', '249123885888', NULL, 'Digital Marketing', 'SD', 'transmission-dma.com', 'Godady', 'transmission', 'cp.enjaz.cloud', NULL, NULL, NULL, 1, 1, '2026-06-04 20:15:15', '2026-08-21 14:49:23'),
(66, 'م / هدوي علي', 'plus-adv', '201147080280', NULL, 'Digital Marketing', 'EG', 'plus-adv.com', 'Godady', 'plus-adv', 'cp.enjaz.cloud', NULL, NULL, NULL, 1, 1, '2026-06-04 20:15:15', '2026-06-04 20:15:15'),
(67, 'م / كريم', 'Alfa Petroleum', '201124252602', NULL, 'Petroleum', 'EG', 'alfa-petroleum.com', 'Godady', 'alfa-petroleum', 'cp.enjaz.cloud', NULL, NULL, NULL, 1, 1, '2026-06-04 20:15:15', '2026-06-04 20:15:15'),
(68, 'م / نيرة الليثي', 'Eldewanya', '201093577377', '', 'تصدير', 'EG', 'eldewanya.com', 'Godady', 'eldewanya', 'cp.enjaz.cloud', '', '', '', 0, 1, '2026-06-04 20:15:15', '2026-09-21 10:12:33'),
(69, 'م / عبد اللطيف محمد', 'infinity Logistic', '201080146149', NULL, 'Logistic', 'EG', 'infinity-logistic.net', 'Godady', 'infinity-logistic', 'cp.enjaz.cloud', NULL, NULL, NULL, 1, 1, '2026-06-04 20:15:15', '2026-06-04 20:15:15'),
(70, 'م / تامر', 'Alkayan Logistics', '201501400887', '', '', 'EG', 'alkayanlogistics.com', 'GoDaddy', 'alkayanlogistics', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-04 20:16:28', '2026-09-01 10:00:38'),
(71, 'م / محمد عبد الله', 'Madar MEP', '201060565785', '', 'MEP', 'EG', 'MadarMEP.com', 'GoDaddy', 'madarmep', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-04 20:17:20', '2026-06-04 20:17:20'),
(72, 'م / محمد المنشاوي', 'Menshawy Group', '201013881148', '', 'Contracting', 'EG', 'menshawy-group.com', 'GoDaddy', 'menshawy', 'cp.enjaz.cloud', '', '', '', 0, 1, '2026-06-04 20:40:54', '2026-07-23 12:21:13'),
(73, 'Rashed', 'Enjaz', '201028855779', '01028855779', 'Enjaz', 'EG', 'Enjaz.app', 'GoDaddy', 'enjaz', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-04 20:43:32', '2026-09-01 09:20:35'),
(74, 'م / عز عادل', 'Blue Nile', '201001300092', '', 'مقاولات', 'EG', 'bluenile-group.net', 'GoDaddy', 'bluenile', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-05 16:22:42', '2026-06-10 12:01:36'),
(75, 'م / حسن سيد', 'Black Cherry Wood', '201150979340', '', 'wood', 'EG', 'blackcherry-wood.com', 'GoDaddy', 'blackcherry', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-09 20:54:56', '2026-06-09 21:06:50'),
(76, 'م / محمود', '3m Services', '201111150639', '', 'خدمات', 'EG', '3m-services.net', 'GoDaddy', '3m-services', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-11 11:08:12', '2026-06-11 11:08:12'),
(78, 'د / نوح عبد الله', 'رائد الشرق', '966505774960', '', 'تجارة الأدوية', 'SA', 'raed-alsharq.com', 'GoDaddy', 'raed-alsharq', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-12 18:59:40', '2026-08-21 14:49:23'),
(79, 'م / احمد عبد الكريم', 'AHL', '201284206086', '', 'construction', 'EG', 'ahl-eg.net', 'GoDaddy', 'ahl-eg', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-13 10:30:24', '2026-06-14 12:02:55'),
(80, 'م / نور الدين هشام', 'Selection Properties', '201101121455', '', 'Real Estate', 'EG', 'selectionproperties.org', '', 'selectionproperties', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-14 11:26:01', '2026-06-14 11:26:11'),
(81, 'م / محمود عبد الهادي', 'Emdad', '201022988711', '', 'توريدات عموامية', 'EG', 'emdad-eg.com', 'GoDaddy', 'emdad-eg', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-14 11:28:28', '2026-07-05 19:06:14'),
(82, 'م / محمد قاعود', 'ART Office', '201009542308', '', 'اثاث مكتبي', 'EG', 'artoffice-eg.com', 'GoDaddy', 'artoffice-eg', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-16 12:38:48', '2026-06-16 12:38:48'),
(83, 'م / محمد سعيد', 'KMT Legal', '201063887871', '', 'قانوني', 'EG', 'kmtlegal.org', 'GoDaddy', 'kmtlegal', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-20 19:56:47', '2026-06-20 19:56:47'),
(84, 'م / حامد', 'protacta', '201060917992', '', 'متجر الكتروني', 'EG', 'protacta', 'GoDaddy', 'protacta', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-21 12:46:27', '2026-06-21 12:46:27'),
(85, 'م / عمر بدران', 'Esco Transports', '201026032095', '', 'transports', 'EG', 'esco-transports.com', 'GoDaddy', 'esco-transports', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-22 13:27:26', '2026-06-22 13:27:26'),
(86, 'م / محمد صلاح', 'Desert Rose', '201003597983', '', 'مبيدات وتوريدات', 'EG', 'desertrose-eg.com', 'GoDaddy', 'desertrose', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-23 21:40:08', '2026-06-23 21:40:08'),
(87, 'م / مازن حسين', 'Alpha M Global', '201553430555', '', 'استيراد وتصدير', 'EG', 'alphamglobal.com', 'GoDaddy', 'alphamglobal', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-27 09:44:36', '2026-07-01 11:28:48'),
(88, 'م / عبد العزيز صلاح', 'egy-cg', '201152065471', '', '', 'EG', 'egy-cg.com', 'GoDaddy', 'egy-cg', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-06-30 07:33:14', '2026-07-19 19:50:24'),
(89, 'م / محمود عبد القادر', 'CEC AFRICA', '201097911542', '', 'مكتب إدارة مشاريع خدمات اونلاين', 'EG', 'cec-africa.net', 'GoDaddy', 'cec-africa', 'cp.enjaz.cloud', '', '', 'نفذ الأوامر والخطوات النهائية دي بالترتيب ده بالظبط في الـ SSH:\r\n\r\n1. تحديث شهادة الـ Mail العامة للأوتلوك:\r\nشغل الأمر ده عشان برامج الميل والموبايل تلقط تشفير الدومين الجديد فوراً وبدون أي تحذيرات أمان للعميل:\r\n\r\nBash\r\ncp /etc/letsencrypt/live/mail.cec-africa.net/fullchain.pem /usr/local/hestia/ssl/mail.crt\r\ncp /etc/letsencrypt/live/mail.cec-africa.net/privkey.pem /usr/local/hestia/ssl/mail.key\r\nsystemctl restart exim4 dovecot\r\n2. قراءة الأكواد لنسخها جوة اللوحة:\r\nشغل الأوامر دي ورا بعض، وانسخ كل كود عشان تحطه في صندوقه الخاص بـ الـ SSL في المتصفح:\r\n\r\nكود الصندوق الأول (SSL Certificate):\r\n\r\nBash\r\nsed -n \'1,/END CERTIFICATE/p\' /etc/letsencrypt/live/mail.cec-africa.net/fullchain.pem\r\nكود الصندوق الثاني (SSL Private Key):\r\n\r\nBash\r\ncat /etc/letsencrypt/live/mail.cec-africa.net/privkey.pem\r\nكود الصندوق الثالث (SSL Certificate Authority / Intermediate):\r\n\r\nBash\r\nsed -n \'/END CERTIFICATE/,$p\' /etc/letsencrypt/live/mail.cec-africa.net/fullchain.pem | sed -n \'2,$p\'\r\n3. التفعيل من واجهة HestiaCP (المتصفح):\r\nادخل على لوحة التحكم -> تبويب Mail.\r\n\r\nاقف على الدومين cec-africa.net واضغط Edit (أيقونة القلم).\r\n\r\nعلّم صح على Setup SSL for this domain (وطبعاً سيب خيار Let\'s Encrypt اللي تحته فاضي).\r\n\r\nاعمل لصق (Paste) لكل كود من الثلاثة اللي فوق في صندوقه المخصص بالترتيب.\r\n\r\nاضغط Save من الأعلى.\r\n\r\n4. ربط وتفعيل الـ Webmail (الـ Roundcube) النهائي:\r\nنفذ الأمر ده في الـ SSH لتوجيه الـ Webmail لاسم المستخدم الصحيح وتحديث الـ Nginx:\r\n\r\nBash\r\n/usr/local/hestia/bin/v-add-mail-domain-webmail cec-africa cec-africa.net roundcube\r\nsystemctl restart nginx\r\nبمجرد ما تخلص الخطوة الأخيرة دي، جرب تفتح https://webmail.cec-africa.net في متصفح خفي، وهتلاقيه فتح صفحة تسجيل دخول الميل الرسمية والمؤمنة بقفل الأمان 🔒 بنسبة 100%، والأوتلوك هيشتغل مع العميل ده فوراً ومن أول محاولة!', 1, 1, '2026-07-05 11:05:56', '2026-07-09 19:16:39'),
(90, 'م / حامد الشريف (سعودي)', 'BNYN', '966555001836', '', 'مقاولات', 'SA', 'bnyn.com.sa', 'sahara', 'bnyn', 'cp.enjaz.cloud', '', '', 'https://shop.sahara.com/cart.php?a=add&domain=register&language=arabic&query=bnyn.sa', 1, 1, '2026-07-07 09:24:18', '2026-08-21 15:27:48'),
(91, 'م / صلاح جاد', 'ركيزة SA', '966553190744', '', 'دعاية وإعلان', 'SA', 'rakiza-des.com', 'GoDaddy', 'rakiza', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-07-08 10:57:25', '2026-08-21 14:49:23'),
(92, 'احمد محسن', 'SportStuff', '201002698969', '', '', 'EG', 'SportStuff.net', 'GoDaddy', 'sportstuff', 'cp.enjaz.cloud', '', '', 'نفذ الاوامر التالية في ال SSH علشان تجدد الشهادا للدومين\r\nsportstuff.net\r\nموعد البدء 09-07-2026\r\nنفذ دول\r\n\r\nsystemctl stop nginx\r\ncertbot renew\r\ncp /etc/letsencrypt/live/mail.sportstuff.net/fullchain.pem /usr/local/hestia/ssl/mail.crt\r\ncp /etc/letsencrypt/live/mail.sportstuff.net/privkey.pem /usr/local/hestia/ssl/mail.key\r\nsystemctl start nginx\r\nsystemctl restart exim4 dovecot', 1, 1, '2026-07-09 19:42:49', '2026-07-09 19:43:41'),
(93, 'م / احمد علي', 'QDS', '201225396933', '', 'تصدير', 'EG', 'qds-export.com', 'GoDaddy', 'qds-export', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-07-10 20:42:09', '2026-07-10 21:02:08'),
(94, 'م/ محمد', 'veora', '201277795959', '', 'تصميم', 'EG', 'veora-eg.com', 'GoDaddy', 'veora', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-07-11 16:26:07', '2026-07-11 16:26:07'),
(95, 'م / صلاح محمد', 'West Engineer', '201026228876', '', 'Engineer', 'EG', 'west-engineer.net', 'GoDaddy', 'west-engineer', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-07-12 14:58:06', '2026-07-12 14:58:06'),
(96, 'م / حسن عبد النبي', 'تدفق المزن', '201116764534', '', 'تجارى', 'EG', 'tadafok-almuzn.com', 'GoDaddy', 'tadafok-almuzn', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-07-12 19:45:02', '2026-08-01 10:29:28'),
(97, 'م / محمود بركات', 'نيو ديل', '201011260142', '', 'تجاري', 'EG', 'newdealeg.com', 'GoDaddy', 'newdealeg', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-07-13 13:13:16', '2026-07-13 13:13:16'),
(98, 'م / محمد', 'Eastline', '201034373883', '', 'Travel', 'EG', 'eastline-travel.com', 'GoDaddy', 'eastline-travel', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-07-14 23:21:49', '2026-07-14 23:21:49'),
(99, 'م / حامد الشريف (سعودي)', 'Abu Taki', '966555001836', '', 'مقاولات', 'SA', 'abutaki.com.sa', 'sahara', 'abutaki', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-07-15 13:13:14', '2026-08-29 10:27:53'),
(100, 'م / خليفة عبد اللطيف', 'تعمير الديار', '966597409020', '', 'مقاولات', 'SA', 'tdc.com.sa', 'sahara', 'tdc', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-07-15 20:31:16', '2026-08-21 14:49:23'),
(101, 'م / بيومي سعد', 'صدارة', '201020887196', '', 'مقاولات', 'EG', 'sadara1.com', 'GoDaddy', 'sadara', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-07-16 09:28:03', '2026-07-23 12:22:10'),
(102, 'م / عمر بدران', 'Mentra One', '201026032095', '', '', 'EG', 'mentra-one.com', 'GoDaddy', 'mentra-one', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-07-19 10:03:59', '2026-07-19 10:03:59'),
(103, 'م / معتز سعيد', 'مؤسسة الحديثة للمقاولات العامة', '966581514236', '', 'المقاولات العامة', 'SA', 'alhaditha-sa.com', 'GoDaddy', 'alhaditha', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-07-19 18:54:57', '2026-08-21 14:49:23'),
(104, 'م / ايناس محمد', 'Upgrade Group', '201210030829', '', 'دعاية وإعلان', 'EG', 'upgrade-group.net', 'GoDaddy', 'upgrade-group', 'panel.enjaz.cloud', '', '', '', 1, 1, '2026-07-23 11:57:10', '2026-07-23 12:46:54'),
(105, 'م / احمد غريب', 'Egaadeg', '201143233888', '', '', 'EG', 'egaadeg.com', 'name.com', 'egaadeg', 'panel.enjaz.cloud', '', '', '', 1, 1, '2026-07-23 12:30:55', '2026-07-23 12:46:44'),
(107, 'م / خالد ايلان', 'sbt-ars', '+6281388888835', '', '', 'ID', 'sbt-ars.com', 'souq', 'sbt-ars.com', 'panel.enjaz.cloud', '', '', '', 1, 1, '2026-07-27 23:43:04', '2026-08-21 15:14:02'),
(108, 'م / محمود الجرواني', 'الثقة العليا', '966567574282', '', '', 'SA', 'althiqahalolya.com', 'GoDaddy', 'althiqahalolya', 'panel.enjaz.cloud', '', 'شارع عبد الله بخاري - العزيزية - مكة', '', 1, 1, '2026-07-29 09:36:07', '2026-08-21 14:49:23'),
(109, 'م / شريف بكري', 'خمر الصالحين', '201030006040', '', 'كافيه', 'EG', 'khamrelsaleheen.com', 'GoDaddy', 'khamrelsaleheen', 'panel.enjaz.cloud', '', '', '', 1, 1, '2026-08-11 14:01:45', '2026-08-13 16:22:52'),
(110, 'م / مجد أمين', 'تلال الربيع', '966534824717', '', 'مفارش', 'SA', 'tilalalrabie-sa.com', 'Salla', 'tilalalrabie-sa', 'panel.enjaz.cloud', '', '', '', 1, 1, '2026-08-13 12:43:02', '2026-08-21 14:49:23'),
(111, 'م / رضا رشوان', 'بواني العاصمة', '966501402000', '', 'مصنع الومنيوم وزجاج', 'SA', 'bawani-alasima.com', 'GoDaddy', 'bawani-alasima', 'panel.enjaz.cloud', '', '', '', 1, 1, '2026-08-13 20:14:23', '2026-08-21 14:49:23'),
(112, 'م / محمد مجدي', 'فواصل المتطورة المحدودة', '201117946965', '', 'مقاولات', 'SA', 'fawasilksa.com', 'GoDaddy', 'fawasilksa', 'panel.enjaz.cloud', '', '', '', 1, 1, '2026-08-15 13:16:20', '2026-08-21 15:23:43'),
(113, 'م / طاهر ابو يحي', 'محمد سليمان واخوته', '966594231906', '', 'صناعة كربونات الكالسيوم ومعاجين الجدران', 'SA', 'm-carbonate-calcium.com', 'GoDaddy', 'm-carbonate-calcium', 'panel.enjaz.cloud', '', '', '', 1, 1, '2026-08-16 19:52:14', '2026-08-21 14:49:23'),
(114, 'م / محمد ابو قمر', 'nub kemet', '201055534845', '', 'تجاري', 'EG', 'nubkemet.com', 'GoDaddy', 'nubkemet', 'panel.enjaz.cloud', '', '', '', 1, 1, '2026-08-17 05:50:46', '2026-08-17 05:50:46'),
(115, 'م / مصطفي المنشاوي', 'بدائع وطنية', '966552166706', '', 'مقاولات', 'SA', 'badaewatanya.com', 'GoDaddy', 'badaewatanya', 'panel.enjaz.cloud', '', '', '', 1, 1, '2026-08-20 19:49:34', '2026-08-22 10:33:59'),
(117, 'م / بلال احمد', 'اوميرا للمقاولات', '201158352430', '', 'التجارو والتوكيلات', 'EG', 'mera-group-eg.com', 'GoDaddy', 'mera-group-eg', 'panel.enjaz.cloud', '', '', '', 1, 1, '2026-08-26 16:04:43', '2026-08-26 16:04:43'),
(118, 'م / معتز سعيد', 'الكوادر العاملة', '966581514236', '', 'مقاولات', 'SA', 'alkawader-alamilah.com', 'GoDaddy', 'alkawader-alamilah', 'panel.enjaz.cloud', '', '', '', 1, 1, '2026-08-29 09:39:24', '2026-08-29 09:39:24'),
(119, 'م / عمر حمدي', 'اجريفانا', '201024323985', '', 'تصدير حاصلات زراعية', 'EG', 'agrivana.net', 'GoDaddy', 'agrivana', 'panel.enjaz.cloud', '', '', '', 1, 1, '2026-08-29 09:49:22', '2026-09-01 09:20:20'),
(120, 'م / محمد يحي', 'المدينة المنورة', '201130192541', '', 'مقاولات', 'EG', 'almadinah-almnawarah.com', 'GoDaddy', 'almadinah-almnawarah', 'panel.enjaz.cloud', '', '', '', 1, 1, '2026-08-30 14:35:24', '2026-08-30 14:35:24'),
(121, 'م / طارق عبد الحميد', 'ديرا الرواد', '201029216789', '', 'مقاولات', 'SA', 'dera-alrowad.com', 'GoDaddy', 'dera-alrowad', 'panel.enjaz.cloud', '', '', '', 1, 1, '2026-09-01 11:01:48', '2026-09-01 11:01:48'),
(122, 'م / محمد', 'شركة العلب الفاخرة للصناعة', '249121229665', '', 'شركة صناعية', 'SD', 'luxury-boxes.com', 'GoDaddy', 'luxury-boxes', 'panel.enjaz.cloud', '', '', '', 1, 1, '2026-09-06 12:14:06', '2026-09-13 19:18:40'),
(123, 'م / عبد الله (ابو كنان)', 'نور للالومنيوم', '966538754212', '', 'مصنع ألومنيوم', 'SA', 'nooraluminum.com', 'GoDaddy', 'nooraluminum', 'panel.enjaz.cloud', '', '', '', 1, 1, '2026-09-06 13:00:20', '2026-09-06 13:00:20'),
(124, 'م / محمود احمد', 'روفتيك', '966541334111', '', 'مقاولات', 'SA', 'rovtecgroup.com', 'GoDaddy', 'rovtecgroup', 'panel.enjaz.cloud', '', '', '', 1, 1, '2026-09-06 14:28:36', '2026-09-06 14:28:36'),
(125, 'م / علاء', 'tmsky', '966566705050', '', 'حجوزات الفنادق', 'SA', 'tmsky.co', 'GoDaddy', 'tmsky', 'panel.enjaz.cloud', '', '', '', 1, 1, '2026-09-06 15:22:56', '2026-09-06 15:22:56'),
(126, 'م / خليفة', 'Surveying Equipment', '966545320095', '', 'Surveying', 'SA', 'sec-surveying.com', 'GoDaddy', 'sec-surveying', 'panel.enjaz.cloud', '', '', '', 1, 1, '2026-09-07 14:28:06', '2026-09-07 14:28:06'),
(127, 'م / محمد', 'الجنوب للطباعة', '249121229665', '', 'طباعة', 'SD', 'aljanoub-printing.com', 'GoDaddy', 'aljanoub-printing', 'panel.enjaz.cloud', '', '', '', 1, 1, '2026-09-13 19:20:25', '2026-09-13 19:20:25'),
(129, 'م / اسلام', 'رواد الطرق', '966536675959', '', 'مقاولات', 'SA', 'rowadalltruq.com', 'GoDaddy', 'rowadalltruq', 'panel.enjaz.cloud', '', '', '', 1, 1, '2026-09-17 19:57:09', '2026-09-17 19:57:09'),
(131, 'م / فايز  عايش', 'شركة الاسطول الذهبي الحديث', '966530932366', '', 'للمقاولات و النقل', 'SA', 'goldenfleet.sa', 'sahara', 'goldenfleet', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-09-20 13:55:50', '2026-09-20 13:55:50'),
(132, 'م / احمد', 'Albishri', '966542546542', '', 'مقاولات', 'SA', 'albishrigroup.com', 'GoDaddy', 'albishrigroup', 'panel.enjaz.cloud', '', '', '', 1, 1, '2026-09-21 10:00:42', '2026-09-21 10:00:42'),
(133, 'م / احمد', 'mbm', '966542546542', '', 'مقاولات', 'SA', 'mbm-co.net', 'GoDaddy', 'mbm-co', 'panel.enjaz.cloud', '', '', '', 1, 1, '2026-09-21 10:10:11', '2026-09-21 10:10:11'),
(134, 'م / عثمان', 'ondi international', '966583471721', '', '', 'SA', 'ondiinternational.com', 'GoDaddy', 'ondiinternational', 'panel.enjaz.cloud', '', '', '', 1, 1, '2026-09-22 13:50:08', '2026-09-22 13:50:08'),
(135, 'م / احمد عبد الشافي', 'سيليا انفرا', '201002222001', '', 'مقاولات', 'EG', 'celiainfra.com', 'GoDaddy', 'celiainfra', 'panel.enjaz.cloud', '', '', '', 0, 1, '2026-09-26 19:03:11', '2026-10-28 08:16:12'),
(136, 'م / احمد فودة', 'MKA', '966566710999', '', 'مقاولات', 'SA', 'mka-ksa.com', 'GoDaddy', 'mka-ksa', 'panel.enjaz.cloud', '', '', '', 1, 1, '2026-09-28 14:18:38', '2026-09-28 14:18:38'),
(137, 'م / محمود ابراهيم', 'Law Gate', '201111150639', '', 'محاماة', 'EG', 'law-gate.net', 'GoDaddy', 'law-gate', 'panel.enjaz.cloud', '', '', '', 1, 1, '2026-09-28 14:21:31', '2026-09-28 14:21:31'),
(138, 'م / محمد المشلاوي', 'Simply Moment', '966593292547', '', 'كافيه', 'SA', 'simply-moment.com', 'GoDaddy', 'simply-moment', 'panel.enjaz.cloud', '', '', '', 1, 1, '2026-09-30 10:09:15', '2026-09-30 10:09:15'),
(139, 'م / حمادة ابراهيم', 'ركائز العمران الاحترافية', '201555333262', '', 'مقاولات', 'SA', 'rakaezalomran.com', 'GoDaddy', 'rakaezalomran', 'panel.enjaz.cloud', '', '', '', 0, 1, '2026-10-05 15:05:22', '2026-10-28 08:10:43'),
(140, 'د / محمد جزر', 'IO Strategy', '201005575740', '', 'استشارات', 'EG', 'io-strategy.com', 'GoDaddy', 'io-strategy', 'panel.enjaz.cloud', '', '', '', 1, 1, '2026-10-10 12:28:57', '2026-10-10 12:28:57'),
(141, 'م / ايمن الحداد', 'Kemet', '966566451193', '', 'مقاولات', 'SA', 'kemetksa.com', 'GoDaddy', 'kemetksa', 'panel.enjaz.cloud', '', '', '', 1, 1, '2026-10-10 13:23:13', '2026-10-10 13:23:13'),
(142, 'م / حامد', 'سماء الطاقة', '966532226489', '', '', 'SA', 'samaalttaqa.com', 'GoDaddy', 'samaalttaqa', 'panel.enjaz.cloud', '', '', '', 0, 1, '2026-10-14 15:33:36', '2026-10-28 08:12:31'),
(143, 'م / احمد فودة', 'DAG-sa', '966566710999', '', '', 'SA', 'dag-sa.com', 'GoDaddy', 'dag-sa', 'panel.enjaz.cloud', '', '', '', 1, 1, '2026-10-16 11:15:02', '2026-10-16 11:15:02'),
(144, 'م / محمد مصطفي', 'egy-star', '201017596440', '', 'مقاولات', 'EG', 'egy-star.com', 'GoDaddy', 'egy-star', 'panel.enjaz.cloud', '', '', '', 1, 1, '2026-10-22 22:01:42', '2026-10-22 22:01:42'),
(145, 'م / حمزة اسماعيل', 'onionpts', '201001783957', '', 'مقاولات', 'EG', 'onionpts.com', 'GoDaddy', 'onionpts', 'panel.enjaz.cloud', '', '', '', 1, 1, '2026-10-24 13:38:41', '2026-10-24 15:10:46'),
(146, 'م / احمد محسن', 'زينة فارماسي', '201002698969', '', 'شركة طبية', 'EG', 'zienapharmacy.com', 'Hostinger', 'zienapharmacy', 'panel.enjaz.cloud', '', '', 'م / احمد محسن شركة sportstuff. net', 1, 1, '2026-10-27 09:10:39', '2026-10-27 09:11:26'),
(147, 'م / احمد محسن', 'cosmobelge', '201002698969', '', '', 'EG', 'cosmobelge.com', 'GoDaddy', 'cosmobelge', 'cp.enjaz.cloud', '', '', 'م / احمد محسن شركة sportstuff. net', 1, 1, '2026-10-27 12:23:46', '2026-10-27 12:23:46'),
(148, 'م / سميح', 'باور سيفينج', '966552471181', '', 'مقاولات', 'SA', 'powersaving-sa.com', 'GoDaddy', 'powersaving-sa', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-10-28 08:38:16', '2026-10-28 08:38:16'),
(149, 'م / ياسر', 'الباشا', '966556693033', '', 'رخام وسيراميك', 'SA', 'albashamodern.com', 'GoDaddy', 'albashamodern', 'cp.enjaz.cloud', '', '', '', 1, 1, '2026-10-29 09:16:02', '2026-10-29 09:18:13');

-- --------------------------------------------------------

--
-- Table structure for table `client_subscriptions`
--

CREATE TABLE `client_subscriptions` (
  `id` int UNSIGNED NOT NULL,
  `client_id` int UNSIGNED NOT NULL,
  `service_id` int UNSIGNED NOT NULL,
  `plan_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'اسم الخطة (مثل: أساسية، بريميوم)',
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'EGP',
  `original_price` decimal(12,2) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'سعر الاشتراك الفعلي',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('active','expired','cancelled','pending') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_by` int UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `client_subscriptions`
--

INSERT INTO `client_subscriptions` (`id`, `client_id`, `service_id`, `plan_name`, `currency`, `original_price`, `price`, `start_date`, `end_date`, `status`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'G5', 'EGP', NULL, 4000.00, '2025-07-16', '2026-07-15', 'expired', '', 1, '2026-06-04 13:26:26', '2026-10-12 13:13:03'),
(2, 1, 3, '', 'EGP', 23000.00, 20000.00, '2025-07-16', NULL, 'active', '', 1, '2026-06-04 13:29:13', '2026-09-10 14:39:09'),
(3, 2, 1, 'G5', 'EGP', NULL, 2000.00, '2025-12-07', '2026-12-06', 'active', '', 1, '2026-06-04 13:35:32', '2026-10-12 13:13:03'),
(4, 3, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2025-12-11', '2026-12-10', 'active', '', 1, '2026-06-04 14:02:36', '2026-06-04 14:02:36'),
(5, 3, 3, 'موقع تعريفي', 'EGP', NULL, 0.00, '2025-12-11', NULL, 'active', '', 1, '2026-06-04 14:09:54', '2026-06-16 11:31:21'),
(6, 4, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2025-12-21', '2026-12-20', 'active', '', 1, '2026-06-04 14:17:34', '2026-06-04 14:17:34'),
(7, 5, 1, 'G5', 'EGP', NULL, 2000.00, '2025-12-22', '2026-12-21', 'active', '', 1, '2026-06-04 14:19:29', '2026-10-12 13:13:03'),
(8, 5, 3, 'موقع تعريفي', 'EGP', NULL, 0.00, '2025-12-22', NULL, 'active', '', 1, '2026-06-04 14:19:48', '2026-06-16 11:31:31'),
(9, 2, 3, 'موقع تعريفي', 'EGP', NULL, 0.00, '2025-12-07', NULL, 'active', '', 1, '2026-06-04 14:21:20', '2026-06-16 11:31:06'),
(10, 6, 2, 'دومين', 'EGP', NULL, 750.00, '2025-12-28', '2026-12-27', 'active', '', 1, '2026-06-04 14:24:53', '2026-10-12 13:52:11'),
(11, 6, 1, 'G5', 'EGP', NULL, 2000.00, '2025-12-28', '2026-12-27', 'active', '', 1, '2026-06-04 14:25:13', '2026-10-12 13:13:03'),
(12, 6, 3, 'موقع تعريفي', 'EGP', NULL, 0.00, '2025-12-28', NULL, 'active', '', 1, '2026-06-04 14:25:36', '2026-06-16 11:31:55'),
(13, 7, 2, 'royalsmart-rs.com', 'EGP', NULL, 750.00, '2025-12-30', '2026-12-29', 'active', '', 1, '2026-06-04 14:40:23', '2026-10-12 13:52:11'),
(14, 7, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2025-12-30', '2026-12-29', 'active', '', 1, '2026-06-04 14:40:53', '2026-06-04 14:40:53'),
(15, 8, 2, 'stars-smcs.com', 'EGP', NULL, 720.00, '2025-12-30', '2026-12-29', 'active', 'stars-smcs.com', 1, '2026-06-04 14:45:43', '2026-10-12 13:52:11'),
(16, 8, 1, 'G5', 'EGP', NULL, 2000.00, '2025-12-30', '2026-12-29', 'active', '', 1, '2026-06-04 14:46:06', '2026-10-12 13:13:03'),
(17, 8, 2, 'nogom-services.com', 'EGP', 720.00, 720.00, '2025-12-30', '2026-12-29', 'expired', 'nogom-services.com', 1, '2026-06-04 14:47:34', '2026-10-30 11:21:04'),
(18, 9, 2, 'namaa-invests.com', 'EGP', NULL, 750.00, '2025-12-31', '2026-12-30', 'active', '', 1, '2026-06-04 14:50:59', '2026-10-12 13:52:11'),
(19, 9, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2025-12-31', '2026-12-30', 'active', '', 1, '2026-06-04 14:53:14', '2026-06-04 14:53:14'),
(21, 10, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-01-04', '2027-01-03', 'active', '', 1, '2026-06-04 15:07:47', '2026-06-04 15:07:47'),
(22, 11, 1, 'G5', 'EGP', NULL, 2000.00, '2026-01-05', '2027-01-04', 'active', '', 1, '2026-06-04 15:11:48', '2026-10-12 13:13:03'),
(23, 12, 2, 'onboarding4u.com', 'EGP', NULL, 750.00, '2026-01-08', '2027-01-07', 'active', '', 1, '2026-06-04 15:14:42', '2026-10-12 13:52:11'),
(24, 12, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-01-08', '2027-01-07', 'active', '', 1, '2026-06-04 15:15:04', '2026-06-04 15:15:04'),
(25, 12, 3, 'موقع تعريفي', 'EGP', NULL, 0.00, '2026-01-08', NULL, 'active', '', 1, '2026-06-04 15:15:25', '2026-06-16 11:32:06'),
(26, 13, 2, 'alsherifgroup.net', 'EGP', NULL, 850.00, '2026-01-08', '2027-01-07', 'active', '', 1, '2026-06-04 15:20:47', '2026-10-12 13:52:11'),
(27, 13, 1, 'G20', 'EGP', NULL, 5000.00, '2026-01-08', '2027-01-07', 'active', '', 1, '2026-06-04 15:21:08', '2026-10-12 13:13:03'),
(28, 14, 2, 'دومين', 'EGP', NULL, 750.00, '2026-01-10', '2027-01-09', 'active', '', 1, '2026-06-04 15:23:00', '2026-10-12 13:52:11'),
(29, 14, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-01-10', '2027-01-09', 'active', '', 1, '2026-06-04 15:23:19', '2026-06-04 15:23:19'),
(30, 15, 2, 'دومين', 'EGP', NULL, 750.00, '2026-01-11', '2027-01-10', 'active', '', 1, '2026-06-04 15:27:51', '2026-10-12 13:52:11'),
(31, 15, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-01-11', '2027-01-10', 'active', '', 1, '2026-06-04 15:28:09', '2026-06-04 15:28:09'),
(32, 16, 2, 'bluesea-metal.com', 'EGP', NULL, 750.00, '2026-01-11', '2027-01-10', 'active', '', 1, '2026-06-04 15:37:49', '2026-10-12 13:52:11'),
(33, 16, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-01-11', '2027-01-10', 'active', '', 1, '2026-06-04 15:38:07', '2026-06-04 15:38:07'),
(34, 17, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-01-15', '2027-01-14', 'active', '', 1, '2026-06-04 15:42:39', '2026-06-04 15:42:39'),
(35, 17, 3, 'موقع تعريفي', 'EGP', NULL, 0.00, '2026-01-15', NULL, 'active', '', 1, '2026-06-04 15:42:56', '2026-06-16 11:32:19'),
(36, 18, 2, 'دومين', 'EGP', NULL, 1050.00, '2026-01-16', '2027-01-15', 'active', '', 1, '2026-06-04 15:45:15', '2026-10-12 13:52:11'),
(37, 18, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-01-16', '2027-01-15', 'active', '', 1, '2026-06-04 15:45:29', '2026-06-04 15:45:29'),
(38, 19, 2, 'دومين', 'EGP', NULL, 750.00, '2026-01-17', '2027-01-16', 'active', '', 1, '2026-06-04 15:48:39', '2026-10-12 13:52:11'),
(39, 19, 1, 'G5', 'EGP', NULL, 2000.00, '2026-01-17', '2027-01-16', 'active', '', 1, '2026-06-04 15:49:12', '2026-10-12 13:13:03'),
(40, 20, 2, 'دومين', 'EGP', NULL, 750.00, '2026-01-19', '2027-01-18', 'active', '', 1, '2026-06-04 15:54:16', '2026-10-12 13:52:11'),
(41, 20, 1, 'G5', 'EGP', NULL, 2000.00, '2026-01-19', '2027-01-18', 'active', '', 1, '2026-06-04 15:54:50', '2026-10-12 13:13:03'),
(42, 21, 2, 'دومين', 'EGP', NULL, 750.00, '2026-01-19', '2027-01-18', 'active', '', 1, '2026-06-04 15:56:33', '2026-10-12 13:52:11'),
(43, 21, 1, 'G5', 'EGP', NULL, 2000.00, '2026-01-19', '2027-01-18', 'active', '', 1, '2026-06-04 15:56:46', '2026-10-12 13:13:03'),
(44, 22, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-01-19', '2027-01-18', 'active', '', 1, '2026-06-04 15:58:27', '2026-06-04 15:58:27'),
(45, 23, 2, 'دومين', 'EGP', NULL, 750.00, '2026-01-19', '2027-01-18', 'active', '', 1, '2026-06-04 15:59:42', '2026-10-12 13:52:11'),
(46, 23, 1, 'G5', 'EGP', NULL, 2000.00, '2026-01-19', '2027-01-18', 'active', '', 1, '2026-06-04 15:59:59', '2026-10-12 13:13:03'),
(47, 24, 2, 'دومين', 'EGP', NULL, 750.00, '2026-01-21', '2027-01-20', 'active', '', 1, '2026-06-04 16:02:08', '2026-10-12 13:52:11'),
(48, 24, 1, 'G5', 'EGP', NULL, 2000.00, '2026-01-21', '2027-01-20', 'active', '', 1, '2026-06-04 16:02:21', '2026-10-12 13:13:03'),
(49, 25, 2, 'دومين', 'EGP', NULL, 750.00, '2026-01-24', '2027-01-23', 'active', '', 1, '2026-06-04 16:05:42', '2026-10-12 13:52:11'),
(50, 25, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-01-24', '2027-01-23', 'active', '', 1, '2026-06-04 16:05:57', '2026-06-04 16:05:57'),
(51, 26, 2, 'دومين', 'EGP', NULL, 750.00, '2026-01-24', '2027-01-23', 'active', '', 1, '2026-06-04 16:09:23', '2026-10-12 13:52:11'),
(52, 26, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-01-24', '2027-01-23', 'active', '', 1, '2026-06-04 16:09:53', '2026-06-04 16:09:53'),
(53, 27, 2, 'دومين', 'EGP', NULL, 1670.00, '2026-01-26', '2027-01-25', 'active', '', 1, '2026-06-04 16:12:33', '2026-10-12 13:52:11'),
(54, 27, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-01-26', '2027-01-25', 'active', '', 1, '2026-06-04 16:12:49', '2026-06-14 13:11:39'),
(55, 28, 2, 'دومين', 'EGP', NULL, 750.00, '2026-01-26', '2027-01-25', 'active', '', 1, '2026-06-04 16:16:48', '2026-10-12 13:52:11'),
(56, 28, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-01-26', '2027-01-25', 'active', '', 1, '2026-06-04 16:17:09', '2026-06-04 16:17:09'),
(60, 29, 1, '06-20G-12000EG-3Domain', 'EGP', NULL, 12000.00, '2026-01-29', '2027-01-28', 'active', '', 1, '2026-06-04 16:33:15', '2026-08-08 10:28:38'),
(61, 30, 2, 'دومين', 'EGP', NULL, 850.00, '2026-02-02', '2027-02-01', 'active', '', 1, '2026-06-04 16:34:33', '2026-10-12 13:52:11'),
(62, 30, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-02-02', '2027-02-01', 'active', '', 1, '2026-06-04 16:34:46', '2026-06-04 16:34:46'),
(63, 31, 2, 'دومين', 'EGP', NULL, 750.00, '2026-02-05', '2027-02-04', 'active', '', 1, '2026-06-04 18:41:23', '2026-10-12 13:52:11'),
(64, 31, 1, '07-5G-950EG', 'EGP', NULL, 950.00, '2026-02-05', '2027-02-04', 'active', '', 1, '2026-06-04 18:42:19', '2026-06-04 18:42:19'),
(65, 32, 2, 'دومين', 'EGP', NULL, 750.00, '2026-02-06', '2027-02-05', 'active', '', 1, '2026-06-04 18:44:45', '2026-10-12 13:52:11'),
(66, 32, 1, '07-5G-950EG', 'EGP', NULL, 950.00, '2026-02-06', '2027-02-05', 'active', '', 1, '2026-06-04 18:45:24', '2026-06-04 18:45:24'),
(67, 33, 2, 'دومين', 'EGP', NULL, 750.00, '2026-02-09', '2027-02-08', 'active', '', 1, '2026-06-04 18:47:16', '2026-10-12 13:52:11'),
(68, 33, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-02-09', '2027-02-08', 'active', '', 1, '2026-06-04 18:47:41', '2026-06-04 18:47:41'),
(70, 34, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-02-10', '2027-02-09', 'active', '', 1, '2026-06-04 18:54:13', '2026-06-04 18:54:13'),
(71, 35, 2, 'دومين', 'EGP', NULL, 750.00, '2026-02-10', '2027-02-09', 'active', '', 1, '2026-06-04 18:56:25', '2026-10-12 13:52:11'),
(72, 35, 1, '07-5G-950EG', 'EGP', NULL, 950.00, '2026-02-10', '2027-02-09', 'active', '', 1, '2026-06-04 18:56:39', '2026-06-04 18:56:39'),
(73, 36, 2, 'دومين', 'EGP', NULL, 750.00, '2026-02-15', '2027-02-14', 'active', '', 1, '2026-06-04 19:04:19', '2026-10-12 13:52:11'),
(74, 36, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-02-15', '2027-02-14', 'active', '', 1, '2026-06-04 19:04:46', '2026-06-04 19:04:46'),
(75, 37, 2, 'دومين', 'EGP', NULL, 750.00, '2026-02-15', '2027-02-14', 'active', '', 1, '2026-06-04 19:07:02', '2026-10-12 13:52:11'),
(76, 37, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-02-15', '2027-02-14', 'active', '', 1, '2026-06-04 19:07:29', '2026-06-04 19:07:29'),
(78, 38, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-02-15', '2027-02-14', 'active', '', 1, '2026-06-04 19:10:54', '2026-06-04 19:10:54'),
(79, 39, 2, 'دومين', 'EGP', NULL, 750.00, '2026-02-15', '2027-02-14', 'active', '', 1, '2026-06-04 19:13:17', '2026-10-12 13:52:11'),
(80, 39, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-02-15', '2027-02-14', 'active', '', 1, '2026-06-04 19:14:13', '2026-06-04 19:14:13'),
(81, 40, 2, 'دومين', 'EGP', NULL, 750.00, '2026-02-19', '2027-02-18', 'active', '', 1, '2026-06-04 19:16:08', '2026-10-12 13:52:11'),
(82, 40, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-02-19', '2027-02-18', 'active', '', 1, '2026-06-04 19:16:46', '2026-06-04 19:16:46'),
(84, 41, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-02-26', '2027-02-25', 'active', '', 1, '2026-06-04 19:20:14', '2026-06-04 19:20:14'),
(85, 42, 2, 'دومين', 'EGP', NULL, 750.00, '2026-02-28', '2027-02-27', 'active', '', 1, '2026-06-04 19:35:01', '2026-10-12 13:52:11'),
(86, 42, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-02-28', '2027-02-27', 'active', '', 1, '2026-06-04 19:35:25', '2026-06-04 19:35:25'),
(87, 43, 2, 'دومين', 'EGP', NULL, 750.00, '2026-03-02', '2027-03-01', 'active', '', 1, '2026-06-04 19:37:12', '2026-10-12 13:52:11'),
(88, 43, 1, 'G5', 'EGP', NULL, 2000.00, '2026-03-02', '2027-03-01', 'active', '', 1, '2026-06-04 19:37:29', '2026-10-12 13:13:03'),
(89, 43, 3, 'موقع تعريفي', 'EGP', NULL, 0.00, '2026-03-02', NULL, 'active', '', 1, '2026-06-04 19:37:45', '2026-06-16 11:32:31'),
(90, 44, 1, 'G10', 'EGP', NULL, 4000.00, '2026-03-03', '2027-03-02', 'active', '', 1, '2026-06-04 19:40:32', '2026-10-12 13:13:03'),
(91, 44, 3, 'موقع تعريفي', 'EGP', NULL, 0.00, '2026-03-03', NULL, 'active', '', 1, '2026-06-04 19:40:54', '2026-06-04 19:40:54'),
(92, 45, 2, 'دومين', 'EGP', NULL, 750.00, '2026-03-09', '2027-03-08', 'active', '', 1, '2026-06-04 19:42:45', '2026-10-12 13:52:11'),
(93, 45, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-03-09', '2027-03-08', 'active', '', 1, '2026-06-04 19:43:07', '2026-06-04 19:43:07'),
(94, 45, 3, 'موقع تعريفي', 'EGP', NULL, 0.00, '2026-03-09', NULL, 'active', '', 1, '2026-06-04 19:44:09', '2026-06-04 19:44:09'),
(95, 46, 2, 'دومين', 'EGP', NULL, 750.00, '2026-03-09', '2027-03-08', 'active', '', 1, '2026-06-04 19:45:28', '2026-10-12 13:52:11'),
(96, 46, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-03-09', '2027-03-08', 'active', '', 1, '2026-06-04 19:45:53', '2026-06-04 19:45:53'),
(97, 46, 3, 'موقع تعريفي', 'EGP', NULL, 0.00, '2026-03-09', NULL, 'active', '', 1, '2026-06-04 19:46:09', '2026-06-04 19:46:09'),
(98, 47, 2, 'دومين', 'EGP', NULL, 750.00, '2026-03-12', '2027-03-11', 'active', '', 1, '2026-06-04 19:49:47', '2026-10-12 13:52:11'),
(99, 47, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-03-12', '2027-03-11', 'active', '', 1, '2026-06-04 19:50:10', '2026-06-04 19:50:10'),
(100, 48, 2, 'دومين', 'EGP', NULL, 750.00, '2026-03-13', '2027-03-12', 'active', '', 1, '2026-06-04 19:51:46', '2026-10-12 13:52:11'),
(101, 48, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-03-13', '2027-03-12', 'active', '', 1, '2026-06-04 19:52:08', '2026-06-04 19:52:08'),
(102, 49, 2, 'دومين', 'EGP', NULL, 750.00, '2026-03-16', '2027-03-15', 'active', '', 1, '2026-06-04 19:54:03', '2026-10-12 13:52:11'),
(103, 49, 1, 'G5', 'EGP', NULL, 2000.00, '2026-03-16', '2027-03-15', 'active', '', 1, '2026-06-04 19:54:20', '2026-10-12 13:13:03'),
(104, 49, 3, 'موقع تعريفي', 'EGP', NULL, 0.00, '2026-03-16', NULL, 'active', '', 1, '2026-06-04 19:54:38', '2026-06-04 19:54:38'),
(105, 50, 2, 'دومين', 'EGP', NULL, 850.00, '2026-03-16', '2027-03-15', 'active', '', 1, '2026-06-04 19:56:30', '2026-10-12 13:52:11'),
(106, 50, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-03-16', '2027-03-15', 'active', '', 1, '2026-06-04 19:56:46', '2026-06-04 19:56:46'),
(108, 51, 2, 'دومين', 'EGP', NULL, 750.00, '2026-03-25', '2027-03-24', 'active', '', 1, '2026-06-04 19:59:32', '2026-10-12 13:52:11'),
(109, 51, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-03-25', '2027-03-24', 'active', '', 1, '2026-06-04 19:59:51', '2026-06-04 19:59:51'),
(110, 70, 2, 'دومين', 'EGP', NULL, 750.00, '2026-03-30', '2027-03-29', 'active', '', 1, '2026-06-04 20:36:10', '2026-10-12 13:52:11'),
(111, 70, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-03-30', '2027-03-29', 'active', '', 1, '2026-06-04 20:36:24', '2026-06-04 20:36:24'),
(112, 70, 3, 'موقع تعريفي', 'EGP', NULL, 0.00, '2026-03-30', NULL, 'active', '', 1, '2026-06-04 20:36:41', '2026-06-04 20:36:41'),
(113, 72, 2, 'دومين', 'EGP', NULL, 750.00, '2026-03-16', '2027-03-15', 'active', '', 1, '2026-06-04 20:41:11', '2026-10-12 13:52:11'),
(114, 72, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-03-16', '2027-03-15', 'active', '', 1, '2026-06-04 20:41:25', '2026-06-04 20:41:25'),
(117, 52, 1, '01-3G-Offer-800EG', 'EGP', NULL, 800.00, '2026-03-26', '2027-03-25', 'active', '', 1, '2026-06-04 20:47:32', '2026-07-17 20:02:39'),
(118, 53, 2, 'دومين', 'EGP', NULL, 750.00, '2026-03-28', '2027-03-27', 'active', '', 1, '2026-06-04 20:50:39', '2026-10-12 13:52:11'),
(119, 53, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-03-28', '2027-03-27', 'active', '', 1, '2026-06-04 20:51:02', '2026-06-04 20:51:02'),
(121, 54, 1, 'G5', 'EGP', NULL, 2000.00, '2026-03-29', '2027-03-28', 'active', '', 1, '2026-06-04 21:23:43', '2026-10-12 13:13:03'),
(122, 55, 2, 'دومين', 'EGP', NULL, 850.00, '2026-03-29', '2027-03-28', 'active', '', 1, '2026-06-04 21:25:44', '2026-10-12 13:52:11'),
(123, 55, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-03-29', '2027-03-28', 'active', '', 1, '2026-06-04 21:26:12', '2026-06-04 21:26:12'),
(124, 57, 2, 'دومين', 'EGP', NULL, 750.00, '2026-04-07', '2027-04-06', 'active', '', 1, '2026-06-04 21:28:16', '2026-10-12 13:52:11'),
(125, 57, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-04-07', '2027-04-06', 'active', '', 1, '2026-06-04 21:28:50', '2026-06-04 21:28:50'),
(126, 58, 2, 'دومين', 'EGP', NULL, 750.00, '2026-04-08', '2027-04-07', 'active', '', 1, '2026-06-04 21:32:18', '2026-10-12 13:52:11'),
(127, 58, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-04-08', '2027-04-07', 'active', '', 1, '2026-06-04 21:32:52', '2026-06-04 21:32:52'),
(128, 59, 2, 'دومين', 'EGP', NULL, 750.00, '2026-04-08', '2027-04-07', 'active', '', 1, '2026-06-04 21:35:16', '2026-10-12 13:52:11'),
(129, 59, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-04-08', '2027-04-07', 'active', '', 1, '2026-06-04 21:35:30', '2026-06-04 21:35:30'),
(131, 60, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-04-13', '2027-04-12', 'active', '', 1, '2026-06-04 21:38:03', '2026-06-05 00:03:36'),
(132, 62, 2, 'دومين', 'EGP', NULL, 750.00, '2026-04-15', '2027-04-14', 'active', '', 1, '2026-06-04 21:39:17', '2026-10-12 13:52:11'),
(133, 62, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-04-15', '2027-04-14', 'active', '', 1, '2026-06-04 21:39:29', '2026-06-04 21:39:29'),
(135, 69, 2, 'دومين', 'EGP', NULL, 800.00, '2026-06-04', '2027-06-03', 'active', '', 1, '2026-06-04 21:42:00', '2026-10-12 13:52:11'),
(136, 69, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-06-04', '2027-06-03', 'active', '', 1, '2026-06-04 21:42:12', '2026-06-04 21:42:12'),
(137, 68, 2, 'دومين', 'EGP', NULL, 800.00, '2026-06-03', '2027-06-02', 'active', '', 1, '2026-06-04 21:44:52', '2026-10-12 13:52:11'),
(138, 68, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-06-03', '2027-06-02', 'active', '', 1, '2026-06-04 21:45:03', '2026-06-04 21:45:03'),
(139, 67, 2, 'دومين', 'EGP', NULL, 800.00, '2026-06-02', '2027-06-01', 'active', '', 1, '2026-06-04 21:45:48', '2026-10-12 13:52:11'),
(140, 67, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-06-02', '2027-06-01', 'active', '', 1, '2026-06-04 21:46:00', '2026-06-04 21:46:00'),
(141, 66, 2, 'plus-adv.com', 'EGP', NULL, 750.00, '2026-05-03', '2027-05-02', 'active', '', 1, '2026-06-04 21:49:55', '2026-10-12 13:52:11'),
(142, 66, 1, 'G5', 'EGP', NULL, 2000.00, '2026-05-03', '2027-05-02', 'active', '', 1, '2026-06-04 21:50:17', '2026-10-12 13:13:03'),
(143, 65, 2, 'transmission-dma.com', 'EGP', NULL, 750.00, '2026-04-22', '2027-04-21', 'active', '', 1, '2026-06-04 21:51:38', '2026-10-12 13:52:11'),
(144, 65, 1, 'G5', 'EGP', NULL, 2000.00, '2026-04-22', '2027-04-21', 'active', '', 1, '2026-06-04 21:51:51', '2026-10-12 13:13:03'),
(145, 56, 2, 'دومين', 'EGP', NULL, 750.00, '2026-03-31', '2027-03-30', 'active', '', 1, '2026-06-04 21:53:55', '2026-10-12 13:52:11'),
(146, 56, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-03-31', '2027-03-30', 'active', '', 1, '2026-06-04 21:54:07', '2026-06-04 21:54:07'),
(147, 71, 2, 'دومين', 'EGP', NULL, 750.00, '2026-04-01', '2027-03-31', 'active', '', 1, '2026-06-04 21:55:27', '2026-10-12 13:52:11'),
(148, 71, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-04-01', '2027-03-31', 'active', '', 1, '2026-06-04 21:55:47', '2026-06-04 21:55:47'),
(149, 61, 2, 'دومين', 'EGP', NULL, 750.00, '2026-04-15', '2027-04-14', 'active', '', 1, '2026-06-04 22:01:02', '2026-10-12 13:52:11'),
(150, 61, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-04-15', '2027-04-14', 'active', '', 1, '2026-06-04 22:01:14', '2026-06-04 22:01:14'),
(151, 63, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-04-17', '2027-04-16', 'active', '', 1, '2026-06-04 22:03:24', '2026-06-04 22:03:24'),
(153, 64, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-04-18', '2027-04-17', 'active', '', 1, '2026-06-04 22:04:49', '2026-06-04 22:05:37'),
(154, 1, 2, 'mishal-lawfirm.com', 'EGP', NULL, 1000.00, '2025-07-16', '2026-07-15', 'expired', '', 1, '2026-06-04 22:20:07', '2026-07-16 11:05:36'),
(155, 29, 2, 'firstair-eg.com', 'EGP', NULL, 1495.00, '2026-04-30', '2027-04-29', 'active', 'firstair-eg.com', 1, '2026-06-05 00:57:37', '2026-10-12 13:52:11'),
(156, 29, 2, 'firstair-sa.com', 'EGP', NULL, 1495.00, '2026-01-17', '2027-01-16', 'active', 'firstair-sa.com', 1, '2026-06-05 00:58:46', '2026-10-12 13:52:11'),
(157, 29, 2, 'first-air-eg.com', 'EGP', NULL, 1495.00, '2026-01-12', '2027-01-11', 'active', 'first-air-eg.com', 1, '2026-06-05 01:06:00', '2026-10-12 13:52:11'),
(158, 74, 2, 'bluenile-group.net', 'EGP', NULL, 800.00, '2026-06-05', '2027-06-04', 'active', '', 1, '2026-06-05 16:42:00', '2026-10-12 13:52:11'),
(159, 74, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-06-05', '2027-06-04', 'active', '', 1, '2026-06-05 16:42:08', '2026-06-05 16:42:08'),
(160, 73, 2, 'enjaz.cloud', 'EGP', NULL, 0.00, '2025-11-04', '2026-11-03', 'expired', '', 1, '2026-06-05 22:59:16', '2026-10-12 12:17:44'),
(161, 73, 2, 'enjaz.app', 'EGP', NULL, 0.00, '2025-10-08', '2026-10-07', 'expired', '', 1, '2026-06-05 23:00:14', '2026-10-03 09:43:24'),
(162, 75, 2, 'blackcherry-wood.com', 'EGP', NULL, 800.00, '2026-06-09', '2027-06-08', 'active', '', 1, '2026-06-09 20:59:08', '2026-10-12 13:52:11'),
(163, 75, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-06-09', '2027-06-08', 'active', '', 1, '2026-06-09 20:59:19', '2026-06-09 20:59:19'),
(164, 76, 2, '3m-services', 'EGP', NULL, 900.00, '2026-06-11', '2027-06-10', 'active', '', 1, '2026-06-11 11:08:30', '2026-10-12 13:52:11'),
(165, 76, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-06-11', '2027-06-10', 'active', '', 1, '2026-06-11 11:08:39', '2026-06-11 11:08:39'),
(167, 73, 1, 'G5', 'EGP', 0.00, 0.00, '2025-07-08', '2026-10-07', 'expired', '', 1, '2026-06-11 20:18:23', '2026-10-12 13:13:03'),
(168, 78, 2, 'raed-alsharq.com', 'EGP', NULL, 900.00, '2026-06-12', '2027-06-11', 'active', '', 1, '2026-06-12 19:00:04', '2026-10-12 13:52:11'),
(169, 78, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-06-12', '2027-06-11', 'active', '', 1, '2026-06-12 19:00:16', '2026-06-12 19:00:16'),
(170, 79, 2, 'ahl-eg.net', 'EGP', NULL, 900.00, '2026-06-13', '2027-06-12', 'active', '', 1, '2026-06-13 10:30:38', '2026-10-12 13:52:11'),
(171, 79, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-06-13', '2027-06-12', 'active', '', 1, '2026-06-13 10:30:48', '2026-06-13 10:30:48'),
(172, 80, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-06-14', '2027-06-13', 'active', '', 1, '2026-06-14 11:26:23', '2026-06-14 11:26:23'),
(173, 81, 2, 'emdad-eg.com', 'EGP', NULL, 900.00, '2026-06-14', '2027-06-13', 'active', '', 1, '2026-06-14 11:44:06', '2026-10-12 13:52:11'),
(174, 81, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-06-14', '2027-06-13', 'active', '', 1, '2026-06-14 11:44:14', '2026-06-14 11:44:14'),
(175, 82, 2, 'artoffice-eg.com', 'EGP', NULL, 900.00, '2026-06-16', '2027-06-15', 'active', '', 1, '2026-06-16 12:39:01', '2026-10-12 13:52:11'),
(176, 82, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-06-16', '2027-06-15', 'active', '', 1, '2026-06-16 12:39:09', '2026-06-16 12:39:09'),
(177, 29, 2, 'firstair-group.com', 'EGP', NULL, 1700.00, '2026-06-19', '2027-06-18', 'active', '', 1, '2026-06-19 15:37:59', '2026-10-12 13:52:11'),
(178, 83, 1, 'G5', 'EGP', NULL, 2000.00, '2026-06-20', '2027-06-19', 'active', '', 1, '2026-06-20 19:57:06', '2026-10-12 13:13:03'),
(179, 84, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-06-21', '2027-06-20', 'active', '', 1, '2026-06-21 12:46:35', '2026-06-21 12:46:35'),
(180, 85, 2, 'esco-transports.com', 'EGP', NULL, 900.00, '2026-06-22', '2027-06-21', 'active', '', 1, '2026-06-22 13:27:35', '2026-10-12 13:52:11'),
(181, 85, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-06-22', '2027-06-21', 'active', '', 1, '2026-06-22 13:27:42', '2026-06-22 13:27:42'),
(182, 86, 2, 'desertrose-eg.com', 'EGP', NULL, 900.00, '2026-06-24', '2027-06-23', 'active', '', 1, '2026-06-23 21:40:26', '2026-10-12 13:52:11'),
(183, 86, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-06-24', '2027-06-23', 'active', '', 1, '2026-06-23 21:40:35', '2026-06-23 21:40:35'),
(184, 87, 2, 'alphamglobal.com', 'EGP', NULL, 900.00, '2026-06-27', '2027-06-26', 'active', '', 1, '2026-06-27 09:44:44', '2026-10-12 13:52:11'),
(185, 87, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-06-27', '2027-06-26', 'active', '', 1, '2026-06-27 09:44:52', '2026-06-27 09:44:52'),
(186, 87, 3, 'موقع تعريفي', 'EGP', NULL, 0.00, '2026-06-27', NULL, 'active', '4000.00', 1, '2026-06-27 09:45:13', '2026-07-17 16:49:14'),
(187, 88, 1, 'G5', 'EGP', NULL, 2000.00, '2026-06-30', '2027-06-29', 'active', '', 1, '2026-06-30 08:56:22', '2026-10-12 13:13:03'),
(188, 89, 2, 'cec-africa.net', 'EGP', NULL, 950.00, '2026-07-05', '2027-07-04', 'active', '', 1, '2026-07-05 11:06:09', '2026-10-12 13:52:11'),
(189, 89, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-07-05', '2027-07-04', 'active', '', 1, '2026-07-05 11:06:16', '2026-07-05 11:06:16'),
(190, 90, 1, 'G5', 'EGP', NULL, 2000.00, '2026-07-07', '2027-07-06', 'active', '', 1, '2026-07-07 09:24:31', '2026-10-12 13:13:03'),
(191, 91, 2, 'rakiza-des.com', 'EGP', NULL, 900.00, '2026-07-08', '2027-07-07', 'active', '', 1, '2026-07-08 10:57:45', '2026-10-12 13:52:11'),
(192, 91, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-07-08', '2027-07-07', 'active', '', 1, '2026-07-08 10:57:52', '2026-07-08 10:57:52'),
(193, 92, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-07-09', '2027-07-08', 'active', '', 1, '2026-07-09 19:43:13', '2026-07-09 19:43:13'),
(194, 93, 2, 'qds-export.com', 'EGP', NULL, 900.00, '2026-07-10', '2027-07-09', 'active', '', 1, '2026-07-10 20:42:24', '2026-10-12 13:52:11'),
(195, 93, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-07-10', '2027-07-09', 'active', '', 1, '2026-07-10 20:42:32', '2026-07-10 20:42:32'),
(196, 94, 2, 'veora-eg.com', 'EGP', NULL, 900.00, '2026-07-11', '2027-07-10', 'active', '', 1, '2026-07-11 16:26:19', '2026-10-12 13:52:11'),
(197, 94, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-07-11', '2027-07-10', 'active', '', 1, '2026-07-11 16:26:26', '2026-07-11 16:26:26'),
(198, 94, 3, 'موقع تعريفي', 'EGP', NULL, 0.00, '2026-07-11', '2027-07-10', 'active', 'Landing Page for free', 1, '2026-07-11 16:26:52', '2026-07-11 16:26:52'),
(199, 95, 2, 'west-engineer.net', 'EGP', NULL, 950.00, '2026-07-12', '2027-07-11', 'active', '', 1, '2026-07-12 14:58:22', '2026-10-12 13:52:11'),
(200, 95, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-07-12', '2027-07-11', 'active', '', 1, '2026-07-12 14:58:28', '2026-07-12 14:58:28'),
(201, 96, 2, 'tadafok-almuzn.com', 'EGP', NULL, 900.00, '2026-07-12', '2027-07-11', 'active', '', 1, '2026-07-12 19:45:55', '2026-10-12 13:52:11'),
(202, 96, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-07-12', '2027-07-11', 'active', '', 1, '2026-07-12 19:46:04', '2026-07-12 19:46:04'),
(203, 97, 2, 'newdealeg.com', 'EGP', NULL, 900.00, '2026-07-13', '2027-07-12', 'active', 'الدومين تم حجزه من خلال مزاد\r\nتم تطبيق رسوم للمزاد قيمتها \r\n1485.15 قبل الضريبة\r\n742.08 تكلفة الدومين لمدة سنة قبل الضريبة\r\nالاجمالي 2227.23 غير شامل الضريبة\r\nالضريبة 323.90\r\n\r\nالاجمالي العام : 2551.13 غير شامل رسوم تدبير العملة الخاصة بالبنك\r\nالاجمالي النهائي 2680 جنيه', 1, '2026-07-13 13:17:59', '2026-10-12 13:52:11'),
(204, 97, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-07-13', '2027-07-12', 'active', '', 1, '2026-07-13 13:18:07', '2026-07-13 13:18:07'),
(205, 98, 2, 'eastline-travel.com', 'EGP', NULL, 900.00, '2026-07-15', '2027-07-14', 'active', '', 1, '2026-07-14 23:22:11', '2026-10-12 13:52:11'),
(206, 98, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-07-15', '2027-07-14', 'active', '', 1, '2026-07-14 23:22:18', '2026-07-14 23:22:18'),
(207, 99, 1, 'G5', 'EGP', NULL, 2000.00, '2026-07-15', '2027-07-14', 'active', '', 1, '2026-07-15 13:13:23', '2026-10-12 13:13:03'),
(208, 101, 2, 'sadara1.com', 'EGP', NULL, 900.00, '2026-07-16', '2027-07-15', 'active', '', 1, '2026-07-16 09:28:11', '2026-10-12 13:52:11'),
(209, 101, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-07-16', '2027-07-15', 'active', '', 1, '2026-07-16 09:28:17', '2026-07-16 09:28:17'),
(210, 1, 2, 'mishal-lawfirm.com', 'EGP', NULL, 1000.00, '2026-07-16', '2027-07-15', 'active', 'تجديد: ', 1, '2026-07-16 11:13:45', '2026-10-12 13:52:11'),
(211, 1, 1, 'G5', 'EGP', NULL, 4000.00, '2026-07-16', '2027-07-15', 'active', 'تجديد: ', 1, '2026-07-16 11:14:07', '2026-10-12 13:13:03'),
(212, 25, 3, 'موقع تعريفي', 'EGP', NULL, 0.00, '2026-01-24', '2027-07-16', 'active', '', 1, '2026-07-17 19:47:54', '2026-08-16 13:53:45'),
(213, 32, 3, 'موقع تعريفي', 'EGP', NULL, 0.00, '2026-02-06', NULL, 'active', '', 1, '2026-07-17 19:48:49', '2026-08-16 13:53:22'),
(215, 91, 3, 'موقع تعريفي', 'EGP', NULL, 0.00, '2026-07-17', NULL, 'active', '', 1, '2026-07-17 19:52:49', '2026-07-17 19:52:49'),
(217, 89, 3, 'موقع تعريفي', 'EGP', NULL, 0.00, '2026-07-17', NULL, 'active', '', 1, '2026-07-17 19:53:47', '2026-07-17 19:53:47'),
(218, 90, 3, 'موقع تعريفي', 'EGP', NULL, 0.00, '2026-07-17', NULL, 'active', '', 1, '2026-07-17 19:55:15', '2026-07-17 19:55:15'),
(219, 100, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-07-18', '2027-07-16', 'active', '', 1, '2026-07-18 12:31:21', '2026-07-18 12:32:10'),
(220, 102, 2, 'mentra-one.com', 'EGP', NULL, 950.00, '2026-07-19', '2027-07-18', 'active', '', 1, '2026-07-19 10:04:08', '2026-10-12 13:52:11'),
(221, 102, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-07-19', '2027-07-18', 'active', '', 1, '2026-07-19 10:04:16', '2026-07-19 10:04:16'),
(222, 103, 2, 'alhaditha-sa.com', 'EGP', NULL, 900.00, '2026-07-19', '2027-07-18', 'active', '', 1, '2026-07-19 18:55:06', '2026-10-12 13:52:11'),
(223, 103, 1, 'G5', 'EGP', NULL, 2000.00, '2026-07-19', '2027-07-18', 'active', '', 1, '2026-07-19 18:55:15', '2026-10-12 13:13:03'),
(224, 104, 2, 'upgrade-group.net', 'EGP', NULL, 950.00, '2026-07-22', '2027-07-21', 'active', '', 1, '2026-07-23 11:57:20', '2026-10-12 13:52:11'),
(225, 104, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-07-22', '2027-07-21', 'active', '', 1, '2026-07-23 11:57:30', '2026-07-23 11:57:30'),
(226, 105, 1, 'G5', 'EGP', NULL, 2000.00, '2026-07-23', '2027-07-22', 'active', '', 1, '2026-07-23 12:31:03', '2026-10-12 13:13:03'),
(227, 97, 4, 'newdealeg.com', 'EGP', NULL, 1750.00, '2026-07-26', '2027-07-25', 'active', '', 1, '2026-07-26 10:16:59', '2026-07-26 10:17:14'),
(228, 107, 1, 'G5', 'EGP', NULL, 2000.00, '2026-07-28', '2027-07-27', 'active', '', 1, '2026-07-27 23:43:15', '2026-10-12 13:13:03'),
(229, 108, 2, 'althiqahalolya.com', 'EGP', NULL, 1000.00, '2026-07-29', '2027-07-28', 'active', '', 1, '2026-07-29 09:36:34', '2026-10-12 13:52:11'),
(230, 108, 1, 'G5', 'EGP', NULL, 2500.00, '2026-07-29', '2027-07-28', 'active', '', 1, '2026-07-29 09:36:51', '2026-10-12 13:13:03'),
(231, 109, 2, 'khamrelsaleheen.com', 'EGP', NULL, 900.00, '2026-08-11', '2027-08-10', 'active', '', 1, '2026-08-11 14:02:05', '2026-10-12 13:52:11'),
(232, 109, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-08-11', '2027-08-10', 'active', '', 1, '2026-08-11 14:02:12', '2026-08-11 14:02:12'),
(233, 110, 1, 'G10', 'SAR', 310.00, 4000.00, '2026-08-13', '2027-08-12', 'active', 'السعر 310 ريال 10 جيجا', 1, '2026-08-13 12:43:35', '2026-09-30 10:25:32'),
(234, 111, 1, 'G5', 'SAR', 190.00, 2500.00, '2026-08-13', '2027-08-12', 'active', 'ما يعادل 190 ريال سعودي', 1, '2026-08-13 20:14:49', '2026-09-30 10:25:53'),
(235, 112, 2, 'fawasilksa.com', 'EGP', NULL, 950.00, '2026-08-15', '2027-08-14', 'active', '', 1, '2026-08-15 13:16:35', '2026-10-12 13:52:11'),
(236, 112, 1, 'G5', 'EGP', NULL, 2000.00, '2026-08-15', '2027-08-14', 'active', '', 1, '2026-08-15 13:16:42', '2026-10-12 13:13:03'),
(237, 113, 2, 'm-carbonate-calcium.com', 'EGP', NULL, 1125.00, '2026-08-16', '2027-08-15', 'active', 'تكلفة الدومين 86 ريال', 1, '2026-08-16 19:52:57', '2026-10-12 13:52:11'),
(238, 113, 1, 'G5', 'SAR', 190.00, 2450.00, '2026-08-16', '2027-08-15', 'active', 'تكلفة الباقة 190 ريال', 1, '2026-08-16 19:53:43', '2026-09-30 10:26:17'),
(239, 114, 2, 'nubkemet.com', 'EGP', NULL, 950.00, '2026-08-17', '2027-08-16', 'active', '', 1, '2026-08-17 05:51:27', '2026-10-12 13:52:11'),
(240, 114, 1, '01-1G-800EG', 'EGP', NULL, 800.00, '2026-08-17', '2027-08-16', 'active', '', 1, '2026-08-17 05:52:14', '2026-08-17 05:52:14'),
(241, 115, 2, 'badaewatanya.com', 'EGP', NULL, 1115.00, '2026-08-20', '2027-08-19', 'active', 'تكلفة الدومين 86 ريال', 1, '2026-08-20 19:50:17', '2026-10-12 13:52:11'),
(242, 115, 1, 'G5', 'SAR', 190.00, 2500.00, '2026-08-20', '2027-08-19', 'active', 'تكلفة الباقة 5 جيجا -- 190 ريال', 1, '2026-08-20 19:51:05', '2026-09-30 10:26:42'),
(244, 117, 2, 'mera-group-eg.com', 'EGP', 950.00, 950.00, '2026-08-26', '2027-08-25', 'active', '', 1, '2026-08-26 16:05:00', '2026-10-12 13:52:11'),
(245, 117, 1, 'G5', 'EGP', 2000.00, 2000.00, '2026-08-26', '2027-08-25', 'active', '', 1, '2026-08-26 16:05:08', '2026-10-12 13:13:03'),
(246, 118, 2, 'alkawader-alamilah.com', 'EGP', 950.00, 900.00, '2026-08-29', '2027-08-28', 'active', '', 1, '2026-08-29 09:39:35', '2026-10-12 13:52:11'),
(247, 118, 1, 'G5', 'EGP', 2000.00, 2000.00, '2026-08-29', '2027-08-28', 'active', '', 1, '2026-08-29 09:39:45', '2026-10-12 13:13:03'),
(248, 119, 2, 'agrivana.net', 'EGP', 950.00, 950.00, '2026-08-29', '2027-08-28', 'active', '', 1, '2026-08-29 09:50:26', '2026-10-12 13:52:11'),
(249, 119, 1, '01-1G-800EG', 'EGP', 2000.00, 800.00, '2026-08-30', '2027-08-28', 'active', '', 1, '2026-08-30 08:56:04', '2026-09-13 09:38:33'),
(250, 120, 2, 'almadinah-almnawarah.com', 'EGP', 950.00, 950.00, '2026-08-30', '2027-08-29', 'active', '', 1, '2026-08-30 14:35:38', '2026-10-12 13:52:11'),
(251, 120, 1, 'G5', 'EGP', 2000.00, 2000.00, '2026-08-30', '2027-08-29', 'active', '', 1, '2026-08-30 14:35:44', '2026-10-12 13:13:03'),
(252, 121, 2, 'dera-alrowad.com', 'EGP', 950.00, 950.00, '2026-09-01', '2027-08-31', 'active', 'العميل مصري دفع بالمصري مع الخصم 15% \r\n950 للدومين + 2125 لباقة الايميلات\r\n__________\r\nلكن تم التأكيد علي العميل انه سيتم المحاسبة بالسعر الرسمي للخدمة\r\n86 ريال للدومين + 190 ريال لباقة الايميلات عند التجديد', 1, '2026-09-01 11:03:13', '2026-10-12 13:52:11'),
(253, 121, 1, 'G5', 'EGP', 2125.00, 2125.00, '2026-09-01', '2027-08-31', 'active', 'العميل مصري دفع بالمصري مع الخصم 15% \r\n950 للدومين + 2125 لباقة الايميلات\r\n__________\r\nلكن تم التأكيد علي العميل انه سيتم المحاسبة بالسعر الرسمي للخدمة\r\n86 ريال للدومين + 190 ريال لباقة الايميلات عند التجديد', 1, '2026-09-01 11:04:43', '2026-10-12 13:13:03'),
(254, 122, 2, 'luxury-boxes.com', 'SAR', 72.00, 950.00, '2026-09-06', '2027-09-05', 'active', 'ما يعادل 72 ريال سعودي', 1, '2026-09-06 12:14:16', '2026-10-12 13:52:11'),
(255, 122, 1, 'G10', 'SAR', 303.00, 4000.00, '2026-09-06', '2027-09-05', 'active', 'ما يعادل 303 ريال سعودي', 1, '2026-09-06 12:14:24', '2026-10-12 13:13:03'),
(256, 123, 2, 'nooraluminum.com', 'SAR', 86.00, 1135.00, '2026-09-06', '2027-09-05', 'active', '', 1, '2026-09-06 13:06:35', '2026-09-06 13:06:35'),
(257, 123, 1, 'G5', 'SAR', 190.00, 2500.00, '2026-09-06', '2027-09-05', 'active', '', 1, '2026-09-06 13:11:13', '2026-09-30 10:24:01'),
(258, 124, 2, 'دومين بالريال', 'SAR', 86.00, 1135.00, '2026-09-06', '2027-09-05', 'active', '', 1, '2026-09-06 14:28:47', '2026-09-06 14:28:47'),
(259, 124, 1, 'G5', 'SAR', 190.00, 2450.00, '2026-09-06', '2027-09-05', 'active', '', 1, '2026-09-06 14:29:10', '2026-09-30 10:23:36'),
(260, 125, 1, 'G10', 'SAR', 310.00, 4000.00, '2026-09-06', '2027-09-05', 'active', '', 1, '2026-09-06 15:23:20', '2026-09-30 10:23:20'),
(261, 123, 3, 'موقع تعريفي', 'SAR', 841.00, 11112.00, '2026-09-06', '2027-09-05', 'active', '', 1, '2026-09-06 15:31:53', '2026-09-10 14:35:02'),
(262, 126, 2, 'sec-surveying.com', 'SAR', 86.00, 1130.00, '2026-09-07', '2027-09-06', 'active', '', 1, '2026-09-07 14:28:31', '2026-09-07 14:28:31'),
(263, 126, 1, 'G5', 'SAR', 190.00, 2500.00, '2026-09-07', '2027-09-06', 'active', '', 1, '2026-09-07 14:28:54', '2026-09-30 10:22:56'),
(264, 119, 3, '4000 جنيه', 'EGP', 4000.00, 3950.00, '2026-09-13', '2027-09-12', 'active', '', 1, '2026-09-13 09:39:19', '2026-09-29 15:08:23'),
(265, 127, 2, 'aljanoub-printing.com', 'SAR', 72.00, 950.00, '2026-09-13', '2027-09-12', 'active', '', 1, '2026-09-13 19:20:46', '2026-10-12 13:52:11'),
(266, 127, 1, 'G20', 'SAR', 606.00, 8000.00, '2026-09-13', '2027-09-12', 'active', '', 1, '2026-09-13 19:21:35', '2026-10-12 13:13:03'),
(267, 129, 2, 'rowadalltruq.com', 'SAR', 86.00, 1150.00, '2026-09-17', '2027-09-16', 'active', '', 1, '2026-09-17 19:57:33', '2026-09-17 19:57:33'),
(268, 129, 1, 'G5', 'SAR', 190.00, 2450.00, '2026-09-17', '2027-09-16', 'active', '', 1, '2026-09-17 19:57:55', '2026-09-30 10:22:09'),
(270, 131, 1, 'G5', 'SAR', 190.00, 2450.00, '2026-09-20', '2027-09-19', 'active', '', 1, '2026-09-20 14:15:28', '2026-09-30 10:21:54'),
(271, 132, 2, 'albishrigroup.com', 'SAR', 86.00, 1130.00, '2026-09-21', '2027-09-20', 'active', '', 1, '2026-09-21 10:05:10', '2026-09-21 10:05:10'),
(272, 132, 1, 'G5', 'SAR', 190.00, 2450.00, '2026-09-21', '2027-09-20', 'active', '', 1, '2026-09-21 10:05:25', '2026-09-30 10:21:15'),
(273, 133, 2, 'mbm-co.net', 'SAR', 86.00, 1130.00, '2026-09-21', '2027-09-20', 'active', '', 1, '2026-09-21 10:10:20', '2026-09-21 10:10:20'),
(274, 133, 1, 'G5', 'SAR', 190.00, 2450.00, '2026-09-21', '2027-09-20', 'active', '', 1, '2026-09-21 10:10:46', '2026-09-30 10:21:03'),
(275, 134, 2, 'ondiinternational.com', 'SAR', 86.00, 1130.00, '2026-09-22', '2027-09-21', 'active', '', 1, '2026-09-22 13:50:29', '2026-09-22 13:50:29'),
(276, 134, 1, 'G5', 'SAR', 190.00, 2450.00, '2026-09-22', '2027-09-21', 'active', '', 1, '2026-09-22 13:51:03', '2026-09-30 10:20:47'),
(277, 135, 2, 'celiainfra.com', 'EGP', 500.00, 500.00, '2026-09-26', '2027-09-25', 'active', 'تم عمل خصم للدومين بقيمة 450ج \r\nبدلا من دفع 950 تم دفع 500 ولكن تم الاتفاق علي ان التجديد سيكون بالسعر الرسمي 1350ج', 1, '2026-09-26 19:04:20', '2026-10-12 13:52:11'),
(278, 135, 1, 'G5', 'EGP', 2000.00, 2000.00, '2026-09-26', '2027-09-25', 'active', '', 1, '2026-09-26 19:04:30', '2026-10-12 13:13:03'),
(279, 136, 2, 'mka-ksa.com', 'EGP', 949.99, 500.00, '2026-09-28', '2027-09-27', 'active', 'تم الاتفاق معاه ومع م / احمد عبد الشافي علي ان التجديد يكون بالسعر الرسمي حوالي 1350ج تقريبا', 1, '2026-09-28 14:18:51', '2026-10-12 13:52:11'),
(280, 136, 1, 'G5', 'EGP', 2000.00, 2000.00, '2026-09-28', '2027-09-27', 'active', '', 1, '2026-09-28 14:19:00', '2026-10-12 13:13:03'),
(281, 137, 2, 'law-gate.net', 'EGP', 950.00, 950.00, '2026-09-28', '2027-09-27', 'active', '', 1, '2026-09-28 14:21:58', '2026-10-12 13:52:11'),
(282, 137, 1, '01-1G-800EG', 'EGP', 800.00, 800.00, '2026-09-28', '2027-09-27', 'active', '', 1, '2026-09-28 14:22:07', '2026-09-28 14:22:07'),
(283, 138, 2, 'simply-moment.com', 'SAR', 86.00, 1130.00, '2026-09-30', '2027-09-29', 'active', '', 1, '2026-09-30 10:10:48', '2026-09-30 10:10:48'),
(284, 138, 1, 'G5', 'SAR', 190.00, 2450.00, '2026-09-30', '2027-09-29', 'active', '', 1, '2026-09-30 10:11:11', '2026-09-30 10:26:58'),
(285, 73, 2, 'enjaz.app', 'EGP', NULL, 0.00, '2026-10-03', '2027-10-02', 'active', 'تجديد: تم التجديد', 1, '2026-10-03 09:43:24', '2026-10-12 13:52:11'),
(286, 73, 1, 'G5', 'EGP', NULL, 0.00, '2026-10-03', '2027-10-02', 'active', 'تجديد: تم التجديد', 1, '2026-10-03 09:43:40', '2026-10-12 13:13:03'),
(287, 139, 2, 'دومين', 'EGP', 950.00, 950.00, '2026-10-05', '2027-10-04', 'active', '', 1, '2026-10-05 15:05:49', '2026-10-12 13:52:11'),
(288, 139, 1, 'G5', 'EGP', 2000.00, 2000.00, '2026-10-05', '2027-10-04', 'active', '', 1, '2026-10-05 15:06:03', '2026-10-12 13:13:03'),
(289, 140, 2, 'io-strategy.com', 'EGP', 950.00, 950.00, '2026-10-10', '2027-10-09', 'active', '', 1, '2026-10-10 12:29:06', '2026-10-12 13:52:11'),
(290, 140, 1, 'G5', 'EGP', 2000.00, 2000.00, '2026-10-10', '2027-10-09', 'active', '', 1, '2026-10-10 12:29:13', '2026-10-12 13:13:03'),
(291, 141, 2, 'kemetksa.com', 'EGP', 950.00, 950.00, '2026-09-29', '2027-09-28', 'active', '', 1, '2026-10-10 13:23:24', '2026-10-12 13:52:11'),
(292, 141, 1, 'G5', 'EGP', 2000.00, 2000.00, '2026-09-29', '2027-09-28', 'active', '', 1, '2026-10-10 13:23:31', '2026-10-12 13:13:03'),
(293, 73, 2, 'enjaz.cloud', 'EGP', 0.00, 0.00, '2026-10-07', '2027-10-06', 'active', 'تجديد:', 1, '2026-10-12 12:17:44', '2026-10-12 13:52:11'),
(294, 142, 2, 'samaalttaqa.com', 'SAR', 86.00, 1200.00, '2026-10-14', '2027-10-13', 'active', '', 1, '2026-10-14 15:33:59', '2026-10-14 15:33:59'),
(295, 142, 1, 'G5', 'SAR', 190.00, 2600.00, '2026-10-14', '2027-10-13', 'active', '', 1, '2026-10-14 15:34:26', '2026-10-14 15:34:26'),
(296, 143, 2, 'dag-sa.com', 'EGP', 500.00, 500.00, '2026-10-15', '2027-10-14', 'active', '', 1, '2026-10-16 11:15:50', '2026-10-16 11:16:58'),
(297, 143, 1, 'G5', 'EGP', 2000.00, 2000.00, '2026-10-15', '2027-10-14', 'active', '', 1, '2026-10-16 11:16:04', '2026-10-16 11:16:52'),
(298, 141, 2, 'kemet-sa.com', 'EGP', 950.00, 950.00, '2026-10-19', '2027-10-18', 'active', '', 1, '2026-10-19 14:58:36', '2026-10-19 14:58:36'),
(299, 144, 2, 'egy-star.com', 'EGP', 950.00, 950.00, '2026-10-23', '2027-10-22', 'active', '', 1, '2026-10-22 22:02:01', '2026-10-22 22:02:01'),
(300, 144, 1, 'G5', 'EGP', 2000.00, 2000.00, '2026-10-23', '2027-10-22', 'active', '', 1, '2026-10-22 22:02:15', '2026-10-22 22:02:15'),
(301, 145, 2, 'onionpts.com', 'EGP', 950.00, 950.00, '2026-10-24', '2027-10-23', 'active', '', 1, '2026-10-24 14:10:53', '2026-10-24 14:10:53'),
(302, 145, 1, 'G5', 'EGP', 2000.00, 2000.00, '2026-10-24', '2027-10-23', 'active', '', 1, '2026-10-24 14:11:03', '2026-10-24 14:11:03'),
(303, 146, 1, 'G10', 'EGP', 4000.00, 4000.00, '2026-10-27', '2027-10-26', 'active', '', 1, '2026-10-27 09:10:52', '2026-10-27 09:10:52'),
(304, 147, 1, '01-1G-800EG', 'EGP', 800.00, 800.00, '2026-10-27', '2027-10-26', 'active', '', 1, '2026-10-27 12:24:04', '2026-10-27 12:24:04'),
(305, 148, 2, 'powersaving-sa.com', 'SAR', 86.00, 1260.00, '2026-10-28', '2027-10-27', 'active', '', 1, '2026-10-28 08:49:48', '2026-10-28 08:49:48'),
(306, 148, 1, 'G5', 'SAR', 190.00, 2800.00, '2026-10-28', '2027-10-27', 'active', 'سعر الريال 14.75 جنيه', 1, '2026-10-28 08:50:28', '2026-10-28 08:50:28'),
(307, 149, 2, 'albashamodern.com', 'SAR', 86.00, 1250.00, '2026-10-29', '2027-10-28', 'active', '14.75', 1, '2026-10-29 09:16:50', '2026-10-29 09:16:50'),
(308, 149, 1, 'G5', 'SAR', 190.00, 2800.00, '2026-10-29', '2027-10-28', 'active', '14.75', 1, '2026-10-29 09:17:17', '2026-10-29 09:17:17');

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `expense_date` date NOT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'أخرى',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` int UNSIGNED DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `title`, `amount`, `expense_date`, `category`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'حجز دومين smartsource-eg.com', 750.00, '2025-12-28', 'دومين', '', 1, '2026-06-05 01:13:32', '2026-06-05 01:20:29'),
(2, 'حجز دومين royalsmart-rs.com', 750.00, '2025-12-30', 'دومين', '', 1, '2026-06-05 01:16:19', '2026-06-05 01:18:34'),
(3, 'حجز دومين stars-smcs.com', 720.00, '2025-12-30', 'دومين', '', 1, '2026-06-05 01:16:50', '2026-06-05 01:18:44'),
(4, 'حجز دومين namaa-invests.com', 750.00, '2025-12-31', 'دومين', '', 1, '2026-06-05 01:19:12', '2026-06-05 01:21:08'),
(5, 'حجز دومين onboarding4u.com', 720.00, '2026-01-08', 'دومين', '', 1, '2026-06-05 01:44:07', '2026-06-05 01:44:07'),
(6, 'حجز دومين alsherifgroup.net', 835.00, '2026-01-08', 'دومين', '', 1, '2026-06-05 01:44:07', '2026-06-05 01:44:07'),
(7, 'حجز دومين eltahady-trade.com', 720.00, '2026-01-10', 'دومين', '', 1, '2026-06-05 01:44:07', '2026-06-05 01:44:07'),
(8, 'حجز دومين arabhousevent.com', 720.00, '2026-01-11', 'دومين', '', 1, '2026-06-05 01:44:07', '2026-06-05 01:44:07'),
(9, 'حجز دومين bluesea-metal.com', 720.00, '2026-01-11', 'دومين', '', 1, '2026-06-05 01:44:07', '2026-06-05 01:44:07'),
(10, 'حجز دومين admissions-office-eg.com', 1035.00, '2026-01-16', 'دومين', '', 1, '2026-06-05 01:44:07', '2026-06-05 01:44:07'),
(11, 'حجز دومين tm-medical.net', 720.00, '2026-01-17', 'دومين', '', 1, '2026-06-05 01:44:07', '2026-06-05 01:44:07'),
(12, 'حجز دومين amdconst.com', 720.00, '2026-01-19', 'دومين', '', 1, '2026-06-05 01:44:07', '2026-06-05 01:44:07'),
(13, 'حجز دومين hemmatwatn.com', 720.00, '2026-01-19', 'دومين', '', 1, '2026-06-05 01:44:07', '2026-06-05 01:44:07'),
(14, 'حجز دومين gladiousscape.com', 720.00, '2026-01-19', 'دومين', '', 1, '2026-06-05 01:44:07', '2026-06-05 01:44:07'),
(15, 'حجز دومين dimec-eg.com', 720.00, '2026-01-21', 'دومين', '', 1, '2026-06-05 01:44:07', '2026-06-05 01:44:07'),
(16, 'حجز دومين  veraagency-eg.com', 720.00, '2026-01-24', 'دومين', '', 1, '2026-06-05 01:44:07', '2026-06-05 01:44:07'),
(17, 'حجز دومين advancedtech-eg.com', 720.00, '2026-01-24', 'دومين', '', 1, '2026-06-05 01:44:07', '2026-06-05 01:44:07'),
(18, 'حجز دومين valvelink.co', 1660.00, '2026-01-25', 'دومين', '', 1, '2026-06-05 01:44:07', '2026-06-05 01:44:07'),
(19, 'حجز دومين alexair-eg.com', 720.00, '2026-01-26', 'دومين', '', 1, '2026-06-05 01:44:07', '2026-06-05 01:44:07'),
(20, 'حجز دومين shariha.net', 835.00, '2026-02-02', 'دومين', '', 1, '2026-06-05 01:44:07', '2026-06-05 01:44:07'),
(21, 'حجز دومين metalpwr.com', 720.00, '2026-02-05', 'دومين', '', 1, '2026-06-05 01:44:07', '2026-06-05 01:44:07'),
(22, 'حجز دومين oxyva-eg.com', 720.00, '2026-02-06', 'دومين', '', 1, '2026-06-05 01:44:07', '2026-06-05 01:44:07'),
(23, 'حجز دومين ovo-way.com', 720.00, '2026-02-09', 'دومين', '', 1, '2026-06-05 01:44:07', '2026-06-05 01:44:07'),
(24, 'حجز دومين exception-co.com', 720.00, '2026-02-10', 'دومين', '', 1, '2026-06-05 01:44:07', '2026-06-05 01:44:07'),
(25, 'حجز دومين elhassan-ps.com', 720.00, '2026-02-15', 'دومين', '', 1, '2026-06-05 01:44:07', '2026-06-05 01:44:07'),
(26, 'حجز دومين makkahest.com', 720.00, '2026-02-15', 'دومين', '', 1, '2026-06-05 01:44:07', '2026-06-05 01:44:07'),
(27, 'حجز دومين  nourtiba-eg.com', 720.00, '2026-02-15', 'دومين', '', 1, '2026-06-05 01:44:07', '2026-06-05 01:44:07'),
(28, 'حجز دومين abpromedical.com', 720.00, '2026-02-19', 'دومين', '', 1, '2026-06-05 01:44:07', '2026-06-05 01:44:07'),
(29, 'حجز دومين primegatetech.com', 720.00, '2026-02-28', 'دومين', '', 1, '2026-06-05 01:44:07', '2026-06-05 01:44:07'),
(30, 'حجز دومين almotaheda-eng.com', 720.00, '2026-03-02', 'دومين', '', 1, '2026-06-05 01:44:07', '2026-06-05 01:44:07'),
(31, 'حجز دومين alkayan-trans.com', 720.00, '2026-03-09', 'دومين', '', 1, '2026-06-05 01:44:07', '2026-06-05 01:44:07'),
(32, 'حجز دومين fattahfoundation.com', 720.00, '2026-03-09', 'دومين', '', 1, '2026-06-05 01:44:07', '2026-06-05 01:44:07'),
(33, 'حجز دومين enbridge-eg.com', 720.00, '2026-03-12', 'دومين', '', 1, '2026-06-05 01:44:07', '2026-06-05 01:44:07'),
(34, 'حجز دومين oujajsalt.com', 720.00, '2026-03-13', 'دومين', '', 1, '2026-06-05 01:44:07', '2026-06-05 01:44:07'),
(35, 'حجز دومين whitegroup-eng.com', 720.00, '2026-03-16', 'دومين', '', 1, '2026-06-05 01:44:07', '2026-06-05 01:44:07'),
(36, 'حجز دومين menshawy-group.com', 720.00, '2026-03-16', 'دومين', '', 1, '2026-06-05 01:44:07', '2026-06-05 01:44:07'),
(37, 'حجز دومين procreativity.net', 835.00, '2026-03-16', 'دومين', '', 1, '2026-06-05 01:44:07', '2026-06-05 01:44:07'),
(38, 'حجز دومين egy-fruitex.com', 720.00, '2026-03-25', 'دومين', '', 1, '2026-06-05 01:44:07', '2026-06-05 01:44:07'),
(39, 'حجز دومين g-tech-solution.com', 720.00, '2026-03-28', 'دومين', '', 1, '2026-06-05 01:44:07', '2026-06-05 01:44:07'),
(40, 'حجز دومين future-green.net', 835.00, '2026-03-29', 'دومين', '', 1, '2026-06-05 01:44:07', '2026-06-05 01:44:07'),
(41, 'حجز دومين alkayanlogistics.com', 720.00, '2026-03-30', 'دومين', '', 1, '2026-06-05 01:44:07', '2026-06-05 01:44:07'),
(42, 'حجز دومين snoby-eg.com', 720.00, '2026-03-31', 'دومين', '', 1, '2026-06-05 01:44:07', '2026-06-05 01:44:07'),
(43, 'حجز دومين MadarMEP.com', 720.00, '2026-04-01', 'دومين', '', 1, '2026-06-05 01:44:07', '2026-06-05 01:44:07'),
(44, 'حجز دومين omega-supplies.com', 720.00, '2026-04-07', 'دومين', '', 1, '2026-06-05 01:44:07', '2026-06-05 01:44:07'),
(45, 'حجز دومين myriad-co.com', 720.00, '2026-04-08', 'دومين', '', 1, '2026-06-05 01:44:07', '2026-06-05 01:44:07'),
(46, 'حجز دومين  seniorconstructions.com', 720.00, '2026-04-08', 'دومين', '', 1, '2026-06-05 01:44:07', '2026-06-05 01:44:07'),
(47, 'حجز دومين alnasem-eg.com', 720.00, '2026-04-15', 'دومين', '', 1, '2026-06-05 01:44:07', '2026-06-05 01:44:07'),
(48, 'حجز دومين medx-hub.com', 720.00, '2026-04-15', 'دومين', '', 1, '2026-06-05 01:44:07', '2026-06-05 01:44:07'),
(49, 'حجز دومين transmission-dma.com', 720.00, '2026-04-22', 'دومين', '', 1, '2026-06-05 01:44:07', '2026-06-05 01:44:07'),
(50, 'حجز دومين  plus-adv.com', 720.00, '2026-05-03', 'دومين', '', 1, '2026-06-05 01:44:07', '2026-06-05 01:44:07'),
(51, 'حجز دومين  alfa-petroleum.com', 760.00, '2026-06-02', 'دومين', '', 1, '2026-06-05 01:44:07', '2026-06-05 01:44:07'),
(52, 'حجز دومين eldewanya.com', 760.00, '2026-06-03', 'دومين', '', 1, '2026-06-05 01:44:07', '2026-06-05 01:44:07'),
(53, 'حجز دومين infinity-logistic.net', 760.00, '2026-06-04', 'دومين', '', 1, '2026-06-05 01:44:07', '2026-06-05 01:44:07'),
(54, 'حجز دومين bluenile-group.net', 865.00, '2026-06-05', 'دومين', 'تم تحصيل 800 جنيه فقط من العميل', 1, '2026-06-05 20:31:53', '2026-06-05 20:31:53'),
(55, 'حجز دومين blackcherry-wood.com', 780.00, '2026-06-10', 'دومين', '', 1, '2026-06-09 21:02:02', '2026-06-09 21:02:02'),
(56, 'حجز دومين 3m-services.net', 895.00, '2026-06-11', 'دومين', '', 1, '2026-06-11 11:13:57', '2026-06-11 11:13:57'),
(57, 'حجز دومين raed-alsharq.com', 780.00, '2026-06-12', 'دومين', '', 1, '2026-06-12 19:01:25', '2026-06-12 19:01:25'),
(58, 'حجز دومين ahl-eg.net', 866.00, '2026-06-13', 'دومين', '', 1, '2026-06-13 10:32:17', '2026-06-13 10:32:17'),
(59, 'حجز دومين emdad-eg.com', 775.00, '2026-06-14', 'دومين', '', 1, '2026-06-14 11:44:47', '2026-06-14 11:44:47'),
(60, 'حجز دومين artoffice-eg.com', 775.00, '2026-06-16', 'دومين', '', 1, '2026-06-16 12:40:52', '2026-06-16 12:40:52'),
(61, 'حجز دومين enjaz.cloud', 1000.00, '2026-06-16', 'دومين', '', 1, '2026-06-16 20:44:46', '2026-06-16 20:44:46'),
(62, 'حجز دومين enjaz.app', 1000.00, '2026-06-16', 'دومين', '', 1, '2026-06-16 20:45:10', '2026-06-16 20:45:10'),
(63, 'حجز دومين nogom-services.com', 720.00, '2025-12-30', 'دومين', '', 1, '2026-06-16 20:46:52', '2026-06-16 20:46:52'),
(64, 'حجز دومين mishal-lawfirm.com', 850.00, '2025-07-04', 'دومين', '', 1, '2026-06-16 20:47:54', '2026-06-16 20:47:54'),
(65, 'حجز دومين firstair-eg.com', 1305.00, '2026-04-30', 'دومين', '', 1, '2026-06-16 20:49:11', '2026-06-16 20:49:11'),
(66, 'حجز دومين firstair-sa.com', 1305.00, '2026-01-17', 'دومين', '', 1, '2026-06-16 20:50:05', '2026-06-16 20:50:05'),
(67, 'حجز دومين first-air-eg.com', 1305.00, '2026-01-12', 'دومين', '', 1, '2026-06-16 20:50:48', '2026-06-16 20:50:48'),
(68, 'حجز دومين firstair-group.com', 775.00, '2026-06-19', 'دومين', '', 1, '2026-06-19 15:38:30', '2026-06-19 15:38:30'),
(69, 'حجز دومين esco-transports.com', 775.00, '2026-06-22', 'دومين', '', 1, '2026-06-22 13:28:16', '2026-06-22 13:28:16'),
(70, 'حجز دومين desertrose-eg.com', 775.00, '2026-06-24', 'دومين', '', 1, '2026-06-23 21:41:21', '2026-06-23 21:41:21'),
(71, 'حجز دومين cec-africa.net', 875.00, '2026-07-05', 'دومين', '', 1, '2026-07-05 14:33:12', '2026-07-05 14:33:12'),
(72, 'حجز دومين rakiza-des.com', 700.00, '2026-07-08', 'دومين', '', 1, '2026-07-08 13:01:26', '2026-07-08 13:01:26'),
(73, 'حجز دومين qds-export.com', 710.00, '2026-07-10', 'دومين', '', 1, '2026-07-10 20:44:21', '2026-07-10 20:44:21'),
(74, 'حجز دومين veora-eg.com', 700.00, '2026-07-11', 'دومين', '', 1, '2026-07-11 17:24:52', '2026-07-11 17:24:52'),
(75, 'حجز دومين west-engineer.net', 880.00, '2026-07-12', 'دومين', '', 1, '2026-07-12 14:59:22', '2026-07-12 14:59:22'),
(76, 'حجز دومين tadafok-almuzn.com', 710.00, '2026-07-12', 'دومين', '', 1, '2026-07-12 19:48:54', '2026-07-12 19:48:54'),
(77, 'حجز دومين mishal-lawfirm.com', 750.00, '2026-07-14', 'دومين', '', 1, '2026-07-14 15:51:06', '2026-07-14 15:51:06'),
(78, 'حجز دومين sadara1.com', 730.00, '2026-07-16', 'دومين', '', 1, '2026-07-16 09:30:08', '2026-07-16 09:30:08'),
(79, 'حجز الدومين upgrade-group.net', 903.00, '2026-07-23', 'دومين', '', 1, '2026-07-23 11:59:28', '2026-07-23 11:59:36'),
(80, 'حجز دومين mentra-one.com', 725.00, '2026-07-23', 'دومين', '', 1, '2026-07-23 12:00:35', '2026-07-23 12:00:35'),
(81, 'حجز دومين alhaditha-sa.com', 903.00, '2026-07-23', 'دومين', '', 1, '2026-07-23 12:01:07', '2026-07-23 12:01:07'),
(82, 'حجز دومين newdealeg.com', 1100.00, '2026-07-13', 'دومين', '', 1, '2026-08-02 14:10:51', '2026-08-15 15:25:52'),
(83, 'حجز دومين althiqahalolya.com', 725.00, '2026-08-02', 'دومين', '', 1, '2026-08-02 14:12:31', '2026-08-02 14:12:31'),
(84, 'حجز دومين fawasilksa.com', 725.00, '2026-08-15', 'دومين', '', 1, '2026-08-15 15:21:52', '2026-08-15 15:21:52'),
(85, 'حجز دومين khamrelsaleheen.com', 725.00, '2026-08-10', 'دومين', '', 1, '2026-08-15 15:22:32', '2026-08-15 15:22:32'),
(86, 'حجز دومين eastline-travel.com', 725.00, '2026-08-15', 'دومين', '', 1, '2026-08-15 15:29:55', '2026-08-15 15:29:55'),
(87, 'حجز دومين badaewatanya.com', 725.00, '2026-08-21', 'دومين', '', 1, '2026-08-21 15:31:13', '2026-08-21 15:31:13'),
(88, 'حجز دومين nubkemet.com', 725.00, '2026-08-21', 'دومين', '', 1, '2026-08-21 15:31:39', '2026-08-21 15:31:39'),
(89, 'حجز دومين m-carbonate-calcium.com', 725.00, '2026-08-21', 'دومين', '', 1, '2026-08-21 15:32:04', '2026-08-21 15:32:04'),
(90, 'حجز دومين alkawader-alamilah.com', 726.00, '2026-08-29', 'دومين', '', 1, '2026-08-29 09:51:10', '2026-08-29 09:53:22'),
(91, 'حجز دومين agrivana.net', 897.00, '2026-08-29', 'دومين', '', 1, '2026-08-29 09:53:15', '2026-08-29 09:53:15'),
(92, 'حجز دومين almadinah-almnawarah.com', 726.00, '2026-08-30', 'دومين', '', 1, '2026-08-30 14:38:01', '2026-08-30 14:38:01'),
(93, 'حجز دومين dera-alrowad.com', 726.00, '2026-09-01', 'دومين', '', 1, '2026-09-01 18:35:27', '2026-09-01 18:35:27'),
(94, 'حجز دومين luxury-boxes.com', 600.00, '2026-09-06', 'دومين', '', 1, '2026-09-06 12:15:53', '2026-09-06 12:15:53'),
(95, 'حجز دومين rovtecgroup.com', 600.00, '2026-09-06', 'دومين', '', 1, '2026-09-06 14:35:34', '2026-09-06 14:35:34'),
(96, 'حجز دومين nooraluminum.com', 600.00, '2026-09-06', 'دومين', '', 1, '2026-09-06 14:36:10', '2026-09-06 14:36:10'),
(97, 'حجز دومين sec-surveying.com', 600.00, '2026-09-07', 'دومين', '', 1, '2026-09-07 13:37:11', '2026-09-07 13:37:11'),
(98, 'حجز دومين aljanoub-printing.com', 600.00, '2026-09-14', 'دومين', '', 1, '2026-09-14 18:28:52', '2026-09-14 18:28:52'),
(99, 'حجز دومين rowadalltruq.com', 605.00, '2026-09-20', 'دومين', '', 1, '2026-09-20 14:25:08', '2026-09-20 14:25:08'),
(100, 'حجز دومين albishrigroup.com', 611.00, '2026-09-21', 'دومين', '', 1, '2026-09-21 10:05:53', '2026-09-21 10:05:53'),
(101, 'حجز دومين mbm-co.net', 930.00, '2026-09-21', 'دومين', '', 1, '2026-09-21 10:06:41', '2026-09-21 10:06:41'),
(102, 'حجز دومين ondiinternational.com', 608.00, '2026-09-22', 'دومين', '', 1, '2026-09-22 14:17:11', '2026-09-22 14:17:11'),
(103, 'اعلانات فيسبوك شهر ديسمبر 2025', 4901.98, '2025-12-31', 'إعلانات', '', 1, '2026-09-22 14:24:07', '2026-09-22 14:24:07'),
(104, 'اعلانات فيسبوك شهر يناير 2026', 5197.00, '2026-01-31', 'إعلانات', '', 1, '2026-09-22 14:24:40', '2026-09-22 14:24:40'),
(105, 'اعلانات فيسبوك شهر فبراير 2026', 3000.00, '2026-02-28', 'إعلانات', '', 1, '2026-09-22 14:25:33', '2026-09-22 14:25:44'),
(106, 'اعلانات فيسبوك شهر مارس 2026', 5000.00, '2026-03-31', 'إعلانات', '', 1, '2026-09-22 14:26:19', '2026-09-22 14:26:27'),
(107, 'اعلانات فيسبوك شهر أبريل 2026', 2000.00, '2026-04-30', 'إعلانات', '', 1, '2026-09-22 14:26:56', '2026-09-22 14:27:58'),
(108, 'اعلانات فيسبوك شهر مايو 2026', 2000.00, '2026-05-31', 'إعلانات', '', 1, '2026-09-22 14:27:19', '2026-09-22 14:27:19'),
(109, 'اعلانات فيسبوك شهر يونيو 2026', 8000.00, '2026-06-30', 'إعلانات', '', 1, '2026-09-22 14:28:16', '2026-09-22 14:28:44'),
(110, 'اعلانات فيسبوك شهر يوليو 2026', 7000.00, '2026-07-31', 'إعلانات', '', 1, '2026-09-22 14:29:27', '2026-09-22 14:30:12'),
(111, 'اعلانات فيسبوك شهر أغسطس 2026', 13524.71, '2026-08-31', 'إعلانات', '', 1, '2026-09-22 14:29:57', '2026-09-22 14:30:19'),
(112, 'حجز دومين celiainfra.com', 608.00, '2026-09-26', 'دومين', '', 1, '2026-09-26 19:08:11', '2026-09-26 19:08:19'),
(113, 'حجز دومين mka-ksa.com', 610.00, '2026-09-30', 'دومين', '', 1, '2026-09-30 10:37:59', '2026-09-30 10:37:59'),
(114, 'حجز دومين law-gate.net', 925.00, '2026-09-30', 'دومين', '', 1, '2026-09-30 10:38:40', '2026-09-30 10:38:40'),
(115, 'حجز دومين simply-moment.com', 610.00, '2026-09-30', 'دومين', '', 1, '2026-09-30 10:39:04', '2026-09-30 10:39:04'),
(116, 'تجديد الدويمن enjaz.app', 1245.00, '2026-10-03', 'دومين', '', 1, '2026-10-03 09:44:10', '2026-10-03 09:44:10'),
(117, 'حجز دومين kemetksa.com', 610.00, '2026-09-29', 'دومين', '', 1, '2026-10-10 13:24:59', '2026-10-10 13:24:59'),
(118, 'حجز دومين rakaezalomran.com', 610.00, '2026-10-05', 'دومين', '', 1, '2026-10-10 13:25:34', '2026-10-10 13:26:11'),
(119, 'حجز دومين io-strategy.com', 610.00, '2026-10-10', 'دومين', '', 1, '2026-10-10 13:25:54', '2026-10-10 13:25:54'),
(120, 'حجز دومين dag-sa.com', 625.00, '2026-10-15', 'دومين', '', 1, '2026-10-16 11:18:16', '2026-10-16 11:18:16'),
(121, 'حجز دومين samaalttaqa.com', 625.00, '2026-10-13', 'دومين', '', 1, '2026-10-16 11:18:50', '2026-10-16 11:18:50'),
(122, 'حجز دومين kemet-sa.com', 628.00, '2026-10-19', 'دومين', '', 1, '2026-10-19 15:00:49', '2026-10-19 15:00:49'),
(123, 'حجز دومين egy-star.com', 555.00, '2026-10-23', 'دومين', '', 1, '2026-10-22 22:03:05', '2026-10-22 22:03:05'),
(124, 'حجز دومين powersaving-sa.com', 555.00, '2026-10-28', 'دومين', '', 1, '2026-10-29 09:12:07', '2026-10-29 09:12:17'),
(125, 'حجز دومين albashamodern.com', 555.00, '2026-10-29', 'دومين', '', 1, '2026-10-29 09:17:43', '2026-10-29 09:17:43');

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int UNSIGNED NOT NULL,
  `invoice_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'رقم الفاتورة (INV-0001)',
  `client_id` int UNSIGNED NOT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `paid_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `remaining` decimal(10,2) GENERATED ALWAYS AS ((`total_amount` - `paid_amount`)) STORED,
  `items` json NOT NULL COMMENT 'بنود الفاتورة',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` enum('draft','sent','paid','partial','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `created_by` int UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `invoice_number`, `client_id`, `total_amount`, `paid_amount`, `items`, `notes`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'INV-0001', 90, 2000.00, 2000.00, '[{\"end\": \"2027-07-06\", \"plan\": \"02-5G-2000EG\", \"price\": \"2000.00\", \"start\": \"2026-07-07\", \"service\": \"استضافة البريد الإلكتروني\"}, {\"end\": null, \"plan\": \"موقع تعريفي\", \"price\": \"0.00\", \"start\": \"2026-07-17\", \"service\": \"تصميم المواقع\"}]', '', 'sent', 1, '2026-07-17 19:55:49', '2026-07-17 19:55:49');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int UNSIGNED NOT NULL,
  `client_id` int UNSIGNED NOT NULL,
  `subscription_id` int UNSIGNED DEFAULT NULL COMMENT 'مرتبط باشتراك محدد (اختياري)',
  `amount` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'كاش',
  `reference_number` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'رقم الإيصال أو التحويل',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `receipt_file` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `client_id`, `subscription_id`, `amount`, `payment_date`, `payment_method`, `reference_number`, `notes`, `receipt_file`, `created_by`, `created_at`) VALUES
(2, 1, 2, 23000.00, '2025-07-16', 'انستا باي', '', '', NULL, 1, '2026-06-04 13:32:22'),
(3, 1, 1, 2000.00, '2025-07-16', 'انستا باي', '', '', NULL, 1, '2026-06-04 13:32:58'),
(4, 2, 3, 2000.00, '2025-12-07', 'transfer', '', '', NULL, 1, '2026-06-04 13:43:12'),
(5, 3, 4, 800.00, '2025-12-11', 'انستا باي', '', '', NULL, 1, '2026-06-04 14:10:19'),
(6, 4, 6, 800.00, '2025-12-21', 'transfer', '', '', NULL, 1, '2026-06-04 14:17:53'),
(7, 5, 7, 2000.00, '2025-12-22', 'transfer', '', '', NULL, 1, '2026-06-04 14:20:10'),
(9, 6, 10, 750.00, '2025-12-28', 'transfer', '', '', NULL, 1, '2026-06-04 14:26:37'),
(10, 6, 11, 2000.00, '2025-12-28', 'انستا باي', '', '', 'uploads/receipts/1780583738_6a218d3a17cf6.jpeg', 1, '2026-06-04 14:26:54'),
(11, 7, 13, 750.00, '2025-12-30', 'انستا باي', '', '', NULL, 1, '2026-06-04 14:42:06'),
(12, 7, 14, 800.00, '2025-12-30', 'انستا باي', '', '', NULL, 1, '2026-06-04 14:42:34'),
(13, 8, 17, 720.00, '2025-12-30', 'انستا باي', '', 'nogom-services.com', NULL, 1, '2026-06-04 14:48:43'),
(14, 8, 15, 720.00, '2025-12-30', 'انستا باي', '', 'stars-smcs.com', NULL, 1, '2026-06-04 14:49:11'),
(15, 8, 16, 2000.00, '2025-12-30', 'انستا باي', '', '', NULL, 1, '2026-06-04 14:49:35'),
(16, 9, 18, 750.00, '2025-12-31', 'انستا باي', '', '', NULL, 1, '2026-06-04 14:53:31'),
(17, 9, 19, 800.00, '2025-12-31', 'انستا باي', '', '', NULL, 1, '2026-06-04 14:54:00'),
(19, 10, 21, 800.00, '2026-01-04', 'انستا باي', '', '', NULL, 1, '2026-06-04 15:08:25'),
(20, 11, 22, 2000.00, '2026-01-05', 'انستا باي', '', '', NULL, 1, '2026-06-04 15:12:16'),
(21, 12, 23, 750.00, '2026-01-08', 'انستا باي', '', '', NULL, 1, '2026-06-04 15:15:42'),
(22, 12, 24, 800.00, '2026-01-08', 'انستا باي', '', '', NULL, 1, '2026-06-04 15:16:04'),
(23, 13, 26, 850.00, '2026-01-08', 'انستا باي', '', '', NULL, 1, '2026-06-04 15:21:26'),
(24, 13, 27, 5000.00, '2026-01-08', 'انستا باي', '', '', NULL, 1, '2026-06-04 15:21:43'),
(25, 14, 28, 750.00, '2026-01-10', 'انستا باي', '', '', NULL, 1, '2026-06-04 15:23:35'),
(26, 15, 30, 750.00, '2026-01-11', 'انستا باي', '', '', NULL, 1, '2026-06-04 15:29:16'),
(27, 16, 32, 750.00, '2026-01-11', 'انستا باي', '', '', NULL, 1, '2026-06-04 15:39:54'),
(28, 16, 33, 800.00, '2026-01-11', 'انستا باي', '', '', NULL, 1, '2026-06-04 15:40:08'),
(29, 17, 34, 800.00, '2026-01-15', 'انستا باي', '', '', NULL, 1, '2026-06-04 15:43:30'),
(30, 18, 36, 1050.00, '2026-01-16', 'انستا باي', '', '', NULL, 1, '2026-06-04 15:45:54'),
(31, 18, 37, 800.00, '2026-01-16', 'انستا باي', '', '', NULL, 1, '2026-06-04 15:46:17'),
(32, 19, 38, 750.00, '2026-01-17', 'انستا باي', '', '', NULL, 1, '2026-06-04 15:48:59'),
(33, 19, 39, 2000.00, '2026-01-17', 'انستا باي', '', '', NULL, 1, '2026-06-04 15:49:28'),
(34, 20, 40, 750.00, '2026-01-19', 'انستا باي', '', '', NULL, 1, '2026-06-04 15:55:05'),
(35, 20, 41, 2000.00, '2026-01-19', 'انستا باي', '', '', NULL, 1, '2026-06-04 15:55:26'),
(36, 21, 42, 750.00, '2026-01-19', 'انستا باي', '', '', NULL, 1, '2026-06-04 15:57:04'),
(37, 21, 43, 2000.00, '2026-01-19', 'انستا باي', '', '', NULL, 1, '2026-06-04 15:57:18'),
(38, 22, 44, 800.00, '2026-01-19', 'انستا باي', '', '', NULL, 1, '2026-06-04 15:58:44'),
(39, 23, 45, 750.00, '2026-01-19', 'انستا باي', '', '', NULL, 1, '2026-06-04 16:00:22'),
(40, 23, 46, 2000.00, '2026-01-19', 'انستا باي', '', '', NULL, 1, '2026-06-04 16:00:42'),
(41, 24, 47, 750.00, '2026-01-21', 'انستا باي', '', '', NULL, 1, '2026-06-04 16:02:36'),
(42, 24, 48, 2000.00, '2026-01-21', 'انستا باي', '', '', NULL, 1, '2026-06-04 16:02:53'),
(43, 25, 49, 750.00, '2026-01-24', 'انستا باي', '', '', NULL, 1, '2026-06-04 16:06:18'),
(44, 25, 50, 800.00, '2026-01-24', 'انستا باي', '', '', NULL, 1, '2026-06-04 16:06:36'),
(45, 26, 51, 750.00, '2026-01-24', 'انستا باي', '', '', NULL, 1, '2026-06-04 16:10:17'),
(46, 27, 53, 1670.00, '2026-01-26', 'انستا باي', '', '', NULL, 1, '2026-06-04 16:13:43'),
(47, 27, 54, 800.00, '2026-01-26', 'انستا باي', '', '', NULL, 1, '2026-06-04 16:14:45'),
(48, 28, 55, 750.00, '2026-01-26', 'انستا باي', '', '', NULL, 1, '2026-06-04 16:17:57'),
(49, 28, 56, 800.00, '2026-01-26', 'انستا باي', '', '', NULL, 1, '2026-06-04 16:18:25'),
(50, 30, 61, 850.00, '2026-02-02', 'انستا باي', '', '', NULL, 1, '2026-06-04 16:34:59'),
(51, 30, 62, 800.00, '2026-02-02', 'انستا باي', '', '', NULL, 1, '2026-06-04 16:35:14'),
(52, 31, 63, 750.00, '2026-02-05', 'انستا باي', '', '', NULL, 1, '2026-06-04 18:42:53'),
(53, 31, 64, 950.00, '2026-02-05', 'انستا باي', '', '', NULL, 1, '2026-06-04 18:43:25'),
(54, 32, 65, 750.00, '2026-02-06', 'انستا باي', '', '', NULL, 1, '2026-06-04 18:45:41'),
(55, 32, 66, 950.00, '2026-02-06', 'انستا باي', '', '', NULL, 1, '2026-06-04 18:45:59'),
(56, 33, 67, 750.00, '2026-02-09', 'انستا باي', '', '', NULL, 1, '2026-06-04 18:48:35'),
(57, 34, 70, 800.00, '2026-02-10', 'انستا باي', '', '', NULL, 1, '2026-06-04 18:54:40'),
(58, 35, 71, 750.00, '2026-02-10', 'انستا باي', '', '', NULL, 1, '2026-06-04 18:57:27'),
(59, 35, 72, 950.00, '2026-02-10', 'انستا باي', '', '', NULL, 1, '2026-06-04 18:58:04'),
(60, 36, 73, 750.00, '2026-02-15', 'انستا باي', '', '', NULL, 1, '2026-06-04 19:05:02'),
(61, 37, 75, 750.00, '2026-02-15', 'انستا باي', '', '', NULL, 1, '2026-06-04 19:08:20'),
(62, 38, 78, 800.00, '2026-02-15', 'انستا باي', '', '', NULL, 1, '2026-06-04 19:11:11'),
(63, 39, 79, 750.00, '2026-02-15', 'انستا باي', '', '', NULL, 1, '2026-06-04 19:14:34'),
(64, 39, 80, 800.00, '2026-02-15', 'انستا باي', '', '', NULL, 1, '2026-06-04 19:14:52'),
(65, 40, 81, 750.00, '2026-02-19', 'انستا باي', '', '', NULL, 1, '2026-06-04 19:17:00'),
(66, 40, 82, 800.00, '2026-02-19', 'انستا باي', '', '', NULL, 1, '2026-06-04 19:17:14'),
(67, 41, 84, 800.00, '2026-02-26', 'انستا باي', '', '', NULL, 1, '2026-06-04 19:20:29'),
(68, 42, 85, 750.00, '2026-02-28', 'انستا باي', '', '', NULL, 1, '2026-06-04 19:35:50'),
(69, 42, 86, 800.00, '2026-02-28', 'انستا باي', '', '', NULL, 1, '2026-06-04 19:36:03'),
(70, 43, 87, 750.00, '2026-03-02', 'انستا باي', '', '', NULL, 1, '2026-06-04 19:38:06'),
(71, 43, 88, 2000.00, '2026-03-02', 'انستا باي', '', '', NULL, 1, '2026-06-04 19:38:17'),
(72, 44, 90, 4000.00, '2026-03-03', 'انستا باي', '', '', NULL, 1, '2026-06-04 19:41:11'),
(73, 45, 92, 750.00, '2026-03-09', 'انستا باي', '', '', NULL, 1, '2026-06-04 19:43:23'),
(74, 45, 93, 800.00, '2026-03-09', 'انستا باي', '', '', NULL, 1, '2026-06-04 19:43:38'),
(75, 46, 95, 750.00, '2026-03-09', 'انستا باي', '', '', NULL, 1, '2026-06-04 19:46:30'),
(76, 46, 96, 800.00, '2026-03-09', 'انستا باي', '', '', NULL, 1, '2026-06-04 19:46:50'),
(77, 47, 98, 750.00, '2026-03-12', 'انستا باي', '', '', NULL, 1, '2026-06-04 19:50:26'),
(78, 47, 99, 800.00, '2026-03-12', 'انستا باي', '', '', NULL, 1, '2026-06-04 19:50:40'),
(79, 48, 100, 750.00, '2026-03-13', 'انستا باي', '', '', NULL, 1, '2026-06-04 19:52:37'),
(80, 48, 101, 800.00, '2026-03-13', 'انستا باي', '', '', NULL, 1, '2026-06-04 19:52:58'),
(81, 49, 102, 750.00, '2026-03-16', 'انستا باي', '', '', NULL, 1, '2026-06-04 19:54:57'),
(82, 49, 103, 2000.00, '2026-03-16', 'انستا باي', '', '', NULL, 1, '2026-06-04 19:55:10'),
(83, 50, 105, 850.00, '2026-03-16', 'انستا باي', '', '', NULL, 1, '2026-06-04 19:57:26'),
(84, 50, 106, 800.00, '2026-03-16', 'انستا باي', '', '', NULL, 1, '2026-06-04 19:57:52'),
(85, 51, 108, 750.00, '2026-03-25', 'انستا باي', '', '', NULL, 1, '2026-06-04 20:00:06'),
(86, 51, 109, 800.00, '2026-03-25', 'انستا باي', '', '', NULL, 1, '2026-06-04 20:00:20'),
(87, 70, 110, 750.00, '2026-03-30', 'انستا باي', '', '', NULL, 1, '2026-06-04 20:36:57'),
(88, 70, 111, 800.00, '2026-03-30', 'انستا باي', '', '', NULL, 1, '2026-06-04 20:37:13'),
(89, 72, 114, 800.00, '2026-03-16', 'انستا باي', '', '', NULL, 1, '2026-06-04 20:41:58'),
(90, 52, 117, 800.00, '2026-03-26', 'انستا باي', '', '', NULL, 1, '2026-06-04 20:48:20'),
(91, 53, 118, 750.00, '2026-03-28', 'انستا باي', '', '', NULL, 1, '2026-06-04 20:51:17'),
(92, 53, 119, 800.00, '2026-03-28', 'انستا باي', '', '', NULL, 1, '2026-06-04 20:51:31'),
(93, 54, 121, 2000.00, '2026-03-29', 'انستا باي', '', '', NULL, 1, '2026-06-04 21:23:58'),
(94, 55, 122, 850.00, '2026-03-29', 'انستا باي', '', '', NULL, 1, '2026-06-04 21:26:31'),
(95, 55, 123, 800.00, '2026-03-29', 'انستا باي', '', '', NULL, 1, '2026-06-04 21:26:57'),
(96, 57, 124, 750.00, '2026-04-07', 'انستا باي', '', '', NULL, 1, '2026-06-04 21:29:15'),
(97, 57, 125, 800.00, '2026-04-07', 'انستا باي', '', '', NULL, 1, '2026-06-04 21:29:27'),
(98, 58, 126, 750.00, '2026-04-08', 'انستا باي', '', '', NULL, 1, '2026-06-04 21:33:12'),
(99, 58, 127, 800.00, '2026-04-08', 'انستا باي', '', '', NULL, 1, '2026-06-04 21:33:34'),
(100, 59, 129, 800.00, '2026-04-08', 'انستا باي', '', '', NULL, 1, '2026-06-04 21:35:47'),
(101, 60, 131, 800.00, '2026-04-13', 'انستا باي', '', '', NULL, 1, '2026-06-04 21:38:34'),
(102, 62, 132, 750.00, '2026-04-15', 'انستا باي', '', '', NULL, 1, '2026-06-04 21:40:12'),
(103, 69, 135, 800.00, '2026-06-04', 'انستا باي', '', '', NULL, 1, '2026-06-04 21:42:22'),
(104, 69, 136, 800.00, '2026-06-04', 'انستا باي', '', '', NULL, 1, '2026-06-04 21:42:34'),
(105, 68, 137, 800.00, '2026-06-03', 'انستا باي', '', '', NULL, 1, '2026-06-04 21:45:15'),
(106, 67, 139, 800.00, '2026-06-02', 'انستا باي', '', '', NULL, 1, '2026-06-04 21:46:21'),
(107, 66, 141, 750.00, '2026-05-03', 'انستا باي', '', '', NULL, 1, '2026-06-04 21:50:37'),
(108, 66, 142, 2000.00, '2026-05-03', 'انستا باي', '', '', NULL, 1, '2026-06-04 21:50:53'),
(109, 65, 143, 750.00, '2026-04-22', 'انستا باي', '', '', NULL, 1, '2026-06-04 21:52:06'),
(110, 65, 144, 2000.00, '2026-04-22', 'انستا باي', '', '', NULL, 1, '2026-06-04 21:52:27'),
(111, 56, 145, 750.00, '2026-03-31', 'انستا باي', '', '', NULL, 1, '2026-06-04 21:54:24'),
(112, 56, 146, 800.00, '2026-03-31', 'انستا باي', '', '', NULL, 1, '2026-06-04 21:54:41'),
(113, 71, 147, 750.00, '2026-04-01', 'انستا باي', '', '', NULL, 1, '2026-06-04 21:56:55'),
(114, 71, 148, 800.00, '2026-04-01', 'انستا باي', '', '', NULL, 1, '2026-06-04 21:57:29'),
(115, 61, 149, 750.00, '2026-04-15', 'انستا باي', '', '', NULL, 1, '2026-06-04 22:01:32'),
(116, 61, 150, 800.00, '2026-04-15', 'انستا باي', '', '', NULL, 1, '2026-06-04 22:01:49'),
(117, 63, 151, 800.00, '2026-04-17', 'انستا باي', '', '', NULL, 1, '2026-06-04 22:03:37'),
(118, 64, 153, 800.00, '2026-04-18', 'انستا باي', '', '', NULL, 1, '2026-06-04 22:05:24'),
(119, 29, 60, 7000.00, '2026-01-29', 'انستا باي', '', '', NULL, 1, '2026-06-04 23:20:10'),
(120, 29, 156, 1495.00, '2026-01-17', 'انستا باي', '', '', NULL, 1, '2026-06-05 01:09:51'),
(121, 29, 157, 1495.00, '2026-01-12', 'انستا باي', '', '', NULL, 1, '2026-06-05 01:10:14'),
(122, 29, 155, 1495.00, '2026-04-30', 'انستا باي', '', '', NULL, 1, '2026-06-05 01:10:32'),
(123, 74, 158, 800.00, '2026-06-05', 'فودافون كاش', '', '', 'uploads/receipts/1780677764_6a22fc84b7539.jpeg', 1, '2026-06-05 16:42:44'),
(124, 75, 162, 800.00, '2026-06-10', 'انستا باي', '', '', NULL, 1, '2026-06-09 21:06:18'),
(125, 76, 164, 900.00, '2026-06-11', 'انستا باي', '', 'تم تحويل المبلغ الاجمالي دفعة واحدة', NULL, 1, '2026-06-11 11:09:17'),
(126, 76, 165, 800.00, '2026-06-11', 'انستا باي', '', 'تم تحويل المبلغ الاجمالي دفعة واحدة', NULL, 1, '2026-06-11 11:09:28'),
(127, 78, 168, 900.00, '2026-06-12', 'انستا باي', '', '', NULL, 1, '2026-06-12 19:00:28'),
(128, 79, 170, 900.00, '2026-06-13', 'انستا باي', '', '', NULL, 1, '2026-06-13 10:30:59'),
(129, 67, 140, 800.00, '2026-06-13', 'انستا باي', '', '', NULL, 1, '2026-06-13 11:33:54'),
(130, 59, 128, 750.00, '2026-06-13', 'انستا باي', '', '', NULL, 1, '2026-06-13 12:12:51'),
(131, 79, 171, 800.00, '2026-06-13', 'انستا باي', '', '', NULL, 1, '2026-06-13 16:32:00'),
(132, 81, 173, 900.00, '2026-06-14', 'انستا باي', '', '', NULL, 1, '2026-06-14 11:44:23'),
(133, 75, 163, 800.00, '2026-06-16', 'انستا باي', '', '', NULL, 1, '2026-06-16 09:45:08'),
(134, 82, 175, 900.00, '2026-06-16', 'انستا باي', '', '', NULL, 1, '2026-06-16 12:39:24'),
(135, 80, 172, 800.00, '2026-06-17', 'انستا باي', '', '', NULL, 1, '2026-06-17 09:52:47'),
(136, 84, 179, 800.00, '2026-06-21', 'فودافون كاش', '', '', NULL, 1, '2026-06-21 13:42:28'),
(137, 83, 178, 2000.00, '2026-06-21', 'فودافون كاش', '', '', NULL, 1, '2026-06-21 13:45:55'),
(138, 85, 180, 900.00, '2026-06-22', 'انستا باي', '', '', NULL, 1, '2026-06-22 13:27:55'),
(139, 86, 182, 900.00, '2026-06-24', 'انستا باي', '', '', NULL, 1, '2026-06-23 21:40:49'),
(140, 87, 184, 900.00, '2026-06-27', 'انستا باي', '', '', NULL, 1, '2026-06-27 09:45:27'),
(141, 87, 185, 100.00, '2026-06-27', 'انستا باي', '', '', NULL, 1, '2026-06-27 09:45:36'),
(142, 87, 185, 700.00, '2026-06-27', 'انستا باي', '', '', NULL, 1, '2026-06-27 12:43:53'),
(143, 85, 181, 800.00, '2026-06-29', 'انستا باي', '', '', NULL, 1, '2026-06-29 11:15:07'),
(144, 88, 187, 2000.00, '2026-06-30', 'انستا باي', '', '', NULL, 1, '2026-06-30 08:56:36'),
(145, 89, 188, 950.00, '2026-07-05', 'انستا باي', '', '', NULL, 1, '2026-07-05 11:06:27'),
(146, 89, 189, 800.00, '2026-07-05', 'انستا باي', '', '', NULL, 1, '2026-07-05 11:06:35'),
(147, 82, 176, 800.00, '2026-07-05', 'انستا باي', '', '', NULL, 1, '2026-07-05 16:55:29'),
(148, 81, 174, 800.00, '2026-07-05', 'انستا باي', '', '', NULL, 1, '2026-07-05 19:00:24'),
(149, 78, 169, 800.00, '2026-07-06', 'انستا باي', '', '', NULL, 1, '2026-07-05 22:30:40'),
(150, 91, 191, 900.00, '2026-07-08', 'انستا باي', '', '', NULL, 1, '2026-07-08 10:58:03'),
(151, 91, 192, 800.00, '2026-07-08', 'انستا باي', '', '', NULL, 1, '2026-07-08 10:58:14'),
(152, 90, 190, 2000.00, '2026-07-08', 'انستا باي', '', '', NULL, 1, '2026-07-08 17:06:54'),
(153, 93, 194, 900.00, '2026-07-10', 'انستا باي', '', '', NULL, 1, '2026-07-10 20:42:41'),
(154, 93, 195, 800.00, '2026-07-11', 'انستا باي', '', '', NULL, 1, '2026-07-10 21:02:22'),
(155, 94, 196, 900.00, '2026-07-11', 'انستا باي', '', '', NULL, 1, '2026-07-11 16:27:01'),
(156, 94, 197, 100.00, '2026-07-11', 'انستا باي', '', '', NULL, 1, '2026-07-11 16:27:11'),
(157, 92, 193, 800.00, '2026-07-11', 'انستا باي', '', '', NULL, 1, '2026-07-11 16:38:07'),
(158, 95, 199, 950.00, '2026-07-12', 'انستا باي', '', '', NULL, 1, '2026-07-12 14:58:37'),
(159, 94, 197, 700.00, '2026-07-12', 'انستا باي', '', '', NULL, 1, '2026-07-12 15:12:31'),
(160, 96, 201, 900.00, '2026-07-12', 'انستا باي', '', '', NULL, 1, '2026-07-12 19:46:17'),
(161, 96, 202, 800.00, '2026-07-12', 'انستا باي', '', '', NULL, 1, '2026-07-12 19:46:27'),
(162, 74, 159, 800.00, '2026-07-14', 'انستا باي', '', '', NULL, 1, '2026-07-14 15:46:46'),
(163, 98, 205, 900.00, '2026-07-15', 'انستا باي', '', '', NULL, 1, '2026-07-14 23:22:29'),
(164, 98, 206, 800.00, '2026-07-15', 'انستا باي', '', '', NULL, 1, '2026-07-14 23:22:40'),
(165, 101, 208, 900.00, '2026-07-16', 'انستا باي', '', '', NULL, 1, '2026-07-16 09:28:27'),
(166, 100, 219, 800.00, '2026-07-16', 'انستا باي', '', '', NULL, 1, '2026-07-18 12:31:34'),
(167, 100, NULL, 150.00, '2026-07-18', 'انستا باي', '', '', NULL, 1, '2026-07-18 12:32:28'),
(168, 86, 183, 800.00, '2026-07-19', 'انستا باي', '', '', NULL, 1, '2026-07-19 11:47:40'),
(169, 104, 224, 950.00, '2026-07-23', 'انستا باي', '', '', NULL, 1, '2026-07-23 11:57:41'),
(170, 104, 225, 800.00, '2026-07-23', 'انستا باي', '', '', NULL, 1, '2026-07-23 11:57:59'),
(171, 101, 209, 800.00, '2026-07-26', 'انستا باي', '', '', NULL, 1, '2026-07-26 10:09:24'),
(172, 97, 204, 800.00, '2026-07-26', 'انستا باي', '', '', NULL, 1, '2026-07-26 10:13:44'),
(173, 97, 203, 900.00, '2026-07-26', 'انستا باي', '', '', NULL, 1, '2026-07-26 10:14:02'),
(174, 97, 227, 1750.00, '2026-07-26', 'انستا باي', '', '', NULL, 1, '2026-07-26 10:17:30'),
(175, 108, 229, 1000.00, '2026-07-29', 'انستا باي', '', '', NULL, 1, '2026-07-29 09:37:01'),
(176, 107, 228, 2000.00, '2026-07-29', 'انستا باي', '', '', NULL, 1, '2026-07-29 10:47:56'),
(177, 108, 230, 2500.00, '2026-07-29', 'انستا باي', '', '', NULL, 1, '2026-07-29 13:19:57'),
(178, 105, 226, 2000.00, '2026-07-30', 'انستا باي', '', '', NULL, 1, '2026-07-29 21:00:32'),
(179, 103, 222, 900.00, '2026-07-30', 'انستا باي', '', '', NULL, 1, '2026-07-29 21:00:51'),
(180, 103, 223, 2000.00, '2026-07-30', 'انستا باي', '', '', NULL, 1, '2026-07-29 21:00:59'),
(181, 102, 220, 950.00, '2026-08-02', 'انستا باي', '', '', NULL, 1, '2026-08-02 09:46:19'),
(182, 102, 221, 800.00, '2026-08-02', 'انستا باي', '', '', NULL, 1, '2026-08-02 09:46:26'),
(183, 29, 177, 1700.00, '2026-08-08', 'انستا باي', '', 'الايصال ب 6700 الدومين firstair-group.com ب 1700 جنيه اضافة 10 جيجا ب 5000 جنيه', 'uploads/receipts/1786185085_6a77057d506bc.jpeg', 1, '2026-08-08 10:26:33'),
(184, 29, 60, 5000.00, '2026-08-08', 'انستا باي', '', 'الايصال ب 6700\r\nالدومين firstair-group.com ب 1700 جنيه\r\nاضافة 10 جيجا ب 5000 جنيه', 'uploads/receipts/1786185041_6a770551189b1.jpeg', 1, '2026-08-08 10:30:41'),
(185, 95, 200, 800.00, '2026-08-10', 'انستا باي', '', '', NULL, 1, '2026-08-10 18:43:57'),
(186, 109, 231, 900.00, '2026-08-11', 'انستا باي', '', '', NULL, 1, '2026-08-11 14:02:21'),
(187, 99, 207, 2000.00, '2026-08-12', 'انستا باي', '', '', NULL, 1, '2026-08-12 10:32:22'),
(188, 110, 233, 4000.00, '2026-08-13', 'انستا باي', '', '', 'uploads/receipts/1786625045_6a7dbc158e40f.pdf', 1, '2026-08-13 12:44:05'),
(189, 112, 235, 950.00, '2026-08-15', 'فودافون كاش', '', 'تم الارسال من بنك المشرق الي المحفظة الالكترونية', NULL, 1, '2026-08-15 13:17:12'),
(190, 112, 236, 2000.00, '2026-08-16', 'انستا باي', '', '', NULL, 1, '2026-08-16 12:26:26'),
(191, 113, 237, 1125.00, '2026-08-16', 'انستا باي', '', '86 ريال', NULL, 1, '2026-08-16 19:54:00'),
(192, 114, 239, 950.00, '2026-08-17', 'فودافون كاش', '', '', NULL, 1, '2026-08-17 05:52:51'),
(193, 111, 234, 2500.00, '2026-08-18', 'فودافون كاش', '', 'ما يعادل 190  ريال', NULL, 1, '2026-08-17 21:23:18'),
(194, 115, 241, 1115.00, '2026-08-20', 'انستا باي', '', 'تكلفة الدومين 86 ريال', NULL, 1, '2026-08-20 19:51:32'),
(195, 115, 242, 2500.00, '2026-08-22', 'انستا باي', '', '190 ريال', NULL, 1, '2026-08-22 10:15:42'),
(196, 117, 245, 950.00, '2026-08-26', 'انستا باي', '', '', NULL, 1, '2026-08-26 16:05:17'),
(197, 117, NULL, 2000.00, '2026-08-27', 'انستا باي', '', '', NULL, 1, '2026-08-27 12:16:08'),
(198, 120, 250, 950.00, '2026-08-30', 'فودافون كاش', '', '', NULL, 1, '2026-08-30 14:35:54'),
(199, 121, 252, 950.00, '2026-09-01', 'انستا باي', '', 'العميل مصري دفع بالمصري مع الخصم 15% \r\n950 للدومين + 2125 لباقة الايميلات\r\n__________\r\nلكن تم التأكيد علي العميل انه سيتم المحاسبة بالسعر الرسمي للخدمة\r\n86 ريال للدومين + 190 ريال لباقة الايميلات عند التجديد', NULL, 1, '2026-09-01 11:05:14'),
(200, 121, 253, 2125.00, '2026-09-01', 'انستا باي', '', 'العميل مصري دفع بالمصري مع الخصم 15% \r\n950 للدومين + 2125 لباقة الايميلات\r\n__________\r\nلكن تم التأكيد علي العميل انه سيتم المحاسبة بالسعر الرسمي للخدمة\r\n86 ريال للدومين + 190 ريال لباقة الايميلات عند التجديد', NULL, 1, '2026-09-01 11:05:24'),
(201, 118, 246, 900.00, '2026-09-06', 'انستا باي', '', '', NULL, 1, '2026-09-06 13:32:23'),
(202, 118, 247, 2000.00, '2026-09-06', 'انستا باي', '', '', NULL, 1, '2026-09-06 13:32:32'),
(203, 124, 258, 1135.00, '2026-09-06', 'انستا باي', '', 'بنك الراجحي', NULL, 1, '2026-09-06 14:29:24'),
(204, 124, 259, 2450.00, '2026-09-06', 'انستا باي', '', 'بنك الراجحي', NULL, 1, '2026-09-06 19:10:54'),
(205, 125, 260, 4000.00, '2026-09-07', 'انستا باي', '', 'STC Pay', NULL, 1, '2026-09-07 08:52:36'),
(206, 109, 232, 800.00, '2026-09-07', 'انستا باي', '', '', NULL, 1, '2026-09-07 09:05:52'),
(207, 126, 262, 1130.00, '2026-09-07', 'انستا باي', '', '', NULL, 1, '2026-09-07 14:29:18'),
(208, 123, 256, 1135.00, '2026-09-07', 'انستا باي', '', '', NULL, 1, '2026-09-07 19:32:19'),
(209, 123, 257, 2500.00, '2026-09-07', 'انستا باي', '', '', NULL, 1, '2026-09-07 19:32:33'),
(210, 123, NULL, 11112.00, '2026-09-07', 'انستا باي', '', '', NULL, 1, '2026-09-07 19:33:15'),
(211, 114, 240, 800.00, '2026-09-11', 'انستا باي', '', '', NULL, 1, '2026-09-10 21:53:33'),
(212, 120, 251, 2000.00, '2026-09-13', 'انستا باي', '', '', NULL, 1, '2026-09-13 09:38:07'),
(213, 119, 248, 950.00, '2026-09-14', 'انستا باي', '', 'دفع 2000ج وباقي تكلفة تصميم الموقع من اصل 5750ج', NULL, 1, '2026-09-14 18:17:47'),
(214, 119, 249, 800.00, '2026-09-14', 'انستا باي', '', 'دفع 2000ج وباقي تكلفة تصميم الموقع من اصل 5750ج', NULL, 1, '2026-09-14 18:18:31'),
(215, 119, 264, 250.00, '2026-09-14', 'انستا باي', '', 'دفع 2000ج وباقي تكلفة تصميم الموقع من اصل 5750ج', NULL, 1, '2026-09-14 18:19:50'),
(216, 127, 265, 950.00, '2026-09-14', 'انستا باي', '', 'ما يعادل 72 ريال سعودي', NULL, 1, '2026-09-14 18:23:39'),
(217, 127, 266, 8000.00, '2026-09-14', 'انستا باي', '', 'ما يعادل 606 ريال سعودي', NULL, 1, '2026-09-14 18:23:53'),
(218, 122, 254, 950.00, '2026-09-14', 'انستا باي', '', 'ما يعادل 72 ريال سعودي', NULL, 1, '2026-09-14 18:24:57'),
(219, 122, 255, 4000.00, '2026-09-14', 'انستا باي', '', 'ما يعادل 303 ريال سعودي', NULL, 1, '2026-09-14 18:25:10'),
(220, 129, 267, 1150.00, '2026-09-17', 'انستا باي', '', 'تم تحوويل 86 ريال بما يعادل 1150 جنيه مصري', NULL, 1, '2026-09-17 19:58:20'),
(221, 126, 263, 2500.00, '2026-09-20', 'انستا باي', '', 'ما يعادل 190 ريال', NULL, 1, '2026-09-20 13:52:39'),
(222, 132, 271, 1130.00, '2026-09-21', 'انستا باي', '', 'ما يعادل 86 ريال', NULL, 1, '2026-09-21 10:07:17'),
(223, 133, 273, 1130.00, '2026-09-21', 'انستا باي', '', 'ما يعادل 86 ريال', NULL, 1, '2026-09-21 10:11:04'),
(224, 1, 211, 5000.00, '2026-09-21', 'انستا باي', '', 'انا دفعتهم عنه بما انه بعتلي زيارة عمرة', NULL, 1, '2026-09-21 10:13:47'),
(225, 134, 275, 1130.00, '2026-09-22', 'انستا باي', '', 'ما يعادل 86 ريال', NULL, 1, '2026-09-22 13:51:31'),
(226, 135, 277, 500.00, '2026-09-26', 'انستا باي', '', 'تم عمل خصم للدومين بقيمة 450ج \r\nبدلا من دفع 950 تم دفع 500 ولكن تم الاتفاق علي ان التجديد سيكون بالسعر الرسمي 1350ج', NULL, 1, '2026-09-26 19:04:38'),
(227, 136, 279, 500.00, '2026-09-28', 'انستا باي', '', 'تم الاتفاق معاه ومع م / احمد عبد الشافي علي ان التجديد يكون بالسعر الرسمي حوالي 1350ج تقريبا', NULL, 1, '2026-09-28 14:19:56'),
(228, 137, 281, 950.00, '2026-09-28', 'انستا باي', '', '', NULL, 1, '2026-09-28 14:22:17'),
(229, 137, 282, 800.00, '2026-09-28', 'انستا باي', '', '', NULL, 1, '2026-09-28 14:22:23'),
(230, 119, 264, 3700.00, '2026-09-29', 'انستا باي', '', '', NULL, 1, '2026-09-29 15:08:52'),
(231, 138, 283, 1130.00, '2026-09-30', 'انستا باي', '', '86 ريال', NULL, 1, '2026-09-30 10:18:48'),
(232, 129, 268, 2450.00, '2026-09-30', 'انستا باي', '', '190 ريال', NULL, 1, '2026-09-30 10:22:24'),
(233, 138, 284, 2450.00, '2026-09-30', 'انستا باي', '', '', NULL, 1, '2026-09-30 15:04:16'),
(234, 131, 270, 2450.00, '2026-10-05', 'انستا باي', '', '', NULL, 1, '2026-10-05 15:03:36'),
(235, 139, 287, 950.00, '2026-10-05', 'فودافون كاش', '', '', NULL, 1, '2026-10-05 15:06:13'),
(236, 134, 276, 2450.00, '2026-10-08', 'انستا باي', '', '', NULL, 1, '2026-10-08 09:50:40'),
(237, 140, 289, 950.00, '2026-10-10', 'انستا باي', '', '', NULL, 1, '2026-10-10 12:29:21'),
(238, 140, 290, 2000.00, '2026-10-10', 'انستا باي', '', '', NULL, 1, '2026-10-10 12:29:27'),
(239, 141, 291, 950.00, '2026-09-29', 'فودافون كاش', '', '', NULL, 1, '2026-10-10 13:23:39'),
(240, 142, 294, 1200.00, '2026-10-14', 'انستا باي', '', '', NULL, 1, '2026-10-14 15:34:40'),
(241, 143, 296, 500.00, '2026-10-15', 'انستا باي', '', '', NULL, 1, '2026-10-16 11:16:14'),
(242, 143, 297, 2000.00, '2026-10-15', 'انستا باي', '', '', NULL, 1, '2026-10-16 11:16:29'),
(243, 136, 280, 2000.00, '2026-10-16', 'انستا باي', '', '', NULL, 1, '2026-10-16 11:17:20'),
(244, 141, 298, 950.00, '2026-10-19', 'فودافون كاش', '', 'حجزنا دومين جديد علشان الاول ما كانش عاجبه', NULL, 1, '2026-10-19 14:59:12'),
(245, 141, 292, 2000.00, '2026-10-19', 'فودافون كاش', '', '', NULL, 1, '2026-10-19 14:59:22'),
(246, 144, 299, 950.00, '2026-10-23', 'انستا باي', '', '', NULL, 1, '2026-10-22 22:02:25'),
(248, 145, 302, 950.00, '2026-10-24', 'انستا باي', '', 'دفع 1000 ج وتم خصم 950 تكلفة الدومين', NULL, 1, '2026-10-24 14:11:52'),
(249, 145, 302, 2000.00, '2026-10-25', 'انستا باي', '', '', NULL, 1, '2026-10-25 15:23:50'),
(250, 132, 272, 2450.00, '2026-10-26', 'انستا باي', '', '190 ريال', NULL, 1, '2026-10-26 09:38:17'),
(251, 133, 274, 2450.00, '2026-10-26', 'انستا باي', '', '190 ريال', NULL, 1, '2026-10-26 09:38:40'),
(252, 146, 303, 4000.00, '2026-10-27', 'انستا باي', '', '', NULL, 1, '2026-10-27 09:11:00'),
(253, 147, 304, 800.00, '2026-10-27', 'انستا باي', '', '', NULL, 1, '2026-10-27 12:24:11'),
(254, 148, 305, 1260.00, '2026-10-28', 'انستا باي', '', '86 ريال × 14.75 جنيه', NULL, 1, '2026-10-28 08:51:14'),
(255, 148, 306, 2800.00, '2026-10-28', 'انستا باي', '', '190ريال × 14.75 جنيه', NULL, 1, '2026-10-28 08:51:32'),
(256, 149, 307, 1250.00, '2026-10-29', 'انستا باي', '', '', NULL, 1, '2026-10-29 09:17:25');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'اسم الخدمة',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `default_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `duration_months` int NOT NULL DEFAULT '12' COMMENT 'مدة الاشتراك الافتراضية بالأشهر',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` int UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sort_order` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `name`, `description`, `default_price`, `duration_months`, `status`, `created_by`, `created_at`, `sort_order`) VALUES
(1, 'استضافة البريد الإلكتروني', 'خدمة استضافة البريد الإلكتروني الاحترافي', 1000.00, 12, 1, NULL, '2026-06-04 12:49:30', 2),
(2, 'حجز الدومين', 'تسجيل وتجديد أسماء النطاقات', 1350.00, 12, 1, NULL, '2026-06-04 12:49:30', 1),
(3, 'تصميم المواقع', 'تصميم وتطوير المواقع الإلكترونية', 3000.00, 0, 1, NULL, '2026-06-04 12:49:30', 3),
(4, 'مزاد لحجز دومين', '', 0.00, 12, 1, 1, '2026-07-26 10:14:49', 3);

-- --------------------------------------------------------

--
-- Table structure for table `service_plans`
--

CREATE TABLE `service_plans` (
  `id` int UNSIGNED NOT NULL,
  `service_id` int UNSIGNED NOT NULL,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'EGP',
  `original_price` decimal(12,2) DEFAULT NULL,
  `price` decimal(12,2) NOT NULL DEFAULT '0.00',
  `sort_order` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `service_plans`
--

INSERT INTO `service_plans` (`id`, `service_id`, `name`, `description`, `currency`, `original_price`, `price`, `sort_order`, `status`, `created_at`) VALUES
(1, 1, '01-1G-800EG', '', 'EGP', NULL, 800.00, 0, 1, '2026-06-04 13:25:01'),
(2, 1, 'G5', 'مساحة 5 جيجا', 'EGP', NULL, 2000.00, 1, 1, '2026-06-04 13:25:23'),
(3, 1, 'G10', 'مساحة 10 جيجا', 'EGP', NULL, 4000.00, 2, 1, '2026-06-04 13:25:47'),
(4, 3, 'موقع تعريفي', '', 'EGP', NULL, 3000.00, 0, 1, '2026-06-04 13:28:54'),
(5, 2, 'دومين', '', 'EGP', NULL, 1350.00, 0, 1, '2026-06-04 14:24:02'),
(6, 1, 'G20', 'مساحة 20 جيجا', 'EGP', 8000.00, 5000.00, 3, 1, '2026-06-04 15:19:53'),
(7, 1, '06-20G-12000EG-3Domain', '', 'EGP', NULL, 12000.00, 4, 1, '2026-06-04 16:32:49'),
(8, 1, '07-5G-950EG', '', 'EGP', NULL, 950.00, 5, 1, '2026-06-04 18:41:54'),
(9, 1, '01-3G-Offer-800EG', '', 'EGP', NULL, 800.00, 6, 1, '2026-06-04 20:45:14'),
(10, 2, 'دومين', '', 'EGP', NULL, 1350.00, 2, 1, '2026-07-17 20:00:27'),
(11, 2, 'دومين بالريال', '', 'SAR', 86.00, 1130.00, 0, 1, '2026-09-06 13:01:54'),
(12, 1, 'G5', 'مساحة 5 جيجا للسعودية', 'SAR', 190.00, 2450.00, 0, 1, '2026-09-30 10:11:52'),
(13, 1, 'G10', 'مساحة 10 جيجا للسعودية', 'SAR', 310.00, 4000.00, 1, 1, '2026-09-30 10:13:28'),
(14, 1, 'G20', 'مساحة 20 جيجا للسعودية', 'SAR', 600.00, 8000.00, 2, 1, '2026-09-30 10:16:35'),
(15, 1, 'G50', '', 'SAR', 999.00, 13000.00, 3, 1, '2026-09-30 10:18:03');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int UNSIGNED NOT NULL,
  `key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `key`, `value`, `updated_at`) VALUES
(1, 'company_name', 'إنجاز للحلول الذكية', '2026-06-04 12:49:30'),
(2, 'company_phone', '201225251888', '2026-06-04 13:39:11'),
(3, 'company_email', 'info@enjaz.app', '2026-06-04 13:39:11'),
(4, 'company_address', 'Bearer Token\r\n\r\nBearer ak_de6fe9510afd4967e0046ea921e81007a56e259d7e428dad', '2026-06-05 19:07:43'),
(5, 'invoice_prefix', 'INV', '2026-06-04 12:49:30'),
(6, 'invoice_counter', '2', '2026-07-17 19:55:49'),
(7, 'whatsapp_api_url', 'https://whatsapp.enjaz.cloud/api/sessions/sess_1782994220045/messages', '2026-09-07 09:08:29'),
(8, 'whatsapp_api_token', 'ak_de6fe9510afd4967e0046ea921e81007a56e259d7e428dad', '2026-06-05 19:19:48'),
(9, 'whatsapp_sender', '201225251888', '2026-06-04 22:14:06'),
(10, 'currency', 'جنيه', '2026-06-04 12:49:30'),
(11, 'renewal_warning_days', '60', '2026-10-12 14:16:31'),
(12, 'payment_methods', 'انستا باي,فودافون كاش', '2026-06-04 14:43:05'),
(13, 'default_home_page', 'dashboard.php', '2026-08-22 12:22:18'),
(14, 'renewal_domain_price_egp', '1350', '2026-10-12 13:13:03'),
(15, 'renewal_domain_price_sar', '86', '2026-10-12 13:13:03');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int UNSIGNED NOT NULL,
  `username` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','employee') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'employee',
  `permissions` json DEFAULT NULL COMMENT 'صلاحيات مخصصة للموظف',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1=نشط, 0=موقوف',
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `role`, `permissions`, `status`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$HMgtq.XGLFO2GvH.eXtLku86XWVJPJnv9sFEfgU29.yq0SfF1BN0W', 'مدير النظام', 'admin', NULL, 1, '2026-10-30 12:27:15', '2026-06-04 12:49:30', '2026-10-30 12:27:15');

-- --------------------------------------------------------

--
-- Table structure for table `whatsapp_logs`
--

CREATE TABLE `whatsapp_logs` (
  `id` int UNSIGNED NOT NULL,
  `client_id` int UNSIGNED DEFAULT NULL,
  `mobile` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `msg_type` enum('renewal','payment','statement','custom','bulk') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'custom',
  `status` enum('sent','failed','pending') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `response` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'رد الـ API',
  `sent_by` int UNSIGNED DEFAULT NULL,
  `sent_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `whatsapp_logs`
--

INSERT INTO `whatsapp_logs` (`id`, `client_id`, `mobile`, `message`, `msg_type`, `status`, `response`, `sent_by`, `sent_at`) VALUES
(1, 73, '201225251888', 'السادة / Rashed\r\n\r\nنود إعلامكم بأن اشتراككم لدى إنجاز للحلول الذكية ينتهي قريباً.\r\nيرجى التواصل معنا لتجديد الاشتراك.\r\n\r\nشكراً لثقتكم 🌟', 'renewal', 'failed', '{\"success\":false,\"error_code\":\"EMPTY_MESSAGE\",\"message\":\"Message must contain text, media_url, or media_id.\"}', 1, '2026-06-04 22:14:25'),
(2, 73, '201225251888', 'السادة / Rashed\r\n\r\nنود إعلامكم بأن اشتراككم لدى إنجاز للحلول الذكية ينتهي قريباً.\r\nيرجى التواصل معنا لتجديد الاشتراك.\r\n\r\nشكراً لثقتكم 🌟', 'renewal', 'failed', 'Forbidden', 1, '2026-06-05 19:08:03'),
(3, 73, '201225251888', 'السادة / Rashed\r\n\r\nنشكركم على سداد دفعتكم.\r\nنتطلع دائماً لخدمتكم.\r\n\r\nإنجاز للحلول الذكية ✅', 'payment', 'failed', 'Forbidden', 1, '2026-06-05 19:08:27'),
(4, 73, '201028855779', 'السادة / Rashed\r\n\r\nنشكركم على سداد دفعتكم.\r\nنتطلع دائماً لخدمتكم.\r\n\r\nإنجاز للحلول الذكية ✅', 'payment', 'failed', 'Forbidden', 1, '2026-06-05 19:08:55'),
(5, 73, '201028855779', 'السادة / Rashed\r\n\r\nإجمالي خدماتكم: 0.00 جنيه\r\nالمسدّد: 0.00 جنيه\r\nالمتبقي: 0.00 جنيه\r\n\r\nللاستفسار تواصلوا معنا.', 'statement', 'failed', 'Forbidden', 1, '2026-06-05 19:09:04'),
(6, 73, '201028855779', 'السادة / Rashed\r\n\r\nنود إعلامكم بأن اشتراككم لدى إنجاز للحلول الذكية ينتهي قريباً.\r\nيرجى التواصل معنا لتجديد الاشتراك.\r\n\r\nشكراً لثقتكم 🌟', 'renewal', 'failed', 'Forbidden', 1, '2026-06-05 19:09:10'),
(7, 73, '201028855779', 'السادة / Rashed\r\n\r\nنود إعلامكم بأن اشتراككم لدى إنجاز للحلول الذكية ينتهي قريباً.\r\nيرجى التواصل معنا لتجديد الاشتراك.\r\n\r\nشكراً لثقتكم 🌟', 'renewal', 'failed', '{\"success\":false,\"error_code\":\"EMPTY_MESSAGE\",\"message\":\"Message must contain text, media_url, or media_id.\"}', 1, '2026-06-05 19:10:24'),
(8, 73, '201028855779', 'jeeff', 'custom', 'failed', 'Forbidden', 1, '2026-06-05 19:11:31'),
(9, 73, '201028855779', 'السادة / Rashed\r\n\r\nنشكركم على سداد دفعتكم.\r\nنتطلع دائماً لخدمتكم.\r\n\r\nإنجاز للحلول الذكية ✅', 'payment', 'failed', '{\"success\":false,\"error_code\":\"EMPTY_MESSAGE\",\"message\":\"Message must contain text, media_url, or media_id.\"}', 1, '2026-06-05 19:20:06'),
(10, 73, '201028855779', 'السادة / Rashed\r\n\r\nنود إعلامكم بأن اشتراككم لدى إنجاز للحلول الذكية ينتهي قريباً.\r\nيرجى التواصل معنا لتجديد الاشتراك.\r\n\r\nشكراً لثقتكم 🌟', 'renewal', 'failed', '{\"success\":false,\"error_code\":\"EMPTY_MESSAGE\",\"message\":\"Message must contain text, media_url, or media_id.\"}', 1, '2026-06-05 19:20:26'),
(11, 73, '201028855779', 'السادة / Rashed\r\n\r\nنشكركم على سداد دفعتكم.\r\nنتطلع دائماً لخدمتكم.\r\n\r\nإنجاز للحلول الذكية ✅', 'payment', 'failed', '{\"success\":false,\"error_code\":\"EMPTY_MESSAGE\",\"message\":\"Message must contain text, media_url, or media_id.\"}', 1, '2026-06-05 19:20:47'),
(12, 73, '201028855779', 'hpl]', 'custom', 'failed', '{\"success\":false,\"error_code\":\"EMPTY_MESSAGE\",\"message\":\"Message must contain text, media_url, or media_id.\"}', 1, '2026-06-05 19:35:47'),
(13, 73, '201028855779', 'السلام عليكم', 'custom', 'sent', '{\"success\":true,\"message_id\":\"msg_1780689448341_8eu0h\",\"status\":\"queued\"}', 1, '2026-06-05 19:57:28'),
(14, 73, '201028855779', 'السادة / Rashed\r\n\r\nإجمالي خدماتكم: 0.00 جنيه\r\nالمسدّد: 0.00 جنيه\r\nالمتبقي: 0.00 جنيه\r\n\r\nللاستفسار تواصلوا معنا.', 'statement', 'sent', '{\"success\":true,\"message_id\":\"msg_1780689482229_cda4a\",\"status\":\"queued\"}', 1, '2026-06-05 19:58:02'),
(15, 73, '201028855779', 'السادة شركة Rashed\r\nاهلا بكم معنا', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1780752956812_7xx8c\",\"status\":\"queued\"}', 1, '2026-06-06 13:35:56'),
(16, 73, '201028855779', 'السادة شركة Enjaz\r\n\r\n*شكراً لثقتكم.* 🙏', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1780753035867_yz004\",\"status\":\"queued\"}', 1, '2026-06-06 13:37:15'),
(17, 75, '201150979340', '*مساء الخير* 🌿\r\n\r\n*نحب نطمئن أن الخدمة شغالة بشكل مناسب لحضراتكم،*\r\n> وفي حال وجود أي ملاحظات أو مقترحات تطوير نرحّب بها في أي وقت.\r\n\r\n*وجودكم معانا محل تقدير*🤝\r\n\r\n🎗️ `إنجاز للحلول الذكية.`', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1781174635896_uk815\",\"status\":\"queued\"}', 1, '2026-06-11 10:43:55'),
(18, 67, '201124252602', '*مساء الخير* 🌿\r\n\r\n*نحب نطمئن أن الخدمة شغالة بشكل مناسب لحضراتكم،*\r\n> وفي حال وجود أي ملاحظات أو مقترحات تطوير نرحّب بها في أي وقت.\r\n\r\n*وجودكم معانا محل تقدير*🤝\r\n\r\n🎗️ `إنجاز للحلول الذكية.`', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1781174650131_hyaco\",\"status\":\"queued\"}', 1, '2026-06-11 10:44:10'),
(19, 59, '201017571005', 'السلام عليكم 🌿\r\nاخبار حضرتك ايه \r\nأتمنى تكون بخير.🙏\r\n\r\nحابب أطمن حضرتك إن الخدمة شغّالة تمام،\r\nومحتاجين بس نكمّل الإجراءات الإدارية الخاصة بها.\r\n\r\n تفاصيل التكلفة: \r\n– تكلفة الدومين: 750 جنيه\r\n\r\n الإجمالي: 750 جنيه\r\n\r\n طريقة الدفع: \r\nالتحويل عبر InstaPay او فودافون كاش\r\nعلى الرقم: 01028855779', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1781348624667_77zyg\",\"status\":\"queued\"}', 1, '2026-06-13 11:03:44'),
(20, 67, '201124252602', 'السلام عليكم 🌿\r\nاخبار حضرتك ايه \r\nأتمنى تكون بخير.🙏\r\n\r\nحابب أطمن حضرتك إن الخدمة شغّالة تمام،\r\nومحتاجين بس نكمّل الإجراءات الإدارية الخاصة بها.\r\n\r\n*تفاصيل التكلفة:*\r\n– الإيميل الاحترافي: 800 جنيه سنويًا (1 جيجا)\r\n\r\n*الإجمالي: 800 جنيه سنويًا*\r\n\r\n*طريقة الدفع:*\r\nالتحويل عبر InstaPay او فودافون كاش\r\nعلى الرقم: 01028855779', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1781348851791_a2j8u\",\"status\":\"queued\"}', 1, '2026-06-13 11:07:31'),
(21, 73, '201028855779', 'السلام عليكم', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1781350054087_hv9r8\",\"status\":\"queued\"}', 1, '2026-06-13 11:27:34'),
(22, 73, '201028855779', 'السادة / Rashed\n\nنود إعلامكم بأن الاشتراكات التالية لديكم تنتهي قريباً:\n\n• خدمة «حجز الدومين» (enjaz.app) - تنتهي بتاريخ 07/10/2026\n\nيرجى التواصل معنا لتجديد الاشتراك والاستمرار في الاستفادة من خدماتنا.\n\nشكراً لثقتكم 🌟\nإنجاز للحلول الذكية', 'bulk', 'failed', '{\"success\":false,\"error_code\":\"SESSION_NOT_FOUND\",\"message\":\"Session not found or access denied\"}', 1, '2026-09-07 09:04:46'),
(23, 73, '201028855779', 'السادة / Rashed\n\nنود إعلامكم بأن الاشتراكات التالية لديكم تنتهي قريباً:\n\n• خدمة «حجز الدومين» (enjaz.app) - تنتهي بتاريخ 07/10/2026\n\nيرجى التواصل معنا لتجديد الاشتراك والاستمرار في الاستفادة من خدماتنا.\n\nشكراً لثقتكم 🌟\nإنجاز للحلول الذكية', 'bulk', 'failed', '{\"success\":false,\"error_code\":\"SESSION_NOT_FOUND\",\"message\":\"Session not found or access denied\"}', 1, '2026-09-07 09:04:56'),
(24, 73, '201028855779', '.', 'bulk', 'failed', '{\"success\":false,\"error_code\":\"SESSION_NOT_FOUND\",\"message\":\"Session not found or access denied\"}', 1, '2026-09-07 09:05:21'),
(25, 73, '201028855779', 'السادة / Rashed\n\nنود إعلامكم بأن الاشتراكات التالية لديكم تنتهي قريباً:\n\n• خدمة «حجز الدومين» (enjaz.app) - تنتهي بتاريخ 07/10/2026\n\nيرجى التواصل معنا لتجديد الاشتراك والاستمرار في الاستفادة من خدماتنا.\n\nشكراً لثقتكم 🌟\nإنجاز للحلول الذكية', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1788772114228_iz791\",\"status\":\"queued\"}', 1, '2026-09-07 09:08:34'),
(26, 73, '201028855779', 'السادة / Rashed\n\nنود إعلامكم بأن الاشتراكات التالية لديكم تنتهي قريباً:\n\n• خدمة «حجز الدومين» (enjaz.app) - تنتهي بتاريخ 07/10/2026\n• خدمة «حجز الدومين» (enjaz.cloud) - تنتهي بتاريخ 03/11/2026\n\nيرجى التواصل معنا لتجديد الاشتراك والاستمرار في الاستفادة من خدماتنا.\n\nشكراً لثقتكم 🌟\nإنجاز للحلول الذكية', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1790449806839_gyiki\",\"status\":\"queued\"}', 1, '2026-09-26 19:10:06'),
(27, 138, '966593292547', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018275387_npoe1\",\"status\":\"queued\"}', 1, '2026-10-03 09:04:35'),
(28, 137, '201111150639', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018280149_7n4m7\",\"status\":\"queued\"}', 1, '2026-10-03 09:04:40'),
(29, 136, '966566710999', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018295923_v1vii\",\"status\":\"queued\"}', 1, '2026-10-03 09:04:55'),
(30, 135, '201002222001', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018312901_thw6m\",\"status\":\"queued\"}', 1, '2026-10-03 09:05:12'),
(31, 134, '966583471721', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018325670_7efhj\",\"status\":\"queued\"}', 1, '2026-10-03 09:05:25'),
(32, 133, '966542546542', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018341548_yer5j\",\"status\":\"queued\"}', 1, '2026-10-03 09:05:41'),
(33, 132, '966542546542', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018350898_u1mol\",\"status\":\"queued\"}', 1, '2026-10-03 09:05:50'),
(34, 131, '966530932366', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018358078_2bxvk\",\"status\":\"queued\"}', 1, '2026-10-03 09:05:58'),
(35, 129, '966536675959', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018362904_v15kk\",\"status\":\"queued\"}', 1, '2026-10-03 09:06:02'),
(36, NULL, '201055281000', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018378857_kyqyh\",\"status\":\"queued\"}', 1, '2026-10-03 09:06:18'),
(37, 127, '249121229665', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018386784_s6chj\",\"status\":\"queued\"}', 1, '2026-10-03 09:06:26'),
(38, 126, '966545320095', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018398070_as0wp\",\"status\":\"queued\"}', 1, '2026-10-03 09:06:38'),
(39, 125, '966566705050', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018413166_xbwtk\",\"status\":\"queued\"}', 1, '2026-10-03 09:06:53'),
(40, 124, '966541334111', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018425357_b7gxy\",\"status\":\"queued\"}', 1, '2026-10-03 09:07:05'),
(41, 123, '966538754212', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018434177_ds41d\",\"status\":\"queued\"}', 1, '2026-10-03 09:07:14'),
(42, 122, '249121229665', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018438506_bkrgj\",\"status\":\"queued\"}', 1, '2026-10-03 09:07:18'),
(43, 121, '201029216789', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018450308_ut2m4\",\"status\":\"queued\"}', 1, '2026-10-03 09:07:30'),
(44, 120, '201130192541', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018463093_p2rat\",\"status\":\"queued\"}', 1, '2026-10-03 09:07:43'),
(45, 119, '201024323985', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018467836_z7kux\",\"status\":\"queued\"}', 1, '2026-10-03 09:07:47'),
(46, 118, '966581514236', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018472038_unbba\",\"status\":\"queued\"}', 1, '2026-10-03 09:07:52'),
(47, 117, '201158352430', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018478857_lh8ed\",\"status\":\"queued\"}', 1, '2026-10-03 09:07:58'),
(48, 115, '966552166706', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018489684_x8rn9\",\"status\":\"queued\"}', 1, '2026-10-03 09:08:09'),
(49, 114, '201055534845', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018498379_ebqrl\",\"status\":\"queued\"}', 1, '2026-10-03 09:08:18'),
(50, 113, '966594231906', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018511298_ggskq\",\"status\":\"queued\"}', 1, '2026-10-03 09:08:31'),
(51, 112, '201117946965', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018521112_cmggs\",\"status\":\"queued\"}', 1, '2026-10-03 09:08:41'),
(52, 111, '966501402000', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018531297_d7lgn\",\"status\":\"queued\"}', 1, '2026-10-03 09:08:51'),
(53, 110, '966534824717', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018543248_zrcpj\",\"status\":\"queued\"}', 1, '2026-10-03 09:09:03'),
(54, 109, '201030006040', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018557281_iukll\",\"status\":\"queued\"}', 1, '2026-10-03 09:09:17'),
(55, 108, '966567574282', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018567134_q4ezi\",\"status\":\"queued\"}', 1, '2026-10-03 09:09:27'),
(56, 107, '6281388888835', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018571904_fsc6v\",\"status\":\"queued\"}', 1, '2026-10-03 09:09:31'),
(57, 97, '201011260142', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018578107_o93hp\",\"status\":\"queued\"}', 1, '2026-10-03 09:09:38'),
(58, 105, '201143233888', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018586943_zrq3g\",\"status\":\"queued\"}', 1, '2026-10-03 09:09:46'),
(59, 104, '201210030829', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018590690_p9pf9\",\"status\":\"queued\"}', 1, '2026-10-03 09:09:50'),
(60, 103, '966581514236', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018594966_netqi\",\"status\":\"queued\"}', 1, '2026-10-03 09:09:54'),
(61, 102, '201026032095', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018608929_jwlix\",\"status\":\"queued\"}', 1, '2026-10-03 09:10:08'),
(62, 100, '966597409020', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018616193_v60ni\",\"status\":\"queued\"}', 1, '2026-10-03 09:10:16'),
(63, 101, '201020887196', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018627206_giz3i\",\"status\":\"queued\"}', 1, '2026-10-03 09:10:27'),
(64, 1, '010', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'failed', '{\"success\":false,\"error_code\":\"SEND_FAILED\",\"message\":\"Invalid phone number format.\"}', 1, '2026-10-03 09:10:31'),
(65, 99, '966555001836', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018645178_snxpo\",\"status\":\"queued\"}', 1, '2026-10-03 09:10:45'),
(66, 98, '201034373883', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018650193_n4now\",\"status\":\"queued\"}', 1, '2026-10-03 09:10:50'),
(67, 96, '201116764534', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018654147_4ivie\",\"status\":\"queued\"}', 1, '2026-10-03 09:10:54'),
(68, 95, '201026228876', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018662250_zf3a5\",\"status\":\"queued\"}', 1, '2026-10-03 09:11:02'),
(69, 94, '201277795959', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018672140_1fbaq\",\"status\":\"queued\"}', 1, '2026-10-03 09:11:12'),
(70, 93, '201225396933', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018680231_7dtx1\",\"status\":\"queued\"}', 1, '2026-10-03 09:11:20'),
(71, 92, '201002698969', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018688449_v7x58\",\"status\":\"queued\"}', 1, '2026-10-03 09:11:28'),
(72, 91, '966553190744', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018695514_vovcc\",\"status\":\"queued\"}', 1, '2026-10-03 09:11:35'),
(73, 90, '966555001836', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018709740_f837c\",\"status\":\"queued\"}', 1, '2026-10-03 09:11:49'),
(74, 89, '201097911542', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018721258_curvy\",\"status\":\"queued\"}', 1, '2026-10-03 09:12:01'),
(75, 88, '201152065471', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018725602_7q6u3\",\"status\":\"queued\"}', 1, '2026-10-03 09:12:05'),
(76, 87, '201553430555', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018734369_u06tr\",\"status\":\"queued\"}', 1, '2026-10-03 09:12:14'),
(77, 86, '201003597983', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018744217_otp01\",\"status\":\"queued\"}', 1, '2026-10-03 09:12:24'),
(78, 85, '201026032095', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018756711_yznic\",\"status\":\"queued\"}', 1, '2026-10-03 09:12:36'),
(79, 84, '201060917992', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018763179_5z1s3\",\"status\":\"queued\"}', 1, '2026-10-03 09:12:43'),
(80, 83, '201063887871', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018775505_552bp\",\"status\":\"queued\"}', 1, '2026-10-03 09:12:55'),
(81, 29, '201067996402', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018789547_4i5nj\",\"status\":\"queued\"}', 1, '2026-10-03 09:13:09'),
(82, 82, '201009542308', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018797492_qb6sk\",\"status\":\"queued\"}', 1, '2026-10-03 09:13:17'),
(83, 81, '201022988711', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018808606_bxh4b\",\"status\":\"queued\"}', 1, '2026-10-03 09:13:28'),
(84, 80, '201101121455', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018824216_agi1p\",\"status\":\"queued\"}', 1, '2026-10-03 09:13:44'),
(85, 79, '201284206086', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018834320_0umdj\",\"status\":\"queued\"}', 1, '2026-10-03 09:13:54'),
(86, 78, '966505774960', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018842594_392v6\",\"status\":\"queued\"}', 1, '2026-10-03 09:14:02'),
(87, 76, '201111150639', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018856136_s38p1\",\"status\":\"queued\"}', 1, '2026-10-03 09:14:16'),
(88, 75, '201150979340', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018870131_kyv01\",\"status\":\"queued\"}', 1, '2026-10-03 09:14:30'),
(89, 74, '201001300092', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018886214_wrdio\",\"status\":\"queued\"}', 1, '2026-10-03 09:14:46'),
(90, 69, '201080146149', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018892099_h3mai\",\"status\":\"queued\"}', 1, '2026-10-03 09:14:52'),
(91, 67, '201124252602', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018903539_u3a1g\",\"status\":\"queued\"}', 1, '2026-10-03 09:15:03'),
(92, 66, '201147080280', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018920211_w4lfo\",\"status\":\"queued\"}', 1, '2026-10-03 09:15:20'),
(93, 65, '249123885888', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018930674_r4c63\",\"status\":\"queued\"}', 1, '2026-10-03 09:15:30'),
(94, 64, '201143594429', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018945478_l2zy0\",\"status\":\"queued\"}', 1, '2026-10-03 09:15:45'),
(95, 63, '201225770713', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018952280_0lcin\",\"status\":\"queued\"}', 1, '2026-10-03 09:15:52'),
(96, 62, '201003939499', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018961312_grh7z\",\"status\":\"queued\"}', 1, '2026-10-03 09:16:01'),
(97, 61, '201100691874', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018967128_hf2s2\",\"status\":\"queued\"}', 1, '2026-10-03 09:16:07'),
(98, 60, '201280265841', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018980498_nfc9s\",\"status\":\"queued\"}', 1, '2026-10-03 09:16:20'),
(99, 59, '201017571005', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018987233_18i3g\",\"status\":\"queued\"}', 1, '2026-10-03 09:16:27'),
(100, 58, '201157184292', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791018990924_d66in\",\"status\":\"queued\"}', 1, '2026-10-03 09:16:30'),
(101, 57, '201117013024', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791019004058_jilua\",\"status\":\"queued\"}', 1, '2026-10-03 09:16:44'),
(102, 71, '201060565785', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791019013767_cn8og\",\"status\":\"queued\"}', 1, '2026-10-03 09:16:53'),
(103, 56, '201018766588', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791019019013_od820\",\"status\":\"queued\"}', 1, '2026-10-03 09:16:59'),
(104, 70, '201501400887', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791019023937_c2nff\",\"status\":\"queued\"}', 1, '2026-10-03 09:17:03'),
(105, 55, '201113416722', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791019032973_gmfqy\",\"status\":\"queued\"}', 1, '2026-10-03 09:17:12'),
(106, 54, '201012468796', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791019040798_17344\",\"status\":\"queued\"}', 1, '2026-10-03 09:17:20'),
(107, 53, '201210622000', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791019053801_agzxu\",\"status\":\"queued\"}', 1, '2026-10-03 09:17:33'),
(108, 52, '201150695780', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791019066947_01n7s\",\"status\":\"queued\"}', 1, '2026-10-03 09:17:46'),
(109, 51, '201028240959', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791019073681_o5wuj\",\"status\":\"queued\"}', 1, '2026-10-03 09:17:53'),
(110, 50, '010', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'failed', '{\"success\":false,\"error_code\":\"SEND_FAILED\",\"message\":\"Invalid phone number format.\"}', 1, '2026-10-03 09:18:03'),
(111, 49, '201050207906', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791019098046_kqvs6\",\"status\":\"queued\"}', 1, '2026-10-03 09:18:18'),
(112, 48, '201003738806', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791019106816_yla78\",\"status\":\"queued\"}', 1, '2026-10-03 09:18:26'),
(113, 47, '201220404582', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791019113766_eoe3e\",\"status\":\"queued\"}', 1, '2026-10-03 09:18:33');
INSERT INTO `whatsapp_logs` (`id`, `client_id`, `mobile`, `message`, `msg_type`, `status`, `response`, `sent_by`, `sent_at`) VALUES
(114, 46, '201130713582', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791019126562_4jwd4\",\"status\":\"queued\"}', 1, '2026-10-03 09:18:46'),
(115, 45, '201501400887', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791019140877_eo6cu\",\"status\":\"queued\"}', 1, '2026-10-03 09:19:00'),
(116, 44, '201018920639', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791019155738_lqsck\",\"status\":\"queued\"}', 1, '2026-10-03 09:19:15'),
(117, 43, '201012173608', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791019165893_f4bpt\",\"status\":\"queued\"}', 1, '2026-10-03 09:19:25'),
(118, 42, '201227187286', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791019173941_eqive\",\"status\":\"queued\"}', 1, '2026-10-03 09:19:33'),
(119, 41, '201210400738', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791019188996_yljq7\",\"status\":\"queued\"}', 1, '2026-10-03 09:19:48'),
(120, 40, '201091166448', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791019205098_1cmty\",\"status\":\"queued\"}', 1, '2026-10-03 09:20:05'),
(121, 39, '201028085860', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791019220899_kqw6p\",\"status\":\"queued\"}', 1, '2026-10-03 09:20:20'),
(122, 38, '201505588416', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791019225649_s7ht9\",\"status\":\"queued\"}', 1, '2026-10-03 09:20:25'),
(123, 35, '201006829244', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791019229377_3oyrd\",\"status\":\"queued\"}', 1, '2026-10-03 09:20:29'),
(124, 34, '201005151818', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791019241375_0corp\",\"status\":\"queued\"}', 1, '2026-10-03 09:20:41'),
(125, 32, '201555115296', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791019253144_vhj7x\",\"status\":\"queued\"}', 1, '2026-10-03 09:20:53'),
(126, 31, '201090416807', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791019266108_a3xub\",\"status\":\"queued\"}', 1, '2026-10-03 09:21:06'),
(127, 30, '966561036181', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791019275833_iop1m\",\"status\":\"queued\"}', 1, '2026-10-03 09:21:15'),
(128, 28, '201226178167', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791019287572_m013e\",\"status\":\"queued\"}', 1, '2026-10-03 09:21:27'),
(129, 27, '201018067511', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791019303442_8z5dl\",\"status\":\"queued\"}', 1, '2026-10-03 09:21:43'),
(130, 25, '201206671454', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791019312260_ad93n\",\"status\":\"queued\"}', 1, '2026-10-03 09:21:52'),
(131, 24, '201028071308', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791019318994_2x091\",\"status\":\"queued\"}', 1, '2026-10-03 09:21:58'),
(132, 23, '201207575483', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791019326219_d6vef\",\"status\":\"queued\"}', 1, '2026-10-03 09:22:06'),
(133, 22, '201055144517', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791019341190_4ccbs\",\"status\":\"queued\"}', 1, '2026-10-03 09:22:21'),
(134, 21, '201128478217', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791019354042_xzv0n\",\"status\":\"queued\"}', 1, '2026-10-03 09:22:34'),
(135, 20, '201001479363', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791019359958_c07f7\",\"status\":\"queued\"}', 1, '2026-10-03 09:22:39'),
(136, 19, '201016225666', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791019369621_yvu6s\",\"status\":\"queued\"}', 1, '2026-10-03 09:22:49'),
(137, 18, '201038239100', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791019382577_b2j23\",\"status\":\"queued\"}', 1, '2026-10-03 09:23:02'),
(138, 17, '201288333348', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791019388710_u2sc0\",\"status\":\"queued\"}', 1, '2026-10-03 09:23:08'),
(139, 16, '201013982131', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791019404489_rfa9c\",\"status\":\"queued\"}', 1, '2026-10-03 09:23:24'),
(140, 13, '201119589121', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791019417664_ogx31\",\"status\":\"queued\"}', 1, '2026-10-03 09:23:37'),
(141, 12, '201003939499', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791019431388_6ua0b\",\"status\":\"queued\"}', 1, '2026-10-03 09:23:51'),
(142, 11, '201111130655', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791019437140_odoi7\",\"status\":\"queued\"}', 1, '2026-10-03 09:23:57'),
(143, 10, '201023399311', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791019442889_d9s6g\",\"status\":\"queued\"}', 1, '2026-10-03 09:24:02'),
(144, 9, '201099000850', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791019458636_82bsh\",\"status\":\"queued\"}', 1, '2026-10-03 09:24:18'),
(145, 8, '201012692176', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791019465673_h2xn2\",\"status\":\"queued\"}', 1, '2026-10-03 09:24:25'),
(146, 7, '201097488085', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791019478882_5mbcv\",\"status\":\"queued\"}', 1, '2026-10-03 09:24:38'),
(147, 6, '201019826096', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791019484733_him8z\",\"status\":\"queued\"}', 1, '2026-10-03 09:24:44'),
(148, 5, '201000008145', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791019500907_9st17\",\"status\":\"queued\"}', 1, '2026-10-03 09:25:00'),
(149, 4, '201220003177', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791019508983_zl0fu\",\"status\":\"queued\"}', 1, '2026-10-03 09:25:08'),
(150, 3, '201277795959', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791019513690_c3xf7\",\"status\":\"queued\"}', 1, '2026-10-03 09:25:13'),
(151, 2, '201112957899', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791019529589_h4cyw\",\"status\":\"queued\"}', 1, '2026-10-03 09:25:29'),
(152, 73, '201028855779', '*مساء الخير يا فندم* 🌿\r\n\r\nحابين نطمن على حضرتك ونتأكد إن كل شيء شغال معاك بالشكل المطلوب. 💚\r\n\r\nلو عند حضرتك أي استفسار أو ملاحظة، فريق *إنجاز* موجود دائمًا لمساعدتك.\r\n\r\n`ولو في أي حاجة نقدر نساعدك فيها أو نطوّرها معاك، ابعتلنا في أي وقت.`\r\n\r\nشكرًا لثقتك المستمرة في *إنجاز*. 🤝\r\n\r\n🎗️ *إنجاز للحلول الذكية*', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791019534350_9l8n6\",\"status\":\"queued\"}', 1, '2026-10-03 09:25:34'),
(153, 2, '201112957899', 'السادة / م / مصعب نبيل\n\nنود إعلامكم بأن الاشتراكات التالية لديكم تنتهي قريباً:\n\n• خدمة «استضافة البريد الإلكتروني» (02-5G-2000EG) - تنتهي بتاريخ 06/12/2026\n\nيرجى التواصل معنا لتجديد الاشتراك والاستمرار في الاستفادة من خدماتنا.\n\nشكراً لثقتكم 🌟\nإنجاز للحلول الذكية', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791807276243_q6yno\",\"status\":\"queued\"}', 1, '2026-10-12 12:14:36'),
(154, 3, '201277795959', 'السادة / م / محمد\n\nنود إعلامكم بأن الاشتراكات التالية لديكم تنتهي قريباً:\n\n• خدمة «استضافة البريد الإلكتروني» (01-1G-800EG) - تنتهي بتاريخ 10/12/2026\n\nيرجى التواصل معنا لتجديد الاشتراك والاستمرار في الاستفادة من خدماتنا.\n\nشكراً لثقتكم 🌟\nإنجاز للحلول الذكية', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791807277364_gao0f\",\"status\":\"queued\"}', 1, '2026-10-12 12:14:37'),
(155, 2, '201028855779', 'السلام عليكم ورحمة الله وبركاته 🌷\r\n\r\nعميلنا العزيز، نود تذكيركم بقرب موعد تجديد اشتراكاتكم الخاصة بنطاق (**kentrotfm.com**) بتاريخ **06/12/2026**:\r\n\r\n🌐 **الدومين:** kentrotfm.com\r\n📧 **استضافة البريد الإلكتروني – باقة 5 جيجا:** 2,000 جنيه\r\n\r\n💰 **إجمالي التجديد: 2,000 جنيه مصري**\r\n\r\nنرجو إتمام التحويل قبل موعد الانتهاء لضمان استمرار الخدمة دون انقطاع، مع إرسال صورة التحويل بعد السداد.\r\n\r\n💳 **بيانات التحويل:**\r\n\r\n🇸🇦 **السعودية**\r\n• الراجحي – رقم الحساب: 077060010006088389454\r\n• D360 – IBAN: SA3036036036065660911461\r\n• STC Pay: 0567457572\r\n\r\n🇪🇬 **مصر**\r\n• انستا باي او فودافون كاش:\r\n• 01028855779\r\n\r\nشكرًا لثقتكم بنا، ونسعد دائمًا بخدمتكم 💚\r\n\r\n**إنجاز للحلول الذكية**', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791814609825_t314u\",\"status\":\"queued\"}', 1, '2026-10-12 14:16:49'),
(156, 2, '201028855779', '*السلام عليكم ورحمة الله وبركاته* 🌷\r\n\r\nعميلنا العزيز، نود تذكيركم بقرب موعد تجديد اشتراكاتكم الخاصة بنطاق (*kentrotfm.com*) بتاريخ *06/12/2026*:\r\n\r\n🌐 *الدومين:* kentrotfm.com\r\n📧 *استضافة البريد الإلكتروني – باقة 5 جيجا:* 2,000 جنيه\r\n\r\n💰 *إجمالي التجديد: 2,000 جنيه مصري*\r\n\r\nنرجو إتمام التحويل قبل موعد الانتهاء لضمان استمرار الخدمة دون انقطاع، مع إرسال صورة التحويل بعد السداد.\r\n\r\n💳 *بيانات التحويل:*\r\n\r\n🇸🇦 *السعودية*\r\n• الراجحي – رقم الحساب: 077060010006088389454\r\n• D360 – IBAN: SA3036036036065660911461\r\n• STC Pay: 0567457572\r\n\r\n🇪🇬 *مصر*\r\n• انستا باي او فودافون كاش:\r\n• 01028855779\r\n\r\nشكرًا لثقتكم بنا، ونسعد دائمًا بخدمتكم 💚\r\n\r\n`إنجاز للحلول الذكية`', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791815001517_dsxcv\",\"status\":\"queued\"}', 1, '2026-10-12 14:23:21'),
(157, 2, '201028855779', '*السلام عليكم ورحمة الله وبركاته* 🌷\r\n\r\nعميلنا العزيز، نود تذكيركم بقرب موعد تجديد اشتراكاتكم الخاصة بنطاق (*kentrotfm.com*) بتاريخ *06/12/2026*:\r\n\r\n🌐 *الدومين:* kentrotfm.com\r\n📧 *استضافة البريد الإلكتروني – باقة 5 جيجا:* 2,000 جنيه\r\n\r\n💰 *إجمالي التجديد: 2,000 جنيه مصري*\r\n\r\n`نرجو إتمام التحويل قبل موعد الانتهاء لضمان استمرار الخدمة دون انقطاع، مع إرسال صورة التحويل بعد السداد.`\r\n\r\n💳 *بيانات التحويل:*\r\n\r\n🇸🇦 *السعودية*\r\n• الراجحي – رقم الحساب: 077060010006088389454\r\n• D360 – IBAN: SA3036036036065660911461\r\n• STC Pay: 0567457572\r\n\r\n🇪🇬 *مصر*\r\n• انستا باي او فودافون كاش:\r\n• 01028855779\r\n\r\nشكرًا لثقتكم بنا، ونسعد دائمًا بخدمتكم 💚\r\n\r\n`إنجاز للحلول الذكية`', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1791815132632_65ji7\",\"status\":\"queued\"}', 1, '2026-10-12 14:25:32'),
(158, 4, '201220003177', '*السلام عليكم ورحمة الله وبركاته* 🌷\r\n\r\nعميلنا العزيز، نود تذكيركم بقرب موعد تجديد اشتراكاتكم الخاصة بنطاق (*nexforges.com*) بتاريخ *20/12/2026*:\r\n\r\n🌐 *الدومين:* nexforges.com\r\n📧 *استضافة البريد الإلكتروني – باقة 1 جيجا:* 800 جنيه\r\n\r\n💰 *إجمالي التجديد: 800 جنيه مصري*\r\n\r\n`نرجو إتمام التحويل قبل موعد الانتهاء لضمان استمرار الخدمة دون انقطاع، مع إرسال صورة التحويل بعد السداد.`\r\n\r\n💳 *بيانات التحويل:*\r\n\r\n🇸🇦 *السعودية*\r\n• الراجحي – رقم الحساب: 077060010006088389454\r\n• D360 – IBAN: SA3036036036065660911461\r\n• STC Pay: 0567457572\r\n\r\n🇪🇬 *مصر*\r\n• انستا باي او فودافون كاش:\r\n• 01028855779\r\n\r\nشكرًا لثقتكم بنا، ونسعد دائمًا بخدمتكم 💚\r\n\r\n`إنجاز للحلول الذكية`', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1792679149613_ylx4j\",\"status\":\"queued\"}', 1, '2026-10-22 14:25:49'),
(159, 5, '201000008145', '*السلام عليكم ورحمة الله وبركاته* 🌷\r\n\r\nعميلنا العزيز، نود تذكيركم بقرب موعد تجديد اشتراكاتكم الخاصة بنطاق (*flourish-landscaping.com*) بتاريخ *21/12/2026*:\r\n\r\n🌐 *الدومين:* flourish-landscaping.com\r\n📧 *استضافة البريد الإلكتروني – باقة 5 جيجا:* 2,000 جنيه\r\n\r\n💰 *إجمالي التجديد: 2,000 جنيه مصري*\r\n\r\n`نرجو إتمام التحويل قبل موعد الانتهاء لضمان استمرار الخدمة دون انقطاع، مع إرسال صورة التحويل بعد السداد.`\r\n\r\n💳 *بيانات التحويل:*\r\n\r\n🇸🇦 *السعودية*\r\n• الراجحي – رقم الحساب: 077060010006088389454\r\n• D360 – IBAN: SA3036036036065660911461\r\n• STC Pay: 0567457572\r\n\r\n🇪🇬 *مصر*\r\n• انستا باي او فودافون كاش:\r\n• 01028855779\r\n\r\nشكرًا لثقتكم بنا، ونسعد دائمًا بخدمتكم 💚\r\n\r\n`إنجاز للحلول الذكية`', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1792679151731_0358d\",\"status\":\"queued\"}', 1, '2026-10-22 14:25:51'),
(160, 6, '201019826096', '*السلام عليكم ورحمة الله وبركاته* 🌷\r\n\r\nعميلنا العزيز، نود تذكيركم بقرب موعد تجديد اشتراكاتكم الخاصة بنطاق (*smartsource-eg.com*) بتاريخ *27/12/2026*:\r\n\r\n🌐 *حجز الدومين (smartsource-eg.com):* 1,350 جنيه\r\n📧 *استضافة البريد الإلكتروني – باقة 5 جيجا:* 2,000 جنيه\r\n\r\n💰 *إجمالي التجديد: 3,350 جنيه مصري*\r\n\r\n`نرجو إتمام التحويل قبل موعد الانتهاء لضمان استمرار الخدمة دون انقطاع، مع إرسال صورة التحويل بعد السداد.`\r\n\r\n💳 *بيانات التحويل:*\r\n\r\n• انستا باي او فودافون كاش:\r\n• 01028855779\r\n\r\nشكرًا لثقتكم بنا، ونسعد دائمًا بخدمتكم 💚\r\n\r\n`إنجاز للحلول الذكية`', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1793174815746_f54hy\",\"status\":\"queued\"}', 1, '2026-10-28 08:06:55'),
(161, 7, '201097488085', '*السلام عليكم ورحمة الله وبركاته* 🌷\r\n\r\nعميلنا العزيز، نود تذكيركم بقرب موعد تجديد اشتراكاتكم الخاصة بنطاق (*royalsmart-rs.com*) بتاريخ *29/12/2026*:\r\n\r\n🌐 *حجز الدومين (royalsmart-rs.com):* 1,350 جنيه\r\n📧 *استضافة البريد الإلكتروني – باقة 1 جيجا:* 800 جنيه\r\n\r\n💰 *إجمالي التجديد: 2,150 جنيه مصري*\r\n\r\n`نرجو إتمام التحويل قبل موعد الانتهاء لضمان استمرار الخدمة دون انقطاع، مع إرسال صورة التحويل بعد السداد.`\r\n\r\n💳 *بيانات التحويل:*\r\n\r\n🇸🇦 *السعودية*\r\n• الراجحي – \r\nرقم الحساب: 077060010006088389454\r\nIBAN: SA5980000866608018389454\r\n\r\n• D360 – IBAN: SA3036036036065660911461\r\n• STC Pay: 0567457572\r\n\r\n🇪🇬 *مصر*\r\n• انستا باي او فودافون كاش:\r\n• 01028855779\r\n\r\nشكرًا لثقتكم بنا، ونسعد دائمًا بخدمتكم 💚\r\n\r\n`إنجاز للحلول الذكية`', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1793359172689_886y1\",\"status\":\"queued\"}', 1, '2026-10-30 11:19:32'),
(162, 8, '201012692176', '*السلام عليكم ورحمة الله وبركاته* 🌷\r\n\r\nعميلنا العزيز، نود تذكيركم بقرب موعد تجديد اشتراكاتكم الخاصة بنطاق (*stars-smcs.com*) بتاريخ *29/12/2026*:\r\n\r\n🌐 *حجز الدومين (stars-smcs.com):* 1,350 جنيه\r\n📧 *استضافة البريد الإلكتروني – باقة 5 جيجا:* 2,000 جنيه\r\n\r\n💰 *إجمالي التجديد: 3,350 جنيه مصري*\r\n\r\n`نرجو إتمام التحويل قبل موعد الانتهاء لضمان استمرار الخدمة دون انقطاع، مع إرسال صورة التحويل بعد السداد.`\r\n\r\n💳 *بيانات التحويل:*\r\n🇪🇬 *مصر*\r\n• انستا باي او فودافون كاش:\r\n• 01028855779\r\n\r\nشكرًا لثقتكم بنا، ونسعد دائمًا بخدمتكم 💚\r\n\r\n`إنجاز للحلول الذكية`', 'bulk', 'sent', '{\"success\":true,\"message_id\":\"msg_1793359174671_u7ozf\",\"status\":\"queued\"}', 1, '2026-10-30 11:19:34');

-- --------------------------------------------------------

--
-- Table structure for table `whatsapp_queue`
--

CREATE TABLE `whatsapp_queue` (
  `id` int UNSIGNED NOT NULL,
  `client_id` int UNSIGNED DEFAULT NULL,
  `mobile` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `send_at` datetime NOT NULL,
  `min_delay` int UNSIGNED NOT NULL DEFAULT '3',
  `max_delay` int UNSIGNED NOT NULL DEFAULT '15',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending' COMMENT 'pending, sending, sent, failed',
  `response` text COLLATE utf8mb4_unicode_ci,
  `sent_by` int UNSIGNED DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `whatsapp_queue`
--

INSERT INTO `whatsapp_queue` (`id`, `client_id`, `mobile`, `message`, `send_at`, `min_delay`, `max_delay`, `status`, `response`, `sent_by`, `created_at`) VALUES
(1, 73, '201028855779', 'السلام عليكم ورحمة الله', '2026-06-13 14:33:00', 3, 15, 'sent', '{\"success\":true,\"message_id\":\"msg_1781350549335_mjduy\",\"status\":\"queued\"}', 1, '2026-06-13 11:27:52'),
(3, 79, '201284206086', '*لوحة التحكم:*\r\nhttps://cp.enjaz.cloud\r\n\r\nاسم المستخدم : ahl-eg\r\n\r\nكلمة المرور : AR7@A3LRAd\r\n\r\n*شرح لوحة التحكم*\r\nhttps://enjaz.app/video-guides.html\r\n\r\n*يمكن تشغيل الايميلات من خلال :*\r\n\r\n*- المتصفح*\r\nhttps://webmail.ahl-eg.net\r\n\r\n*- برنامج الاوتلوك*\r\n     * الاندرويد\r\n     * ال IOS', '2026-06-13 15:00:00', 3, 15, 'sent', '{\"success\":true,\"message_id\":\"msg_1781352003622_2whll\",\"status\":\"queued\"}', 1, '2026-06-13 11:37:39'),
(7, 81, '201022988711', '*اتفضل يا فندم،🙏*\r\nدي لوحة التحكم الخاصة بالخدمة مع شرح مبسط لطريقة الاستخدام.\r\n\r\nوإحنا مع حضرتك دائمًا في أي استفسار أو توضيح،\r\nكمان ممكن ندخل مع حضرتك في جلسة تدريب سريعة لشرح تشغيل لوحة التحكم وإدارة الإيميلات خطوة بخطوة. 🌿\r\n\r\n*شكراً لثقتكم. 🤝*\r\n\r\n*لوحة التحكم:*\r\nhttps://cp.enjaz.cloud\r\n\r\nاسم المستخدم : emdad-eg\r\n\r\nكلمة المرور : AR7@A3LRGd\r\n\r\n*شرح لوحة التحكم*\r\nhttps://enjaz.app/video-guides.html\r\n\r\nيمكن تشغيل الايميلات من خلال :\r\n\r\n*- المتصفح*\r\nhttps://webmail.www.emdad-eg.com\r\n\r\n*- برنامج الاوتلوك*\r\n     * الاندرويد\r\n     * ال IOS', '2026-06-14 16:00:00', 3, 15, 'sent', '{\"success\":true,\"message_id\":\"msg_1781442003809_0nu6a\",\"status\":\"queued\"}', 1, '2026-06-14 11:53:39'),
(8, 82, '201009542308', '*اتفضل يا فندم،🙏*\r\nدي لوحة التحكم الخاصة بالخدمة مع شرح مبسط لطريقة الاستخدام.\r\n\r\nوإحنا مع حضرتك دائمًا في أي استفسار أو توضيح،\r\nكمان ممكن ندخل مع حضرتك في جلسة تدريب سريعة لشرح تشغيل لوحة التحكم وإدارة الإيميلات خطوة بخطوة. 🌿\r\n\r\n`شكراً لثقتكم. 🤝`\r\n\r\n*لوحة التحكم:*\r\nhttps://cp.enjaz.cloud\r\n\r\nاسم المستخدم : artoffice-eg\r\n\r\nكلمة المرور : AR7@A3LRFd\r\n\r\n*شرح لوحة التحكم*\r\nhttps://enjaz.app/video-guides.html\r\n\r\n*يمكن تشغيل الايميلات من خلال :*\r\n\r\n*- المتصفح*\r\nhttps://webmail.artoffice-eg.com\r\n\r\n*- برنامج الاوتلوك*\r\n     * الاندرويد\r\n     * ال IOS', '2026-06-16 17:00:00', 3, 15, 'sent', '{\"success\":true,\"message_id\":\"msg_1781618402822_uxjxl\",\"status\":\"queued\"}', 1, '2026-06-16 12:44:43'),
(9, 86, '201003597983', 'اتفضل يا فندم،🙏\r\nدي لوحة التحكم الخاصة بالخدمة مع شرح مبسط لطريقة الاستخدام.\r\n\r\nhttps://cp.enjaz.cloud\r\n\r\nاسم المستخدم : desertrose\r\n\r\nكلمة المرور : AR7@A3LEHd\r\n\r\nشرح لوحة التحكم\r\nhttps://enjaz.app/video-guides.html\r\n\r\nيمكن تشغيل الايميلات من خلال :\r\n\r\n- المتصفح\r\nhttps://webmail.desertrose-eg.com\r\n\r\n- برنامج الاوتلوك\r\n     * الاندرويد\r\n     * ال IOS\r\n\r\n*وإحنا مع حضرتك دايما في أي استفسار أو توضيح،*\r\nكمان ممكن ندخل مع حضرتك في جلسة تدريب سريعة لشرح تشغيل لوحة التحكم وإدارة الإيميلات خطوة بخطوة. 🌿\r\n\r\n*شكراً لثقتكم. 🤝*', '2026-06-24 01:40:00', 3, 15, 'sent', '{\"success\":true,\"message_id\":\"msg_1782254402622_fay8r\",\"status\":\"queued\"}', 1, '2026-06-23 21:43:35'),
(10, 88, '201152065471', '*صباح الخير* 🌿\r\n\r\n*نحب نطمئن أن الخدمة شغالة بشكل مناسب لحضراتكم،*\r\n> وفي حال وجود أي ملاحظات أو مقترحات تطوير نرحّب بها في أي وقت.\r\n\r\n*وجودكم معانا محل تقدير*🤝\r\n\r\n🎗️ `إنجاز للحلول الذكية.`', '2026-07-01 11:13:00', 3, 15, 'sent', '{\"success\":true,\"message_id\":\"msg_1782893588216_24ek0\",\"status\":\"queued\"}', 1, '2026-07-01 08:12:05'),
(11, 87, '201553430555', '*صباح الخير* 🌿\r\n\r\n*نحب نطمئن أن الخدمة شغالة بشكل مناسب لحضراتكم،*\r\n> وفي حال وجود أي ملاحظات أو مقترحات تطوير نرحّب بها في أي وقت.\r\n\r\n*وجودكم معانا محل تقدير*🤝\r\n\r\n🎗️ `إنجاز للحلول الذكية.`', '2026-07-01 11:13:00', 3, 15, 'sent', '{\"success\":true,\"message_id\":\"msg_1782893617651_9psyq\",\"status\":\"queued\"}', 1, '2026-07-01 08:12:06'),
(12, 86, '201003597983', '*صباح الخير* 🌿\r\n\r\n*نحب نطمئن أن الخدمة شغالة بشكل مناسب لحضراتكم،*\r\n> وفي حال وجود أي ملاحظات أو مقترحات تطوير نرحّب بها في أي وقت.\r\n\r\n*وجودكم معانا محل تقدير*🤝\r\n\r\n🎗️ `إنجاز للحلول الذكية.`', '2026-07-01 11:13:00', 3, 15, 'sent', '{\"success\":true,\"message_id\":\"msg_1782893638533_zrge3\",\"status\":\"queued\"}', 1, '2026-07-01 08:12:06'),
(13, 85, '201026032095', '*صباح الخير* 🌿\r\n\r\n*نحب نطمئن أن الخدمة شغالة بشكل مناسب لحضراتكم،*\r\n> وفي حال وجود أي ملاحظات أو مقترحات تطوير نرحّب بها في أي وقت.\r\n\r\n*وجودكم معانا محل تقدير*🤝\r\n\r\n🎗️ `إنجاز للحلول الذكية.`', '2026-07-01 11:13:00', 3, 15, 'sent', '{\"success\":true,\"message_id\":\"msg_1782893642042_xtoix\",\"status\":\"queued\"}', 1, '2026-07-01 08:12:06'),
(14, 84, '201060917992', '*صباح الخير* 🌿\r\n\r\n*نحب نطمئن أن الخدمة شغالة بشكل مناسب لحضراتكم،*\r\n> وفي حال وجود أي ملاحظات أو مقترحات تطوير نرحّب بها في أي وقت.\r\n\r\n*وجودكم معانا محل تقدير*🤝\r\n\r\n🎗️ `إنجاز للحلول الذكية.`', '2026-07-01 11:13:00', 3, 15, 'sent', '{\"success\":true,\"message_id\":\"msg_1782893665291_0dd8y\",\"status\":\"queued\"}', 1, '2026-07-01 08:12:06'),
(15, 83, '201063887871', '*صباح الخير* 🌿\r\n\r\n*نحب نطمئن أن الخدمة شغالة بشكل مناسب لحضراتكم،*\r\n> وفي حال وجود أي ملاحظات أو مقترحات تطوير نرحّب بها في أي وقت.\r\n\r\n*وجودكم معانا محل تقدير*🤝\r\n\r\n🎗️ `إنجاز للحلول الذكية.`', '2026-07-01 11:13:00', 3, 15, 'sent', '{\"success\":true,\"message_id\":\"msg_1782893667588_onmto\",\"status\":\"queued\"}', 1, '2026-07-01 08:12:06'),
(16, 29, '201067996402', '*صباح الخير* 🌿\r\n\r\n*نحب نطمئن أن الخدمة شغالة بشكل مناسب لحضراتكم،*\r\n> وفي حال وجود أي ملاحظات أو مقترحات تطوير نرحّب بها في أي وقت.\r\n\r\n*وجودكم معانا محل تقدير*🤝\r\n\r\n🎗️ `إنجاز للحلول الذكية.`', '2026-07-01 11:13:00', 3, 15, 'sent', '{\"success\":true,\"message_id\":\"msg_1782893675438_51mg1\",\"status\":\"queued\"}', 1, '2026-07-01 08:12:07'),
(17, 82, '201009542308', '*صباح الخير* 🌿\r\n\r\n*نحب نطمئن أن الخدمة شغالة بشكل مناسب لحضراتكم،*\r\n> وفي حال وجود أي ملاحظات أو مقترحات تطوير نرحّب بها في أي وقت.\r\n\r\n*وجودكم معانا محل تقدير*🤝\r\n\r\n🎗️ `إنجاز للحلول الذكية.`', '2026-07-01 11:13:00', 3, 15, 'sent', '{\"success\":true,\"message_id\":\"msg_1782893679490_gd7jv\",\"status\":\"queued\"}', 1, '2026-07-01 08:12:07'),
(18, 81, '201022988711', '*صباح الخير* 🌿\r\n\r\n*نحب نطمئن أن الخدمة شغالة بشكل مناسب لحضراتكم،*\r\n> وفي حال وجود أي ملاحظات أو مقترحات تطوير نرحّب بها في أي وقت.\r\n\r\n*وجودكم معانا محل تقدير*🤝\r\n\r\n🎗️ `إنجاز للحلول الذكية.`', '2026-07-01 11:13:00', 3, 15, 'sent', '{\"success\":true,\"message_id\":\"msg_1782893685871_5k8zy\",\"status\":\"queued\"}', 1, '2026-07-01 08:12:07'),
(19, 80, '201101121455', '*صباح الخير* 🌿\r\n\r\n*نحب نطمئن أن الخدمة شغالة بشكل مناسب لحضراتكم،*\r\n> وفي حال وجود أي ملاحظات أو مقترحات تطوير نرحّب بها في أي وقت.\r\n\r\n*وجودكم معانا محل تقدير*🤝\r\n\r\n🎗️ `إنجاز للحلول الذكية.`', '2026-07-01 11:13:00', 3, 15, 'sent', '{\"success\":true,\"message_id\":\"msg_1782893700501_o3dxu\",\"status\":\"queued\"}', 1, '2026-07-01 08:12:08'),
(20, 79, '201284206086', '*صباح الخير* 🌿\r\n\r\n*نحب نطمئن أن الخدمة شغالة بشكل مناسب لحضراتكم،*\r\n> وفي حال وجود أي ملاحظات أو مقترحات تطوير نرحّب بها في أي وقت.\r\n\r\n*وجودكم معانا محل تقدير*🤝\r\n\r\n🎗️ `إنجاز للحلول الذكية.`', '2026-07-01 11:13:00', 3, 15, 'sent', '{\"success\":true,\"message_id\":\"msg_1782893727667_hehlb\",\"status\":\"queued\"}', 1, '2026-07-01 08:12:08'),
(21, 78, '966505774960', '*صباح الخير* 🌿\r\n\r\n*نحب نطمئن أن الخدمة شغالة بشكل مناسب لحضراتكم،*\r\n> وفي حال وجود أي ملاحظات أو مقترحات تطوير نرحّب بها في أي وقت.\r\n\r\n*وجودكم معانا محل تقدير*🤝\r\n\r\n🎗️ `إنجاز للحلول الذكية.`', '2026-07-01 11:13:00', 3, 15, 'sent', '{\"success\":true,\"message_id\":\"msg_1782893735296_awcyu\",\"status\":\"queued\"}', 1, '2026-07-01 08:12:08'),
(22, 76, '201111150639', '*صباح الخير* 🌿\r\n\r\n*نحب نطمئن أن الخدمة شغالة بشكل مناسب لحضراتكم،*\r\n> وفي حال وجود أي ملاحظات أو مقترحات تطوير نرحّب بها في أي وقت.\r\n\r\n*وجودكم معانا محل تقدير*🤝\r\n\r\n🎗️ `إنجاز للحلول الذكية.`', '2026-07-01 11:13:00', 3, 15, 'sent', '{\"success\":true,\"message_id\":\"msg_1782893762348_fggod\",\"status\":\"queued\"}', 1, '2026-07-01 08:12:08'),
(23, 75, '201150979340', '*صباح الخير* 🌿\r\n\r\n*نحب نطمئن أن الخدمة شغالة بشكل مناسب لحضراتكم،*\r\n> وفي حال وجود أي ملاحظات أو مقترحات تطوير نرحّب بها في أي وقت.\r\n\r\n*وجودكم معانا محل تقدير*🤝\r\n\r\n🎗️ `إنجاز للحلول الذكية.`', '2026-07-01 11:13:00', 3, 15, 'sent', '{\"success\":true,\"message_id\":\"msg_1782893798327_9cdmr\",\"status\":\"queued\"}', 1, '2026-07-01 08:12:09');

-- --------------------------------------------------------

--
-- Table structure for table `whatsapp_schedules`
--

CREATE TABLE `whatsapp_schedules` (
  `id` int UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'all' COMMENT 'all, active, suspended, debt, expiring',
  `warning_days` int NOT NULL DEFAULT '30',
  `frequency` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'daily' COMMENT 'daily, weekly, monthly, interval',
  `custom_interval_days` int NOT NULL DEFAULT '1',
  `send_at_time` time NOT NULL DEFAULT '10:00:00',
  `last_run` datetime DEFAULT NULL,
  `next_run` datetime NOT NULL,
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '1=active, 0=paused',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `client_subscriptions`
--
ALTER TABLE `client_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `service_id` (`service_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_expense_date` (`expense_date`),
  ADD KEY `idx_category` (`category`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `subscription_id` (`subscription_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `service_plans`
--
ALTER TABLE `service_plans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_plan_service` (`service_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key` (`key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `whatsapp_logs`
--
ALTER TABLE `whatsapp_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `sent_by` (`sent_by`);

--
-- Indexes for table `whatsapp_queue`
--
ALTER TABLE `whatsapp_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status_send_at` (`status`,`send_at`);

--
-- Indexes for table `whatsapp_schedules`
--
ALTER TABLE `whatsapp_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status_next_run` (`status`,`next_run`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=150;

--
-- AUTO_INCREMENT for table `client_subscriptions`
--
ALTER TABLE `client_subscriptions`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=309;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=126;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=257;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `service_plans`
--
ALTER TABLE `service_plans`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `whatsapp_logs`
--
ALTER TABLE `whatsapp_logs`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=163;

--
-- AUTO_INCREMENT for table `whatsapp_queue`
--
ALTER TABLE `whatsapp_queue`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=90;

--
-- AUTO_INCREMENT for table `whatsapp_schedules`
--
ALTER TABLE `whatsapp_schedules`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `clients`
--
ALTER TABLE `clients`
  ADD CONSTRAINT `clients_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `client_subscriptions`
--
ALTER TABLE `client_subscriptions`
  ADD CONSTRAINT `client_subscriptions_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `client_subscriptions_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `client_subscriptions_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `invoices_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`subscription_id`) REFERENCES `client_subscriptions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payments_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `services`
--
ALTER TABLE `services`
  ADD CONSTRAINT `services_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `service_plans`
--
ALTER TABLE `service_plans`
  ADD CONSTRAINT `fk_plan_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `whatsapp_logs`
--
ALTER TABLE `whatsapp_logs`
  ADD CONSTRAINT `whatsapp_logs_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `whatsapp_logs_ibfk_2` FOREIGN KEY (`sent_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
