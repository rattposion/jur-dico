<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\CSRF;
use App\Models\Category;

class CategoriesController extends Controller
{
    public function index(): void
    {
        Auth::requireAuth();
        
        $tree = Category::getTree();
        $all = Category::all();
        
        $success = $_SESSION['flash_success'] ?? null;
        $error = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        $this->view('admin/categories/index', [
            'tree' => $tree, 
            'all' => $all,
            'csrf' => CSRF::generate($this->config),
            'success' => $success,
            'error' => $error
        ]);
    }

    public function store(): void
    {
        Auth::requireAuth();
        $token = $_POST['csrf'] ?? null;
        if (!CSRF::verify($this->config, $token)) {
            $_SESSION['flash_error'] = 'Token CSRF inválido';
            header('Location: /admin/categories');
            return;
        }

        $name = trim((string)($_POST['name'] ?? ''));
        $desc = trim((string)($_POST['description'] ?? ''));
        $parent = (int)($_POST['parent_id'] ?? 0);

        if ($name === '') {
            $_SESSION['flash_error'] = 'Nome da categoria é obrigatório';
            header('Location: /admin/categories');
            return;
        }

        try {
            Category::create($name, $desc ?: null, $parent ?: null);
            $_SESSION['flash_success'] = 'Categoria criada com sucesso';
        } catch (\Exception $e) {
            $_SESSION['flash_error'] = 'Erro ao criar categoria: ' . $e->getMessage();
        }

        header('Location: /admin/categories');
    }

    public function update(): void
    {
        Auth::requireAuth();
        $token = $_POST['csrf'] ?? null;
        if (!CSRF::verify($this->config, $token)) {
            $_SESSION['flash_error'] = 'Token CSRF inválido';
            header('Location: /admin/categories');
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        $name = trim((string)($_POST['name'] ?? ''));
        $desc = trim((string)($_POST['description'] ?? ''));
        $parent = (int)($_POST['parent_id'] ?? 0);

        if ($id <= 0 || $name === '') {
            $_SESSION['flash_error'] = 'Dados inválidos';
            header('Location: /admin/categories');
            return;
        }
        
        if ($id === $parent) {
             $_SESSION['flash_error'] = 'Uma categoria não pode ser pai de si mesma';
             header('Location: /admin/categories');
             return;
        }

        try {
            Category::update($id, $name, $desc ?: null, $parent ?: null);
            $_SESSION['flash_success'] = 'Categoria atualizada com sucesso';
        } catch (\Exception $e) {
            $_SESSION['flash_error'] = 'Erro ao atualizar categoria: ' . $e->getMessage();
        }

        header('Location: /admin/categories');
    }

    public function delete(): void
    {
        Auth::requireAuth();
        $token = $_POST['csrf'] ?? null;
        if (!CSRF::verify($this->config, $token)) {
            $_SESSION['flash_error'] = 'Token CSRF inválido';
            header('Location: /admin/categories');
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['flash_error'] = 'ID inválido';
            header('Location: /admin/categories');
            return;
        }

        try {
            Category::delete($id);
            $_SESSION['flash_success'] = 'Categoria removida com sucesso';
        } catch (\Exception $e) {
            $_SESSION['flash_error'] = 'Erro ao remover categoria. Verifique se existem registros associados.';
        }

        header('Location: /admin/categories');
    }
}
