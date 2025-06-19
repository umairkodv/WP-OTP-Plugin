<?php
/*
Plugin Name: Email OTP Verification
Description: Replaces Theme My Login's email link verification with email-based OTP verification.
Version: 1.4
Author: Umair Zubair
*/

if(!defined('ABSPATH')) exit;

class WPOtpVerification {

    const TABLE_NAME = 'wp_otp_verification';

    public function __construct() {
        register_activation_hook(__FILE__, [$this, 'create_table']);
        add_action('plugins_loaded', [$this, 'maybe_create_table']);
        add_action('user_register', [$this, 'on_user_register'], 10, 1);

        // Add shortcode for OTP form
        add_shortcode('otp_verification_form', [$this, 'otp_verification_form_shortcode']);

        // Handle OTP submission (AJAX or POST)
        add_action('wp_ajax_nopriv_verify_otp', [$this, 'handle_otp_verification']);
        add_action('wp_ajax_verify_otp', [$this, 'handle_otp_verification']);

        // Handle AJAX resend OTP
        add_action('wp_ajax_nopriv_resend_otp', [$this, 'handle_resend_otp']);
        add_action('wp_ajax_resend_otp', [$this, 'handle_resend_otp']);
    }

    public function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'otp_verification';
    }

    public function create_table() {
        global $wpdb;
        $table_name = $this->get_table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            email VARCHAR(255) NOT NULL,
            otp_code VARCHAR(20) NOT NULL,
            expires_at DATETIME NOT NULL,
            verified TINYINT(1) DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function maybe_create_table() {
        global $wpdb;
        $table = $this->get_table_name();
        if($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            $this->create_table();
        }
    }

    public function on_user_register($user_id) {
        $user = get_userdata($user_id);
        if(!$user) return;

        // Generate OTP
        $otp = rand(100000, 999999);
        $expires = '2099-12-31 23:59:59';

        // Store in otp table
        global $wpdb;
        $wpdb->replace($this->get_table_name(), [
            'user_id'    => $user_id,
            'email'      => $user->user_email,
            'otp_code'   => $otp,
            'expires_at' => $expires,
            'verified'   => 0
        ]);

        // Save OTP and expiry to user meta
        update_user_meta($user_id, 'email_otp', $otp);
        update_user_meta($user_id, 'email_otp_expiry', $expires);

        // Prepare email
        $first_name = get_user_meta($user_id, 'first_name', true);
        if (!$first_name) {
            $nickname = get_user_meta($user_id, 'nickname', true);
            $first_name = $nickname ? $nickname : 'User';
        }

        $subject = 'Your Captain Forest Login Code';
        $body = "Hello {$first_name},\n\n";
        $body .= "Here is your single use login code for Captain Forest: {$otp}\n\n";
        $body .= "This code is valid for the next 10 minutes.\n\n";
        $body .= "If you did not request a login code from Captain Forest, please ignore this email.\n";

        // Send OTP email
        wp_mail(
            $user->user_email,
            $subject,
            $body
        );
    }

    // Helper for url-safe base64 decode
    private function base64_urlsafe_decode($input) {
        $input = strtr($input, '-_,', '+/=');
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $input .= str_repeat('=', $padlen);
        }
        return base64_decode($input);
    }

    // Shortcode for OTP verification form
    public function otp_verification_form_shortcode($atts) {
        $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : get_current_user_id();
        $ref_url = isset($_GET['ref_url']) ? sanitize_text_field($_GET['ref_url']) : '';
        ?>
        <style>
        .otp-verification-wrapper {
            max-width: 430px;
            margin: 40px auto 0 auto;
            padding: 32px 28px 26px 28px;
            background: #fff;
            box-shadow: 0 6px 32px 0 rgba(16,30,54,.14);
            border-radius: 10px;
            font-family: 'Segoe UI', Arial, sans-serif;
        }
        .otp-verification-wrapper h2 {
            text-align:center;
            margin-bottom: 16px;
            color: #30bea7;
            letter-spacing: 1px;
            font-size: 2rem;
            font-weight: 600;
        }
        .otp-label {
            font-weight: 500;
            color: #222;
            margin-bottom: 12px;
            display: block;
            font-size: 1.04em;
        }
        .otp-input {
            width: 100%;
            padding: 15px 15px;
            border-radius: 0; /* straight corners */
            border: 1px solid #d7e1ec;
            font-size: 1.15rem;
            margin-bottom: 18px;
            transition: border-color 0.2s;
            box-sizing: border-box;
        }
        .otp-input:focus {
            border-color: #30bea7;
            outline: none;
        }
        .otp-btn {
            width: 100%;
            background: #17B794 !important;
            color: #fff;
            border: none;
            border-radius: 0 !important; /* straight corners */
            padding: 12px 0;
            font-size: 1.13rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .otp-btn:disabled {
            background: #cccccc !important;
            color: #fff;
            cursor: not-allowed;
        }
        .otp-btn:hover:enabled {
            background: #13a384 !important;
        }
        .otp-resend-btn {
            width: 100%;
            background: none;
            border: none;
            color: #30bea7;
            text-decoration: underline;
            font-size: 1em;
            margin-top: 8px;
            cursor: pointer;
            transition: color 0.2s;
            padding: 0;
        }
        .otp-resend-btn:disabled {
            color: #bbbbbb;
            cursor: not-allowed;
        }
        .otp-resend-btn:hover:enabled {
            color: #22a08c;
        }
        #otp-verification-message {
            margin-top: 18px;
            text-align: center;
            font-size: 1.08em;
            color: #d13e3e;
        }
        #otp-verification-message.success {
            color: #000;
        }
        .otp-timer {
            margin-bottom: 12px;
            text-align: center;
            font-size: 1.08em;
            color: #30bea7;
            font-weight: bold;
            letter-spacing: 1px;
        }
        </style>
        <?php
        // For debug, print the decoded ref_url (1 level deep)
        if ($ref_url) {
            // $decoded = $this->base64_urlsafe_decode($ref_url);
            // ?>
            <!-- <div class="debug-refurl">
            <strong>Debug Ref URL:</strong><br> -->
             <?php
            //     echo esc_html($ref_url);
            //     if ($decoded && preg_match('/^https?:\/\//', $decoded)) {
            //         echo "<br><small>(decoded: " . esc_html($decoded) . ")</small>";
            //     }
            //     ?>
            <!-- </div> -->
            <?php
        }
        ?>
        <div class="otp-verification-wrapper">
            <h2>Verify OTP</h2>
            <div class="otp-timer" id="otp-timer">10:00</div>
            <form id="otp-verification-form" method="post" autocomplete="off">
                <label for="otp_code" class="otp-label">Enter the OTP sent to your email:</label>
                <input type="text" name="otp_code" id="otp_code" maxlength="6" required class="otp-input" placeholder="6-digit code">
                <input type="hidden" name="user_id" value="<?php echo esc_attr($user_id); ?>">
                <input type="hidden" id="ref_url" name="ref_url" value="<?php echo esc_attr($ref_url); ?>">
                <button type="submit" class="otp-btn" id="otp-verify-btn">Verify</button>
                <button type="button" id="resend-otp-btn" class="otp-resend-btn">Resend OTP</button>
            </form>
            <div id="otp-verification-message"></div>
        </div>
        <script>
        // OTP Timer (Fake 10 Minutes)
        (function(){
            var timerDisplay = document.getElementById('otp-timer');
            var otpField = document.getElementById('otp_code');
            var verifyBtn = document.getElementById('otp-verify-btn');
            var resendBtn = document.getElementById('resend-otp-btn');
            var totalSeconds = 600; // 10 minutes

            function pad(n) { return n < 10 ? '0' + n : n; }
            function updateTimer() {
                var minutes = Math.floor(totalSeconds / 60);
                var seconds = totalSeconds % 60;
                timerDisplay.textContent = pad(minutes) + ':' + pad(seconds);
                if(totalSeconds <= 0) {
                    timerDisplay.textContent = "OTP expired. Please resend OTP.";
                    otpField.disabled = true;
                    verifyBtn.disabled = true;
                    resendBtn.disabled = false;
                } else {
                    totalSeconds--;
                    setTimeout(updateTimer, 1000);
                }
            }
            // Start timer
            updateTimer();

            // Initially enable/disable buttons
            otpField.disabled = false;
            verifyBtn.disabled = false;
            resendBtn.disabled = false;
        })();

        jQuery(document).ready(function($){
            $('#otp-verification-form').on('submit', function(e){
                e.preventDefault();
                var form = $(this);
                var ref_url = $('#ref_url').val();

                // PHP's base64 decode logic ported to JS for redirect
                function base64_urlsafe_decode(input) {
                    input = input.replace(/[-_,]/g, function(m) {
                        return {'-':'+','_':'/','=':''}[m];
                    });
                    var pad = input.length % 4;
                    if (pad) input += "====".slice(pad);
                    // atob may throw, so wrap in try/catch
                    try { return atob(input); } catch(e) { return ""; }
                }

                // Check for plan_id=2 in decoded URL
                function hasPlanId2(url) {
                    if (!url) return false;
                    try {
                        var parsed = new URL(url);
                        return parsed.searchParams.get('plan_id') === '2';
                    } catch (e) {
                        // fallback to string search if invalid URL
                        return url.indexOf('plan_id=2') !== -1;
                    }
                }

                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'verify_otp',
                        otp_code: form.find('#otp_code').val(),
                        user_id: form.find('input[name="user_id"]').val()
                    },
                    success: function(response) {
                        var msg = response.data && response.data.message ? response.data.message : 'No message';
                        if(response.success) {
                            $('#otp-verification-message').text(msg).addClass('success');
                            form.hide();
                            setTimeout(function(){
                                if(ref_url) {
                                    var decoded = base64_urlsafe_decode(ref_url);
                                    if(decoded && hasPlanId2(decoded)) {
                                        window.location.href = '/tds-my-account/';
                                    } else if(decoded && /^https?:\/\//.test(decoded)) {
                                        window.location.href = decoded;
                                    } else {
                                        window.location.href = '/tds-my-account/';
                                    }
                                } else {
                                    window.location.href = '/tds-my-account/';
                                }
                            }, 1000);
                        } else {
                            $('#otp-verification-message').removeClass('success').text(msg);
                        }
                    },
                    error: function() {
                        $('#otp-verification-message').removeClass('success').text('An error occurred.');
                    }
                });
            });

            // Resend OTP click handler
            $('#resend-otp-btn').on('click', function(){
                var form = $('#otp-verification-form');
                var btn = $(this);
                btn.prop('disabled', true).text('Resending...');
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'resend_otp',
                        user_id: form.find('input[name="user_id"]').val()
                    },
                    success: function(response) {
                        $('#otp-verification-message').removeClass('success').text(response.data && response.data.message ? response.data.message : 'No message');
                        // Re-enable OTP field and timer
                        $('#otp_code').prop('disabled', false);
                        $('#otp-verify-btn').prop('disabled', false);
                        btn.prop('disabled', false).text('Resend OTP');
                        // Restart timer (reload page or re-initialize timer)
                        window.location.reload();
                    },
                    error: function() {
                        $('#otp-verification-message').removeClass('success').text('An error occurred.');
                        btn.prop('disabled', false).text('Resend OTP');
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    // Handle OTP verification (AJAX)
    public function handle_otp_verification() {
        global $wpdb;
        $user_id = intval($_POST['user_id'] ?? 0);
        $otp_code = sanitize_text_field($_POST['otp_code'] ?? '');

        $debug = [
            'user_id' => $user_id,
            'otp_code' => $otp_code,
            'table_name' => $this->get_table_name(),
        ];

        if(!$user_id || !$otp_code) {
            $debug['error'] = 'Missing data';
            wp_send_json_error([
                'message' => 'Missing data.',
                'debug' => $debug,
            ]);
        }

        // Remove expiry check: do not check expires_at
        $sql = $wpdb->prepare(
            "SELECT * FROM {$this->get_table_name()} WHERE user_id = %d AND otp_code = %s",
            $user_id, $otp_code
        );
        $debug['sql'] = $sql;

        $row = $wpdb->get_row($sql);
        $debug['row'] = $row;

        if($row && !$row->verified) {
            $wpdb->update(
                $this->get_table_name(),
                ['verified' => 1],
                ['id' => $row->id]
            );

            // TDS verification block
            $tds_validate = get_user_meta($user_id, 'tds_validate', true);
            if (!is_array($tds_validate)) {
                $tds_validate = @unserialize($tds_validate); // fallback, just in case
            }
            if (is_array($tds_validate)) {
                $tds_validate['validation_time'] = time();
                update_user_meta($user_id, 'tds_validate', $tds_validate);
            }

            $debug['success'] = true;
            wp_send_json_success([
                'message' => 'Your email has been verified!',
                'debug' => $debug,
            ]);
        } else {
            $debug['fail'] = true;
            wp_send_json_error([
                'message' => 'Invalid OTP.',
                'debug' => $debug,
            ]);
        }
    }

    // AJAX handler for resending OTP
    public function handle_resend_otp() {
        $user_id = intval($_POST['user_id'] ?? 0);
        if(!$user_id) {
            wp_send_json_error(['message' => 'User ID missing.']);
        }
        $user = get_userdata($user_id);
        if(!$user) {
            wp_send_json_error(['message' => 'User not found.']);
        }

        // Generate new OTP and far future expiry
        $otp = rand(100000, 999999);
        $expires = '2099-12-31 23:59:59';

        global $wpdb;
        // Remove previous OTP for this user
        $wpdb->delete($this->get_table_name(), ['user_id' => $user_id]);
        // Insert new OTP
        $wpdb->insert($this->get_table_name(), [
            'user_id'    => $user_id,
            'email'      => $user->user_email,
            'otp_code'   => $otp,
            'expires_at' => $expires,
            'verified'   => 0
        ]);
        update_user_meta($user_id, 'email_otp', $otp);
        update_user_meta($user_id, 'email_otp_expiry', $expires);

        // Prepare email (same as on register)
        $first_name = get_user_meta($user_id, 'first_name', true);
        if (!$first_name) {
            $nickname = get_user_meta($user_id, 'nickname', true);
            $first_name = $nickname ? $nickname : 'User';
        }

        $subject = 'Your New Captain Forest Login Code';
        $body = "Hello {$first_name},\n\n";
        $body .= "Here is your single use login code for Captain Forest: {$otp}\n\n";
        $body .= "This code is valid for the next 10 minutes.\n\n";
        $body .= "If you did not request a login code from Captain Forest, please ignore this email.\n";

        wp_mail(
            $user->user_email,
            $subject,
            $body
        );

        wp_send_json_success(['message' => 'A new OTP has been sent to your email.']);
    }
}

new WPOtpVerification();

// Restriction/redirect logic
add_action('template_redirect', function() {
    $request_uri = $_SERVER['REQUEST_URI'];

    // Restrict verify-otp page for not logged in users
    if (preg_match('#/verify-otp(/|\?|$)#', $request_uri) && !is_user_logged_in()) {
        wp_redirect(site_url('/tds-login-register/'));
        exit;
    }

    if (!is_user_logged_in()) return;

    $user_id = get_current_user_id();

    // Fetch and check tds_validate meta
    $tds_validate = get_user_meta($user_id, 'tds_validate', true);
    if (!is_array($tds_validate)) {
        $tds_validate = @unserialize($tds_validate);
    }
    $is_verified = (is_array($tds_validate) && !empty($tds_validate['validation_time']));

    // List of pages to restrict
    $restricted_slugs = [
        'tds-my-account',
    ];

    // Check if restricted page
    $is_restricted = false;
    foreach ($restricted_slugs as $slug) {
        if (preg_match('#/' . $slug . '(/|\?|$)#', $request_uri)) {
            $is_restricted = true;
            break;
        }
    }

    // If not verified and on restricted page, redirect to OTP
    if ($is_restricted && !$is_verified) {
        $ref_url = isset($_GET['ref_url']) ? sanitize_text_field($_GET['ref_url']) : '';
        $redirect_url = site_url('/verify-otp/?user_id=' . $user_id);
        if ($ref_url) {
            $redirect_url .= '&ref_url=' . urlencode($ref_url);
        }
        wp_redirect($redirect_url);
        exit;
    }

    // If already verified and on OTP page, redirect to account page
    if (
        $is_verified &&
        preg_match('#/verify-otp(/|\?|$)#', $request_uri)
    ) {
        wp_redirect(site_url('/tds-my-account/'));
        exit;
    }
});

// Add this filter OUTSIDE the class, after instantiation
add_filter('tds_email_message_content', function($message, $user_id, $email_type) {
    if ($email_type === 'register_email_body') {
        $otp = get_user_meta($user_id, 'email_otp', true);
        $first_name = get_user_meta($user_id, 'first_name', true);
        if (!$first_name) {
            $nickname = get_user_meta($user_id, 'nickname', true);
            $first_name = $nickname ? $nickname : 'User';
        }
        $content = "Hello {$first_name}\n\n";
        $content .= "Here is your single use login code for Captain Forest: {$otp}\n";
        $content .= "This code is valid for next 10 minutes.\n";
        $content .= "If you did not request a login code from Captain Forest, please ignore this email.\n";
        $message = $content;
    }
    return $message;
}, 10, 3);
