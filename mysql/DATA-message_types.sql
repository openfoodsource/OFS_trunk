SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;


INSERT INTO `message_types` (`message_type_id`, `key1_target`, `key2_target`, `description`) VALUES
(0, '', '', 'orphaned message'),
(1, 'basket_items.bpid', '', 'customer notes to producer'),
(2, 'ledger.transaction_id', '', 'ledger comment'),
(3, 'ledger.transaction_id', '', 'ledger batch number'),
(4, 'ledger.transaction_id', '', 'ledger memo'),
(5, 'ledger.transaction_group_id', '', 'adjustment group memo'),
(6, 'ledger.transaction_id', '', 'ledger paypal comment');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
