<?php
/**
 * Template Name: Customer Dashboard
 *
 * @package EventPass_Namibia
 */

if ( ! is_user_logged_in() ) {
    wp_redirect( home_url('/customer-login') );
    exit;
}

$current_user = wp_get_current_user();
$user_id      = $current_user->ID;
$full_name    = !empty($current_user->display_name) ? $current_user->display_name : $current_user->user_login;
$user_email   = $current_user->user_email;
$phone_number = get_user_meta( $user_id, 'phone_number', true );
if ( empty($phone_number) ) $phone_number = '+264 (Unspecified)';

// Calculate user avatar initials
$words = explode( ' ', trim( $full_name ) );
$initials = '';
foreach ( $words as $w ) {
    if ( ! empty($w) ) {
        $initials .= strtoupper( mb_substr( $w, 0, 1 ) );
    }
    if ( strlen($initials) >= 2 ) break;
}
if ( empty($initials) ) $initials = 'EP';

// Query user passes
$user_tickets = get_posts( array(
    'post_type'      => 'ep_ticket',
    'post_status'    => 'publish',
    'posts_per_page' => 50,
    'meta_query'     => array(
        'relation' => 'OR',
        array(
            'key'     => '_ep_ticket_user_id',
            'value'   => $user_id,
            'compare' => '=',
        ),
        array(
            'key'     => '_ep_ticket_email',
            'value'   => $user_email,
            'compare' => '=',
        ),
    ),
) );

// Query real user order receipts
$user_orders = get_posts( array(
    'post_type'      => 'ep_order',
    'post_status'    => array('publish', 'pending', 'draft'),
    'posts_per_page' => 50,
    'orderby'        => 'date',
    'order'          => 'DESC',
    'meta_query'     => array(
        'relation' => 'OR',
        array(
            'key'     => '_ep_order_user_id',
            'value'   => $user_id,
            'compare' => '=',
        ),
        array(
            'key'     => '_ep_order_customer_email',
            'value'   => $user_email,
            'compare' => '=',
        ),
    ),
) );

get_header();
?>

<style>
@media print {
    body * {
        visibility: hidden !important;
    }
    #printPassContainer, #printPassContainer * {
        visibility: visible !important;
    }
    #printPassContainer {
        position: fixed !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 20px !important;
        background: #ffffff !important;
        color: #0B1B3D !important;
        display: block !important;
        box-shadow: none !important;
        border: none !important;
    }
    .no-print, header, footer, .ep-page-banner, .ep-dashboard-sidebar {
        display: none !important;
    }
}
</style>

<!-- Banner Section -->
<div class="ep-page-banner no-print" style="background: linear-gradient(135deg, var(--ep-navy-deep) 0%, var(--ep-navy) 100%); color: #ffffff; padding: 3.5rem 0 2.5rem;">
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <span style="color: var(--ep-cyan); font-weight: 700; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.5px;">Customer Portal</span>
                <h1 style="font-size: 2.25rem; font-weight: 800; margin: 0.25rem 0 0.5rem; line-height: 1.2;">My Bookings & Tickets</h1>
                <p style="font-size: 1rem; color: #CBD5E1; margin: 0;">Access your digital tickets, download branded QR entry passes and review booking history.</p>
            </div>
            <div>
                <a href="<?php echo esc_url(home_url('/explore-events')); ?>" class="ep-btn ep-btn-primary ep-btn-sm">
                    <span>Find More Events</span>
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container" style="padding: 3.5rem 1.5rem 5rem;">
    <?php if ( isset($_GET['welcome']) ) : ?>
        <div class="no-print" style="background: #ECFDF5; border: 1px solid #10B981; color: #065F46; padding: 1rem 1.5rem; border-radius: 12px; font-size: 0.95rem; margin-bottom: 2rem; display: flex; align-items: center; justify-content: space-between;">
            <div>
                <strong>🎉 Welcome to EventPass Namibia, <?php echo esc_html($full_name); ?>!</strong>
                <p style="margin: 0.2rem 0 0; font-size: 0.85rem;">Your customer account is now fully active. You can browse live events and reserve secure QR passes.</p>
            </div>
            <a href="<?php echo esc_url(home_url('/explore-events')); ?>" class="ep-btn ep-btn-primary ep-btn-sm" style="background: #059669; border-color: #059669;">
                <span>Browse Events &rarr;</span>
            </a>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 280px 1fr; gap: 2.5rem; align-items: start;">
        <!-- Left Sidebar: Authenticated User Profile -->
        <div class="ep-dashboard-sidebar no-print" style="background: #ffffff; border: 1px solid var(--ep-slate-200); border-radius: var(--ep-radius-lg); padding: 1.75rem; box-shadow: var(--ep-shadow-sm);">
            <div style="text-align: center; padding-bottom: 1.5rem; border-bottom: 1px solid var(--ep-slate-100); margin-bottom: 1.5rem;">
                <div style="width: 72px; height: 72px; border-radius: 50%; background: linear-gradient(135deg, var(--ep-navy) 0%, var(--ep-teal-dark) 100%); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.75rem; font-weight: 800; margin: 0 auto 0.75rem;">
                    <?php echo esc_html( $initials ); ?>
                </div>
                <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--ep-navy); margin: 0;"><?php echo esc_html( $full_name ); ?></h3>
                <span style="font-size: 0.85rem; color: var(--ep-slate-600); display: block; word-break: break-all;"><?php echo esc_html( $user_email ); ?></span>
                <span style="font-size: 0.8rem; color: var(--ep-slate-500); display: block; margin-top: 0.2rem;">📱 <?php echo esc_html( $phone_number ); ?></span>
                <span style="display: inline-block; margin-top: 0.5rem; background: rgba(16,185,129,0.1); color: var(--ep-success); font-size: 0.75rem; font-weight: 700; padding: 0.2rem 0.6rem; border-radius: 9999px;">Verified Customer</span>
            </div>

            <ul style="list-style: none; display: flex; flex-direction: column; gap: 0.5rem; padding: 0; margin: 0;">
                <li><a href="#tickets" style="display: block; padding: 0.6rem 1rem; border-radius: 6px; background: rgba(0,180,216,0.1); color: var(--ep-teal-dark); font-weight: 700;">🎟️ My Digital Tickets (<?php echo count($user_tickets); ?>)</a></li>
                <li><a href="#receipts" style="display: block; padding: 0.6rem 1rem; border-radius: 6px; color: var(--ep-slate-600); font-weight: 500;">🧾 Order Receipts (<?php echo count($user_orders); ?>)</a></li>
                <li><a href="<?php echo esc_url(home_url('/explore-events')); ?>" style="display: block; padding: 0.6rem 1rem; border-radius: 6px; color: var(--ep-slate-600); font-weight: 500;">🔍 Discover Events</a></li>
                <li><a href="<?php echo esc_url(home_url('/contact-us')); ?>" style="display: block; padding: 0.6rem 1rem; border-radius: 6px; color: var(--ep-slate-600); font-weight: 500;">💬 Attendee Support</a></li>
                <li><a href="<?php echo esc_url( wp_logout_url( home_url('/customer-login?loggedout=1') ) ); ?>" style="display: block; padding: 0.6rem 1rem; border-radius: 6px; color: var(--ep-danger); font-weight: 600;">🚪 Sign Out</a></li>
            </ul>
        </div>

        <!-- Right Main Panel: Digital Passes -->
        <div class="ep-dashboard-content">
            <h2 id="tickets" class="no-print" style="font-size: 1.5rem; font-weight: 800; color: var(--ep-navy); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                <span>🎟️</span> Active Digital Passes & Entry Tickets
            </h2>

            <?php if ( ! empty( $user_tickets ) ) : ?>
                <?php foreach ( $user_tickets as $t_post ) : 
                    $t_id       = $t_post->ID;
                    $ev_id      = get_post_meta( $t_id, '_ep_ticket_event_id', true );
                    $ev_title   = $ev_id ? get_the_title( $ev_id ) : get_the_title( $t_id );
                    $tier_name  = get_post_meta( $t_id, '_ep_ticket_tier', true ) ?: 'General Admission';
                    $token      = get_post_meta( $t_id, '_ep_ticket_token', true ) ?: ('EP-NAM-' . $t_id);
                    $holder     = get_post_meta( $t_id, '_ep_ticket_holder', true ) ?: $full_name;
                    $venue      = get_post_meta( $ev_id, '_ep_event_venue', true ) ?: 'Independence Stadium, Windhoek';
                    $event_date = get_post_meta( $ev_id, '_ep_event_date', true ) ?: 'Upcoming 2026';
                    $event_time = get_post_meta( $ev_id, '_ep_event_time', true ) ?: '18:00 - 02:00 CAT';
                    $status     = get_post_meta( $t_id, '_ep_ticket_status', true ) ?: 'valid';
                    $qr_svg     = function_exists('eventpass_get_qr_svg') ? eventpass_get_qr_svg($token, 130) : '';

                    $pass_data = [
                        'id'         => $t_id,
                        'eventTitle' => $ev_title,
                        'venue'      => $venue,
                        'date'       => $event_date,
                        'time'       => $event_time,
                        'tier'       => $tier_name,
                        'holder'     => $holder,
                        'token'      => $token,
                        'status'     => $status,
                        'qr_svg'     => $qr_svg,
                    ];
                ?>
                    <!-- Digital Pass Card -->
                    <div class="ep-pass-card no-print" style="background: #ffffff; border: 1px solid var(--ep-slate-200); border-radius: var(--ep-radius-lg); overflow: hidden; margin-bottom: 2rem; box-shadow: var(--ep-shadow-md); display: grid; grid-template-columns: 1.75fr 1fr;">
                        <div style="padding: 2rem; border-right: 2px dashed var(--ep-slate-200); position: relative;">
                            <span class="ep-category-badge" style="position: static; margin-bottom: 0.75rem; display: inline-block;">Official Event Pass</span>
                            <h3 style="font-size: 1.4rem; font-weight: 800; color: var(--ep-navy); margin: 0.25rem 0 0.75rem;">
                                <?php echo esc_html( $ev_title ); ?>
                            </h3>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin: 1.25rem 0;">
                                <div>
                                    <span style="font-size: 0.75rem; text-transform: uppercase; color: var(--ep-slate-600); font-weight: 700; display: block;">Date & Time</span>
                                    <strong style="font-size: 0.95rem; color: var(--ep-navy);"><?php echo esc_html( $event_date ); ?></strong>
                                    <small style="display: block; color: #64748B;"><?php echo esc_html( $event_time ); ?></small>
                                </div>
                                <div>
                                    <span style="font-size: 0.75rem; text-transform: uppercase; color: var(--ep-slate-600); font-weight: 700; display: block;">Venue</span>
                                    <strong style="font-size: 0.95rem; color: var(--ep-navy);"><?php echo esc_html( $venue ); ?></strong>
                                </div>
                            </div>

                            <div style="display: flex; gap: 1.5rem; border-top: 1px solid var(--ep-slate-100); padding-top: 1rem; align-items: center; flex-wrap: wrap;">
                                <div>
                                    <span style="font-size: 0.75rem; color: var(--ep-slate-600); display: block;">Tier Category</span>
                                    <strong style="color: var(--ep-teal-dark); font-size: 1rem;"><?php echo esc_html( $tier_name ); ?></strong>
                                </div>
                                <div>
                                    <span style="font-size: 0.75rem; color: var(--ep-slate-600); display: block;">Ticket Holder</span>
                                    <strong style="color: var(--ep-navy); font-size: 1rem;"><?php echo esc_html( $holder ); ?></strong>
                                </div>
                                <div>
                                    <span style="font-size: 0.75rem; color: var(--ep-slate-600); display: block;">Status</span>
                                    <?php if ( $status === 'valid' ) : ?>
                                        <span style="color: var(--ep-success); font-weight: 700; font-size: 0.9rem;">● Valid Pass</span>
                                    <?php else : ?>
                                        <span style="color: var(--ep-danger); font-weight: 700; font-size: 0.9rem;">● <?php echo esc_html( ucfirst($status) ); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- QR Stub Section -->
                        <div style="background: var(--ep-navy-deep); padding: 2rem 1.5rem; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; color: #ffffff;">
                            <span style="font-size: 0.7rem; text-transform: uppercase; color: var(--ep-cyan); font-weight: 800; letter-spacing: 1px; margin-bottom: 0.75rem;">Gate Check-In QR</span>

                            <div style="margin-bottom: 0.75rem; background: #ffffff; padding: 8px; border-radius: 8px; display: inline-block;">
                                <?php echo $qr_svg; ?>
                            </div>

                            <code style="font-size: 0.85rem; letter-spacing: 1px; color: var(--ep-cyan); font-weight: 700; margin-bottom: 0.75rem;"><?php echo esc_html( $token ); ?></code>

                            <div style="display: flex; gap: 0.5rem; width: 100%;">
                                <button type="button" onclick='showBrandedPassModal(<?php echo wp_json_encode($pass_data); ?>)' class="ep-btn ep-btn-sm" style="background: var(--ep-teal); color: var(--ep-navy-deep); font-weight: 700; font-size: 0.8rem; flex: 1; justify-content: center; border: none; cursor: pointer;">
                                    🎫 View Pass
                                </button>
                                <button type="button" onclick='printPassDirect(<?php echo wp_json_encode($pass_data); ?>)' class="ep-btn ep-btn-sm" style="background: rgba(255,255,255,0.15); color: #ffffff; border: 1px solid rgba(255,255,255,0.25); font-size: 0.8rem; flex: 1; justify-content: center; cursor: pointer;">
                                    🖨️ Print / PDF
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <!-- Empty State -->
                <div class="no-print" style="background: #ffffff; border: 1px solid var(--ep-slate-200); border-radius: var(--ep-radius-lg); padding: 3.5rem 2rem; text-align: center; margin-bottom: 2rem;">
                    <span style="font-size: 3rem; display: block; margin-bottom: 1rem;">🎫</span>
                    <h3 style="font-size: 1.35rem; font-weight: 800; color: var(--ep-navy); margin: 0 0 0.5rem;">No Active Tickets Found</h3>
                    <p style="color: var(--ep-slate-600); max-width: 460px; margin: 0 auto 1.75rem; font-size: 0.95rem;">
                        You haven't reserved or purchased any event passes yet. Explore upcoming concerts, conferences, and festivals across Namibia.
                    </p>
                    <a href="<?php echo esc_url(home_url('/explore-events')); ?>" class="ep-btn ep-btn-primary ep-btn-md">
                        <span>Browse Available Events &rarr;</span>
                    </a>
                </div>
            <?php endif; ?>

            <!-- Real Order Receipts Table -->
            <div id="receipts" class="no-print" style="background: #ffffff; border: 1px solid var(--ep-slate-200); border-radius: var(--ep-radius-lg); padding: 1.75rem; box-shadow: var(--ep-shadow-sm);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
                    <div>
                        <h3 style="font-size: 1.2rem; font-weight: 800; color: var(--ep-navy); margin: 0;">Verified Order Receipts</h3>
                        <span style="font-size: 0.8rem; color: #64748B;">Tax invoices and digital settlement records for your bookings</span>
                    </div>
                    <span style="font-size: 0.85rem; font-weight: 700; color: var(--ep-navy);">
                        <?php echo count($user_orders); ?> Order(s)
                    </span>
                </div>

                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                        <thead>
                            <tr style="border-bottom: 2px solid var(--ep-slate-200); text-align: left; color: var(--ep-slate-600);">
                                <th style="padding: 0.75rem 0.5rem;">Booking Ref</th>
                                <th style="padding: 0.75rem 0.5rem;">Event</th>
                                <th style="padding: 0.75rem 0.5rem;">Date</th>
                                <th style="padding: 0.75rem 0.5rem;">Total</th>
                                <th style="padding: 0.75rem 0.5rem;">Payment</th>
                                <th style="padding: 0.75rem 0.5rem;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ( ! empty($user_orders) ) : ?>
                                <?php foreach ( $user_orders as $ord ) : 
                                    $ord_id     = $ord->ID;
                                    $ord_num    = get_post_meta($ord_id, '_ep_order_number', true) ?: ('EP-' . $ord_id);
                                    $ord_ev_id  = get_post_meta($ord_id, '_ep_order_event_id', true);
                                    $ord_title  = $ord_ev_id ? get_the_title($ord_ev_id) : 'Namibia Event Pass';
                                    $ord_date   = get_the_date('M j, Y H:i', $ord_id);
                                    $ord_total  = floatval(get_post_meta($ord_id, '_ep_order_total', true));
                                    $ord_status = get_post_meta($ord_id, '_ep_order_payment_status', true) ?: 'pending';
                                    $ord_method = get_post_meta($ord_id, '_ep_order_payment_method', true) ?: 'Finatic Gateway';
                                ?>
                                    <tr style="border-bottom: 1px solid var(--ep-slate-100);">
                                        <td style="padding: 1rem 0.5rem;"><strong><code><?php echo esc_html($ord_num); ?></code></strong></td>
                                        <td style="padding: 1rem 0.5rem; color: var(--ep-navy); font-weight: 600;"><?php echo esc_html($ord_title); ?></td>
                                        <td style="padding: 1rem 0.5rem; color: var(--ep-slate-600);"><?php echo esc_html($ord_date); ?></td>
                                        <td style="padding: 1rem 0.5rem; font-weight: 800; color: var(--ep-navy);">N$ <?php echo number_format($ord_total, 2); ?></td>
                                        <td style="padding: 1rem 0.5rem;"><small style="color: var(--ep-slate-600); font-weight: 600; text-transform: uppercase;"><?php echo esc_html($ord_method); ?></small></td>
                                        <td style="padding: 1rem 0.5rem;">
                                            <?php if ($ord_status === 'completed') : ?>
                                                <span style="background: #ECFDF5; color: #065F46; padding: 3px 9px; border-radius: 999px; font-weight: 700; font-size: 11px;">PAID / ISSUED</span>
                                            <?php elseif ($ord_status === 'cancelled') : ?>
                                                <span style="background: #FEF2F2; color: #991B1B; padding: 3px 9px; border-radius: 999px; font-weight: 700; font-size: 11px;">CANCELLED</span>
                                            <?php else : ?>
                                                <span style="background: #FFFBEB; color: #92400E; padding: 3px 9px; border-radius: 999px; font-weight: 700; font-size: 11px;">PENDING VERIFICATION</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr style="border-bottom: 1px solid var(--ep-slate-100);">
                                    <td colspan="6" style="padding: 2rem 0.5rem; text-align: center; color: var(--ep-slate-500); font-style: italic;">
                                        No transaction receipts found for your account.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Branded Printable/Downloadable Ticket Pass (Sprint 3) -->
<div id="epBrandedPassModal" class="no-print" style="display: none; position: fixed; inset: 0; z-index: 9999; background: rgba(6, 14, 32, 0.85); backdrop-filter: blur(8px); align-items: center; justify-content: center; padding: 1.5rem; overflow-y: auto;">
    <div style="background: #ffffff; border-radius: 20px; max-width: 600px; width: 100%; max-height: 92vh; overflow-y: auto; padding: 0; box-shadow: 0 25px 60px rgba(0,0,0,0.4); position: relative; border: 2px solid var(--ep-teal);">
        <button type="button" onclick="document.getElementById('epBrandedPassModal').style.display='none';" style="position: absolute; top: 1rem; right: 1rem; z-index: 10; background: #F1F5F9; border: none; width: 36px; height: 36px; border-radius: 50%; font-size: 1.25rem; font-weight: 700; cursor: pointer; color: #475569; display: flex; align-items: center; justify-content: center;">&times;</button>

        <!-- Printable Pass Card Content Container -->
        <div id="brandedPassModalBody" style="padding: 2rem;">
            <!-- Injected via showBrandedPassModal(passData) -->
        </div>

        <!-- Action Footer -->
        <div style="background: #F8FAFC; border-top: 1px solid #E2E8F0; padding: 1rem 1.5rem; display: flex; justify-content: space-between; align-items: center; border-radius: 0 0 18px 18px;">
            <button type="button" onclick="document.getElementById('epBrandedPassModal').style.display='none';" class="ep-btn ep-btn-outline ep-btn-sm" style="border-color: #CBD5E1;">Close</button>
            <button type="button" onclick="window.print()" class="ep-btn ep-btn-primary ep-btn-sm" style="background: var(--ep-navy-deep); border-color: var(--ep-navy-deep);">
                <span>🖨️ Print Ticket / Download PDF</span>
            </button>
        </div>
    </div>
</div>

<!-- Isolated Container for Direct Print -->
<div id="printPassContainer" style="display: none;"></div>

<script>
function showBrandedPassModal(p) {
    const body = document.getElementById('brandedPassModalBody');
    body.innerHTML = renderPassTemplate(p);

    // Also populate direct print container
    document.getElementById('printPassContainer').innerHTML = renderPassTemplate(p);
    document.getElementById('epBrandedPassModal').style.display = 'flex';
}

function printPassDirect(p) {
    document.getElementById('printPassContainer').innerHTML = renderPassTemplate(p);
    window.print();
}

function renderPassTemplate(p) {
    return `
        <div style="border: 2px solid #0B1B3D; border-radius: 16px; overflow: hidden; background: #ffffff; color: #0B1B3D; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
            <!-- Header Bar -->
            <div style="background: #0B1B3D; color: #ffffff; padding: 1.25rem 1.5rem; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <span style="font-size: 0.75rem; text-transform: uppercase; color: #38BDF8; letter-spacing: 1.5px; font-weight: 800;">Official Event Pass</span>
                    <h2 style="margin: 0.2rem 0 0; font-size: 1.35rem; font-weight: 900; color: #ffffff;">EVENTPASS NAMIBIA</h2>
                </div>
                <div style="text-align: right;">
                    <span style="display: inline-block; background: rgba(56, 189, 248, 0.2); color: #38BDF8; padding: 3px 10px; border-radius: 999px; font-weight: 700; font-size: 0.75rem; border: 1px solid rgba(56, 189, 248, 0.4);">
                        ${p.tier}
                    </span>
                </div>
            </div>

            <!-- Event Details -->
            <div style="padding: 1.5rem;">
                <h3 style="font-size: 1.4rem; font-weight: 800; color: #0B1B3D; margin: 0 0 1rem; line-height: 1.3;">
                    ${p.eventTitle}
                </h3>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem; font-size: 0.9rem;">
                    <div>
                        <span style="font-size: 0.75rem; text-transform: uppercase; color: #64748B; font-weight: 700; display: block;">Date & Opening</span>
                        <strong style="color: #0B1B3D;">${p.date}</strong><br>
                        <span style="font-size: 0.8rem; color: #64748B;">${p.time}</span>
                    </div>
                    <div>
                        <span style="font-size: 0.75rem; text-transform: uppercase; color: #64748B; font-weight: 700; display: block;">Venue / Location</span>
                        <strong style="color: #0B1B3D;">${p.venue}</strong>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; padding-top: 1rem; border-top: 1px dashed #CBD5E1; font-size: 0.9rem;">
                    <div>
                        <span style="font-size: 0.75rem; text-transform: uppercase; color: #64748B; font-weight: 700; display: block;">Ticket Holder</span>
                        <strong style="color: #0B1B3D;">${p.holder}</strong>
                    </div>
                    <div>
                        <span style="font-size: 0.75rem; text-transform: uppercase; color: #64748B; font-weight: 700; display: block;">Ticket Status</span>
                        <span style="color: #059669; font-weight: 800;">● ${p.status.toUpperCase()}</span>
                    </div>
                </div>
            </div>

            <!-- Authentic ISO QR Stub -->
            <div style="background: #0F172A; color: #ffffff; padding: 1.5rem; text-align: center; border-top: 2px dashed #334155;">
                <span style="font-size: 0.7rem; text-transform: uppercase; color: #38BDF8; font-weight: 800; letter-spacing: 1.5px; display: block; margin-bottom: 0.75rem;">
                    ISO/IEC 18004 Authentic Optical Gate Pass
                </span>

                <div style="background: #ffffff; padding: 10px; border-radius: 10px; display: inline-block; margin-bottom: 0.75rem;">
                    ${p.qr_svg}
                </div>

                <div style="font-family: monospace; font-size: 1.1rem; font-weight: 800; color: #F59E0B; letter-spacing: 2px; margin-bottom: 0.5rem;">
                    ${p.token}
                </div>

                <small style="color: #94A3B8; font-size: 0.75rem; display: block;">
                    Present this pass to gate stewards for barcode/optical scanning. Non-transferable once checked in.
                </small>
            </div>
        </div>
    `;
}
</script>

<?php
get_footer();
