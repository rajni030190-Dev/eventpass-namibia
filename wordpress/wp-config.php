<?php
/**
 * EventPass Namibia - WordPress Configuration
 * Standardized WordPress Core Architecture
 */

// ** Database settings - EventPass Namibia ** //
define( 'DB_NAME', 'eventpass_namibia' );
define( 'DB_USER', 'root' );
define( 'DB_PASSWORD', 'Email99!' );
define( 'DB_HOST', '127.0.0.1:3306' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

// ** Authentication unique keys and salts ** //
define( 'AUTH_KEY',         'SVvdFv}.VUZpK#cMWiS~X$;w4I=l6o1J<jwPT7=c-/S5n5F%kWm%GqQ@j6wrcVPS' );
define( 'SECURE_AUTH_KEY',  '*.5Mjw:9Fa(x@%%-G-)C3s_!?XTM|JM>TdIwt{!M=2[^W@dopun!iHXk86hI5.Ar' );
define( 'LOGGED_IN_KEY',    '8fss=aYwd-HL:6Jb,!KS^!)aEZwHUzVxfVdx]fy*G9#~##=CEta0n7b.OZh$]xP%' );
define( 'NONCE_KEY',        'atuk%XUJ!%u)MY){h^P-WR~-g8~Pj3-*8vZW)hWRemiLzV!!/nu)c/mQJjZmrBcK' );
define( 'AUTH_SALT',        'ciXkBK6jtO0eMu=OfhGqGg)%7Sc}S,CP.U-4uRvFI=@DsZSt^_YaunCTHe9|p#R_' );
define( 'SECURE_AUTH_SALT', 'LSX=RfsxtJMWJ#7ZbQr2aBz@>T8$(Aw{}Aosp3@,[B3tLr*2EG[+oEBf~z+XDBPz' );
define( 'LOGGED_IN_SALT',   '6i?FS[UO^2::%a=l:x!i}|fZICiyJc1aQN3@t_;/7h{MF^E~u0Ea3agm+22jnEkE' );
define( 'NONCE_SALT',       '4}g-|TxZ(akOD9hZ~HHp8#>W+-hg#$745b2z}}8h^XuOuvQDrT}jvCg7XsKH[eIX' );

// ** Database Table prefix ** //
$table_prefix = 'wp_';

// ** Site URLs ** //
define( 'WP_HOME', 'http://localhost:8080' );
define( 'WP_SITEURL', 'http://localhost:8080' );

// ** File System Method ** //
define( 'FS_METHOD', 'direct' );

// ** Debugging Mode ** //
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
@ini_set( 'display_errors', 0 );

// ** Disable Automatic Updates ** //
define( 'AUTOMATIC_UPDATER_DISABLED', true );
define( 'WP_AUTO_UPDATE_CORE', false );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
