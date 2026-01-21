<?php
use App\Core\View;
?>
<div class="auth-container">
  <div class="auth-card">
    <h2 class="auth-title">Login</h2>
    <?php if (!empty($error)): ?>
      <div class="alert alert-error">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
        Credenciais inválidas
      </div>
    <?php endif; ?>
    <form method="post" action="/login" novalidate>
      <input type="hidden" name="csrf" value="<?= View::e($csrf) ?>">
      
      <div class="form-group">
        <label for="email">Email Profissional</label>
        <input type="email" id="email" name="email" required aria-describedby="emailHelp" placeholder="nome@empresa.com">
        <div class="field-error" data-error-for="email"></div>
      </div>

      <div class="form-group">
        <label for="password">Senha</label>
        <input type="password" id="password" name="password" minlength="6" required aria-describedby="passHelp" placeholder="••••••••">
        <div class="field-error" data-error-for="password"></div>
      </div>

      <button class="btn btn-primary" type="submit" style="width:100%; margin-top:1rem; padding:0.8rem;">
        Entrar na Plataforma
      </button>
      <span class="loader" style="display:none" aria-hidden="true"></span>
    </form>
    
    <div style="text-align:center; margin-top:1.5rem; font-size:0.9rem; color:var(--legal-muted);">
      Ainda não tem acesso? <a href="/register" style="font-weight:600;">Criar conta</a>
    </div>
  </div>
</div>
