<?php

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'openfoodsource_cms');

/** MySQL database username */
define('DB_USER', 'openfood_user');

/** MySQL database password */
define('DB_PASSWORD', 'WoordPrez DayTaBaze PazzWoorD');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         '****************************************************************');
define('SECURE_AUTH_KEY',  '***                                                          ***');
define('LOGGED_IN_KEY',    '***   FILL IN THESE VALUES FROM THE WORDPRESS CONFIG FILE    ***');
define('NONCE_KEY',        '***                                                          ***');
define('AUTH_SALT',        '***    Normally this configuration lives in the wordpress    ***');
define('SECURE_AUTH_SALT', '***  directory, but it is here for convenience and security  ***');
define('LOGGED_IN_SALT',   '***                                                          ***');
define('NONCE_SALT',       '****************************************************************');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * WordPress Localized Language, defaults to English.
 *
 * Change this to localize WordPress. A corresponding MO file for the chosen
 * language must be installed to wp-content/languages. For example, install
 * de_DE.mo to wp-content/languages and set WPLANG to 'de_DE' to enable German
 * language support.
 */
define('WPLANG', '');

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 */
define('WP_DEBUG', false);

/**
 * For automatic updates
 *
 */

define( 'AUTOMATIC_UPDATER_DISABLED', true );
define( 'WP_AUTO_UPDATE_CORE', false );

?>