SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;


INSERT INTO `ofs_accounts` (`account_id`, `internal_key`, `internal_subkey`, `account_number`, `sub_account_number`, `description`) VALUES
(1, 'producer fee', '', '', '', 'producer fee'),
(2, 'customer fee', '', '', '', 'customer fee'),
(3, 'delivery cost', '', '', '', 'delivery cost'),
(4, 'membership dues', '', '', '', 'membership renewal'),
(5, 'payment received', '', '', '', 'payment received'),
(6, 'paypal charges', '', '', '', 'paypal fees'),
(7, 'payment sent', '', '', '', 'payment sent'),
(8, 'contract transport', '', '', '', 'Contract use of transportation services'),
(9, 'mileage paid', '', '', '', 'Mileage paid as work credit'),
(10, 'work credit', '', '', '', 'Work credits to volunteers'),
(11, 'lost product', '', '', '', 'Product lost'),
(12, 'broken product', '', '', '', 'Product damaged (broken)'),
(13, 'thawed product', '', '', '', 'Product damaged (thawed)'),
(14, 'frozen product', '', '', '', 'Product damaged (frozen)'),
(15, 'order cost', '', '', '', 'Visitor fee (per order)'),
(16, 'pricing error', '', '', '', 'Error because of pricing'),
(17, 'writeoff', '', '', '', 'Customer service adjustments'),
(18, 'subcategory fee', '', '', '', 'Account for subcategory fee'),
(19, 'product fee', '', '', '', 'Account for product fee'),
(20, 'volume discount', '', '', '', 'Discount for high-volume orders'),
(21, 'payment made', '', '', '', 'Account for: payments made'),
(22, 'advertising', '', '', '', 'advertising'),
(23, 'site coordinators', '', '', '', 'Site Coordinators'),
(24, 'bad debt', '', '', '', 'Bad Debt'),
(25, 'marketing', '', '', '', 'marketing (food samples, etc)');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
