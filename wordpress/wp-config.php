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

// =============================================================================
// EVENTPASS NAMIBIA - BREVO PRODUCTION SMTP CONFIGURATION (SOW MODULE 3 & 4)
// Brevo (formerly Sendinblue) is the EXCLUSIVE production SMTP provider.
// Standard endpoint: smtp-relay.brevo.com via TLS over Port 587.
// In local development (localhost:8080), without credentials configured,
// the Resilient Local Mail Interceptor captures all emails safely into the audit trail.
// When deploying to production, provide the authenticated Brevo credentials below
// or configure via environment variables (BREVO_SMTP_USER, BREVO_SMTP_KEY).
// =============================================================================
define( 'BREVO_SMTP_HOST', getenv('BREVO_SMTP_HOST') ?: 'smtp-relay.brevo.com' );
define( 'BREVO_SMTP_PORT', getenv('BREVO_SMTP_PORT') ? intval(getenv('BREVO_SMTP_PORT')) : 587 );
define( 'BREVO_SMTP_SECURE', getenv('BREVO_SMTP_SECURE') ?: 'tls' );
define( 'BREVO_SMTP_USER', getenv('BREVO_SMTP_USER') ?: '' ); // Provide authenticated Brevo SMTP login or configure via Admin Hub
define( 'BREVO_SMTP_KEY', getenv('BREVO_SMTP_KEY') ?: '' );   // Provide Brevo SMTP master key or configure via Admin Hub
define( 'BREVO_SENDER_EMAIL', getenv('BREVO_SENDER_EMAIL') ?: 'no-reply@eventpass.com.na' );
define( 'BREVO_SENDER_NAME', getenv('BREVO_SENDER_NAME') ?: 'EventPass Namibia' );

// =============================================================================
// FINATIC NAMIBIA PAYMENT GATEWAY CONFIGURATION (SOW MODULE 5)
// Finatic Technologies is the primary Namibian payment processor (Visa / MC / EFT).
// When staging or running demo test transactions, leave FINATIC_LIVE_MODE as false.
// When client provides official live merchant credentials, uncomment & fill below:
// =============================================================================
// define( 'FINATIC_LIVE_MODE', true ); // Set true to deduct real money via live gateway
// define( 'FINATIC_MERCHANT_ID', 'FINATIC_MERCHANT_ID_HERE' );
// define( 'FINATIC_API_KEY', 'FINATIC_PUBLIC_API_KEY_HERE' );
// define( 'FINATIC_SECRET_KEY', 'FINATIC_SECRET_HMAC_KEY_HERE' );


/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
