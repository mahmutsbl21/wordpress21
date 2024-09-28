<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'rojhat2024' );

/** Database username */
define( 'DB_USER', 'rojhat21' );

/** Database password */
define( 'DB_PASSWORD', 'rojhat2121' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

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
define( 'AUTH_KEY',         'L]ES[5,KBSp/Y5M;<L4{%Hl;ec-0a__`;LnAh.?U9Aki,59#6EDs!]uB)k~NQbxm' );
define( 'SECURE_AUTH_KEY',  'H[3L65/)laRl[z3CK)P^l[/r ezvYwGP*cS& XW.H0}=c#%76ffWBo(t=U{@ieSt' );
define( 'LOGGED_IN_KEY',    'q Q,{R*94Ki}RLy|rIicZLjl1r42tc]gKE*6 3CYnfQsd)/v $L.#2_<lX[{-|Zo' );
define( 'NONCE_KEY',        'E?G~3K2GV<Syj`N.,yCjr3V~Bfu!@-)afxDeYz[Puec*E@ sL?{}f$_Uww|R6G2o' );
define( 'AUTH_SALT',        'sJ5pW4M>Laqy:D[l*)(Qk>x8.fXT3M<5f3lzImKk)a`xd1hA+]*xi_c$)3JZ5*(I' );
define( 'SECURE_AUTH_SALT', 'W,B<h=suN=]:x}BfOVBpr .aYmS2,&9;GP~`6Tx0.^:DDyC9!ZA6_*L<%2MBjDkI' );
define( 'LOGGED_IN_SALT',   'l1[yn)[A=H=Yun%u%AM8L-`s&dVv>.E=Sg$Bgr!2/pfxrD]8T_D]]@1$3fbFeR.)' );
define( 'NONCE_SALT',       'C0f~=emo?YqWa<RgTT3%d_eU0Hb#99n-5%S4wBNz8l%4OL|v&Hnly24dcam~?:Z]' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

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
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
