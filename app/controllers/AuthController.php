<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Pure authentication - login, logout, token refresh, and reading the
 * currently logged-in user's info. No product or account-management
 * logic lives here.
 */
class AuthController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->call->database();
        $this->call->library('api');
        $this->call->model('UserModel');
    }

    // POST /api/login
    public function login()
    {
        $this->api->rate_limit();
        $body = $this->api->body();

        $username = $body['username'] ?? '';
        $password = $body['password'] ?? '';

        $user = $this->UserModel->findByUsername($username); $stmt = $this->db->raw("SELECT DATABASE() AS dbname, @@hostname AS mysql_host, @@server_uuid AS server_uuid, COUNT(*) AS user_count FROM users WHERE username = ?", [$username]); $dbg = $stmt->fetch(PDO::FETCH_ASSOC); error_log("LOGIN DEBUG db=" . ($dbg["dbname"] ?? "NULL") . " mysql_host=" . ($dbg["mysql_host"] ?? "NULL") . " uuid=" . ($dbg["server_uuid"] ?? "NULL") . " user_count=" . ($dbg["user_count"] ?? "NULL"));

        if (!$user || !password_verify($password, $user['password'])) {
            $this->api->respond_error('Invalid username or password.', 401);
        }

        if (!(int) $user['is_active']) {
            $this->api->respond_error('This account is inactive.', 403);
        }

        $tokens = $this->api->issue_tokens([
            'id'   => $user['id'],
            'role' => $user['role'],
        ]);

        $this->api->respond([
            'message' => 'Login successful.',
            'user'    => [
                'id'       => $user['id'],
                'username' => $user['username'],
                'role'     => $user['role'],
            ],
            'tokens' => $tokens,
        ]);
    }

    // POST /api/logout
    public function logout()
    {
        $body = $this->api->body();
        $refresh_token = $body['refresh_token'] ?? '';

        if ($refresh_token) {
            $this->api->revoke_refresh_token($refresh_token);
        }

        $this->api->respond(['message' => 'Logged out successfully.']);
    }

    // GET /api/refresh?refresh_token=...
    public function refresh()
    {
        $params = $this->api->get_query_params();
        $refresh_token = $params['refresh_token'] ?? '';

        if (!$refresh_token) {
            $this->api->respond_error('refresh_token query parameter is required.', 422);
        }

        $this->api->refresh_access_token($refresh_token); // responds itself
    }

    // POST /api/profile
    public function profile()
    {
        $payload = $this->api->require_jwt();
        $this->api->respond(['user' => $payload]);
    }
}




