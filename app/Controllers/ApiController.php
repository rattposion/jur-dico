<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Record;
use App\Models\Category;

class ApiController extends Controller
{
    public function records(): void
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $q = isset($_GET['q']) ? trim((string)$_GET['q']) : null;
        $res = Record::paginate($page, 20, $q);
        header('Content-Type: application/json');
        echo json_encode($res);
    }

    public function categories(): void
    {
        $cats = Category::all();
        header('Content-Type: application/json');
        echo json_encode(['items' => $cats]);
    }
}

