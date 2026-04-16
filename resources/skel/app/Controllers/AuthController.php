<?php

namespace App\Controllers;

use DevinciIT\Blprnt\Core\Controller;
use DevinciIT\Blprnt\Auth\Auth;
use DevinciIT\Blprnt\Support\RedirectIntended;

class AuthController extends Controller
{
    /**
     * Show login page
     */
    public function index()
    {
        return view('auth/login', [
            'title' => 'Login'
        ]);
    }

    /**
     * Handle login request
     */
    public function login()
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? null;
        $auth = Auth::make()
            ->fields(['username', 'password'])
            ->useTokens(true);

        // Use Request object for input
        global $request;
        $input = (isset($request) && is_object($request) && method_exists($request, 'all')) ? $request->all() : $_POST;
        $result = $auth->attempt($input);

        error_log('AuthController::login() result: ' . var_export($result, true));

        if ($result instanceof \DevinciIT\Blprnt\Core\AuthResult && $result->success) {
            // Clear intended URL before redirecting
            \DevinciIT\Blprnt\Support\RedirectIntended::forget();
            $intended = \DevinciIT\Blprnt\Support\RedirectIntended::get();
            if ($intended && $intended !== '/login') {
                redirect($intended);
            }
            // On success, redirect to referer or /admin
            if ($referer && strpos($referer, '/login') === false) {
                redirect($referer);
            } else {
                redirect('/admin');
            }
        }
        // Default: failed login, re-render view
        return view('auth/login', [
            'title' => 'Login',
            'error' => $result->error ?? 'Invalid credentials'
        ]);
    }

    /**
     * Logout user
     */
    public function logout()
    {
        Auth::make()->logout();

        header('Location: /login');
        exit;
    }

    /**
     * API endpoint for login
     */
    public function apiLogin()
    {
        // Accept JSON POST only, fallback to form if needed
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true);
        if (!is_array($input) || empty($input)) {
            // Fallback: parse as form-urlencoded
            parse_str($raw, $input);
        }
        // DEBUG: Log input received by API
        error_log('apiLogin raw input: ' . $raw);
        error_log('apiLogin parsed input: ' . var_export($input, true));
        $auth = Auth::make()
            ->fields(['username', 'password'])
            ->useTokens(true);

        $auth->handle($input);

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }
}