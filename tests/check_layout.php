<?php

// Simple Layout Integrity Test
// Usage: php tests/check_layout.php

function checkUrl($url, $name) {
    echo "Testing $name ($url)...\n";
    
    // Simulate a request (using curl would be better, but file_get_contents works for local if server is up)
    // Since we might not have the server running on a predictable port for this script context, 
    // we will check the SOURCE FILES for required tokens.
    // This is a static analysis approach.
    
    return true;
}

function checkFileContent($path, $tokens) {
    echo "Checking file: $path\n";
    if (!file_exists($path)) {
        echo "  [FAIL] File not found\n";
        return false;
    }
    
    $content = file_get_contents($path);
    $allFound = true;
    
    foreach ($tokens as $token) {
        if (strpos($content, $token) === false) {
            echo "  [FAIL] Missing token: '$token'\n";
            $allFound = false;
        } else {
            // echo "  [OK] Found '$token'\n";
        }
    }
    
    if ($allFound) {
        echo "  [PASS] All checks passed for this file.\n";
    }
    echo "\n";
    return $allFound;
}

$root = __DIR__ . '/../';

$filesToCheck = [
    'app/Views/layout.php' => [
        'class="header"',
        'class="container header-content"',
        'href="/assets/css/style.css"'
    ],
    'app/Views/home.php' => [
        'class="hero-card"',
        'class="hero-title"',
        'class="btn btn-hero-primary"'
    ],
    'app/Views/settings/api_keys.php' => [
        'class="card"',
        'class="card-header"',
        'class="table"'
    ],
    'public/assets/css/style.css' => [
        '--legal-navy',
        '.hero-card',
        '.header-content'
    ]
];

$totalPass = 0;
$totalFiles = count($filesToCheck);

foreach ($filesToCheck as $relPath => $tokens) {
    if (checkFileContent($root . $relPath, $tokens)) {
        $totalPass++;
    }
}

echo "Summary: $totalPass / $totalFiles files passed design system verification.\n";

if ($totalPass === $totalFiles) {
    exit(0);
} else {
    exit(1);
}
