<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\CSRF;
use App\Core\Auth;

class AuthController extends Controller
{
    public function showLogin(): void
    {
        $this->view('login', ['csrf' => CSRF::generate($this->config)]);
    }

    public function login(): void
    {
        $token = $_POST['csrf'] ?? null;
        if (!CSRF::verify($this->config, $token)) {
            http_response_code(400);
            echo 'csrf';
            return;
        }
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL) ?: '';
        $password = (string)($_POST['password'] ?? '');
        if (!$email || strlen($password) < 6) {
            $this->view('login', ['error' => 'credenciais', 'csrf' => CSRF::generate($this->config)]);
            return;
        }
        if (!Auth::attempt($email, $password)) {
            $this->view('login', ['error' => 'falha', 'csrf' => CSRF::generate($this->config)]);
            return;
        }
        $this->redirect('/');
    }

    public function showRegister(): void
    {
        $this->view('register', ['csrf' => CSRF::generate($this->config)]);
    }

    public function register(): void
    {
        $token = $_POST['csrf'] ?? null;
        if (!CSRF::verify($this->config, $token)) {
            http_response_code(400);
            echo 'csrf';
            return;
        }
        $name = trim((string)($_POST['name'] ?? ''));
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL) ?: '';
        $password = (string)($_POST['password'] ?? '');
        if (!$name || !$email || strlen($password) < 6) {
            $this->view('register', ['error' => 'dados', 'csrf' => CSRF::generate($this->config)]);
            return;
        }
        if (!Auth::register($name, $email, $password)) {
            $this->view('register', ['error' => 'existente', 'csrf' => CSRF::generate($this->config)]);
            return;
        }
        $this->redirect('/login');
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('/');
    }
}
