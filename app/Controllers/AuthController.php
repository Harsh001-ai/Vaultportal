<?php
namespace App\Controllers;

use App\Database;
use App\Request;
use App\Response;
use App\Session;
use App\Services\AuthService;
use Exception;

class AuthController {
    public function showLogin(Request $request): void {
        if (Session::get('user')) {
            $user = Session::get('user');
            Response::redirect($user['role'] === 'admin' ? '/admin/dashboard' : '/dashboard');
        }

        Response::view('auth/login', [
            'title' => 'Sign In — VaultPortal Enterprise',
            'error' => Session::flash('error'),
            'success' => Session::flash('success')
        ], 'auth');
    }

    public function login(Request $request): void {
        $email = $request->input('email');
        $password = $request->input('password');

        try {
            if (empty($email) || empty($password)) {
                throw new Exception("Please enter both email and password.");
            }

            $user = AuthService::authenticate($email, $password);

            if ($request->isAjax()) {
                Response::success([
                    'user' => $user,
                    'redirect' => $user['role'] === 'admin' ? '/admin/dashboard' : '/dashboard'
                ], 'Login successful');
            }

            $redirectUrl = Session::get('redirect_url');
            Session::remove('redirect_url');

            if ($redirectUrl) {
                Response::redirect($redirectUrl);
            }

            Response::redirect($user['role'] === 'admin' ? '/admin/dashboard' : '/dashboard');
        } catch (Exception $e) {
            if ($request->isAjax()) {
                Response::error($e->getMessage(), 401);
            }
            Session::flash('error', $e->getMessage());
            Response::redirect('/login');
        }
    }

    public function showRegister(Request $request): void {
        if (Session::get('user')) {
            Response::redirect('/dashboard');
        }

        Response::view('auth/register', [
            'title' => 'Register Account — VaultPortal',
            'error' => Session::flash('error'),
            'success' => Session::flash('success')
        ], 'auth');
    }

    public function register(Request $request): void {
        $name = $request->input('name');
        $email = $request->input('email');
        $password = $request->input('password');
        $confirmPassword = $request->input('password_confirmation');

        try {
            if ($password !== $confirmPassword) {
                throw new Exception("Passwords do not match.");
            }

            $user = AuthService::register($name, $email, $password, 'user');
            Session::set('user', $user);
            Session::flash('success', 'Account created successfully! Welcome to VaultPortal.');

            if ($request->isAjax()) {
                Response::success([
                    'user' => $user,
                    'redirect' => '/dashboard'
                ], 'Registration successful', 201);
            }

            Response::redirect('/dashboard');
        } catch (Exception $e) {
            if ($request->isAjax()) {
                Response::error($e->getMessage(), 400);
            }
            Session::flash('error', $e->getMessage());
            Response::redirect('/register');
        }
    }

    public function logout(Request $request): void {
        AuthService::logout();
        Response::redirect('/login');
    }

    public function showSetup(Request $request): void {
        $adminCount = Database::fetch("SELECT COUNT(*) as cnt FROM users WHERE role = 'admin'");
        if (($adminCount['cnt'] ?? 0) > 0) {
            Response::redirect('/login');
        }

        Response::view('auth/setup', [
            'title' => 'Initial Administrator Setup — VaultPortal',
            'error' => Session::flash('error')
        ], 'auth');
    }

    public function setupAdmin(Request $request): void {
        $adminCount = Database::fetch("SELECT COUNT(*) as cnt FROM users WHERE role = 'admin'");
        if (($adminCount['cnt'] ?? 0) > 0) {
            Response::error('Initial setup is already locked.', 403);
        }

        $name = $request->input('name');
        $email = $request->input('email');
        $password = $request->input('password');

        try {
            $user = AuthService::register($name, $email, $password, 'admin');
            Session::set('user', $user);
            Session::flash('success', 'Administrator account provisioned successfully.');
            Response::redirect('/admin/dashboard');
        } catch (Exception $e) {
            Session::flash('error', $e->getMessage());
            Response::redirect('/setup');
        }
    }
}
