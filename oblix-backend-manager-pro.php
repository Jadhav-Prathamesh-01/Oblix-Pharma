<?php
/**
 * Plugin Name: Oblix Backend Manager Pro
 * Description: Smart backend management with PM2 auto-restart, GitHub Actions integration, and intelligent health checks
 * Version: 2.0.0
 * Author: Oblix Pharma
 * Text Domain: oblix-backend-manager
 * 
 * This plugin:
 * - Monitors backend health every page load
 * - Auto-triggers GitHub Actions for backend restart
 * - Shows admin dashboard with real-time status
 * - Provides manual restart controls
 * - Logs all activities for debugging
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

final class Oblix_Backend_Manager_Pro {
    const VERSION = '2.0.0';
    const HEALTH_CHECK_HOOK = 'oblix_backend_health_check';
    const AUTO_RESTART_HOOK = 'oblix_backend_auto_restart_cron';
    const GITHUB_WEBHOOK_TIMEOUT = 3;
    const CACHE_TIMEOUT = 300; // 5 minutes

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        // Register hooks
        add_action('plugins_loaded', array($this, 'init_plugin'));
        add_action('wp_footer', array($this, 'background_health_check'));
        add_action(self::HEALTH_CHECK_HOOK, array($this, 'scheduled_health_check'));
        add_action(self::AUTO_RESTART_HOOK, array($this, 'auto_restart_backend'));
        
        // Admin hooks
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_oblix_get_status', array($this, 'ajax_get_status'));
        add_action('wp_ajax_oblix_manual_restart', array($this, 'ajax_manual_restart'));
        add_action('wp_ajax_oblix_trigger_github_action', array($this, 'ajax_trigger_github'));
        
        // Admin styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
        
        // Activation/Deactivation
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init_plugin() {
        // Add cron schedules
        add_filter('cron_schedules', array($this, 'add_cron_schedules'));
        
        // Schedule events
        if (!wp_next_scheduled(self::HEALTH_CHECK_HOOK)) {
            wp_schedule_event(time(), 'oblix_every_5min', self::HEALTH_CHECK_HOOK);
        }
        
        if (!wp_next_scheduled(self::AUTO_RESTART_HOOK)) {
            wp_schedule_event(time(), 'oblix_every_5min', self::AUTO_RESTART_HOOK);
        }
    }

    public function add_cron_schedules($schedules) {
        $schedules['oblix_every_5min'] = array(
            'interval' => 300,
            'display'  => 'Every 5 Minutes (Oblix)'
        );
        return $schedules;
    }

    public function activate() {
        wp_schedule_event(time(), 'oblix_every_5min', self::HEALTH_CHECK_HOOK);
        wp_schedule_event(time(), 'oblix_every_5min', self::AUTO_RESTART_HOOK);
        
        $this->log('Plugin activated - Health check and auto-restart scheduled');
    }

    public function deactivate() {
        wp_clear_scheduled_hook(self::HEALTH_CHECK_HOOK);
        wp_clear_scheduled_hook(self::AUTO_RESTART_HOOK);
    }

    /**
     * Background health check on every page load (non-blocking)
     */
    public function background_health_check() {
        if (is_admin()) {
            return;
        }

        // Use transient to prevent multiple checks
        if (get_transient('oblix_health_check_lock')) {
            return;
        }
        set_transient('oblix_health_check_lock', 1, 60);

        // Do async request
        wp_remote_post(admin_url('admin-ajax.php'), array(
            'blocking'  => false,
            'sslverify' => false,
            'timeout'   => 1,
            'action'    => 'oblix_get_status'
        ));
    }

    /**
     * Scheduled health check (runs via WP-Cron)
     */
    public function scheduled_health_check() {
        $status = $this->check_backend_health();
        
        if (!$status['ok']) {
            $this->log('Backend health check FAILED: ' . $status['message']);
            // Auto-restart if needed
            $this->trigger_backend_restart('Scheduled health check detected backend offline');
        } else {
            $this->log('Backend health check OK - Memory: ' . $status['memory'] . 'MB');
        }
    }

    /**
     * Check backend health
     */
    private function check_backend_health() {
        $cache_key = 'oblix_backend_health';
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }

        $api_url = 'https://api.oblixpharma.com/admin/server-status';
        
        $response = wp_remote_get($api_url, array(
            'timeout'   => 5,
            'sslverify' => false
        ));

        if (is_wp_error($response)) {
            $result = array(
                'ok'      => false,
                'message' => 'Connection error: ' . $response->get_error_message(),
                'memory'  => 0
            );
        } else {
            $code = wp_remote_retrieve_response_code($response);
            $body = json_decode(wp_remote_retrieve_body($response), true);
            
            if ($code === 200 && isset($body['status']) && $body['status'] === 'ok') {
                $result = array(
                    'ok'      => true,
                    'message' => 'Backend is healthy',
                    'memory'  => $body['memory_usage_mb'] ?? 0,
                    'uptime'  => $body['uptime_seconds'] ?? 0,
                    'full'    => $body
                );
            } else {
                $result = array(
                    'ok'      => false,
                    'message' => 'HTTP ' . $code . ' response',
                    'memory'  => 0
                );
            }
        }

        set_transient($cache_key, $result, self::CACHE_TIMEOUT);
        return $result;
    }

    /**
     * Auto-restart backend if needed
     */
    public function auto_restart_backend() {
        $status = $this->check_backend_health();
        
        if (!$status['ok']) {
            $this->log('⚠️ Auto-restart triggered - Backend offline detected');
            $this->trigger_backend_restart('Auto-restart cron job detected backend offline');
        }
    }

    /**
     * MAIN: Trigger GitHub Actions workflow for backend startup
     */
    private function trigger_backend_restart($reason = 'Manual restart requested') {
        $github_token = get_option('oblix_github_token');
        $github_repo = get_option('oblix_github_repo', 'your-username/your-repo');
        
        if (!$github_token) {
            $this->log('❌ GitHub token not configured - Cannot trigger workflow');
            return false;
        }

        if ($github_repo === 'your-username/your-repo') {
            $this->log('❌ GitHub repository not configured - Please set correct repo in settings');
            return false;
        }

        // Use Bearer token format for GitHub API v3
        $args = array(
            'method'      => 'POST',
            'timeout'     => self::GITHUB_WEBHOOK_TIMEOUT,
            'sslverify'   => false,
            'headers'     => array(
                'Authorization' => 'Bearer ' . $github_token,
                'Accept'        => 'application/vnd.github+json',
                'User-Agent'    => 'Oblix-Backend-Manager',
                'Content-Type'  => 'application/json',
                'X-GitHub-Api-Version' => '2022-11-28'
            ),
            'body'        => json_encode(array(
                'ref'    => 'main',
                'inputs' => array(
                    'reason' => $reason . ' at ' . current_time('mysql')
                )
            ))
        );

        // Use repository_dispatch endpoint (more reliable)
        $url = "https://api.github.com/repos/{$github_repo}/dispatches";
        
        $dispatch_body = array(
            'event_type' => 'backend-restart',
            'client_payload' => array(
                'reason' => $reason,
                'timestamp' => current_time('mysql')
            )
        );
        
        $args['body'] = json_encode($dispatch_body);
        
        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            $error_msg = $response->get_error_message();
            $this->log('❌ GitHub dispatch trigger FAILED: ' . $error_msg);
            $this->log('   URL: ' . $url);
            $this->log('   Token: ' . substr($github_token, 0, 15) . '...');
            $this->log('   Repo: ' . $github_repo);
            return false;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($code === 204) {
            $this->log('✅ GitHub Actions dispatch triggered successfully');
            $this->log('   Event: backend-restart');
            $this->log('   Reason: ' . $reason);
            $this->log('   Repository: ' . $github_repo);
            return true;
        } else {
            $this->log('❌ GitHub dispatch trigger failed with HTTP ' . $code);
            $this->log('   Response: ' . $body);
            
            // Parse error message
            $error_data = json_decode($body, true);
            if (isset($error_data['message'])) {
                $this->log('   Error: ' . $error_data['message']);
            }
            
            return false;
        }
    }

    /**
     * AJAX: Get backend status
     */
    public function ajax_get_status() {
        $status = $this->check_backend_health();
        wp_send_json($status);
    }

    /**
     * AJAX: Manual restart backend
     */
    public function ajax_manual_restart() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized', 403);
        }

        $result = $this->trigger_backend_restart('Manual restart from WordPress admin');
        
        if ($result) {
            wp_send_json_success('✅ Restart signal sent to GitHub Actions');
        } else {
            wp_send_json_error('Failed to trigger restart');
        }
    }

    /**
     * AJAX: Trigger GitHub Actions directly from admin
     */
    public function ajax_trigger_github() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized', 403);
        }

        $reason = sanitize_text_field($_POST['reason'] ?? 'Manual trigger from admin');
        $result = $this->trigger_backend_restart($reason);
        
        if ($result) {
            wp_send_json_success('✅ GitHub Actions workflow dispatched');
        } else {
            wp_send_json_error('Failed to dispatch workflow');
        }
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            'Oblix Backend Manager',
            '🔌 Backend Manager',
            'manage_options',
            'oblix-backend-manager',
            array($this, 'render_admin_page'),
            'dashicons-settings',
            80
        );

        add_submenu_page(
            'oblix-backend-manager',
            'Settings',
            'Settings',
            'manage_options',
            'oblix-backend-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Render admin dashboard
     */
    public function render_admin_page() {
        $status = $this->check_backend_health();
        $logs = $this->get_recent_logs(20);
        ?>
        <div class="wrap">
            <h1>🔌 Oblix Backend Manager</h1>
            
            <div style="margin-top: 30px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <!-- Status Box -->
                <div style="background: white; padding: 20px; border: 2px solid #ccc; border-radius: 5px;">
                    <h2>Backend Status</h2>
                    
                    <?php if ($status['ok']): ?>
                        <div style="color: green; font-size: 18px; margin: 10px 0;">
                            <strong>🟢 ONLINE</strong>
                        </div>
                        <p><strong>Memory:</strong> <?php echo $status['memory']; ?> MB / 300 MB</p>
                        <p><strong>Uptime:</strong> <?php echo $status['uptime']; ?> seconds</p>
                    <?php else: ?>
                        <div style="color: red; font-size: 18px; margin: 10px 0;">
                            <strong>🔴 OFFLINE</strong>
                        </div>
                        <p><?php echo $status['message']; ?></p>
                    <?php endif; ?>
                </div>

                <!-- Actions Box -->
                <div style="background: white; padding: 20px; border: 2px solid #ccc; border-radius: 5px;">
                    <h2>Actions</h2>
                    
                    <button class="button button-primary" onclick="oblix_check_status()">
                        ✅ Check Status
                    </button>
                    
                    <button class="button button-danger" onclick="oblix_restart_backend()">
                        🔄 Restart Backend
                    </button>
                    
                    <button class="button" onclick="oblix_trigger_github_action()">
                        🚀 Trigger GitHub Actions
                    </button>
                </div>
            </div>

            <!-- Recent Activity -->
            <div style="margin-top: 30px; background: white; padding: 20px; border: 2px solid #ccc; border-radius: 5px;">
                <h2>Recent Activity Logs</h2>
                <code style="display: block; background: #f0f0f0; padding: 10px; max-height: 300px; overflow-y: auto;">
                    <?php 
                    if (empty($logs)) {
                        echo 'No logs yet';
                    } else {
                        foreach ($logs as $log) {
                            echo htmlspecialchars($log) . "\n";
                        }
                    }
                    ?>
                </code>
            </div>
        </div>

        <script>
        function oblix_check_status() {
            fetch('<?php echo admin_url('admin-ajax.php?action=oblix_get_status'); ?>')
                .then(r => r.json())
                .then(d => {
                    const status = d.ok ? '🟢 ONLINE' : '🔴 OFFLINE';
                    alert(status + '\n\n' + (d.message || 'No additional info'));
                });
        }

        function oblix_restart_backend() {
            if (confirm('Are you sure you want to restart the backend?')) {
                fetch('<?php echo admin_url('admin-ajax.php?action=oblix_manual_restart'); ?>', {
                    method: 'POST'
                })
                .then(r => r.json())
                .then(d => {
                    alert(d.data || 'Restart signal sent!');
                    setTimeout(oblix_check_status, 5000);
                });
            }
        }

        function oblix_trigger_github_action() {
            const reason = prompt('Reason for restart:', 'Manual trigger from WordPress admin');
            if (reason) {
                fetch('<?php echo admin_url('admin-ajax.php?action=oblix_trigger_github'); ?>', {
                    method: 'POST',
                    body: new FormData(Object.assign(document.createElement('form'), {
                        elements: {reason: {value: reason}}
                    }))
                })
                .then(r => r.json())
                .then(d => {
                    alert(d.data || 'GitHub Actions workflow triggered!');
                });
            }
        }
        </script>

        <style>
        .button-danger {
            background-color: #dc3545;
            color: white;
        }
        .button-danger:hover {
            background-color: #c82333;
        }
        </style>
        <?php
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            update_option('oblix_github_token', sanitize_text_field($_POST['github_token'] ?? ''));
            update_option('oblix_github_repo', sanitize_text_field($_POST['github_repo'] ?? 'username/repo'));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }

        $github_token = get_option('oblix_github_token');
        $github_repo = get_option('oblix_github_repo', 'your-username/your-repo');
        ?>
        <div class="wrap">
            <h1>🔌 Backend Manager Settings</h1>

            <form method="post" style="background: white; padding: 20px; border-radius: 5px; max-width: 500px;">
                
                <div style="margin-bottom: 20px;">
                    <label for="github_token"><strong>GitHub Token:</strong></label><br>
                    <input type="password" name="github_token" id="github_token" 
                           value="<?php echo esc_attr($github_token); ?>" 
                           placeholder="ghp_xxxxxxxxxxxxxxxxxxxx"
                           style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ccc; border-radius: 3px;">
                    <p style="font-size: 12px; color: #666;">
                        Create a GitHub Personal Access Token with 'workflow' scope
                    </p>
                </div>

                <div style="margin-bottom: 20px;">
                    <label for="github_repo"><strong>GitHub Repository:</strong></label><br>
                    <input type="text" name="github_repo" id="github_repo" 
                           value="<?php echo esc_attr($github_repo); ?>" 
                           placeholder="username/repo"
                           style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ccc; border-radius: 3px;">
                    <p style="font-size: 12px; color: #666;">
                        Format: owner/repository (e.g., yourname/oblix-backend)
                    </p>
                </div>

                <button type="submit" class="button button-primary">
                    💾 Save Settings
                </button>
            </form>

            <div style="background: #f9f9f9; padding: 15px; margin-top: 20px; border-radius: 5px; max-width: 500px;">
                <h3>How to Set Up GitHub Actions:</h3>
                <ol style="font-size: 14px;">
                    <li>Go to your GitHub repository</li>
                    <li>Create `.github/workflows/backend-auto-start.yml` (we provide this)</li>
                    <li>Create a GitHub Personal Access Token</li>
                    <li>Paste the token above</li>
                    <li>Enter your repository name (username/repo)</li>
                    <li>Save settings</li>
                    <li>Done! Backend will auto-start when needed</li>
                </ol>
            </div>
        </div>
        <?php
    }

    /**
     * Enqueue admin styles
     */
    public function enqueue_admin_styles($hook) {
        if (strpos($hook, 'oblix-backend') === false) {
            return;
        }

        wp_enqueue_style('oblix-admin', plugins_url('admin-style.css', __FILE__));
    }

    /**
     * Logging function
     */
    private function log($message) {
        $logs_file = WP_CONTENT_DIR . '/oblix-backend-logs.txt';
        $timestamp = current_time('mysql');
        $log_entry = "[{$timestamp}] {$message}\n";
        
        file_put_contents($logs_file, $log_entry, FILE_APPEND);
    }

    /**
     * Get recent logs
     */
    private function get_recent_logs($lines = 20) {
        $logs_file = WP_CONTENT_DIR . '/oblix-backend-logs.txt';
        
        if (!file_exists($logs_file)) {
            return array();
        }

        $content = file_get_contents($logs_file);
        $entries = array_reverse(explode("\n", trim($content)));
        return array_slice($entries, 0, $lines);
    }
}

// Initialize plugin
Oblix_Backend_Manager_Pro::get_instance();

// Activation/Deactivation hooks
register_activation_hook(__FILE__, array('Oblix_Backend_Manager_Pro', 'activate'));
register_deactivation_hook(__FILE__, array('Oblix_Backend_Manager_Pro', 'deactivate'));
?>
