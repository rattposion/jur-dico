# Legal Design System - Documentação Técnica

## Visão Geral
O **Legal Design System** é um conjunto de padrões visuais e componentes reutilizáveis projetados para a Plataforma Jurídica. Ele garante consistência, acessibilidade e uma identidade visual profissional ("Legal Navy").

## 1. Cores e Variáveis
O sistema utiliza variáveis CSS (`:root`) definidas em `public/assets/css/style.css`.

| Variável | Cor | Uso |
|----------|-----|-----|
| `--legal-navy` | `#002366` | Cor primária, cabeçalhos, botões principais |
| `--legal-royal` | `#4169e1` | Acentos, links, foco |
| `--legal-gold` | `#d4af37` | Detalhes premium, destaques |
| `--legal-graphite` | `#222222` | Texto principal |
| `--legal-muted` | `#64748b` | Texto secundário, metadados |
| `--legal-bg` | `#f0f2f5` | Fundo da página |
| `--legal-border` | `#e2e8f0` | Bordas de cards e inputs |

## 2. Estrutura de Layout
As páginas devem seguir a seguinte estrutura HTML básica (gerenciada por `app/Views/layout.php`):

```html
<header class="header">
  <div class="container header-content">...</div>
</header>
<main class="container main">
  <!-- Conteúdo da View -->
</main>
<footer class="container footer">...</footer>
```

### Grid System
Use a classe `.grid` para layouts responsivos automáticos:
```html
<div class="grid">
  <div class="card">...</div>
  <div class="card">...</div>
</div>
```
A classe `.grid` usa `grid-template-columns: repeat(auto-fit, minmax(250px, 1fr))` por padrão.

## 3. Componentes

### Cards
```html
<div class="card">
  <div class="card-header">
    <h2>Título do Card</h2>
  </div>
  <div class="card-body">
    <!-- Conteúdo -->
  </div>
</div>
```

### Botões
*   `.btn`: Base para todos os botões.
*   `.btn-primary`: Ação principal (Navy).
*   `.btn-secondary`: Ações secundárias (Borda/Branco).
*   `.btn-danger`: Ações destrutivas (Vermelho claro).

### Formulários
```html
<div class="form-group">
  <label>Rótulo</label>
  <input type="text" class="form-control">
</div>
```
(Inputs têm estilo padrão global, não exigem classe `.form-control` obrigatoriamente, mas é boa prática).

### Tabelas
```html
<div class="table-responsive">
  <table class="table">
    <thead>...</thead>
    <tbody>...</tbody>
  </table>
</div>
```

## 4. Ícones
O projeto utiliza ícones SVG inline (Feather Icons ou similar) para garantir performance e nitidez. Mantenha o padrão `stroke-width="2"` e `fill="none"`.

## 5. Manutenção e Extensão
Para adicionar novas páginas:
1.  Crie o arquivo em `app/Views/`.
2.  Use `$this->view('nome_da_view')` no controlador.
3.  A view deve conter apenas o conteúdo de `<main>`, pois o layout envolve automaticamente.
4.  Utilize as classes `.card`, `.grid` e `.btn` para manter a consistência.
