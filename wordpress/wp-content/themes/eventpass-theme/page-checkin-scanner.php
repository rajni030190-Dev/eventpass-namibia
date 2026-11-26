<?php
/**
 * Template Name: Gate Check-in & Scanner
 *
 * @package EventPass_Namibia
 */

get_header();

// Query published events from database for gate assignment
$scanner_events = get_posts([
    'post_type'      => 'ep_event',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'orderby'        => 'title',
    'order'          => 'ASC'
]);
?>

<div class="ep-scanner-wrapper" style="background: var(--ep-navy-deep); min-height: 85vh; padding: 2.5rem 1rem; color: #ffffff;">
    <div style="max-width: 600px; margin: 0 auto;">
        
        <!-- Scanner Header -->
        <div style="text-align: center; margin-bottom: 2rem;">
            <div style="display: inline-flex; align-items: center; gap: 0.5rem; background: rgba(0,180,216,0.15); border: 1px solid rgba(0,180,216,0.3); padding: 0.35rem 0.85rem; border-radius: var(--ep-radius-full); font-size: 0.8rem; font-weight: 700; color: var(--ep-cyan); text-transform: uppercase;">
                <span style="width: 8px; height: 8px; background: #10B981; border-radius: 50%; display: inline-block;"></span>
                EventPass Gate Access Verifier • Real Database REST API
            </div>
            <h1 style="font-size: 1.75rem; font-weight: 800; margin: 0.5rem 0 0.25rem;">Gate Check-In & Scanner</h1>
            <p style="color: #94A3B8; font-size: 0.9rem; margin: 0;">Scan attendee QR codes or enter ticket token to validate entrance against live database</p>
        </div>

        <!-- Event Selector -->
        <div style="background: var(--ep-navy); border: 1px solid rgba(255,255,255,0.1); border-radius: var(--ep-radius-md); padding: 1rem 1.25rem; margin-bottom: 1.5rem;">
            <label style="display: block; font-size: 0.75rem; text-transform: uppercase; color: var(--ep-teal); font-weight: 700; margin-bottom: 0.35rem;">Assigned Event</label>
            <select id="scannerEventSelect" onchange="onEventChanged()" style="width: 100%; padding: 0.6rem; border-radius: 6px; border: 1px solid rgba(255,255,255,0.2); background: var(--ep-navy-deep); color: #fff; font-size: 0.9rem;">
                <?php if (!empty($scanner_events)) : ?>
                    <?php foreach ($scanner_events as $sev) : 
                        $sev_venue = get_post_meta($sev->ID, '_ep_event_venue', true) ?: 'Namibia';
                    ?>
                        <option value="<?php echo esc_attr($sev->ID); ?>">
                            <?php echo esc_html($sev->post_title); ?> (<?php echo esc_html($sev_venue); ?>)
                        </option>
                    <?php endforeach; ?>
                <?php else : ?>
                    <option value="0">No published events found</option>
                <?php endif; ?>
            </select>
        </div>

        <!-- Camera Viewfinder Box (Powered by html5-qrcode) -->
        <div style="background: #000000; border: 2px solid var(--ep-teal); border-radius: var(--ep-radius-lg); overflow: hidden; position: relative; margin-bottom: 1.5rem; box-shadow: 0 10px 25px rgba(0,0,0,0.5);">
            <div id="videoContainer" style="min-height: 280px; position: relative; display: flex; align-items: center; justify-content: center; background: #0F172A;">
                <div id="reader" style="width: 100%; border: none;"></div>

                <!-- Camera Inactive Standby State -->
                <div id="cameraStandby" style="text-align: center; padding: 2.5rem 1rem;">
                    <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="color: var(--ep-teal); margin-bottom: 0.75rem;"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                    <p style="font-size: 0.95rem; color: #94A3B8; margin: 0 0 1.25rem;">Optical Camera Viewfinder is in standby mode</p>
                    <button type="button" id="startCameraBtn" onclick="startScannerCamera()" class="ep-btn ep-btn-primary ep-btn-sm">
                        <span>📷 Start Optical Camera Scan</span>
                    </button>
                </div>
            </div>

            <!-- Scanner Controls Footer -->
            <div style="background: rgba(11,27,61,0.95); padding: 0.75rem 1.25rem; display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem;">
                <span id="cameraStatusText" style="color: #94A3B8;">Standby Mode</span>
                <div style="display: flex; gap: 8px;">
                    <button type="button" onclick="stopScannerCamera()" id="stopCameraBtn" style="background: #DC2626; border: none; color: #fff; padding: 0.35rem 0.75rem; border-radius: 6px; cursor: pointer; display: none; font-weight: 600;">Stop Camera</button>
                    <button type="button" onclick="toggleCameraFlash()" id="flashBtn" style="background: none; border: 1px solid rgba(255,255,255,0.2); color: #fff; padding: 0.35rem 0.75rem; border-radius: 6px; cursor: pointer; display: none;">Torch</button>
                </div>
            </div>
        </div>

        <!-- Manual Ticket Code Input Fallback -->
        <div style="background: var(--ep-navy); border: 1px solid rgba(255,255,255,0.1); border-radius: var(--ep-radius-md); padding: 1.25rem; margin-bottom: 1.5rem;">
            <label style="display: block; font-size: 0.75rem; text-transform: uppercase; color: var(--ep-teal); font-weight: 700; margin-bottom: 0.4rem;">Manual Ticket / QR Token</label>
            <div style="display: flex; gap: 0.5rem;">
                <input type="text" id="manualTicketInput" placeholder="e.g. EP-NAM-VIP-..." style="flex: 1; padding: 0.7rem 0.9rem; border-radius: 6px; border: 1px solid rgba(255,255,255,0.2); background: var(--ep-navy-deep); color: #fff; font-size: 0.95rem; font-family: monospace;">
                <button type="button" onclick="verifyTicketCode(document.getElementById('manualTicketInput').value)" class="ep-btn ep-btn-primary">
                    <span>Verify Pass</span>
                </button>
            </div>
        </div>

        <!-- Live Scan Feedback Card (Status Result) -->
        <div id="scanResultCard" style="display: none; padding: 1.5rem; border-radius: var(--ep-radius-lg); margin-bottom: 1.5rem; animation: slideUp 0.3s ease;">
            <!-- Dynamically populated via JS from REST API -->
        </div>

        <!-- Gate Statistics Tally -->
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; text-align: center; margin-bottom: 2rem;">
            <div style="background: var(--ep-navy); border: 1px solid rgba(255,255,255,0.08); border-radius: var(--ep-radius-md); padding: 1rem;">
                <span style="font-size: 0.75rem; color: #94A3B8; text-transform: uppercase; display: block;">Total Sold</span>
                <span id="totalSoldCount" style="font-size: 1.5rem; font-weight: 800; color: #ffffff;">0</span>
            </div>
            <div style="background: var(--ep-navy); border: 1px solid rgba(255,255,255,0.08); border-radius: var(--ep-radius-md); padding: 1rem;">
                <span style="font-size: 0.75rem; color: var(--ep-success); text-transform: uppercase; display: block;">Checked In</span>
                <span id="checkedInCount" style="font-size: 1.5rem; font-weight: 800; color: #10B981;">0</span>
            </div>
            <div style="background: var(--ep-navy); border: 1px solid rgba(255,255,255,0.08); border-radius: var(--ep-radius-md); padding: 1rem;">
                <span style="font-size: 0.75rem; color: var(--ep-cyan); text-transform: uppercase; display: block;">Remaining</span>
                <span id="remainingCount" style="font-size: 1.5rem; font-weight: 800; color: var(--ep-cyan);">0</span>
            </div>
        </div>

        <!-- Live Attendee Roster & Manual Check-In Desk (Sprint 3) -->
        <div style="background: var(--ep-navy); border: 1px solid rgba(255,255,255,0.12); border-radius: var(--ep-radius-lg); padding: 1.5rem; margin-bottom: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.75rem;">
                <div>
                    <h3 style="margin: 0; font-size: 1.15rem; font-weight: 800; color: #FFFFFF; display: flex; align-items: center; gap: 0.5rem;">
                        <span>📋</span> Gate Attendee Roster
                    </h3>
                    <span style="font-size: 0.8rem; color: #94A3B8;">Search attendee list & perform manual check-ins</span>
                </div>
                <input type="text" id="rosterSearchInput" onkeyup="filterRoster()" placeholder="Search name, phone, or token..." style="padding: 0.5rem 0.85rem; border-radius: 6px; border: 1px solid rgba(255,255,255,0.2); background: var(--ep-navy-deep); color: #fff; font-size: 0.85rem; width: 220px;">
            </div>

            <div style="max-height: 320px; overflow-y: auto; border: 1px solid rgba(255,255,255,0.08); border-radius: 8px;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem; text-align: left;">
                    <thead style="background: rgba(0,0,0,0.3); position: sticky; top: 0; color: var(--ep-teal);">
                        <tr>
                            <th style="padding: 0.6rem 0.75rem;">Attendee</th>
                            <th style="padding: 0.6rem 0.75rem;">Tier</th>
                            <th style="padding: 0.6rem 0.75rem;">Token</th>
                            <th style="padding: 0.6rem 0.75rem;">Status</th>
                            <th style="padding: 0.6rem 0.75rem; text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="rosterTableBody">
                        <tr>
                            <td colspan="5" style="padding: 2rem; text-align: center; color: #94A3B8;">Loading attendee roster...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<style>
@keyframes scanLine {
    0% { transform: translateY(-100px); }
    50% { transform: translateY(100px); }
    100% { transform: translateY(-100px); }
}
@keyframes slideUp {
    from { opacity: 0; transform: translateY(15px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<!-- Enqueue HTML5-QRCode Scanner Library (Self-Contained Local Asset) -->
<script src="<?php echo esc_url(get_template_directory_uri() . '/assets/js/html5-qrcode.min.js'); ?>"></script>

<script>
const REST_SCAN_URL      = '<?php echo esc_url(rest_url('eventpass/v1/scan')); ?>';
const REST_STATS_URL     = '<?php echo esc_url(rest_url('eventpass/v1/gate-stats')); ?>';
const REST_ATTENDEES_URL = '<?php echo esc_url(rest_url('eventpass/v1/event-attendees')); ?>';

let html5QrCodeScanner = null;
let isScannerActive = false;
let isCooldown = false;
let rosterData = [];

function refreshGateStats() {
    const sel = document.getElementById('scannerEventSelect');
    const eventId = sel ? sel.value : 0;
    fetch(REST_STATS_URL + '?event_id=' + encodeURIComponent(eventId))
        .then(r => r.json())
        .then(data => {
            if (data) {
                document.getElementById('totalSoldCount').textContent = data.total_sold ?? 0;
                document.getElementById('checkedInCount').textContent = data.checked_in ?? 0;
                document.getElementById('remainingCount').textContent = data.remaining ?? 0;
            }
        })
        .catch(() => {});
}

function loadAttendeeRoster() {
    const sel = document.getElementById('scannerEventSelect');
    const eventId = sel ? sel.value : 0;
    const tbody = document.getElementById('rosterTableBody');
    if (!tbody) return;

    if (!eventId || eventId === '0') {
        tbody.innerHTML = '<tr><td colspan="5" style="padding: 1.5rem; text-align: center; color: #94A3B8;">No event selected.</td></tr>';
        return;
    }
    tbody.innerHTML = '<tr><td colspan="5" style="padding: 1.5rem; text-align: center; color: #94A3B8;">Fetching live attendee roster...</td></tr>';
    
    fetch(REST_ATTENDEES_URL + '?event_id=' + encodeURIComponent(eventId))
        .then(r => r.json())
        .then(data => {
            rosterData = (data && data.attendees) ? data.attendees : [];
            renderRoster(rosterData);
        })
        .catch(err => {
            tbody.innerHTML = '<tr><td colspan="5" style="padding: 1.5rem; text-align: center; color: #EF4444;">Failed to load roster.</td></tr>';
        });
}

function renderRoster(list) {
    const tbody = document.getElementById('rosterTableBody');
    if (!tbody) return;

    if (!list || list.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="padding: 1.5rem; text-align: center; color: #94A3B8;">No tickets found for this event.</td></tr>';
        return;
    }

    tbody.innerHTML = list.map(item => {
        const isCheckedIn = (item.status === 'already_used' || item.status === 'used' || item.status === 'scanned' || item.scanned_at);
        const statusBadge = isCheckedIn 
            ? `<span style="background: rgba(16, 185, 129, 0.2); color: #10B981; padding: 2px 8px; border-radius: 4px; font-weight: 700; font-size: 0.75rem;">Checked In</span>`
            : `<span style="background: rgba(56, 189, 248, 0.2); color: var(--ep-cyan); padding: 2px 8px; border-radius: 4px; font-weight: 700; font-size: 0.75rem;">Valid</span>`;

        const actionBtn = isCheckedIn
            ? `<span style="font-size: 0.75rem; color: #94A3B8;">✓ ${item.scanned_at ? item.scanned_at.substring(11, 16) : 'Scanned'}</span>`
            : `<button type="button" onclick="manualCheckIn('${escapeHtml(item.token)}')" class="ep-btn ep-btn-sm" style="padding: 0.25rem 0.65rem; font-size: 0.75rem; background: var(--ep-teal); color: var(--ep-navy-deep); font-weight: 700; border-radius: 4px; border: none; cursor: pointer;">Check In</button>`;

        return `
            <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                <td style="padding: 0.6rem 0.75rem;">
                    <div style="font-weight: 700; color: #FFFFFF;">${escapeHtml(item.holder)}</div>
                    <div style="font-size: 0.75rem; color: #94A3B8;">${escapeHtml(item.email || item.phone || '')}</div>
                </td>
                <td style="padding: 0.6rem 0.75rem; color: #CBD5E1; text-transform: capitalize;">${escapeHtml(item.tier)}</td>
                <td style="padding: 0.6rem 0.75rem;"><code style="color: var(--ep-gold); font-size: 0.8rem;">${escapeHtml(item.token)}</code></td>
                <td style="padding: 0.6rem 0.75rem;">${statusBadge}</td>
                <td style="padding: 0.6rem 0.75rem; text-align: right;">${actionBtn}</td>
            </tr>
        `;
    }).join('');
}

function filterRoster() {
    const q = (document.getElementById('rosterSearchInput').value || '').toLowerCase().trim();
    if (!q) {
        renderRoster(rosterData);
        return;
    }
    const filtered = rosterData.filter(item => {
        return (item.holder && item.holder.toLowerCase().includes(q)) ||
               (item.token && item.token.toLowerCase().includes(q)) ||
               (item.email && item.email.toLowerCase().includes(q)) ||
               (item.phone && item.phone.toLowerCase().includes(q)) ||
               (item.tier && item.tier.toLowerCase().includes(q));
    });
    renderRoster(filtered);
}

function manualCheckIn(token) {
    if (!token) return;
    document.getElementById('manualTicketInput').value = token;
    verifyTicketCode(token);
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function onEventChanged() {
    refreshGateStats();
    loadAttendeeRoster();
}

document.addEventListener('DOMContentLoaded', () => {
    refreshGateStats();
    loadAttendeeRoster();
});

function startScannerCamera() {
    const standby   = document.getElementById('cameraStandby');
    const statusTxt = document.getElementById('cameraStatusText');

    if (typeof Html5Qrcode === 'undefined') {
        alert('Scanner engine library not loaded. Please refresh the page.');
        return;
    }

    if (!html5QrCodeScanner) {
        html5QrCodeScanner = new Html5Qrcode("reader");
    }

    const config = {
        fps: 10,
        qrbox: { width: 220, height: 220 },
        aspectRatio: 1.0
    };

    statusTxt.textContent = 'Requesting camera permissions...';

    html5QrCodeScanner.start(
        { facingMode: "environment" },
        config,
        (decodedText, decodedResult) => {
            if (isCooldown) return;
            isCooldown = true;

            // Optical QR code successfully decoded from video stream
            statusTxt.textContent = 'Pass Scanned! Verifying token: ' + decodedText;

            // Normalize token if pass contains full URL
            let token = decodedText.trim();
            if (token.includes('token=')) {
                const match = token.match(/token=([A-Z0-9\-]+)/i);
                if (match) token = match[1];
            } else if (token.includes('code=')) {
                const match = token.match(/code=([A-Z0-9\-]+)/i);
                if (match) token = match[1];
            }

            verifyTicketCode(token);

            // 2.5 second cooldown between scans to prevent double check-ins
            setTimeout(() => {
                isCooldown = false;
                if (isScannerActive) {
                    statusTxt.textContent = 'Camera Active - Optical Viewfinder Scanning...';
                }
            }, 2500);
        },
        (errorMessage) => {
            // Frame parsing callback (no QR in current frame)
        }
    ).then(() => {
        isScannerActive = true;
        standby.style.display = 'none';
        statusTxt.textContent = 'Camera Active - Optical Viewfinder Scanning...';
        document.getElementById('startCameraBtn').style.display = 'none';
        document.getElementById('stopCameraBtn').style.display = 'inline-block';
    }).catch(err => {
        statusTxt.textContent = 'Camera access blocked';
        standby.style.display = 'block';
        standby.innerHTML = `
            <div style="color: #EF4444; font-size: 0.95rem; margin-bottom: 0.75rem;">
                <strong>Camera Permission Denied or Unavailable</strong>
            </div>
            <p style="font-size: 0.85rem; color: #94A3B8; margin-bottom: 1rem;">
                ${err ? err : 'Please grant camera access in your browser settings to scan QR passes.'}
            </p>
            <button type="button" onclick="startScannerCamera()" class="ep-btn ep-btn-primary ep-btn-sm">
                <span>Retry Camera Permission</span>
            </button>
        `;
    });
}

function stopScannerCamera() {
    if (html5QrCodeScanner && isScannerActive) {
        html5QrCodeScanner.stop().then(() => {
            isScannerActive = false;
            document.getElementById('cameraStandby').style.display = 'block';
            document.getElementById('cameraStatusText').textContent = 'Standby Mode';
            document.getElementById('stopCameraBtn').style.display = 'none';
            document.getElementById('startCameraBtn').style.display = 'inline-block';
        }).catch(() => {});
    }
}

function verifyTicketCode(code) {
    code = (code || '').trim().toUpperCase();
    const resultCard = document.getElementById('scanResultCard');
    resultCard.style.display = 'block';

    if (!code) {
        resultCard.style.background = '#FEF2F2';
        resultCard.style.border = '2px solid #EF4444';
        resultCard.style.color = '#991B1B';
        resultCard.innerHTML = `<strong>Error:</strong> Please enter or scan a ticket code.`;
        return;
    }

    const eventId = document.getElementById('scannerEventSelect').value;

    fetch(REST_SCAN_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ code: code, event_id: parseInt(eventId) })
    })
    .then(res => res.json())
    .then(data => {
        if (data.result === 'valid') {
            resultCard.style.background = '#ECFDF5';
            resultCard.style.border = '2px solid #10B981';
            resultCard.style.color = '#065F46';
            resultCard.innerHTML = `
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <span style="font-size: 2.25rem;">✅</span>
                    <div>
                        <h3 style="margin: 0; font-size: 1.25rem; font-weight: 800;">ENTRY APPROVED</h3>
                        <p style="margin: 0.25rem 0 0; font-size: 0.95rem;">
                            Attendee: <strong>${data.holder}</strong><br>
                            Tier: <strong style="text-decoration: underline;">${data.tier}</strong><br>
                            Pass Token: <code>${code}</code> • Validated Just Now
                        </p>
                    </div>
                </div>
            `;
            refreshGateStats();
            loadAttendeeRoster();
        } else if (data.result === 'already_used') {
            resultCard.style.background = '#FFFBEB';
            resultCard.style.border = '2px solid #F59E0B';
            resultCard.style.color = '#92400E';
            resultCard.innerHTML = `
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <span style="font-size: 2.25rem;">⚠️</span>
                    <div>
                        <h3 style="margin: 0; font-size: 1.25rem; font-weight: 800;">ALREADY USED (DUPLICATE)</h3>
                        <p style="margin: 0.25rem 0 0; font-size: 0.9rem;">
                            Attendee: <strong>${data.holder}</strong> (${data.tier})<br>
                            First checked in at: <strong>${data.scanned_at || 'Earlier'}</strong>.<br>
                            <span style="color: #DC2626; font-weight: 700;">DENY ENTRY - DUPLICATE SCAN.</span>
                        </p>
                    </div>
                </div>
            `;
        } else if (data.result === 'wrong_event') {
            resultCard.style.background = '#FFFBEB';
            resultCard.style.border = '2px solid #F59E0B';
            resultCard.style.color = '#92400E';
            resultCard.innerHTML = `
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <span style="font-size: 2.25rem;">⛔</span>
                    <div>
                        <h3 style="margin: 0; font-size: 1.25rem; font-weight: 800;">WRONG EVENT</h3>
                        <p style="margin: 0.25rem 0 0; font-size: 0.9rem;">${data.message}</p>
                    </div>
                </div>
            `;
        } else {
            // Invalid or cancelled
            resultCard.style.background = '#FEF2F2';
            resultCard.style.border = '2px solid #EF4444';
            resultCard.style.color = '#991B1B';
            resultCard.innerHTML = `
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <span style="font-size: 2.25rem;">❌</span>
                    <div>
                        <h3 style="margin: 0; font-size: 1.25rem; font-weight: 800;">${data.title || 'INVALID PASS'}</h3>
                        <p style="margin: 0.25rem 0 0; font-size: 0.9rem;">${data.message || 'Ticket code could not be verified in database.'}</p>
                    </div>
                </div>
            `;
        }
    })
    .catch(err => {
        resultCard.style.background = '#FEF2F2';
        resultCard.style.border = '2px solid #EF4444';
        resultCard.style.color = '#991B1B';
        resultCard.innerHTML = `<strong>Error:</strong> Network or server communication error.`;
    });
}
</script>

<?php get_footer(); ?>
