<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\CSRF;

class HomeController extends Controller
{
    public function index(): void
    {
        $this->view('home', ['user' => Auth::user(), 'csrf' => CSRF::generate($this->config)]);
    }
}
