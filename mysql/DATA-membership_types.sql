SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;


INSERT INTO `ofs_membership_types` (`membership_type_id`, `set_auth_type`, `initial_cost`, `order_cost`, `order_cost_type`, `customer_fee_percent`, `producer_fee_percent`, `membership_class`, `membership_description`, `pending`, `enabled_type`, `revert_to`, `renew_cost`, `expire_after`, `expire_type`, `expire_message`) VALUES
(1, '+member', 150, 0, 'fixed', 25.000, 10.000, 'Charter member', '$150.00 for the first year &mdash; plus $25.00 per year thereafter.', 0, 3, '1,2,3', 25, 12, 'month', ''),
(2, '+member', 75, 0, 'fixed', 25.000, 10.000, 'Regular member', '$75.00 annual membership.', 0, 3, '2,1,3', 75, 12, 'month', ''),
(3, '+member', 0, 10, 'fixed', 25.000, 100.000, 'Occasional shopper', 'No annual dues, but $10.00 will be added to each order.<br><em>Visitors are not be permitted to sell without upgrading to an annually-paid membership option.</em>', 0, 3, '3,1,2', 0, 0, '', ''),
(4, '+member', 0, 0, 'fixed', 25.000, 100.000, 'Free trial', 'Order once for free and choose a regular membership option with your second order. NOTE: Your first order will begin when you open your first shopping basket.<br><em>Free trial memberships are not permitted to sell.', 0, 1, '1,2,3', 0, 1, 'order', 'Your free trial has expired. Please choose a regular membership type.');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
