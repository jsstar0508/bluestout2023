<?php
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
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'bluestout2023_db' );

/** Database username */
define( 'DB_USER', 'bluestoutdev' );

/** Database password */
define( 'DB_PASSWORD', 'bpXWb77Un6v8R3ZA6Dda' );

/** Database hostname */
define( 'DB_HOST', '127.0.0.1' );

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
define( 'AUTH_KEY',         'iI_]ectKp8^W9o&&):Ff[rN^Z+[>yXP*UKsFI7S-GF[ Qno{+~xnYGCvl8enwH6J' );
define( 'SECURE_AUTH_KEY',  'O<|06s<8,868_94;A[)8ro/.7U])h[C.tS{d6iGi nv4;_Chb]VZY#RT}X>$USBz' );
define( 'LOGGED_IN_KEY',    'g,;#UO(J<OC8v`/X0y^1*V8oa )OB5c]!z#_ci8nf$WO&ghA,b|+6jGBvj6$Pn4W' );
define( 'NONCE_KEY',        '< & `Ce/rS]rMy`$&AS~0[L7@I[R}a e^MyP|{oKJ[vg)z#Pb_XiYcCppxQ,db/g' );
define( 'AUTH_SALT',        '{x)`(d_;FogY[G36^t[TN?9`aOaZ:2*P2vV$1*+>RL}TvO~3&9*i&0o#dKj9@gLg' );
define( 'SECURE_AUTH_SALT', 'WPFY?A+/@v:.d-h=u8G?@7Nd.0~FCb_oL{vJ.?=tkST2e3(@69+Z`#ql8Lg5.K{p' );
define( 'LOGGED_IN_SALT',   '@rjcNZFNP##%9&dX%+S4dwxF!si<_UNc%3;DIF-xAF,O;Ay:2b9z ,;+f/P.z:%r' );
define( 'NONCE_SALT',       'g5k:PId+uiW6r;#k#:sHd[l8wNDBS62Qb=YO_1SF|alYuH]P6|CIOK=B.QRuiiFe' );

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
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', true );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';