<?php
/**
 * Template Name: Organiser Dashboard
 *
 * @package EventPass_Namibia
 */

if (!is_user_logged_in()) {
    wp_redirect(home_url('/login?redirect_to=' . urlencode(home_url('/organiser-dashboard'))));
    exit;
}

$current_user = wp_get_current_user();
$is_admin     = current_user_can('manage_options');
$is_organiser = in_array('event_organiser', (array)$current_user->roles) || in_array('ep_organiser', (array)$current_user->roles) || current_user_can('edit_ep_events');
$org_status   = get_user_meta($current_user->ID, '_ep_organiser_status', true);

// RBAC Guard: Restrict access to administrators and approved accredited organisers only
if (!$is_admin && (!$is_organiser || in_array($org_status, ['pending', 'rejected', 'suspended'], true))) {
    $status_heading = ($is_organiser && $org_status === 'suspended') ? 'Organiser Account Suspended' : (($is_organiser && $org_status === 'pending') ? 'Organiser Application Pending Review' : 'Organiser Accreditation Required');
    $status_body    = ($is_organiser && $org_status === 'suspended')
        ? 'Your organiser account has been temporarily suspended by the Platform Administrator. Please contact EventPass support for assistance.'
        : (($is_organiser && $org_status === 'pending')
            ? 'Your organiser application is currently under review by the Platform Administrator. You will receive an email notification once approved.'
            : 'The Organiser Hub is strictly reserved for verified event organisers and promoters. Your current account is registered as a general attendee.');
    wp_die(
        '<div style="max-width:620px; margin:80px auto; font-family:-apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; text-align:center; padding:45px 35px; background:#fff; border-radius:16px; box-shadow:0 10px 30px rgba(0,0,0,0.08); border:1px solid #E2E8F0;">
            <div style="width:72px; height:72px; background:rgba(0,180,216,0.1); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 1.25rem; font-size:2.2rem;">🏢</div>
            <h1 style="color:#0B1B3D; font-size:1.75rem; font-weight:800; margin-bottom:0.5rem;">' . esc_html($status_heading) . '</h1>
            <p style="color:#64748B; font-size:1rem; line-height:1.6; margin-bottom:2rem;">' . esc_html($status_body) . '</p>
            <div style="display:flex; justify-content:center; gap:12px; flex-wrap:wrap;">
                <a href="' . esc_url(home_url('/organiser-registration')) . '" style="background:#0B1B3D; color:#fff; padding:12px 24px; border-radius:8px; text-decoration:none; font-weight:700; font-size:0.95rem;">Apply for Organiser Accreditation &rarr;</a>
                <a href="' . esc_url(home_url('/customer-dashboard')) . '" style="background:#00B4D8; color:#fff; padding:12px 24px; border-radius:8px; text-decoration:none; font-weight:700; font-size:0.95rem;">Return to Customer Portal</a>
            </div>
        </div>',
        'Organiser Access Required',
        ['response' => 403]
    );
}

$flash_msg    = '';
$flash_type   = 'success';

// 1. Handle Admin Event Moderation Actions
if (isset($_GET['action']) && isset($_GET['event_id'])) {
    $target_event_id = intval($_GET['event_id']);
    $action = sanitize_text_field($_GET['action']);

    if ($action === 'approve_event') {
        check_admin_referer('ep_approve_' . $target_event_id);
        if ($is_admin) {
            wp_update_post([
                'ID'          => $target_event_id,
                'post_status' => 'publish',
            ]);
            $flash_msg  = 'Event successfully approved and published live to EventPass Namibia!';
            $flash_type = 'success';
        }
    } elseif ($action === 'reject_event') {
        check_admin_referer('ep_reject_' . $target_event_id);
        if ($is_admin) {
            wp_update_post([
                'ID'          => $target_event_id,
                'post_status' => 'draft',
            ]);
            $flash_msg  = 'Event has been rejected and moved back to Draft status.';
            $flash_type = 'warning';
        }
    } elseif ($action === 'delete_event') {
        check_admin_referer('ep_delete_event_' . $target_event_id);
        $post_author = intval(get_post_field('post_author', $target_event_id));
        if (!$is_admin && $post_author !== $current_user->ID) {
            wp_die('Unauthorized: You can only delete your own events.', 'Unauthorized', ['response' => 403]);
        }

        // Count sold tickets
        $tickets_count = count(get_posts([
            'post_type'      => 'ep_ticket',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => [
                'relation' => 'OR',
                ['key' => '_ep_ticket_event_id', 'value' => $target_event_id],
                ['key' => '_ep_event_id', 'value' => $target_event_id]
            ]
        ]));

        if ($tickets_count > 0) {
            // Safe archive: set to draft so attendee tickets and financial orders remain intact
            wp_update_post([
                'ID'          => $target_event_id,
                'post_status' => 'draft',
            ]);
            $flash_msg  = sprintf('Event contains %d sold attendee ticket(s). To protect attendee bookings and financial history, it was safely unlisted and moved to Draft status instead of hard deletion.', $tickets_count);
            $flash_type = 'warning';
        } else {
            wp_delete_post($target_event_id, true);
            $flash_msg  = 'Event has been permanently deleted.';
            $flash_type = 'success';
        }
    }
}


// 2. Handle Admin Payout Moderation Actions
if (isset($_GET['action']) && isset($_GET['payout_id'])) {
    $target_payout_id = intval($_GET['payout_id']);
    $action = sanitize_text_field($_GET['action']);

    if ($action === 'process_payout') {
        check_admin_referer('ep_process_payout_' . $target_payout_id);
        if ($is_admin) {
            $res = EventPass_Payout_Engine::process_payout($target_payout_id);
            if (!is_wp_error($res)) {
                $flash_msg  = 'Payout marked as remitted and transferred via EFT!';
                $flash_type = 'success';
            } else {
                $flash_msg  = $res->get_error_message();
                $flash_type = 'error';
            }
        }
    } elseif ($action === 'reject_payout') {
        check_admin_referer('ep_reject_payout_' . $target_payout_id);
        if ($is_admin) {
            EventPass_Payout_Engine::reject_payout($target_payout_id, 'Rejected by Administrator');
            $flash_msg  = 'Payout request rejected and balance restored to organiser account.';
            $flash_type = 'warning';
        }
    }
}


// 3. Handle Organiser Payout Request Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ep_payout_nonce'])) {
    if (!wp_verify_nonce($_POST['ep_payout_nonce'], 'ep_payout_action')) {
        $flash_msg  = 'Security session expired. Please refresh and try again.';
        $flash_type = 'error';
    } else {
        $req_amount = floatval($_POST['payout_amount'] ?? 0);
        $bank_info = [
            'bank_name'      => sanitize_text_field($_POST['bank_name'] ?? 'Bank Windhoek'),
            'account_name'   => sanitize_text_field($_POST['account_name'] ?? ''),
            'account_number' => sanitize_text_field($_POST['account_number'] ?? ''),
            'account_type'   => sanitize_text_field($_POST['account_type'] ?? 'Cheque / Current'),
            'branch_code'    => sanitize_text_field($_POST['branch_code'] ?? '483-872'),
        ];

        $res = EventPass_Payout_Engine::request_payout($current_user->ID, $req_amount, $bank_info);
        if (is_wp_error($res)) {
            $flash_msg  = $res->get_error_message();
            $flash_type = 'error';
        } else {
            $flash_msg  = sprintf('Payout request for N$ %s submitted successfully! Reference: %s. Our finance desk will remit EFT payment within 24 hours.', number_format($req_amount, 2), esc_html($res['reference']));
            $flash_type = 'success';
        }
    }
}


// 4. Handle Event Creation Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ep_create_event_nonce'])) {
    if (!wp_verify_nonce($_POST['ep_create_event_nonce'], 'ep_create_event_action')) {
        $flash_msg  = 'Security session expired. Please refresh and try again.';
        $flash_type = 'error';
    } else {
        $event_title = sanitize_text_field($_POST['event_title'] ?? '');
        $category    = sanitize_text_field($_POST['event_category'] ?? '');
        $town        = sanitize_text_field($_POST['event_town'] ?? 'windhoek');
        $venue       = sanitize_text_field($_POST['event_venue'] ?? '');
        $date        = sanitize_text_field($_POST['event_date'] ?? '');
        $desc        = wp_kses_post($_POST['event_description'] ?? '');
        $image       = esc_url_raw($_POST['event_image'] ?? '');
        $poster      = esc_url_raw($_POST['event_poster'] ?? '');
        if (empty($image)) {
            $image = !empty($poster) ? $poster : get_template_directory_uri() . '/assets/images/hero/concert-lights.jpg';
        }
        if (empty($poster)) {
            $poster = $image;
        }

        // SOW 4.1: Parse Gallery Images
        $gallery_raw = sanitize_textarea_field($_POST['event_gallery'] ?? '');
        $gallery_urls = [];
        if (!empty($gallery_raw)) {
            $glines = preg_split('/[\r\n,]+/', $gallery_raw);
            foreach ($glines as $gl) {
                $curl = esc_url_raw(trim($gl));
                if (!empty($curl)) $gallery_urls[] = $curl;
            }
        }

        // Strict Structured Event & Gate Time Validation
        $gate_time  = sanitize_text_field($_POST['gate_time'] ?? '');
        $start_time = sanitize_text_field($_POST['event_start_time'] ?? '');
        $end_time   = sanitize_text_field($_POST['event_end_time'] ?? '');

        if (!empty($gate_time) && !empty($start_time)) {
            if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $gate_time) || !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $start_time)) {
                $flash_msg  = 'Invalid time format. Please select valid gate opening and start times.';
                $flash_type = 'error';
                $time = '';
            } else {
                $time = 'Gates: ' . $gate_time . ' | Starts: ' . $start_time;
                if (!empty($end_time) && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $end_time)) {
                    $time .= ' - ' . $end_time;
                }
                $time .= ' CAT';
            }
        } else {
            $time = 'Gates: 16:00 | Starts: 18:00 - 02:00 CAT';
        }

        if (empty($event_title) || empty($category) || empty($venue) || empty($date)) {
            $flash_msg  = 'Please complete all required event fields (Title, Category, Venue, Date).';
            $flash_type = 'error';
        } else {
            // Build structured ticket tiers
            $tiers = [];

            // Tier 1: General Admission
            $gen_price = floatval($_POST['tier_gen_price'] ?? 150);
            $gen_qty   = intval($_POST['tier_gen_qty'] ?? 100);
            if ($gen_qty > 0) {
                $tiers['general'] = [
                    'id'                 => 'general',
                    'name'               => sanitize_text_field($_POST['tier_gen_name'] ?? 'General Admission'),
                    'price'              => $gen_price,
                    'total_quantity'     => $gen_qty,
                    'available_quantity' => $gen_qty,
                    'sold_quantity'      => 0,
                    'desc'               => 'Standard entrance access and open stage viewing area.'
                ];
            }

            // Tier 2: VIP Golden Circle
            $vip_qty = intval($_POST['tier_vip_qty'] ?? 0);
            if ($vip_qty > 0) {
                $vip_price = floatval($_POST['tier_vip_price'] ?? 450);
                $tiers['vip'] = [
                    'id'                 => 'vip',
                    'name'               => sanitize_text_field($_POST['tier_vip_name'] ?? 'VIP Golden Circle'),
                    'price'              => $vip_price,
                    'total_quantity'     => $vip_qty,
                    'available_quantity' => $vip_qty,
                    'sold_quantity'      => 0,
                    'desc'               => 'Front-of-stage golden circle, fast-track entrance and VIP bar.'
                ];
            }

            // Tier 3: Early Bird
            $early_qty = intval($_POST['tier_early_qty'] ?? 0);
            if ($early_qty > 0) {
                $early_price = floatval($_POST['tier_early_price'] ?? 100);
                $tiers['early_bird'] = [
                    'id'                 => 'early_bird',
                    'name'               => sanitize_text_field($_POST['tier_early_name'] ?? 'Early Bird Pass'),
                    'price'              => $early_price,
                    'total_quantity'     => $early_qty,
                    'available_quantity' => $early_qty,
                    'sold_quantity'      => 0,
                    'desc'               => 'Discounted entrance pass. Limited quota until sold out.'
                ];
            }

            if (empty($tiers)) {
                $tiers['general'] = [
                    'id'                 => 'general',
                    'name'               => 'General Admission',
                    'price'              => 150.00,
                    'total_quantity'     => 100,
                    'available_quantity' => 100,
                    'sold_quantity'      => 0,
                    'desc'               => 'Standard entrance access.'
                ];
            }

            $prices = array_column($tiers, 'price');
            $min_price = !empty($prices) ? min($prices) : 0;

            $post_status = $is_admin ? 'publish' : 'pending';

            $new_event_id = wp_insert_post([
                'post_title'   => $event_title,
                'post_content' => $desc,
                'post_status'  => $post_status,
                'post_type'    => 'ep_event',
                'post_author'  => $current_user->ID,
            ]);

            if (!is_wp_error($new_event_id)) {
                // SOW 4.1: Handle File Uploads (Poster & Gallery)
                if (!empty($_FILES['event_poster_file']['name']) && $_FILES['event_poster_file']['error'] === UPLOAD_ERR_OK) {
                    require_once(ABSPATH . 'wp-admin/includes/image.php');
                    require_once(ABSPATH . 'wp-admin/includes/file.php');
                    require_once(ABSPATH . 'wp-admin/includes/media.php');
                    $attach_id = media_handle_upload('event_poster_file', $new_event_id);
                    if (!is_wp_error($attach_id)) {
                        $up_url = wp_get_attachment_url($attach_id);
                        if ($up_url) {
                            $poster = $up_url;
                            $image  = $up_url;
                            set_post_thumbnail($new_event_id, $attach_id);
                        }
                    }
                }

                if (!empty($_FILES['event_gallery_files']['name'][0])) {
                    require_once(ABSPATH . 'wp-admin/includes/image.php');
                    require_once(ABSPATH . 'wp-admin/includes/file.php');
                    require_once(ABSPATH . 'wp-admin/includes/media.php');
                    $g_count = count($_FILES['event_gallery_files']['name']);
                    for ($i = 0; $i < $g_count; $i++) {
                        if (!empty($_FILES['event_gallery_files']['tmp_name'][$i]) && $_FILES['event_gallery_files']['error'][$i] === UPLOAD_ERR_OK) {
                            $_FILES['ep_temp_gallery_upload'] = [
                                'name'     => $_FILES['event_gallery_files']['name'][$i],
                                'type'     => $_FILES['event_gallery_files']['type'][$i],
                                'tmp_name' => $_FILES['event_gallery_files']['tmp_name'][$i],
                                'error'    => $_FILES['event_gallery_files']['error'][$i],
                                'size'     => $_FILES['event_gallery_files']['size'][$i],
                            ];
                            $g_attach_id = media_handle_upload('ep_temp_gallery_upload', $new_event_id);
                            if (!is_wp_error($g_attach_id)) {
                                $g_url = wp_get_attachment_url($g_attach_id);
                                if ($g_url) {
                                    $gallery_urls[] = $g_url;
                                }
                            }
                        }
                    }
                }

                wp_set_object_terms($new_event_id, $category, 'ep_event_category');
                update_post_meta($new_event_id, '_ep_event_venue', $venue);
                update_post_meta($new_event_id, '_ep_event_town', $town);
                update_post_meta($new_event_id, '_ep_event_date', $date);
                update_post_meta($new_event_id, '_ep_event_time', $time);
                update_post_meta($new_event_id, '_ep_event_image', $image);
                update_post_meta($new_event_id, '_ep_event_poster', $poster);
                if (!empty($gallery_urls)) {
                    update_post_meta($new_event_id, '_ep_event_gallery', $gallery_urls);
                }
                update_post_meta($new_event_id, '_ep_event_organiser', $current_user->display_name ?: $current_user->user_login);
                update_post_meta($new_event_id, '_ep_ticket_tiers', $tiers);
                update_post_meta($new_event_id, '_ep_min_price', $min_price);

                $flash_msg  = ($post_status === 'publish') ? 'Event created and published live successfully!' : 'Event submitted for Admin Review!';
                $flash_type = 'success';
            } else {
                $flash_msg  = $new_event_id->get_error_message();
                $flash_type = 'error';
            }
        }
    }
}



// 4.5 Handle Event Editing Form Submission (Sprint 3)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ep_edit_event_nonce'])) {
    $edit_event_id = intval($_POST['edit_event_id'] ?? 0);
    if (!wp_verify_nonce($_POST['ep_edit_event_nonce'], 'ep_edit_event_action_' . $edit_event_id)) {
        $flash_msg  = 'Security session expired. Please refresh and try again.';
        $flash_type = 'error';
    } else {
        $post_author = intval(get_post_field('post_author', $edit_event_id));
        if (!$is_admin && $post_author !== $current_user->ID) {
            wp_die('Unauthorized: You can only edit your own events.', 'Unauthorized', ['response' => 403]);
        }

        $event_title = sanitize_text_field($_POST['edit_event_title'] ?? '');
        $category    = sanitize_text_field($_POST['edit_event_category'] ?? '');
        $town        = sanitize_text_field($_POST['edit_event_town'] ?? 'windhoek');
        $venue       = sanitize_text_field($_POST['edit_event_venue'] ?? '');
        $date        = sanitize_text_field($_POST['edit_event_date'] ?? '');
        $desc        = wp_kses_post($_POST['edit_event_description'] ?? '');

        // SOW 4.1: Poster & Gallery Updates for Edit
        $edit_poster      = esc_url_raw($_POST['edit_event_poster'] ?? '');
        $edit_gallery_raw = sanitize_textarea_field($_POST['edit_event_gallery'] ?? '');
        $edit_gallery_urls = [];
        if (!empty($edit_gallery_raw)) {
            $split = preg_split('/[\r\n,]+/', $edit_gallery_raw);
            foreach ($split as $u) {
                $u = trim($u);
                if (!empty($u) && filter_var($u, FILTER_VALIDATE_URL)) {
                    $edit_gallery_urls[] = esc_url_raw($u);
                }
            }
        }

        // Strict Structured Event & Gate Time Validation for Edit
        $edit_gate_time  = sanitize_text_field($_POST['edit_gate_time'] ?? '');
        $edit_start_time = sanitize_text_field($_POST['edit_start_time'] ?? '');
        $edit_end_time   = sanitize_text_field($_POST['edit_end_time'] ?? '');

        if (!empty($edit_gate_time) && !empty($edit_start_time)) {
            if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $edit_gate_time) || !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $edit_start_time)) {
                $flash_msg  = 'Invalid time format. Please select valid gate opening and start times.';
                $flash_type = 'error';
                $time = '';
            } else {
                $time = 'Gates: ' . $edit_gate_time . ' | Starts: ' . $edit_start_time;
                if (!empty($edit_end_time) && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $edit_end_time)) {
                    $time .= ' - ' . $edit_end_time;
                }
                $time .= ' CAT';
            }
        } else {
            $time = 'Gates: 16:00 | Starts: 18:00 - 02:00 CAT';
        }

        if (empty($event_title) || empty($category) || empty($venue) || empty($date)) {
            $flash_msg  = 'Please complete all required fields (Title, Category, Venue, Date).';
            $flash_type = 'error';
        } else {
            wp_update_post([
                'ID'           => $edit_event_id,
                'post_title'   => $event_title,
                'post_content' => $desc,
            ]);

            // SOW 4.1: Handle File Uploads for Edit (Poster & Gallery)
            if (!empty($_FILES['edit_event_poster_file']['name']) && $_FILES['edit_event_poster_file']['error'] === UPLOAD_ERR_OK) {
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/media.php');
                $attach_id = media_handle_upload('edit_event_poster_file', $edit_event_id);
                if (!is_wp_error($attach_id)) {
                    $up_url = wp_get_attachment_url($attach_id);
                    if ($up_url) {
                        $edit_poster = $up_url;
                        set_post_thumbnail($edit_event_id, $attach_id);
                    }
                }
            }

            if (!empty($_FILES['edit_event_gallery_files']['name'][0])) {
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/media.php');
                $g_count = count($_FILES['edit_event_gallery_files']['name']);
                for ($i = 0; $i < $g_count; $i++) {
                    if (!empty($_FILES['edit_event_gallery_files']['tmp_name'][$i]) && $_FILES['edit_event_gallery_files']['error'][$i] === UPLOAD_ERR_OK) {
                        $_FILES['ep_temp_gallery_upload'] = [
                            'name'     => $_FILES['edit_event_gallery_files']['name'][$i],
                            'type'     => $_FILES['edit_event_gallery_files']['type'][$i],
                            'tmp_name' => $_FILES['edit_event_gallery_files']['tmp_name'][$i],
                            'error'    => $_FILES['edit_event_gallery_files']['error'][$i],
                            'size'     => $_FILES['edit_event_gallery_files']['size'][$i],
                        ];
                        $g_attach_id = media_handle_upload('ep_temp_gallery_upload', $edit_event_id);
                        if (!is_wp_error($g_attach_id)) {
                            $g_url = wp_get_attachment_url($g_attach_id);
                            if ($g_url) {
                                $edit_gallery_urls[] = $g_url;
                            }
                        }
                    }
                }
            }

            wp_set_object_terms($edit_event_id, $category, 'ep_event_category');
            update_post_meta($edit_event_id, '_ep_event_venue', $venue);
            update_post_meta($edit_event_id, '_ep_event_town', $town);
            update_post_meta($edit_event_id, '_ep_event_date', $date);
            update_post_meta($edit_event_id, '_ep_event_time', $time);
            update_post_meta($edit_event_id, '_ep_event_poster', $edit_poster);
            update_post_meta($edit_event_id, '_ep_event_gallery', $edit_gallery_urls);
            if (!empty($edit_poster)) {
                update_post_meta($edit_event_id, '_ep_event_image', $edit_poster);
            }

            // Dynamic Ticket Tiers Updates
            $existing_tiers = get_post_meta($edit_event_id, '_ep_ticket_tiers', true);
            if (!is_array($existing_tiers)) {
                $existing_tiers = [];
            }

            $tiers = [];

            // Tier 1: General Admission
            $gen_price = floatval($_POST['edit_tier_gen_price'] ?? 150);
            $gen_qty   = intval($_POST['edit_tier_gen_qty'] ?? 100);
            $gen_sold  = intval($existing_tiers['general']['sold_quantity'] ?? 0);
            if ($gen_qty < $gen_sold) {
                $gen_qty = $gen_sold;
            }
            $tiers['general'] = [
                'id'                 => 'general',
                'name'               => sanitize_text_field($_POST['edit_tier_gen_name'] ?? 'General Admission'),
                'price'              => $gen_price,
                'total_quantity'     => $gen_qty,
                'available_quantity' => max(0, $gen_qty - $gen_sold),
                'sold_quantity'      => $gen_sold,
                'desc'               => $existing_tiers['general']['desc'] ?? 'Standard entrance access and open stage viewing area.'
            ];

            // Tier 2: VIP Golden Circle
            $vip_qty  = intval($_POST['edit_tier_vip_qty'] ?? 0);
            $vip_sold = intval($existing_tiers['vip']['sold_quantity'] ?? 0);
            if ($vip_qty > 0 || $vip_sold > 0) {
                if ($vip_qty < $vip_sold) {
                    $vip_qty = $vip_sold;
                }
                $vip_price = floatval($_POST['edit_tier_vip_price'] ?? 450);
                $tiers['vip'] = [
                    'id'                 => 'vip',
                    'name'               => sanitize_text_field($_POST['edit_tier_vip_name'] ?? 'VIP Golden Circle'),
                    'price'              => $vip_price,
                    'total_quantity'     => $vip_qty,
                    'available_quantity' => max(0, $vip_qty - $vip_sold),
                    'sold_quantity'      => $vip_sold,
                    'desc'               => $existing_tiers['vip']['desc'] ?? 'Front-of-stage golden circle, fast-track entrance and VIP bar.'
                ];
            }

            // Tier 3: Early Bird
            $early_qty  = intval($_POST['edit_tier_early_qty'] ?? 0);
            $early_sold = intval($existing_tiers['early_bird']['sold_quantity'] ?? 0);
            if ($early_qty > 0 || $early_sold > 0) {
                if ($early_qty < $early_sold) {
                    $early_qty = $early_sold;
                }
                $early_price = floatval($_POST['edit_tier_early_price'] ?? 100);
                $tiers['early_bird'] = [
                    'id'                 => 'early_bird',
                    'name'               => sanitize_text_field($_POST['edit_tier_early_name'] ?? 'Early Bird Pass'),
                    'price'              => $early_price,
                    'total_quantity'     => $early_qty,
                    'available_quantity' => max(0, $early_qty - $early_sold),
                    'sold_quantity'      => $early_sold,
                    'desc'               => $existing_tiers['early_bird']['desc'] ?? 'Discounted entrance pass. Limited quota until sold out.'
                ];
            }

            // Preserve any other custom tier keys from existing_tiers
            foreach ($existing_tiers as $k => $old_t) {
                if (!in_array($k, ['general', 'vip', 'early_bird']) && !isset($tiers[$k])) {
                    $tiers[$k] = $old_t;
                }
            }

            $prices = array_column($tiers, 'price');
            $min_price = !empty($prices) ? min($prices) : 0;

            update_post_meta($edit_event_id, '_ep_ticket_tiers', $tiers);
            update_post_meta($edit_event_id, '_ep_min_price', $min_price);

            $flash_msg  = 'Event details and ticket tier quotas updated successfully!';
            $flash_type = 'success';
        }
    }
}

// 5. Query Organiser's Database Events
$events_args = [
    'post_type'      => 'ep_event',
    'post_status'    => ['publish', 'pending', 'draft'],
    'posts_per_page' => 50,
    'orderby'        => 'date',
    'order'          => 'DESC',
];
if (!$is_admin) {
    $events_args['author'] = $current_user->ID;
}
$my_events = get_posts($events_args);

$published_count = 0;
$pending_count   = 0;
foreach ($my_events as $ev) {
    if ($ev->post_status === 'publish') $published_count++;
    elseif ($ev->post_status === 'pending') $pending_count++;
}

// 6. Query Real Financial Ledger
$ledger_user_id = $is_admin ? 0 : $current_user->ID;
$ledger = EventPass_Payout_Engine::get_financial_ledger($ledger_user_id);

$total_gross_sales   = $ledger['gross_sales'];
$total_tickets_sold  = $ledger['total_tickets_sold'];
$platform_commission = $ledger['total_commission'];
$net_earnings        = $ledger['net_earnings'];
$available_balance   = $ledger['available_balance'];
$paid_payouts        = $ledger['paid_payouts'];
$payout_history      = $ledger['payout_history'];

// Saved Banking Profile
$saved_bank_name = get_user_meta($current_user->ID, '_ep_bank_name', true) ?: 'Bank Windhoek';
$saved_acc_name  = get_user_meta($current_user->ID, '_ep_bank_acc_name', true) ?: ($current_user->display_name ?: $current_user->user_login);
$saved_acc_num   = get_user_meta($current_user->ID, '_ep_bank_acc_num', true) ?: '';
$saved_acc_type  = get_user_meta($current_user->ID, '_ep_bank_acc_type', true) ?: 'Cheque / Current';
$saved_branch    = get_user_meta($current_user->ID, '_ep_bank_branch_code', true) ?: '483-872';

// Active Categories
$active_categories = function_exists('eventpass_get_event_categories') ? eventpass_get_event_categories(['status' => 'active']) : [];
if (empty($active_categories)) {
    $terms = get_terms([
        'taxonomy'   => 'ep_event_category',
        'hide_empty' => false,
    ]);
    if (!is_wp_error($terms)) {
        $active_categories = $terms;
    }
}


get_header();
?>

<!-- Banner Section -->
<div class="ep-page-banner" style="background: linear-gradient(135deg, var(--ep-navy-deep) 0%, var(--ep-navy) 100%); color: #ffffff; padding: 3.5rem 0 2.5rem;">
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <span style="color: var(--ep-cyan); font-weight: 700; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.5px;">
                    <?php echo $is_admin ? 'Platform Admin Financial Console' : 'Organiser Financial & Event Console'; ?>
                </span>
                <h1 style="font-size: 2.25rem; font-weight: 800; margin: 0.25rem 0 0.5rem; line-height: 1.2;">
                    <?php echo esc_html($current_user->display_name ?: $current_user->user_login); ?>
                </h1>
                <p style="font-size: 1rem; color: #CBD5E1; margin: 0;">
                    <?php if ($is_admin) : ?>
                        Administrator View • Platform-Wide Sales, Automated Commissions & Payout Queue
                    <?php else : ?>
                        Verified Organiser • Automated 7.5% Marketplace Ledger & Domestic Bank Payouts
                    <?php endif; ?>
                </p>
            </div>
            <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                <button type="button" onclick="document.getElementById('epEventCreateModal').style.display='flex';" class="ep-btn ep-btn-primary ep-btn-sm">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                    <span>+ Create Event</span>
                </button>
                <a href="<?php echo esc_url(admin_url('admin-post.php?action=ep_export_sales_csv')); ?>" class="ep-btn ep-btn-outline ep-btn-sm" style="color: #fff; border-color: rgba(255,255,255,0.4); background: rgba(255,255,255,0.08);">
                    <span>📥 Export Sales CSV</span>
                </a>
                <a href="<?php echo esc_url(home_url('/gate-scanner')); ?>" class="ep-btn ep-btn-secondary ep-btn-sm">
                    <span>Gate Scanner</span>
                </a>
            </div>
        </div>
    </div>
</div>


<div class="container" style="padding: 3rem 1.5rem 5rem;">
    <?php if (!empty($flash_msg)) : ?>
        <div style="background: <?php echo $flash_type === 'success' ? '#ECFDF5' : ($flash_type === 'warning' ? '#FFFBEB' : '#FEF2F2'); ?>; border: 1px solid <?php echo $flash_type === 'success' ? '#10B981' : ($flash_type === 'warning' ? '#F59E0B' : '#FCA5A5'); ?>; color: <?php echo $flash_type === 'success' ? '#065F46' : ($flash_type === 'warning' ? '#92400E' : '#991B1B'); ?>; padding: 1rem 1.5rem; border-radius: 12px; font-size: 0.95rem; margin-bottom: 2rem; display: flex; align-items: center; gap: 0.75rem;">
            <span><?php echo $flash_type === 'success' ? '✅' : ($flash_type === 'warning' ? '⚠️' : '❌'); ?></span>
            <span><?php echo esc_html($flash_msg); ?></span>
        </div>
    <?php endif; ?>

    <!-- Real-Time Financial Metric Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem;">
        <!-- Card 1: Gross Sales -->
        <div style="background: #ffffff; border: 1px solid var(--ep-slate-200); border-radius: var(--ep-radius-lg); padding: 1.5rem; box-shadow: var(--ep-shadow-sm);">
            <span style="font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: var(--ep-slate-600); display: block;">Total Gross Sales</span>
            <span style="font-size: 1.85rem; font-weight: 800; color: var(--ep-navy); display: block; margin: 0.25rem 0;">N$ <?php echo number_format($total_gross_sales, 2); ?></span>
            <span style="font-size: 0.8rem; color: var(--ep-success); font-weight: 600;"><?php echo number_format($total_tickets_sold); ?> tickets sold across live events</span>
        </div>

        <!-- Card 2: Platform Commission -->
        <div style="background: #ffffff; border: 1px solid var(--ep-slate-200); border-radius: var(--ep-radius-lg); padding: 1.5rem; box-shadow: var(--ep-shadow-sm);">
            <span style="font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: var(--ep-slate-600); display: block;">Platform Fee (7.5%)</span>
            <span style="font-size: 1.85rem; font-weight: 800; color: #64748B; display: block; margin: 0.25rem 0;">N$ <?php echo number_format($platform_commission, 2); ?></span>
            <span style="font-size: 0.8rem; color: #64748B;">Automated SOW commission</span>
        </div>

        <!-- Card 3: Net Earnings -->
        <div style="background: #ffffff; border: 1px solid var(--ep-slate-200); border-radius: var(--ep-radius-lg); padding: 1.5rem; box-shadow: var(--ep-shadow-sm);">
            <span style="font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: var(--ep-slate-600); display: block;">Net Organiser Revenue</span>
            <span style="font-size: 1.85rem; font-weight: 800; color: var(--ep-navy); display: block; margin: 0.25rem 0;">N$ <?php echo number_format($net_earnings, 2); ?></span>
            <span style="font-size: 0.8rem; color: var(--ep-teal-dark); font-weight: 600;">92.5% net proceeds</span>
        </div>

        <!-- Card 4: Available Payout Balance -->
        <div style="background: #ffffff; border: 2px solid var(--ep-teal); border-radius: var(--ep-radius-lg); padding: 1.5rem; box-shadow: var(--ep-shadow-sm);">
            <span style="font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: var(--ep-teal-dark); display: block;">Available for Payout</span>
            <span style="font-size: 1.85rem; font-weight: 800; color: var(--ep-teal-dark); display: block; margin: 0.25rem 0;">N$ <?php echo number_format($available_balance, 2); ?></span>
            <button type="button" onclick="document.getElementById('epPayoutRequestModal').style.display='flex';" class="ep-btn ep-btn-sm ep-btn-primary" style="margin-top: 0.25rem; width: 100%; justify-content: center;" <?php echo ($available_balance <= 0) ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : ''; ?>>
                Request EFT Payout &rarr;
            </button>
        </div>
    </div>

    <!-- Active Events Table -->
    <div style="background: #ffffff; border: 1px solid var(--ep-slate-200); border-radius: var(--ep-radius-lg); padding: 2rem; margin-bottom: 2.5rem; box-shadow: var(--ep-shadow-sm);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h3 style="font-size: 1.3rem; font-weight: 800; color: var(--ep-navy); margin: 0;">Event Management & Approvals</h3>
                <span style="color: var(--ep-slate-600); font-size: 0.85rem;">Manage ticket tiers, vet submissions, and monitor door check-in rates</span>
            </div>
            <span style="font-size: 0.85rem; font-weight: 700; color: var(--ep-slate-700);">
                <?php echo $published_count; ?> Published • <?php echo $pending_count; ?> Pending Approval
            </span>
        </div>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--ep-slate-200); text-align: left; color: var(--ep-slate-600);">
                        <th style="padding: 0.75rem;">Event Title</th>
                        <th style="padding: 0.75rem;">Category</th>
                        <th style="padding: 0.75rem;">Date & Town</th>
                        <th style="padding: 0.75rem;">Status</th>
                        <th style="padding: 0.75rem;">Sold / Cap</th>
                        <th style="padding: 0.75rem;">Attendance</th>
                        <th style="padding: 0.75rem;">Gross Sales</th>
                        <th style="padding: 0.75rem;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($my_events)) : ?>
                        <?php foreach ($my_events as $ev) : 
                            $ev_id   = $ev->ID;
                            $terms   = get_the_terms($ev_id, 'ep_event_category');
                            $cat_name = (!empty($terms) && !is_wp_error($terms)) ? $terms[0]->name : 'General';
                            $ev_date = get_post_meta($ev_id, '_ep_event_date', true) ?: 'Upcoming';
                            $ev_town = get_post_meta($ev_id, '_ep_event_town', true) ?: 'Windhoek';
                            $ev_venue = get_post_meta($ev_id, '_ep_event_venue', true) ?: 'TBA';
                            $ev_status = $ev->post_status;

                            $ev_tiers = get_post_meta($ev_id, '_ep_ticket_tiers', true);
                            $cap = 0;
                            $sold = 0;
                            $gross = 0.0;
                            if (is_array($ev_tiers)) {
                                foreach ($ev_tiers as $tr) {
                                    $t_cap = intval($tr['total_quantity'] ?? 0);
                                    $t_sold = intval($tr['sold_quantity'] ?? 0);
                                    $cap += $t_cap;
                                    $sold += $t_sold;
                                    $gross += ($t_sold * floatval($tr['price'] ?? 0));
                                }
                            }
                        ?>
                            <tr style="border-bottom: 1px solid var(--ep-slate-100);">
                                <td style="padding: 1rem 0.75rem;">
                                    <strong style="color: var(--ep-navy); font-size: 0.95rem; display: block;">
                                        <?php echo esc_html(get_the_title($ev_id)); ?>
                                    </strong>
                                    <span style="font-size: 0.8rem; color: #64748B;"><?php echo esc_html($ev_venue); ?></span>
                                </td>
                                <td style="padding: 1rem 0.75rem;">
                                    <span style="font-size: 0.85rem; color: var(--ep-slate-700); font-weight: 600;">
                                        <?php echo esc_html($cat_name); ?>
                                    </span>
                                </td>
                                <td style="padding: 1rem 0.75rem; color: var(--ep-slate-600);">
                                    <strong><?php echo esc_html($ev_date); ?></strong>
                                    <span style="display: block; font-size: 0.8rem; color: var(--ep-teal-dark);"><?php echo esc_html(ucfirst($ev_town)); ?></span>
                                </td>
                                <td style="padding: 1rem 0.75rem;">
                                    <?php if ($ev_status === 'publish') : ?>
                                        <span style="background: rgba(16,185,129,0.1); color: var(--ep-success); padding: 0.3rem 0.75rem; border-radius: 9999px; font-weight: 700; font-size: 0.75rem;">
                                            ● Published Live
                                        </span>
                                    <?php elseif ($ev_status === 'pending') : ?>
                                        <span style="background: rgba(245,158,11,0.1); color: var(--ep-warning); padding: 0.3rem 0.75rem; border-radius: 9999px; font-weight: 700; font-size: 0.75rem;">
                                            ⏳ Pending Admin Approval
                                        </span>
                                    <?php else : ?>
                                        <span style="background: #F1F5F9; color: #64748B; padding: 0.3rem 0.75rem; border-radius: 9999px; font-weight: 700; font-size: 0.75rem;">
                                            Draft / Paused
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 1rem 0.75rem; font-weight: 700;">
                                    <?php echo number_format($sold); ?> / <?php echo number_format($cap); ?>
                                </td>
                                <td style="padding: 1rem 0.75rem; font-weight: 700;">
                                    <?php
                                    $att_stats = function_exists('eventpass_get_gate_attendance_stats') ? eventpass_get_gate_attendance_stats($ev_id) : ['total_sold' => $sold, 'checked_in' => 0];
                                    $att_in    = intval($att_stats['checked_in'] ?? 0);
                                    $att_tot   = max(intval($att_stats['total_sold'] ?? 0), $sold);
                                    $att_pct   = $att_tot > 0 ? round(($att_in / $att_tot) * 100) : 0;
                                    ?>
                                    <span style="color: <?php echo $att_in > 0 ? 'var(--ep-success)' : 'var(--ep-slate-600)'; ?>;">
                                        <?php echo number_format($att_in); ?> / <?php echo number_format($att_tot); ?>
                                    </span>
                                    <span style="display: block; font-size: 0.75rem; color: #64748B; font-weight: 600;">
                                        <?php echo $att_pct; ?>% checked in
                                    </span>
                                </td>
                                <td style="padding: 1rem 0.75rem; font-weight: 700; color: var(--ep-teal-dark);">
                                    N$ <?php echo number_format($gross, 2); ?>
                                </td>
                                <td style="padding: 1rem 0.75rem;">
                                    <?php
                                    $ev_cat_slug = (!empty($terms) && !is_wp_error($terms)) ? $terms[0]->slug : '';
                                    $ev_time = get_post_meta($ev_id, '_ep_event_time', true) ?: '18:00 - 02:00 CAT';
                                    $ev_poster = get_post_meta($ev_id, '_ep_event_poster', true) ?: '';
                                    $ev_gallery = get_post_meta($ev_id, '_ep_event_gallery', true) ?: [];
                                    $ev_gallery_str = is_array($ev_gallery) ? implode(", ", $ev_gallery) : '';
                                    $ev_desc = get_post_field('post_content', $ev_id);
                                    $gen_tier = $ev_tiers['general'] ?? [];
                                    $vip_tier = $ev_tiers['vip'] ?? [];
                                    $early_tier = $ev_tiers['early_bird'] ?? [];
                                    $edit_payload = [
                                        'id'          => $ev_id,
                                        'title'       => get_the_title($ev_id),
                                        'category'    => $ev_cat_slug,
                                        'town'        => $ev_town,
                                        'venue'       => $ev_venue,
                                        'date'        => $ev_date,
                                        'time'        => $ev_time,
                                        'poster'      => $ev_poster,
                                        'gallery'     => $ev_gallery_str,
                                        'desc'        => $ev_desc,
                                        'nonce'       => wp_create_nonce('ep_edit_event_action_' . $ev_id),
                                        'gen_price'   => $gen_tier['price'] ?? 150,
                                        'gen_qty'     => $gen_tier['total_quantity'] ?? 100,
                                        'gen_sold'    => $gen_tier['sold_quantity'] ?? 0,
                                        'vip_price'   => $vip_tier['price'] ?? 450,
                                        'vip_qty'     => $vip_tier['total_quantity'] ?? 0,
                                        'vip_sold'    => $vip_tier['sold_quantity'] ?? 0,
                                        'early_price' => $early_tier['price'] ?? 100,
                                        'early_qty'   => $early_tier['total_quantity'] ?? 0,
                                        'early_sold'  => $early_tier['sold_quantity'] ?? 0,
                                    ];
                                    ?>
                                    <div style="display: flex; gap: 0.35rem; flex-wrap: wrap; align-items: center;">
                                        <a href="<?php echo esc_url(get_permalink($ev_id)); ?>" target="_blank" class="ep-btn ep-btn-sm ep-btn-outline" style="font-size: 0.75rem; padding: 0.3rem 0.55rem;">
                                            View
                                        </a>

                                        <button type="button" class="ep-btn ep-btn-sm ep-btn-outline" style="font-size: 0.75rem; padding: 0.3rem 0.55rem; color: var(--ep-teal-dark); border-color: var(--ep-teal);" onclick='openEditEventModal(<?php echo wp_json_encode($edit_payload); ?>)'>
                                            ✏️ Edit
                                        </button>

                                        <button type="button" class="ep-btn ep-btn-sm ep-btn-outline" style="font-size: 0.75rem; padding: 0.3rem 0.55rem; color: #1D4ED8; border-color: #93C5FD;" onclick='openAttendeeModal(<?php echo $ev_id; ?>, <?php echo wp_json_encode(get_the_title($ev_id)); ?>)'>
                                            📋 Attendees (<?php echo $sold; ?>)
                                        </button>

                                        <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(['action' => 'delete_event', 'event_id' => $ev_id]), 'ep_delete_event_' . $ev_id)); ?>" class="ep-btn ep-btn-sm ep-btn-outline" style="font-size: 0.75rem; padding: 0.3rem 0.55rem; color: #DC2626; border-color: #FCA5A5;" onclick="return confirm('Are you sure you want to delete or archive this event? If tickets have already been sold, it will be safely archived to Draft to protect attendee passes.');">
                                            🗑️ Delete
                                        </a>

                                        <?php if ($is_admin && $ev_status === 'pending') : ?>
                                            <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(['action' => 'approve_event', 'event_id' => $ev_id]), 'ep_approve_' . $ev_id)); ?>" class="ep-btn ep-btn-sm ep-btn-primary" style="font-size: 0.75rem; padding: 0.3rem 0.55rem; background: #059669; border-color: #059669;">
                                                ✓ Approve
                                            </a>
                                            <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(['action' => 'reject_event', 'event_id' => $ev_id]), 'ep_reject_' . $ev_id)); ?>" class="ep-btn ep-btn-sm ep-btn-outline" style="font-size: 0.75rem; padding: 0.3rem 0.55rem; color: #DC2626; border-color: #FCA5A5;">
                                                Reject
                                            </a>
                                        <?php elseif ($is_admin && $ev_status === 'publish') : ?>
                                            <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(['action' => 'reject_event', 'event_id' => $ev_id]), 'ep_reject_' . $ev_id)); ?>" class="ep-btn ep-btn-sm ep-btn-outline" style="font-size: 0.75rem; padding: 0.3rem 0.55rem; color: #64748B;">
                                                Unpublish
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="8" style="padding: 3rem 1rem; text-align: center; color: var(--ep-slate-500);">
                                No events created yet. Click <strong>"+ Create Event"</strong> above to submit your first event listing!
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Payout Requests & Settlement History -->
    <div style="background: #ffffff; border: 1px solid var(--ep-slate-200); border-radius: var(--ep-radius-lg); padding: 2rem; box-shadow: var(--ep-shadow-sm);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h3 style="font-size: 1.3rem; font-weight: 800; color: var(--ep-navy); margin: 0;">Organiser Payouts & EFT Settlement</h3>
                <span style="color: var(--ep-slate-600); font-size: 0.85rem;">Electronic funds transfers to registered Namibian commercial bank accounts</span>
            </div>
            <button type="button" onclick="document.getElementById('epPayoutRequestModal').style.display='flex';" class="ep-btn ep-btn-primary ep-btn-sm" <?php echo ($available_balance <= 0) ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : ''; ?>>
                <span>+ Request Payout</span>
            </button>
        </div>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--ep-slate-200); text-align: left; color: var(--ep-slate-600);">
                        <th style="padding: 0.75rem;">Reference</th>
                        <th style="padding: 0.75rem;">Requested Date</th>
                        <th style="padding: 0.75rem;">Bank & Account</th>
                        <th style="padding: 0.75rem;">Amount (NAD)</th>
                        <th style="padding: 0.75rem;">Status</th>
                        <th style="padding: 0.75rem;">EFT Trace Ref</th>
                        <?php if ($is_admin) : ?><th style="padding: 0.75rem;">Admin Moderation</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($payout_history)) : ?>
                        <?php foreach ($payout_history as $po) : ?>
                            <tr style="border-bottom: 1px solid var(--ep-slate-100);">
                                <td style="padding: 1rem 0.75rem;">
                                    <strong><code><?php echo esc_html($po['reference']); ?></code></strong>
                                </td>
                                <td style="padding: 1rem 0.75rem; color: #64748B;">
                                    <?php echo esc_html($po['created_at']); ?>
                                </td>
                                <td style="padding: 1rem 0.75rem;">
                                    <strong><?php echo esc_html($po['bank_name']); ?></strong><br>
                                    <small style="color: #64748B;">Acc: <?php echo esc_html($po['account_num']); ?></small>
                                </td>
                                <td style="padding: 1rem 0.75rem; font-weight: 800; color: var(--ep-navy);">
                                    N$ <?php echo number_format($po['amount'], 2); ?>
                                </td>
                                <td style="padding: 1rem 0.75rem;">
                                    <?php if ($po['status'] === 'processed') : ?>
                                        <span style="background: #ECFDF5; color: #065F46; padding: 3px 9px; border-radius: 999px; font-weight: 700; font-size: 11px;">PAID / TRANSFERRED</span>
                                    <?php elseif ($po['status'] === 'approved') : ?>
                                        <span style="background: #EFF6FF; color: #1D4ED8; padding: 3px 9px; border-radius: 999px; font-weight: 700; font-size: 11px;">APPROVED FOR EFT</span>
                                    <?php elseif ($po['status'] === 'rejected') : ?>
                                        <span style="background: #FEF2F2; color: #991B1B; padding: 3px 9px; border-radius: 999px; font-weight: 700; font-size: 11px;">REJECTED</span>
                                    <?php else : ?>
                                        <span style="background: #FFFBEB; color: #92400E; padding: 3px 9px; border-radius: 999px; font-weight: 700; font-size: 11px;">PENDING REVIEW</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 1rem 0.75rem;">
                                    <?php if ($po['eft_ref']) : ?>
                                        <code><?php echo esc_html($po['eft_ref']); ?></code>
                                    <?php else : ?>
                                        <span style="color: #94A3B8;">Pending EFT Execution</span>
                                    <?php endif; ?>
                                </td>
                                <?php if ($is_admin) : ?>
                                    <td style="padding: 1rem 0.75rem;">
                                        <?php if ($po['status'] === 'pending' || $po['status'] === 'approved') : ?>
                                            <div style="display: flex; gap: 0.5rem;">
                                                <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(['action' => 'process_payout', 'payout_id' => $po['id']]), 'ep_process_payout_' . $po['id'])); ?>" class="ep-btn ep-btn-sm ep-btn-primary" style="font-size: 0.75rem; background: #059669; border-color: #059669;">
                                                    ✓ Mark Paid
                                                </a>
                                                <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(['action' => 'reject_payout', 'payout_id' => $po['id']]), 'ep_reject_payout_' . $po['id'])); ?>" class="ep-btn ep-btn-sm ep-btn-outline" style="font-size: 0.75rem; color: #DC2626; border-color: #FCA5A5;">
                                                    Reject
                                                </a>
                                            </div>
                                        <?php else : ?>
                                            <span style="color: #94A3B8; font-size: 0.8rem;">Settled</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="<?php echo $is_admin ? 7 : 6; ?>" style="padding: 3rem 1rem; text-align: center; color: var(--ep-slate-500);">
                                No payout requests submitted yet. Once tickets sell, your net revenue will be available for withdrawal.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<!-- Modal 1: Request EFT Payout Modal -->
<div id="epPayoutRequestModal" style="display: none; position: fixed; inset: 0; z-index: 9999; background: rgba(6, 14, 32, 0.75); backdrop-filter: blur(6px); align-items: center; justify-content: center; padding: 1.5rem; overflow-y: auto;">
    <div style="background: #ffffff; border-radius: 20px; max-width: 600px; width: 100%; max-height: 90vh; overflow-y: auto; padding: 2.5rem; box-shadow: 0 25px 50px rgba(0,0,0,0.3); position: relative;">
        <button type="button" onclick="document.getElementById('epPayoutRequestModal').style.display='none';" style="position: absolute; top: 1.5rem; right: 1.5rem; background: #F1F5F9; border: none; width: 36px; height: 36px; border-radius: 50%; font-size: 1.25rem; font-weight: 700; cursor: pointer; color: #475569; display: flex; align-items: center; justify-content: center;">&times;</button>

        <div style="margin-bottom: 1.75rem;">
            <span style="color: var(--ep-teal-dark); font-weight: 800; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px;">EFT Remittance Desk</span>
            <h2 style="font-size: 1.75rem; font-weight: 900; color: var(--ep-navy); margin: 0.25rem 0;">Request Organiser Payout</h2>
            <p style="color: var(--ep-slate-600); font-size: 0.9rem; margin: 0;">
                Funds are settled via Electronic Funds Transfer to certified commercial Namibian banking institutions.
            </p>
        </div>

        <div style="background: rgba(0,180,216,0.06); border: 1px solid rgba(0,180,216,0.25); border-radius: 12px; padding: 1rem 1.25rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
            <span style="font-size: 0.9rem; font-weight: 600; color: var(--ep-navy);">Available Balance:</span>
            <strong style="font-size: 1.25rem; color: var(--ep-teal-dark);">N$ <?php echo number_format($available_balance, 2); ?></strong>
        </div>

        <form id="epPayoutForm" action="<?php echo esc_url(home_url('/organiser-dashboard')); ?>" method="POST">
            <?php wp_nonce_field('ep_payout_action', 'ep_payout_nonce'); ?>

            <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--ep-navy); margin-bottom: 0.4rem;">Requested Amount (NAD) *</label>
                    <input type="number" step="0.01" min="10" max="<?php echo esc_attr($available_balance); ?>" name="payout_amount" required value="<?php echo esc_attr($available_balance > 0 ? $available_balance : '100'); ?>" style="width: 100%; padding: 0.8rem 1rem; border: 1px solid var(--ep-slate-200); border-radius: 10px; font-size: 1.05rem; font-weight: 700; color: var(--ep-navy);">
                </div>

                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--ep-navy); margin-bottom: 0.4rem;">Namibian Commercial Bank *</label>
                    <select name="bank_name" required style="width: 100%; padding: 0.8rem 1rem; border: 1px solid var(--ep-slate-200); border-radius: 10px; font-size: 0.95rem; background: #fff;">
                        <option value="Bank Windhoek" <?php selected($saved_bank_name, 'Bank Windhoek'); ?>>Bank Windhoek (Branch: 483-872)</option>
                        <option value="First National Bank (FNB) Namibia" <?php selected($saved_bank_name, 'First National Bank (FNB) Namibia'); ?>>FNB Namibia (Branch: 282-672)</option>
                        <option value="Standard Bank Namibia" <?php selected($saved_bank_name, 'Standard Bank Namibia'); ?>>Standard Bank Namibia (Branch: 082-772)</option>
                        <option value="Nedbank Namibia" <?php selected($saved_bank_name, 'Nedbank Namibia'); ?>>Nedbank Namibia (Branch: 461-609)</option>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--ep-navy); margin-bottom: 0.4rem;">Account Holder Name *</label>
                        <input type="text" name="account_name" required value="<?php echo esc_attr($saved_acc_name); ?>" style="width: 100%; padding: 0.8rem 1rem; border: 1px solid var(--ep-slate-200); border-radius: 10px; font-size: 0.95rem;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--ep-navy); margin-bottom: 0.4rem;">Account Number *</label>
                        <input type="text" name="account_number" required value="<?php echo esc_attr($saved_acc_num ?: '8001234567'); ?>" style="width: 100%; padding: 0.8rem 1rem; border: 1px solid var(--ep-slate-200); border-radius: 10px; font-size: 0.95rem; font-family: monospace;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--ep-navy); margin-bottom: 0.4rem;">Account Type</label>
                        <select name="account_type" style="width: 100%; padding: 0.8rem 1rem; border: 1px solid var(--ep-slate-200); border-radius: 10px; font-size: 0.95rem; background: #fff;">
                            <option value="Cheque / Current" <?php selected($saved_acc_type, 'Cheque / Current'); ?>>Cheque / Current Account</option>
                            <option value="Savings Account" <?php selected($saved_acc_type, 'Savings Account'); ?>>Savings Account</option>
                            <option value="Transmission" <?php selected($saved_acc_type, 'Transmission'); ?>>Transmission Account</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--ep-navy); margin-bottom: 0.4rem;">Branch Code</label>
                        <input type="text" name="branch_code" value="<?php echo esc_attr($saved_branch); ?>" style="width: 100%; padding: 0.8rem 1rem; border: 1px solid var(--ep-slate-200); border-radius: 10px; font-size: 0.95rem;">
                    </div>
                </div>

                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1rem;">
                    <button type="button" onclick="document.getElementById('epPayoutRequestModal').style.display='none';" class="ep-btn ep-btn-outline" style="border-color: var(--ep-slate-200);">Cancel</button>
                    <button type="submit" class="ep-btn ep-btn-primary">
                        <span>Submit Payout Request &rarr;</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>


<!-- Modal 2: Event Creation Modal -->
<div id="epEventCreateModal" style="display: none; position: fixed; inset: 0; z-index: 9999; background: rgba(6, 14, 32, 0.75); backdrop-filter: blur(6px); align-items: center; justify-content: center; padding: 1.5rem; overflow-y: auto;">
    <div style="background: #ffffff; border-radius: 20px; max-width: 720px; width: 100%; max-height: 90vh; overflow-y: auto; padding: 2.5rem; box-shadow: 0 25px 50px rgba(0,0,0,0.3); position: relative;">
        <button type="button" onclick="document.getElementById('epEventCreateModal').style.display='none';" style="position: absolute; top: 1.5rem; right: 1.5rem; background: #F1F5F9; border: none; width: 36px; height: 36px; border-radius: 50%; font-size: 1.25rem; font-weight: 700; cursor: pointer; color: #475569; display: flex; align-items: center; justify-content: center;">&times;</button>

        <div style="margin-bottom: 2rem;">
            <span style="color: var(--ep-teal-dark); font-weight: 800; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px;">SOW Event Creator</span>
            <h2 style="font-size: 1.8rem; font-weight: 900; color: var(--ep-navy); margin: 0.25rem 0;">Submit New Event for Approval</h2>
            <p style="color: var(--ep-slate-600); font-size: 0.95rem; margin: 0;">Configure your event details. Categories are loaded dynamically from the live database.</p>
        </div>

        <form id="epCreateEventForm" action="<?php echo esc_url(home_url('/organiser-dashboard')); ?>" method="POST" enctype="multipart/form-data">
            <?php wp_nonce_field('ep_create_event_action', 'ep_create_event_nonce'); ?>

            <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--ep-navy); margin-bottom: 0.4rem;">Event Title *</label>
                    <input type="text" name="event_title" required placeholder="e.g. Swakopmund Summer Regatta 2026" style="width: 100%; padding: 0.8rem 1rem; border: 1px solid var(--ep-slate-200); border-radius: 10px; font-size: 0.95rem;">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--ep-navy); margin-bottom: 0.4rem;">Database-Driven Category *</label>
                        <select name="event_category" required style="width: 100%; padding: 0.8rem 1rem; border: 1px solid var(--ep-slate-200); border-radius: 10px; font-size: 0.95rem; background: #fff;">
                            <option value="">-- Select Active Category --</option>
                            <?php if (!empty($active_categories)) : ?>
                                <?php foreach ($active_categories as $cat) : ?>
                                    <option value="<?php echo esc_attr($cat->slug); ?>"><?php echo esc_html((!empty($cat->icon) ? $cat->icon . ' ' : '') . $cat->name); ?></option>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <option value="" disabled>No active categories available</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--ep-navy); margin-bottom: 0.4rem;">Town / Region *</label>
                        <select name="event_town" required style="width: 100%; padding: 0.8rem 1rem; border: 1px solid var(--ep-slate-200); border-radius: 10px; font-size: 0.95rem; background: #fff;">
                            <option value="windhoek">Windhoek, Khomas</option>
                            <option value="swakopmund">Swakopmund, Erongo</option>
                            <option value="walvis-bay">Walvis Bay, Erongo</option>
                            <option value="oshakati">Oshakati, Oshana</option>
                            <option value="rundu">Rundu, Kavango East</option>
                            <option value="katima-mulilo">Katima Mulilo, Zambezi</option>
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--ep-navy); margin-bottom: 0.4rem;">Event Date *</label>
                        <input type="date" name="event_date" required value="2026-12-15" style="width: 100%; padding: 0.8rem 1rem; border: 1px solid var(--ep-slate-200); border-radius: 10px; font-size: 0.95rem;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--ep-navy); margin-bottom: 0.4rem;">Event Venue *</label>
                        <input type="text" name="event_venue" required placeholder="e.g. Swakopmund Dome Arena" style="width: 100%; padding: 0.8rem 1rem; border: 1px solid var(--ep-slate-200); border-radius: 10px; font-size: 0.95rem;">
                    </div>
                </div>

                <div style="background: var(--ep-slate-50); border: 1px solid var(--ep-slate-200); border-radius: 12px; padding: 1.15rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.65rem;">
                        <label style="font-size: 0.85rem; font-weight: 800; color: var(--ep-navy); text-transform: uppercase; letter-spacing: 0.03em;">
                            🕒 Event Schedule & Gate Opening Times *
                        </label>
                        <span style="font-size: 0.75rem; color: #64748B; background: #fff; padding: 2px 8px; border-radius: 4px; border: 1px solid var(--ep-slate-200); font-weight: 600;">CAT (UTC+2)</span>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0.85rem;">
                        <div>
                            <label style="display: block; font-size: 0.78rem; font-weight: 700; color: #475569; margin-bottom: 0.3rem;">
                                🚪 Gates Open *
                            </label>
                            <input type="time" name="gate_time" id="create_gate_time" required value="16:00" style="width: 100%; padding: 0.65rem 0.85rem; border: 1px solid var(--ep-slate-200); border-radius: 8px; font-size: 0.92rem; background: #fff;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.78rem; font-weight: 700; color: #475569; margin-bottom: 0.3rem;">
                                ▶️ Event Starts *
                            </label>
                            <input type="time" name="event_start_time" id="create_start_time" required value="18:00" style="width: 100%; padding: 0.65rem 0.85rem; border: 1px solid var(--ep-slate-200); border-radius: 8px; font-size: 0.92rem; background: #fff;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.78rem; font-weight: 700; color: #475569; margin-bottom: 0.3rem;">
                                ⏹️ Event Ends (Curfew)
                            </label>
                            <input type="time" name="event_end_time" id="create_end_time" value="02:00" style="width: 100%; padding: 0.65rem 0.85rem; border: 1px solid var(--ep-slate-200); border-radius: 8px; font-size: 0.92rem; background: #fff;">
                        </div>
                    </div>
                    <small style="display: block; margin-top: 0.5rem; font-size: 0.76rem; color: #64748B;">
                        Strict time pickers enforced. Free-form text is disabled to ensure valid event schedule data.
                    </small>
                </div>

                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--ep-navy); margin-bottom: 0.4rem;">Event Description *</label>
                    <textarea name="event_description" rows="3" required placeholder="Describe event highlights..." style="width: 100%; padding: 0.8rem 1rem; border: 1px solid var(--ep-slate-200); border-radius: 10px; font-size: 0.95rem;"></textarea>
                </div>

                <!-- SOW 4.1: Event Poster & Photo Gallery Media Uploads -->
                <div style="background: #F8FAFC; border: 1px solid var(--ep-slate-200); border-radius: 12px; padding: 1.25rem;">
                    <strong style="color: var(--ep-navy); font-size: 0.9rem; display: block; margin-bottom: 0.75rem;">
                        🖼️ Event Media & Visual Atmosphere (Upload Files)
                    </strong>

                    <!-- Poster Upload -->
                    <div style="margin-bottom: 1rem; background: #fff; padding: 0.9rem 1rem; border-radius: 10px; border: 1px solid var(--ep-slate-200);">
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--ep-navy); margin-bottom: 0.35rem;">
                            Official Event Poster / Flyer
                        </label>
                        <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
                            <label for="create_poster_file" style="cursor: pointer; background: var(--ep-teal-dark, #0d9488); color: #fff; padding: 0.55rem 1rem; border-radius: 8px; font-size: 0.85rem; font-weight: 600; display: inline-flex; align-items: center; gap: 0.4rem;">
                                📁 Choose Poster from Device
                            </label>
                            <input type="file" name="event_poster_file" id="create_poster_file" accept="image/*" style="display: none;" onchange="epPreviewPoster(this, 'create_poster_preview', 'create_poster_status')">
                            <span id="create_poster_status" style="font-size: 0.82rem; color: #64748B;">No file chosen (JPG, PNG, WebP)</span>
                        </div>
                        <div id="create_poster_preview" style="display: none; margin-top: 0.75rem; max-width: 180px; border-radius: 8px; overflow: hidden; border: 2px solid #0d9488; box-shadow: 0 4px 10px rgba(0,0,0,0.08);">
                            <img src="" style="width: 100%; height: auto; display: block;">
                        </div>
                        <details style="margin-top: 0.65rem;">
                            <summary style="font-size: 0.78rem; color: #0d9488; cursor: pointer; font-weight: 600;">🔗 Or use an image URL instead</summary>
                            <div style="margin-top: 0.4rem;">
                                <input type="url" name="event_poster" id="create_event_poster" placeholder="https://domain.com.na/poster.jpg (leave blank for default)" style="width: 100%; padding: 0.55rem 0.75rem; border: 1px solid var(--ep-slate-200); border-radius: 6px; font-size: 0.85rem; background: #fff;">
                            </div>
                        </details>
                    </div>

                    <!-- Gallery Upload -->
                    <div style="background: #fff; padding: 0.9rem 1rem; border-radius: 10px; border: 1px solid var(--ep-slate-200);">
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--ep-navy); margin-bottom: 0.35rem;">
                            Event Photo Gallery (Atmosphere & Highlights)
                        </label>
                        <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
                            <label for="create_gallery_files" style="cursor: pointer; background: #0F172A; color: #fff; padding: 0.55rem 1rem; border-radius: 8px; font-size: 0.85rem; font-weight: 600; display: inline-flex; align-items: center; gap: 0.4rem;">
                                📸 Select Gallery Photos (Multiple)
                            </label>
                            <input type="file" name="event_gallery_files[]" id="create_gallery_files" multiple accept="image/*" style="display: none;" onchange="epPreviewGallery(this, 'create_gallery_preview', 'create_gallery_status')">
                            <span id="create_gallery_status" style="font-size: 0.82rem; color: #64748B;">Select multiple photos</span>
                        </div>
                        <div id="create_gallery_preview" style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-top: 0.75rem;"></div>
                        <details style="margin-top: 0.65rem;">
                            <summary style="font-size: 0.78rem; color: #0d9488; cursor: pointer; font-weight: 600;">🔗 Or enter gallery image URLs manually</summary>
                            <div style="margin-top: 0.4rem;">
                                <textarea name="event_gallery" id="create_event_gallery" rows="2" placeholder="https://image1.jpg, https://image2.jpg" style="width: 100%; padding: 0.55rem 0.75rem; border: 1px solid var(--ep-slate-200); border-radius: 6px; font-size: 0.85rem; background: #fff;"></textarea>
                            </div>
                        </details>
                    </div>
                </div>

                <!-- Structured Ticket Tiers -->
                <div style="background: var(--ep-slate-50); border: 1px solid var(--ep-slate-200); border-radius: 12px; padding: 1.25rem;">
                    <strong style="color: var(--ep-navy); font-size: 0.9rem; display: block; margin-bottom: 0.5rem;">Ticket Tier Inventory Configuration</strong>
                    
                    <div style="margin-bottom: 0.75rem;">
                        <span style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--ep-teal-dark);">Tier 1: General Admission</span>
                        <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 0.5rem; margin-top: 0.25rem;">
                            <input type="text" name="tier_gen_name" value="General Admission" style="padding: 0.65rem 0.85rem; border: 1px solid var(--ep-slate-200); border-radius: 8px; font-size: 0.85rem;">
                            <input type="number" name="tier_gen_price" placeholder="Price (N$)" value="200" style="padding: 0.65rem 0.85rem; border: 1px solid var(--ep-slate-200); border-radius: 8px; font-size: 0.85rem;">
                            <input type="number" name="tier_gen_qty" placeholder="Cap Qty" value="500" style="padding: 0.65rem 0.85rem; border: 1px solid var(--ep-slate-200); border-radius: 8px; font-size: 0.85rem;">
                        </div>
                    </div>

                    <div>
                        <span style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--ep-navy);">Tier 2: VIP Golden Circle (Optional)</span>
                        <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 0.5rem; margin-top: 0.25rem;">
                            <input type="text" name="tier_vip_name" value="VIP Golden Circle" style="padding: 0.65rem 0.85rem; border: 1px solid var(--ep-slate-200); border-radius: 8px; font-size: 0.85rem;">
                            <input type="number" name="tier_vip_price" placeholder="Price (N$)" value="550" style="padding: 0.65rem 0.85rem; border: 1px solid var(--ep-slate-200); border-radius: 8px; font-size: 0.85rem;">
                            <input type="number" name="tier_vip_qty" placeholder="Cap Qty" value="100" style="padding: 0.65rem 0.85rem; border: 1px solid var(--ep-slate-200); border-radius: 8px; font-size: 0.85rem;">
                        </div>
                    </div>
                </div>

                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1rem;">
                    <button type="button" onclick="document.getElementById('epEventCreateModal').style.display='none';" class="ep-btn ep-btn-outline" style="border-color: var(--ep-slate-200);">Cancel</button>
                    <button type="submit" class="ep-btn ep-btn-primary">
                        <?php echo $is_admin ? 'Publish Event Live &rarr;' : 'Submit for Admin Approval &rarr;'; ?>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>


<!-- Modal 3: Event Edit Modal (Sprint 3) -->
<div id="epEditEventModal" style="display: none; position: fixed; inset: 0; z-index: 9999; background: rgba(6, 14, 32, 0.75); backdrop-filter: blur(6px); align-items: center; justify-content: center; padding: 1.5rem; overflow-y: auto;">
    <div style="background: #ffffff; border-radius: 20px; max-width: 720px; width: 100%; max-height: 90vh; overflow-y: auto; padding: 2.5rem; box-shadow: 0 25px 50px rgba(0,0,0,0.3); position: relative;">
        <button type="button" onclick="document.getElementById('epEditEventModal').style.display='none';" style="position: absolute; top: 1.5rem; right: 1.5rem; background: #F1F5F9; border: none; width: 36px; height: 36px; border-radius: 50%; font-size: 1.25rem; font-weight: 700; cursor: pointer; color: #475569; display: flex; align-items: center; justify-content: center;">&times;</button>

        <div style="margin-bottom: 2rem;">
            <span style="color: var(--ep-teal-dark); font-weight: 800; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px;">Organiser Lifecycle Controls</span>
            <h2 style="font-size: 1.8rem; font-weight: 900; color: var(--ep-navy); margin: 0.25rem 0;">Edit Event & Ticket Inventory</h2>
            <p style="color: var(--ep-slate-600); font-size: 0.95rem; margin: 0;">Update event schedule, venue location, description, and dynamic ticket tier quotas.</p>
        </div>

        <form id="epEditEventForm" action="<?php echo esc_url(home_url('/organiser-dashboard')); ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="edit_event_id" id="edit_event_id" value="">
            <input type="hidden" name="ep_edit_event_nonce" id="edit_event_nonce" value="">

            <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--ep-navy); margin-bottom: 0.4rem;">Event Title *</label>
                    <input type="text" name="edit_event_title" id="edit_event_title" required style="width: 100%; padding: 0.8rem 1rem; border: 1px solid var(--ep-slate-200); border-radius: 10px; font-size: 0.95rem;">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--ep-navy); margin-bottom: 0.4rem;">Category *</label>
                        <select name="edit_event_category" id="edit_event_category" required style="width: 100%; padding: 0.8rem 1rem; border: 1px solid var(--ep-slate-200); border-radius: 10px; font-size: 0.95rem; background: #fff;">
                            <option value="">-- Select Category --</option>
                            <?php if (!empty($active_categories)) : ?>
                                <?php foreach ($active_categories as $cat) : ?>
                                    <option value="<?php echo esc_attr($cat->slug); ?>"><?php echo esc_html((!empty($cat->icon) ? $cat->icon . ' ' : '') . $cat->name); ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--ep-navy); margin-bottom: 0.4rem;">Town / Region *</label>
                        <select name="edit_event_town" id="edit_event_town" required style="width: 100%; padding: 0.8rem 1rem; border: 1px solid var(--ep-slate-200); border-radius: 10px; font-size: 0.95rem; background: #fff;">
                            <option value="windhoek">Windhoek, Khomas</option>
                            <option value="swakopmund">Swakopmund, Erongo</option>
                            <option value="walvis-bay">Walvis Bay, Erongo</option>
                            <option value="oshakati">Oshakati, Oshana</option>
                            <option value="rundu">Rundu, Kavango East</option>
                            <option value="katima-mulilo">Katima Mulilo, Zambezi</option>
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--ep-navy); margin-bottom: 0.4rem;">Event Date *</label>
                        <input type="date" name="edit_event_date" id="edit_event_date" required style="width: 100%; padding: 0.8rem 1rem; border: 1px solid var(--ep-slate-200); border-radius: 10px; font-size: 0.95rem;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--ep-navy); margin-bottom: 0.4rem;">Event Venue *</label>
                        <input type="text" name="edit_event_venue" id="edit_event_venue" required style="width: 100%; padding: 0.8rem 1rem; border: 1px solid var(--ep-slate-200); border-radius: 10px; font-size: 0.95rem;">
                    </div>
                </div>

                <div style="background: var(--ep-slate-50); border: 1px solid var(--ep-slate-200); border-radius: 12px; padding: 1.15rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.65rem;">
                        <label style="font-size: 0.85rem; font-weight: 800; color: var(--ep-navy); text-transform: uppercase; letter-spacing: 0.03em;">
                            🕒 Event Schedule & Gate Opening Times *
                        </label>
                        <span style="font-size: 0.75rem; color: #64748B; background: #fff; padding: 2px 8px; border-radius: 4px; border: 1px solid var(--ep-slate-200); font-weight: 600;">CAT (UTC+2)</span>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0.85rem;">
                        <div>
                            <label style="display: block; font-size: 0.78rem; font-weight: 700; color: #475569; margin-bottom: 0.3rem;">
                                🚪 Gates Open *
                            </label>
                            <input type="time" name="edit_gate_time" id="edit_gate_time" required value="16:00" style="width: 100%; padding: 0.65rem 0.85rem; border: 1px solid var(--ep-slate-200); border-radius: 8px; font-size: 0.92rem; background: #fff;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.78rem; font-weight: 700; color: #475569; margin-bottom: 0.3rem;">
                                ▶️ Event Starts *
                            </label>
                            <input type="time" name="edit_start_time" id="edit_start_time" required value="18:00" style="width: 100%; padding: 0.65rem 0.85rem; border: 1px solid var(--ep-slate-200); border-radius: 8px; font-size: 0.92rem; background: #fff;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.78rem; font-weight: 700; color: #475569; margin-bottom: 0.3rem;">
                                ⏹️ Event Ends (Curfew)
                            </label>
                            <input type="time" name="edit_end_time" id="edit_end_time" value="02:00" style="width: 100%; padding: 0.65rem 0.85rem; border: 1px solid var(--ep-slate-200); border-radius: 8px; font-size: 0.92rem; background: #fff;">
                        </div>
                    </div>
                </div>

                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--ep-navy); margin-bottom: 0.4rem;">Event Description *</label>
                    <textarea name="edit_event_description" id="edit_event_description" rows="3" required style="width: 100%; padding: 0.8rem 1rem; border: 1px solid var(--ep-slate-200); border-radius: 10px; font-size: 0.95rem;"></textarea>
                </div>

                <!-- SOW 4.1: Event Poster & Photo Gallery Media Uploads for Edit -->
                <div style="background: #F8FAFC; border: 1px solid var(--ep-slate-200); border-radius: 12px; padding: 1.25rem;">
                    <strong style="color: var(--ep-navy); font-size: 0.9rem; display: block; margin-bottom: 0.75rem;">
                        🖼️ Event Media & Visual Atmosphere (Upload Files)
                    </strong>

                    <!-- Poster Edit / Upload -->
                    <div style="margin-bottom: 1rem; background: #fff; padding: 0.9rem 1rem; border-radius: 10px; border: 1px solid var(--ep-slate-200);">
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--ep-navy); margin-bottom: 0.35rem;">
                            Official Event Poster / Flyer
                        </label>
                        <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
                            <label for="edit_poster_file" style="cursor: pointer; background: var(--ep-teal-dark, #0d9488); color: #fff; padding: 0.55rem 1rem; border-radius: 8px; font-size: 0.85rem; font-weight: 600; display: inline-flex; align-items: center; gap: 0.4rem;">
                                📁 Upload New Poster File
                            </label>
                            <input type="file" name="edit_event_poster_file" id="edit_poster_file" accept="image/*" style="display: none;" onchange="epPreviewPoster(this, 'edit_poster_preview', 'edit_poster_status')">
                            <span id="edit_poster_status" style="font-size: 0.82rem; color: #64748B;">Keep current or choose new</span>
                        </div>
                        <div id="edit_poster_preview" style="display: none; margin-top: 0.75rem; max-width: 180px; border-radius: 8px; overflow: hidden; border: 2px solid #0d9488; box-shadow: 0 4px 10px rgba(0,0,0,0.08);">
                            <img src="" id="edit_poster_preview_img" style="width: 100%; height: auto; display: block;">
                        </div>
                        <details style="margin-top: 0.65rem;">
                            <summary style="font-size: 0.78rem; color: #0d9488; cursor: pointer; font-weight: 600;">🔗 Or edit image URL directly</summary>
                            <div style="margin-top: 0.4rem;">
                                <input type="url" name="edit_event_poster" id="edit_event_poster" placeholder="https://domain.com.na/poster.jpg" style="width: 100%; padding: 0.55rem 0.75rem; border: 1px solid var(--ep-slate-200); border-radius: 6px; font-size: 0.85rem; background: #fff;">
                            </div>
                        </details>
                    </div>

                    <!-- Gallery Edit / Upload -->
                    <div style="background: #fff; padding: 0.9rem 1rem; border-radius: 10px; border: 1px solid var(--ep-slate-200);">
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--ep-navy); margin-bottom: 0.35rem;">
                            Event Photo Gallery (Add More Photos)
                        </label>
                        <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
                            <label for="edit_gallery_files" style="cursor: pointer; background: #0F172A; color: #fff; padding: 0.55rem 1rem; border-radius: 8px; font-size: 0.85rem; font-weight: 600; display: inline-flex; align-items: center; gap: 0.4rem;">
                                📸 Select Additional Photos
                            </label>
                            <input type="file" name="edit_event_gallery_files[]" id="edit_gallery_files" multiple accept="image/*" style="display: none;" onchange="epPreviewGallery(this, 'edit_gallery_preview', 'edit_gallery_status')">
                            <span id="edit_gallery_status" style="font-size: 0.82rem; color: #64748B;">Upload more photos to append</span>
                        </div>
                        <!-- Current & New Gallery Previews -->
                        <div id="edit_existing_gallery_preview" style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-top: 0.75rem;"></div>
                        <div id="edit_gallery_preview" style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-top: 0.5rem;"></div>
                        <details style="margin-top: 0.65rem;">
                            <summary style="font-size: 0.78rem; color: #0d9488; cursor: pointer; font-weight: 600;">🔗 Or edit gallery URLs manually</summary>
                            <div style="margin-top: 0.4rem;">
                                <textarea name="edit_event_gallery" id="edit_event_gallery" rows="2" placeholder="https://image1.jpg, https://image2.jpg" style="width: 100%; padding: 0.55rem 0.75rem; border: 1px solid var(--ep-slate-200); border-radius: 6px; font-size: 0.85rem; background: #fff;"></textarea>
                            </div>
                        </details>
                    </div>
                </div>

                <!-- Structured Ticket Tiers -->
                <div style="background: var(--ep-slate-50); border: 1px solid var(--ep-slate-200); border-radius: 12px; padding: 1.25rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                        <strong style="color: var(--ep-navy); font-size: 0.9rem;">Ticket Tier Pricing & Capacity Quotas</strong>
                        <span style="font-size: 0.75rem; color: #64748B;">Capacity cannot be set lower than already sold</span>
                    </div>
                    
                    <div style="margin-bottom: 1rem;">
                        <div style="display: flex; justify-content: space-between; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--ep-teal-dark);">
                            <span>Tier 1: General Admission</span>
                            <span id="edit_tier_gen_sold_badge" style="color: #64748B; font-weight: 600;"></span>
                        </div>
                        <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 0.5rem; margin-top: 0.25rem;">
                            <input type="text" name="edit_tier_gen_name" id="edit_tier_gen_name" value="General Admission" style="padding: 0.65rem 0.85rem; border: 1px solid var(--ep-slate-200); border-radius: 8px; font-size: 0.85rem;">
                            <input type="number" name="edit_tier_gen_price" id="edit_tier_gen_price" placeholder="Price (N$)" value="200" style="padding: 0.65rem 0.85rem; border: 1px solid var(--ep-slate-200); border-radius: 8px; font-size: 0.85rem;">
                            <input type="number" name="edit_tier_gen_qty" id="edit_tier_gen_qty" placeholder="Cap Qty" value="500" style="padding: 0.65rem 0.85rem; border: 1px solid var(--ep-slate-200); border-radius: 8px; font-size: 0.85rem;">
                        </div>
                    </div>

                    <div style="margin-bottom: 1rem;">
                        <div style="display: flex; justify-content: space-between; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--ep-navy);">
                            <span>Tier 2: VIP Golden Circle (Optional)</span>
                            <span id="edit_tier_vip_sold_badge" style="color: #64748B; font-weight: 600;"></span>
                        </div>
                        <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 0.5rem; margin-top: 0.25rem;">
                            <input type="text" name="edit_tier_vip_name" id="edit_tier_vip_name" value="VIP Golden Circle" style="padding: 0.65rem 0.85rem; border: 1px solid var(--ep-slate-200); border-radius: 8px; font-size: 0.85rem;">
                            <input type="number" name="edit_tier_vip_price" id="edit_tier_vip_price" placeholder="Price (N$)" value="550" style="padding: 0.65rem 0.85rem; border: 1px solid var(--ep-slate-200); border-radius: 8px; font-size: 0.85rem;">
                            <input type="number" name="edit_tier_vip_qty" id="edit_tier_vip_qty" placeholder="Cap Qty" value="100" style="padding: 0.65rem 0.85rem; border: 1px solid var(--ep-slate-200); border-radius: 8px; font-size: 0.85rem;">
                        </div>
                    </div>

                    <div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--ep-navy);">
                            <span>Tier 3: Early Bird Pass (Optional)</span>
                            <span id="edit_tier_early_sold_badge" style="color: #64748B; font-weight: 600;"></span>
                        </div>
                        <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 0.5rem; margin-top: 0.25rem;">
                            <input type="text" name="edit_tier_early_name" id="edit_tier_early_name" value="Early Bird Pass" style="padding: 0.65rem 0.85rem; border: 1px solid var(--ep-slate-200); border-radius: 8px; font-size: 0.85rem;">
                            <input type="number" name="edit_tier_early_price" id="edit_tier_early_price" placeholder="Price (N$)" value="100" style="padding: 0.65rem 0.85rem; border: 1px solid var(--ep-slate-200); border-radius: 8px; font-size: 0.85rem;">
                            <input type="number" name="edit_tier_early_qty" id="edit_tier_early_qty" placeholder="Cap Qty" value="0" style="padding: 0.65rem 0.85rem; border: 1px solid var(--ep-slate-200); border-radius: 8px; font-size: 0.85rem;">
                        </div>
                    </div>
                </div>

                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1rem;">
                    <button type="button" onclick="document.getElementById('epEditEventModal').style.display='none';" class="ep-btn ep-btn-outline" style="border-color: var(--ep-slate-200);">Cancel</button>
                    <button type="submit" class="ep-btn ep-btn-primary">
                        Save Changes &rarr;
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal 4: Event Attendee Roster Modal (Sprint 3) -->
<div id="epAttendeeModal" style="display: none; position: fixed; inset: 0; z-index: 9999; background: rgba(6, 14, 32, 0.75); backdrop-filter: blur(6px); align-items: center; justify-content: center; padding: 1.5rem; overflow-y: auto;">
    <div style="background: #ffffff; border-radius: 20px; max-width: 800px; width: 100%; max-height: 90vh; overflow-y: auto; padding: 2rem; box-shadow: 0 25px 50px rgba(0,0,0,0.3); position: relative;">
        <button type="button" onclick="document.getElementById('epAttendeeModal').style.display='none';" style="position: absolute; top: 1.5rem; right: 1.5rem; background: #F1F5F9; border: none; width: 36px; height: 36px; border-radius: 50%; font-size: 1.25rem; font-weight: 700; cursor: pointer; color: #475569; display: flex; align-items: center; justify-content: center;">&times;</button>

        <div style="margin-bottom: 1.5rem;">
            <span style="color: var(--ep-teal-dark); font-weight: 800; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px;">Live Door Attendance</span>
            <h2 id="attendeeModalTitle" style="font-size: 1.6rem; font-weight: 900; color: var(--ep-navy); margin: 0.25rem 0;">Event Attendee Roster</h2>
            <p style="color: var(--ep-slate-600); font-size: 0.9rem; margin: 0;">Verified ticket holders, pass tokens, and real-time gate check-in status.</p>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: 1rem; flex-wrap: wrap;">
            <input type="text" id="organiserRosterSearch" onkeyup="filterOrganiserRoster()" placeholder="Search attendee name, email, token..." style="flex: 1; min-width: 240px; padding: 0.6rem 0.9rem; border: 1px solid var(--ep-slate-200); border-radius: 8px; font-size: 0.9rem;">
            <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                <div id="organiserRosterStats" style="font-size: 0.85rem; font-weight: 700; color: var(--ep-navy);">
                    Loading...
                </div>
                <button type="button" onclick="downloadOrganiserRosterCSV()" class="ep-btn ep-btn-sm ep-btn-outline" style="font-size: 0.78rem; padding: 0.4rem 0.8rem; border-color: var(--ep-teal); color: var(--ep-teal-dark);">
                    📥 Export CSV
                </button>
            </div>
        </div>

        <div style="max-height: 380px; overflow-y: auto; border: 1px solid var(--ep-slate-200); border-radius: 10px;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem; text-align: left;">
                <thead style="background: var(--ep-slate-50); position: sticky; top: 0; color: var(--ep-navy); border-bottom: 1px solid var(--ep-slate-200);">
                    <tr>
                        <th style="padding: 0.65rem 0.85rem;">Attendee</th>
                        <th style="padding: 0.65rem 0.85rem;">Tier</th>
                        <th style="padding: 0.65rem 0.85rem;">Token</th>
                        <th style="padding: 0.65rem 0.85rem;">Status</th>
                        <th style="padding: 0.65rem 0.85rem; text-align: right;">Gate Action</th>
                    </tr>
                </thead>
                <tbody id="organiserRosterBody">
                    <tr>
                        <td colspan="5" style="padding: 2rem; text-align: center; color: var(--ep-slate-500);">Loading attendees...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
const ORG_REST_ATTENDEES_URL = '<?php echo esc_url(rest_url('eventpass/v1/event-attendees')); ?>';
const ORG_REST_SCAN_URL      = '<?php echo esc_url(rest_url('eventpass/v1/scan')); ?>';
const ORG_REST_NONCE         = '<?php echo esc_js(wp_create_nonce('wp_rest')); ?>';

let curOrgEventId = 0;
let orgRosterList = [];

// Image Upload Preview Helpers (SOW 4.1)
function epPreviewPoster(input, previewContainerId, statusId) {
    const file = input.files && input.files[0];
    const previewContainer = document.getElementById(previewContainerId);
    const statusSpan = document.getElementById(statusId);
    if (!file) return;

    if (statusSpan) {
        statusSpan.textContent = `✓ Selected: ${file.name} (${Math.round(file.size / 1024)} KB)`;
        statusSpan.style.color = '#059669';
    }

    const reader = new FileReader();
    reader.onload = function(e) {
        if (previewContainer) {
            const img = previewContainer.querySelector('img');
            if (img) img.src = e.target.result;
            previewContainer.style.display = 'block';
        }
    };
    reader.readAsDataURL(file);
}

function epPreviewGallery(input, previewContainerId, statusId) {
    const files = input.files;
    const previewContainer = document.getElementById(previewContainerId);
    const statusSpan = document.getElementById(statusId);
    if (!files || files.length === 0) return;

    if (statusSpan) {
        statusSpan.textContent = `✓ ${files.length} photo(s) selected for upload`;
        statusSpan.style.color = '#059669';
    }

    if (previewContainer) {
        previewContainer.innerHTML = '';
        Array.from(files).forEach(f => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const wrap = document.createElement('div');
                wrap.style.width = '60px';
                wrap.style.height = '60px';
                wrap.style.borderRadius = '8px';
                wrap.style.overflow = 'hidden';
                wrap.style.border = '2px solid #0d9488';
                wrap.style.boxShadow = '0 2px 6px rgba(0,0,0,0.1)';
                const img = document.createElement('img');
                img.src = e.target.result;
                img.style.width = '100%';
                img.style.height = '100%';
                img.style.objectFit = 'cover';
                wrap.appendChild(img);
                previewContainer.appendChild(wrap);
            };
            reader.readAsDataURL(f);
        });
    }
}

function openEditEventModal(data) {
    document.getElementById('edit_event_id').value = data.id;
    document.getElementById('edit_event_nonce').value = data.nonce;
    document.getElementById('edit_event_title').value = data.title;
    document.getElementById('edit_event_category').value = data.category;
    document.getElementById('edit_event_town').value = data.town;
    document.getElementById('edit_event_venue').value = data.venue;
    document.getElementById('edit_event_date').value = data.date;

    // Parse structured time for edit modal
    const timeStr = data.time || '';
    const timeMatches = timeStr.match(/\b\d{2}:\d{2}\b/g) || [];
    if (timeMatches.length >= 2) {
        document.getElementById('edit_gate_time').value = timeMatches[0];
        document.getElementById('edit_start_time').value = timeMatches[1];
        if (timeMatches.length >= 3) {
            document.getElementById('edit_end_time').value = timeMatches[2];
        } else {
            document.getElementById('edit_end_time').value = '02:00';
        }
    } else if (timeMatches.length === 1) {
        document.getElementById('edit_gate_time').value = '16:00';
        document.getElementById('edit_start_time').value = timeMatches[0];
        document.getElementById('edit_end_time').value = '02:00';
    } else {
        document.getElementById('edit_gate_time').value = '16:00';
        document.getElementById('edit_start_time').value = '18:00';
        document.getElementById('edit_end_time').value = '02:00';
    }

    document.getElementById('edit_event_description').value = data.desc;
    if (document.getElementById('edit_event_poster')) {
        document.getElementById('edit_event_poster').value = data.poster || '';
    }
    if (document.getElementById('edit_event_gallery')) {
        document.getElementById('edit_event_gallery').value = data.gallery || '';
    }

    // SOW 4.1: Populate current poster preview in Edit Modal
    const editPosterPreview = document.getElementById('edit_poster_preview');
    const editPosterImg = document.getElementById('edit_poster_preview_img');
    const editPosterStatus = document.getElementById('edit_poster_status');
    if (data.poster) {
        if (editPosterImg) editPosterImg.src = data.poster;
        if (editPosterPreview) editPosterPreview.style.display = 'block';
        if (editPosterStatus) {
            editPosterStatus.textContent = 'Current Poster active (choose new file to replace)';
            editPosterStatus.style.color = '#059669';
        }
    } else {
        if (editPosterPreview) editPosterPreview.style.display = 'none';
        if (editPosterStatus) {
            editPosterStatus.textContent = 'No poster set yet (choose file to upload)';
            editPosterStatus.style.color = '#64748B';
        }
    }

    // SOW 4.1: Populate current gallery thumbnails in Edit Modal
    const existingGalleryPreview = document.getElementById('edit_existing_gallery_preview');
    if (existingGalleryPreview) {
        existingGalleryPreview.innerHTML = '';
        if (data.gallery) {
            const gUrls = data.gallery.split(',').map(u => u.trim()).filter(Boolean);
            gUrls.forEach(u => {
                const wrap = document.createElement('div');
                wrap.style.width = '52px';
                wrap.style.height = '52px';
                wrap.style.borderRadius = '6px';
                wrap.style.overflow = 'hidden';
                wrap.style.border = '2px solid #CBD5E1';
                wrap.title = 'Saved Gallery Photo';
                const img = document.createElement('img');
                img.src = u;
                img.style.width = '100%';
                img.style.height = '100%';
                img.style.objectFit = 'cover';
                wrap.appendChild(img);
                existingGalleryPreview.appendChild(wrap);
            });
        }
    }
    const editNewGalleryPreview = document.getElementById('edit_gallery_preview');
    if (editNewGalleryPreview) editNewGalleryPreview.innerHTML = '';
    const editGalleryStatus = document.getElementById('edit_gallery_status');
    if (editGalleryStatus) {
        editGalleryStatus.textContent = 'Upload more photos to append';
        editGalleryStatus.style.color = '#64748B';
    }

    document.getElementById('edit_tier_gen_price').value = data.gen_price;
    document.getElementById('edit_tier_gen_qty').value = data.gen_qty;
    document.getElementById('edit_tier_gen_qty').min = data.gen_sold;
    document.getElementById('edit_tier_gen_sold_badge').textContent = `Sold: ${data.gen_sold} / Cap: ${data.gen_qty}`;

    document.getElementById('edit_tier_vip_price').value = data.vip_price;
    document.getElementById('edit_tier_vip_qty').value = data.vip_qty;
    document.getElementById('edit_tier_vip_qty').min = data.vip_sold;
    document.getElementById('edit_tier_vip_sold_badge').textContent = `Sold: ${data.vip_sold} / Cap: ${data.vip_qty}`;

    document.getElementById('edit_tier_early_price').value = data.early_price;
    document.getElementById('edit_tier_early_qty').value = data.early_qty;
    document.getElementById('edit_tier_early_qty').min = data.early_sold;
    document.getElementById('edit_tier_early_sold_badge').textContent = `Sold: ${data.early_sold} / Cap: ${data.early_qty}`;

    document.getElementById('epEditEventModal').style.display = 'flex';
}

function openAttendeeModal(eventId, title) {
    curOrgEventId = eventId;
    document.getElementById('attendeeModalTitle').textContent = title + ' – Attendee Roster';
    document.getElementById('organiserRosterSearch').value = '';
    document.getElementById('epAttendeeModal').style.display = 'flex';
    loadOrganiserRoster(eventId);
}

function loadOrganiserRoster(eventId) {
    const tbody = document.getElementById('organiserRosterBody');
    const stats = document.getElementById('organiserRosterStats');
    tbody.innerHTML = '<tr><td colspan="5" style="padding: 2rem; text-align: center; color: var(--ep-slate-500);">Fetching live attendees...</td></tr>';
    stats.textContent = 'Fetching...';

    fetch(ORG_REST_ATTENDEES_URL + '?event_id=' + encodeURIComponent(eventId), {
        headers: { 'X-WP-Nonce': ORG_REST_NONCE }
    })
        .then(r => r.json())
        .then(data => {
            orgRosterList = (data && data.attendees) ? data.attendees : [];
            let checkedInCount = orgRosterList.filter(a => a.status === 'already_used' || a.status === 'used' || a.status === 'scanned' || a.scanned_at).length;
            stats.textContent = `Total: ${orgRosterList.length} • Checked In: ${checkedInCount}`;
            renderOrganiserRoster(orgRosterList);
        })
        .catch(err => {
            tbody.innerHTML = '<tr><td colspan="5" style="padding: 2rem; text-align: center; color: #DC2626;">Failed to load attendee roster.</td></tr>';
        });
}

function renderOrganiserRoster(list) {
    const tbody = document.getElementById('organiserRosterBody');
    if (!list || list.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="padding: 2rem; text-align: center; color: var(--ep-slate-500);">No tickets issued yet for this event.</td></tr>';
        return;
    }

    tbody.innerHTML = list.map(item => {
        const isChecked = (item.status === 'already_used' || item.status === 'used' || item.status === 'scanned' || item.scanned_at);
        const statusBadge = isChecked
            ? `<span style="background: #ECFDF5; color: #065F46; padding: 2px 7px; border-radius: 4px; font-weight: 700; font-size: 11px;">Checked In</span>`
            : `<span style="background: #EFF6FF; color: #1D4ED8; padding: 2px 7px; border-radius: 4px; font-weight: 700; font-size: 11px;">Valid Pass</span>`;

        const action = isChecked
            ? `<span style="font-size: 0.75rem; color: #64748B;">✓ ${item.scanned_at ? item.scanned_at.substring(11, 16) : 'Scanned'}</span>`
            : `<button type="button" onclick="checkInOrganiserAttendee('${orgEscape(item.token)}')" style="padding: 0.25rem 0.55rem; font-size: 0.75rem; background: var(--ep-teal); color: var(--ep-navy-deep); font-weight: 700; border-radius: 4px; border: none; cursor: pointer;">Check In</button>`;

        return `
            <tr style="border-bottom: 1px solid var(--ep-slate-100);">
                <td style="padding: 0.65rem 0.85rem;">
                    <strong style="color: var(--ep-navy); display: block;">${orgEscape(item.holder)}</strong>
                    <span style="font-size: 0.75rem; color: #64748B;">${orgEscape(item.email || item.phone || '')}</span>
                </td>
                <td style="padding: 0.65rem 0.85rem; text-transform: capitalize; color: var(--ep-slate-700);">${orgEscape(item.tier)}</td>
                <td style="padding: 0.65rem 0.85rem;"><code>${orgEscape(item.token)}</code></td>
                <td style="padding: 0.65rem 0.85rem;">${statusBadge}</td>
                <td style="padding: 0.65rem 0.85rem; text-align: right;">${action}</td>
            </tr>
        `;
    }).join('');
}

function filterOrganiserRoster() {
    const q = (document.getElementById('organiserRosterSearch').value || '').toLowerCase().trim();
    if (!q) {
        renderOrganiserRoster(orgRosterList);
        return;
    }
    const filtered = orgRosterList.filter(item => {
        return (item.holder && item.holder.toLowerCase().includes(q)) ||
               (item.token && item.token.toLowerCase().includes(q)) ||
               (item.email && item.email.toLowerCase().includes(q)) ||
               (item.phone && item.phone.toLowerCase().includes(q)) ||
               (item.tier && item.tier.toLowerCase().includes(q));
    });
    renderOrganiserRoster(filtered);
}

function checkInOrganiserAttendee(token) {
    if (!token || !curOrgEventId) return;
    if (!confirm('Confirm manual gate check-in for ticket ' + token + '?')) return;

    fetch(ORG_REST_SCAN_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': ORG_REST_NONCE },
        body: JSON.stringify({ code: token, event_id: curOrgEventId })
    })
    .then(r => r.json())
    .then(data => {
        if (data.result === 'valid') {
            alert('Gate Check-In Approved for ' + (data.holder || 'Attendee') + '!');
            loadOrganiserRoster(curOrgEventId);
        } else {
            alert(data.title + ': ' + (data.message || 'Ticket could not be verified.'));
        }
    })
    .catch(err => {
        alert('Communication error verifying pass.');
    });
}

function orgEscape(s) {
    if (!s) return '';
    return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function downloadOrganiserRosterCSV() {
    if (!orgRosterList || orgRosterList.length === 0) {
        alert('No attendee records available to export.');
        return;
    }
    const headers = ['Attendee Name', 'Email', 'Phone', 'Tier', 'Ticket Token', 'Status', 'Checked In At', 'Issued At'];
    const csvEscape = (v) => '"' + String(v == null ? '' : v).replace(/"/g, '""') + '"';
    const rows = orgRosterList.map(item => {
        const isChecked = (item.status === 'already_used' || item.status === 'used' || item.status === 'scanned' || item.scanned_at);
        return [
            item.holder || '',
            item.email || '',
            item.phone || '',
            item.tier || '',
            item.token || '',
            isChecked ? 'Checked In' : (item.status || 'Valid'),
            item.scanned_at || '',
            item.issued_at || ''
        ].map(csvEscape).join(',');
    });
    const csv = [headers.map(csvEscape).join(','), ...rows].join('\r\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'event-' + curOrgEventId + '-attendees.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}
</script>

<?php
get_footer();
