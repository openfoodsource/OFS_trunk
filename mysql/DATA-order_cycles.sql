SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;


INSERT INTO `order_cycles` (`delivery_id`, `date_open`, `date_closed`, `order_fill_deadline`, `delivery_date`, `msg_all`, `msg_bottom`, `coopfee`, `invoice_price`, `producer_markdown`, `retail_markup`, `wholesale_markup`) VALUES
(1, '2014-10-26 00:00:01', '2014-11-02 18:00:00', '2014-11-04 18:00:00', '2014-11-06', 'This is an invoice note.', '', 0, 1, 10, 25.00, 15),
(2, '2014-11-09 00:00:01', '2014-11-16 18:00:00', '2014-11-18 18:00:00', '2014-11-20', 'This is a different invoice note.', '', 0, 1, 10, 25.00, 15);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
