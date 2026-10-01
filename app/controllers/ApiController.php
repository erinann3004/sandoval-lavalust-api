<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class ApiController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->api = $this->call->library('api');
        if ($_SERVER['REQUEST_METHOD'] !== 'OPTIONS') {
            $this->db = $this->call->database();
        }
    }

    public function preflight() {}

    public function register()
    {
        $input = $this->request_body();
        $firstname = trim((string) ($input['firstname'] ?? ''));
        $lastname = trim((string) ($input['lastname'] ?? ''));
        $username = trim((string) ($input['username'] ?? ''));
        $email = strtolower(trim((string) ($input['email'] ?? '')));
        $password = (string) ($input['password'] ?? '');

        if ($firstname === '' || strlen($firstname) > 100 || $lastname === '' || strlen($lastname) > 100 || $username === '' || strlen($username) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255 || strlen($password) < 8) {
            $this->api->respond_error('Provide first name, last name, username, valid email, and a password of at least 8 characters.', 422);
        }

        $existing = $this->db->raw('SELECT id FROM accounts WHERE email = ? OR username = ? LIMIT 1', [$email, $username])->fetch(PDO::FETCH_ASSOC);
        if ($existing) {
            $this->api->respond_error('That email or username is already registered.', 409);
        }

        $this->db->table('accounts')->insert([
            'firstname' => $firstname,
            'lastname' => $lastname,
            'username' => $username,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'account',
            'is_active' => 1,
        ]);
        $this->api->respond(['message' => 'Account created. Please sign in.'], 201);
    }

    public function login()
    {
        $input = $this->request_body();
        $email = strtolower(trim((string) ($input['email'] ?? '')));
        $password = (string) ($input['password'] ?? '');
        $user = $this->db->raw('SELECT id, firstname, lastname, username, email, password, role, is_active FROM accounts WHERE email = ? LIMIT 1', [$email])->fetch(PDO::FETCH_ASSOC);

        if (!$user || !(int) $user['is_active'] || !password_verify($password, $user['password'])) {
            $this->api->respond_error('Invalid email or password.', 401);
        }

        unset($user['password']);
        unset($user['is_active']);
        $user['id'] = (int) $user['id'];
        $user['name'] = $user['firstname'] . ' ' . $user['lastname'];
        $this->api->respond(['user' => $user, 'tokens' => $this->api->issue_tokens($user)]);
    }

    public function refresh()
    {
        $input = $this->request_body();
        $token = (string) ($input['refresh_token'] ?? '');
        if ($token === '') {
            $this->api->respond_error('Refresh token is required.', 422);
        }
        $this->api->refresh_access_token($token);
    }

    public function logout()
    {
        $input = $this->request_body();
        $token = (string) ($input['refresh_token'] ?? '');
        if ($token !== '') {
            $this->api->revoke_refresh_token($token);
        }
        $this->api->respond(['message' => 'Logged out.']);
    }

    public function products()
    {
        $this->api->require_jwt();
        $products = $this->db->raw('SELECT id, product_name, description, price, quantity, created_at FROM products ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
        $this->api->respond(['products' => $products]);
    }

    public function create_product()
    {
        $this->api->require_jwt();
        $data = $this->product_data($this->request_body());
        $this->db->table('products')->insert($data);
        $id = $this->db->last_id();
        $product = $this->db->raw('SELECT id, product_name, description, price, quantity, created_at FROM products WHERE id = ?', [$id])->fetch(PDO::FETCH_ASSOC);
        $this->api->respond(['product' => $product], 201);
    }

    public function update_product($id)
    {
        $this->api->require_jwt();
        $id = (int) $id;
        $existing = $this->db->raw('SELECT id, product_name, description, price, quantity FROM products WHERE id = ? LIMIT 1', [$id])->fetch(PDO::FETCH_ASSOC);
        if (!$existing) {
            $this->api->respond_error('Product not found.', 404);
        }

        $data = $this->product_data(array_merge($existing, $this->request_body()));
        $this->db->raw('UPDATE products SET product_name = ?, description = ?, price = ?, quantity = ? WHERE id = ?', [...array_values($data), $id]);
        $product = $this->db->raw('SELECT id, product_name, description, price, quantity, created_at FROM products WHERE id = ?', [$id])->fetch(PDO::FETCH_ASSOC);
        $this->api->respond(['product' => $product]);
    }

    public function delete_product($id)
    {
        $this->api->require_jwt();
        $id = (int) $id;
        $result = $this->db->raw('DELETE FROM products WHERE id = ?', [$id]);
        if ($result->rowCount() === 0) {
            $this->api->respond_error('Product not found.', 404);
        }
        $this->api->respond(['message' => 'Product deleted.']);
    }

    private function product_data(array $input)
    {
        $name = trim((string) ($input['product_name'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        $price = $input['price'] ?? null;
        $quantity = $input['quantity'] ?? null;

        if ($name === '' || strlen($name) > 100 || $description === '' || !is_numeric($price) || (float) $price < 0 || !is_numeric($quantity) || filter_var($quantity, FILTER_VALIDATE_INT) === false || (int) $quantity < 0) {
            $this->api->respond_error('Provide a product name (max 100 characters), description, non-negative price, and whole-number quantity.', 422);
        }

        return ['product_name' => $name, 'description' => $description, 'price' => number_format((float) $price, 2, '.', ''), 'quantity' => (int) $quantity];
    }

    private function request_body(): array
    {
        $raw = file_get_contents('php://input');
        if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
            $input = json_decode($raw, true);
            return is_array($input) ? $input : [];
        }

        parse_str($raw, $input);
        return is_array($input) ? $input : $_POST;
    }
}