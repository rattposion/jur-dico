<?php
use App\Core\View;
use App\Core\Auth;
$user = Auth::user();
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= View::e($config['app_name']) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/style.css">
  <?php if (strpos($_SERVER['REQUEST_URI'], '/chat') === 0): ?>
  <link rel="stylesheet" href="/assets/css/chat.css">
  <script type="module" src="https://cdn.jsdelivr.net/npm/emoji-picker-element@^1/index.js"></script>
  <?php endif; ?>
</head>
<body>
  <header class="header">
    <div class="container">
      <div class="header-content">
        <div style="display: flex; align-items: center; gap: 3rem;">
            <a href="/" class="brand">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path></svg>
                <?= View::e($config['app_name']) ?>
            </a>
            
            <nav class="nav">
                <a href="/" class="<?= ($_SERVER['REQUEST_URI'] === '/' || $_SERVER['REQUEST_URI'] === '/index.php') ? 'active' : '' ?>">Início</a>
                <?php if ($user): ?>
                    <a href="/records" class="<?= strpos($_SERVER['REQUEST_URI'], '/records') === 0 ? 'active' : '' ?>">Registros</a>
                    <!-- <a href="/chat" class="<?= strpos($_SERVER['REQUEST_URI'], '/chat') === 0 ? 'active' : '' ?>">Chat</a> -->
                    <?php if (($user['role'] ?? '') === 'admin'): ?>
                        <a href="/admin" class="<?= strpos($_SERVER['REQUEST_URI'], '/admin') === 0 && strpos($_SERVER['REQUEST_URI'], '/admin/ai') === false ? 'active' : '' ?>">Admin</a>
                        <!-- <a href="/admin/ai/analysis" class="<?= strpos($_SERVER['REQUEST_URI'], '/admin/ai/analysis') === 0 ? 'active' : '' ?>">Análise IA</a> -->
                    <?php endif; ?>
                <?php endif; ?>
            </nav>
        </div>

        <nav class="nav">
            <?php if ($user): ?>
                <div style="display: flex; align-items: center; gap: 0.5rem; color: rgba(255,255,255,0.8); font-size: 0.9rem;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    <span><?= View::e($user['name']) ?></span>
                </div>
                <div class="nav-divider"></div>
                <a href="/settings/api-keys" title="Configurações">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.38a2 2 0 0 0-.73-2.73l-.15-.1a2 2 0 0 1-1-1.72v-.51a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                </a>
                <a href="/logout" style="font-size: 0.9rem;">Sair</a>
            <?php else: ?>
                <a href="/login">Entrar</a>
                <a href="/register" class="btn-nav-action">Começar Agora</a>
            <?php endif; ?>
        </nav>
      </div>
    </div>
  </header>
  <main class="<?= (strpos($_SERVER['REQUEST_URI'], '/chat') === 0 || strpos($_SERVER['REQUEST_URI'], '/records') === 0) ? 'full-width-main' : 'container main' ?>">
    <?= $content ?>
  </main>
  <?php if (strpos($_SERVER['REQUEST_URI'], '/chat') !== 0 && strpos($_SERVER['REQUEST_URI'], '/records') !== 0): ?>
  <footer class="container footer">
    <small>© <?= date('Y') ?></small>
  </footer>
  <?php endif; ?>
  <script src="/assets/js/app.js" type="module"></script>
</body>
</html>
