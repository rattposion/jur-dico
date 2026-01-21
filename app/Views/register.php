<?php
use App\Core\View;
?>
<div class="auth-container">
  <div class="auth-card" style="max-width: 500px;">
    <h2 class="auth-title">Criar Conta</h2>
    <?php if (!empty($error)): ?>
      <div class="alert alert-error">Verifique os dados</div>
    <?php endif; ?>
    
    <div class="stepper" style="display:flex; justify-content:space-between; margin-bottom:2rem; position:relative;">
      <div class="step active" style="flex:1; text-align:center; font-size:0.85rem; color:var(--legal-navy); font-weight:600; border-bottom:2px solid var(--legal-navy); padding-bottom:0.5rem;">Dados Pessoais</div>
      <div class="step" style="flex:1; text-align:center; font-size:0.85rem; color:var(--legal-muted); border-bottom:2px solid var(--legal-border); padding-bottom:0.5rem;">Credenciais</div>
      <div class="step" style="flex:1; text-align:center; font-size:0.85rem; color:var(--legal-muted); border-bottom:2px solid var(--legal-border); padding-bottom:0.5rem;">Confirmação</div>
    </div>

    <form id="registerForm" method="post" action="/register" novalidate>
      <input type="hidden" name="csrf" value="<?= View::e($csrf) ?>">
      
      <div data-step="1">
        <div class="form-group">
          <label for="name">Nome Completo</label>
          <input type="text" id="name" name="name" required placeholder="Seu nome">
          <div class="field-error" data-error-for="name"></div>
        </div>
        <button class="btn btn-primary" type="button" id="nextStep" style="width:100%; margin-top:1rem;">Continuar</button>
      </div>

      <div data-step="2" style="display:none">
        <div class="form-group">
          <label for="email">Email Profissional</label>
          <input type="email" id="email" name="email" required placeholder="nome@empresa.com">
          <div class="field-error" data-error-for="email"></div>
        </div>
        <div class="form-group">
          <label for="password">Senha</label>
          <input type="password" id="password" name="password" minlength="6" required placeholder="Mínimo 6 caracteres">
          <div class="field-error" data-error-for="password"></div>
        </div>
        <div style="display:flex; gap:1rem; margin-top:1.5rem;">
          <button class="btn btn-secondary" type="button" id="prevStep" style="flex:1;">Voltar</button>
          <button class="btn btn-primary" type="button" id="nextStep2" style="flex:1;">Continuar</button>
        </div>
      </div>

      <div data-step="3" style="display:none; text-align:center;">
        <div style="margin:2rem 0;">
          <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--legal-success)" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
          <p style="margin-top:1rem; color:var(--legal-graphite);">Tudo pronto! Revise seus dados e clique abaixo para finalizar.</p>
        </div>
        <button class="btn btn-primary" type="submit" style="width:100%;">Finalizar Cadastro</button>
      </div>
    </form>
    
    <div style="text-align:center; margin-top:1.5rem; font-size:0.9rem; color:var(--legal-muted);">
      Já tem uma conta? <a href="/login" style="font-weight:600;">Fazer login</a>
    </div>
  </div>
</div>
