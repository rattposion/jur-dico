<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/app/Core/DB.php';

use App\Core\DB;

// Force init which runs schema creation
DB::init($config);

echo "Database schema updated successfully.\n";
