<?php





 // Added by WP Rocket

/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/documentation/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'leitao-irmao' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'root' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'pvmyo27xzgkked45un5sblvyafw2qqatreu2zyy1dcp94yesklyfb8puuexg44vy' );
define( 'SECURE_AUTH_KEY',  'sigbbtrgf6doo1ttcsw2zm8h75ouxm0ydc8qjhcreawal4nv8jvqfstlsuisxdyl' );
define( 'LOGGED_IN_KEY',    'knfvm3olxu4uncf7mbtj63pqfoebg9k4elusq6ughcuuums7upuvu07zb5etgxvk' );
define( 'NONCE_KEY',        'f2gbttp1qqkk9pcemgon9hk8ocbhllgveatp8zij1u3lmmhnosb1pix5jvn5ibzt' );
define( 'AUTH_SALT',        '0axdlhidktak2489f2m0gbn7o7b7pj3bcdtsdmuz4grv6tppya48muctlpwop7p8' );
define( 'SECURE_AUTH_SALT', 'egguynv8lzdj2rq6ti3fnwoqopg5mdrcvublno570zrpxl8jkatcookglaqwe0pp' );
define( 'LOGGED_IN_SALT',   '4g0kcmqpimsf2psetaxtcofbqstkutf9udlpe7f9latumf9jspl7utzs668jdskl' );
define( 'NONCE_SALT',       'clivnyswvaooyuiz8gpirqvfdioelhgvqu9juonsdjjziutwo4pil1dchqw9rlrx' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_lir';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/documentation/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );

//define( 'DISALLOW_FILE_EDIT', true );

/* Add any custom values between this line and the "stop editing" line. */

define('WP_MEMORY_LIMIT','1024M');

// Limite de execução estendido para dev local (Elementor CSS regeneration pode ser lento)
if ( WP_DEBUG ) {
    @ini_set( 'max_execution_time', 300 );
}

define( 'WP_HOME', 'http://localhost' );
define( 'WP_SITEURL', 'http://localhost' );

//define( 'DUPLICATOR_AUTH_KEY', 'YbyAm[-PN{$mM_Q]u(N9-iGdL5T+ 9,<U:F8T|^DS8Ql*`!hZ|[z9xuAre ,m;^6' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
