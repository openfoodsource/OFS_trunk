SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;


INSERT INTO `production_types` (`production_type_id`, `prodtype`, `proddesc`) VALUES
(1, 'Certified Organic', 'Certified organic by the Department of Agriculture'),
(2, 'All Natural', 'Substantially complies with organic standards, but they haven''t gone through the state certification process'),
(3, '80% Organic', ''),
(4, 'Certified Naturally Grown', ''),
(5, 'Production not specified', '');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
