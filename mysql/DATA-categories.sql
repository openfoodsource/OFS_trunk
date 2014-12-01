SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;


INSERT INTO `categories` (`category_id`, `category_name`, `category_desc`, `taxable`, `parent_id`, `sort_order`) VALUES
(1, 'Vegetables', 'Healthy garden vegetables grown without herbicides and pesticides by Oklahoma farmers.', 0, 0, 1),
(2, 'Meats & Poultry', 'Order a wide variety of all natural meats -- farm raised venison, lamb, grass finished beef, custom-fed pork.', 0, 0, 2),
(3, 'Grains, Flours and Pastas', 'Bake your bread from Oklahoma Wheat for the finest flavor.  Whether you buy stone ground flour or the wheat to grind, this is the best!', 0, 0, 5),
(4, 'Dairy and Eggs', 'Yes, we have eggs from free ranging chickens.', 0, 0, 4),
(5, 'Condiments, Sauces, Spices', '', 0, 0, 7),
(6, 'Baked Goods and Desserts', '', 0, 0, 6),
(7, 'Nuts, Seeds & Beans', 'Pecans, almonds and more.', 0, 0, 9),
(8, 'Non-Food Items', '', 1, 0, 23),
(9, 'Beverages', '', 0, 0, 14),
(10, 'Poultry', '', 0, 0, 3),
(11, 'Jams, Jellies, & Sweeteners', '', 0, 0, 11),
(12, 'Farming Products and Animals', 'Products to be used by farmers such as seed, animal feeds and sometimes live farm animals.', 0, 0, 20),
(13, 'Fruits', '', 0, 0, 8),
(14, 'Health and Beauty', '', 1, 0, 21),
(15, 'Gift Baskets/Boxes', '', 1, 0, 24),
(19, 'Herbs', '', 0, 0, 10),
(20, 'Candy/Fudge', '', 0, 0, 12),
(21, 'Pantry', '', 0, 0, 13),
(23, 'Household Supplies', 'Locally made household cleaners, detergents, sachets, and more.', 1, 0, 25),
(24, 'Pet-Related', '', 1, 0, 26),
(25, '** Charity **', 'Donate money to help feed the poor.  You choose the amount.', 0, 0, 27),
(26, 'Sales Tax', 'Sales Tax Not Included in Price of Product', 0, 0, 28),
(27, 'Prepared Foods (Refrigerated/Frozen)', 'Pre-made Entrees, Soups, Breads, Side Dishes', 0, 0, 15),
(28, 'Meat Processing Fees', 'Fees collected by Co-op to pay processors.', 0, 0, 30),
(30, 'Prepared Food (Non-Refrigerated)', 'Entrees, side dishes, soups that do not need refrigeration', 0, 0, 19),
(31, 'Food Cooperative Items', NULL, 1, 0, 31),
(32, 'Services', NULL, 1, 0, 32),
(33, 'Discounts', '', 0, 0, 33),
(34, 'Unavailable Items', 'Items not currently available but which might be again at some future date.', 0, 0, 34),
(35, 'Opportunities', 'Opportunities for involvement', 0, 0, 35),
(36, 'Live Plants and Gardening', 'Anything related to gardening', 1, 0, 37),
(37, 'Miscellaneous Non-Taxable', '', 0, 0, 38),
(38, 'Recreation', '', 1, 0, 39),
(39, 'Vegetable Plants & Seeds', '', 1, 0, 36);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
