SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;


INSERT INTO `ofs_sites` (`site_id`, `site_type`, `site_short`, `site_long`, `delivery_type`, `site_description`, `delivery_charge`, `route_id`, `hub_id`, `truck_code`, `delivery_postal_code`, `inactive`) VALUES
(1, 'customer', 'ONE', 'Downtown', 'P', 'Collection point/hub for downtown', 0, 1, 1, 'TRK', '12356', 1),
(2, 'customer', 'TWO', 'Westside', 'P', 'West industrial park in the Wireworks building', 0, 1, 1, 'TRK', '12376', 0),
(3, 'customer', 'THREE', 'South neighborhood', 'P', 'Frogger high school in the south residential district.', 0, 1, 1, 'TRK', '12375', 0),
(4, 'institution', 'FOUR', 'East metro', 'P', 'Located in the plaza just north of the skybridge.', 0, 1, 1, 'TRK', '12393', 0),
(5, 'customer', 'FIVE', 'Shipped', 'D', 'Products will be shipped directly to customer homes via Turtle Delivery.', 10, 1, 1, 'TRK', '', 0);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
