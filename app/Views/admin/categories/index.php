<?php use App\Core\View; ?>

<div class="header-inner" style="padding-top: 2rem; padding-bottom: 2rem;">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 style="font-size: 2rem; margin: 0; color: var(--legal-navy);">Gerenciar Categorias</h1>
            <p class="text-muted" style="margin-top: 0.5rem;">Organize a estrutura de classificação dos processos.</p>
        </div>
        <a href="/admin" class="btn btn-secondary flex items-center gap-2">
             <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
             Voltar ao Dashboard
        </a>
    </div>

    <!-- Flash Messages -->
    <?php if ($success): ?>
        <div class="alert alert-success mb-4" style="background: var(--legal-success-light); color: var(--legal-success); padding: 1rem; border-radius: 6px; border: 1px solid var(--legal-success);">
            <?= View::e($success) ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error mb-4" style="background: var(--legal-danger-light); color: var(--legal-danger); padding: 1rem; border-radius: 6px; border: 1px solid var(--legal-danger);">
            <?= View::e($error) ?>
        </div>
    <?php endif; ?>

    <div class="grid-layout" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; align-items: start;">
        
        <!-- List / Tree -->
        <div class="card" style="grid-column: span 2; background: white; border-radius: 8px; border: 1px solid var(--legal-border); box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div class="card-header-simple" style="padding: 1.5rem; border-bottom: 1px solid var(--legal-border);">
                <h2 style="margin: 0; font-size: 1.25rem;">Categorias Existentes</h2>
            </div>
            <div class="card-body" style="padding: 1.5rem;">
                <?php if (empty($tree)): ?>
                    <div class="empty-state text-center py-4" style="text-align: center; padding: 2rem; color: #666;">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="margin-bottom: 1rem; opacity: 0.5;"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                        <p>Nenhuma categoria cadastrada.</p>
                    </div>
                <?php else: ?>
                    <ul class="category-tree">
                        <?php 
                        function renderTree($nodes, $csrf) {
                            foreach ($nodes as $node) {
                                echo '<li class="category-item">';
                                echo '<div class="category-content" style="display: flex; justify-content: space-between; align-items: center;">';
                                echo '<span>' . View::e($node['name']) . '</span>';
                                echo '<div class="actions">';
                                
                                // Edit Button
                                echo '<button onclick="editCategory('.$node['id'].', \''.View::e($node['name']).'\', \''.View::e($node['description']??'').'\', '.($node['parent_id']??0).')" class="btn btn-sm btn-outline-primary" style="padding: 0.25rem 0.5rem; margin-right: 0.5rem; font-size: 0.8rem; background: none; border: none; cursor: pointer;" title="Editar">✏️</button>';
                                
                                // Delete Form
                                echo '<form action="/admin/categories/delete" method="POST" style="display:inline;" onsubmit="return confirm(\'Tem certeza que deseja excluir a categoria: '.View::e($node['name']).'?\');">';
                                echo '<input type="hidden" name="csrf" value="'.$csrf.'">';
                                echo '<input type="hidden" name="id" value="'.$node['id'].'">';
                                echo '<button type="submit" class="btn btn-sm btn-outline-danger" style="padding: 0.25rem 0.5rem; font-size: 0.8rem; background: none; border: none; cursor: pointer;" title="Excluir">🗑️</button>';
                                echo '</form>';
                                
                                echo '</div>';
                                echo '</div>';
                                if (!empty($node['children'])) {
                                    echo '<ul class="category-children">';
                                    renderTree($node['children'], $csrf);
                                    echo '</ul>';
                                }
                                echo '</li>';
                            }
                        }
                        renderTree($tree, $csrf);
                        ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <!-- Form (Create/Edit) -->
        <div class="card sticky-panel" style="position: sticky; top: 1rem; background: white; border-radius: 8px; border: 1px solid var(--legal-border); box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div class="card-header-simple" style="padding: 1.5rem; border-bottom: 1px solid var(--legal-border);">
                <h2 id="formTitle" style="margin: 0; font-size: 1.25rem;">Nova Categoria</h2>
            </div>
            <div class="card-body" style="padding: 1.5rem;">
                <form id="categoryForm" action="/admin/categories/store" method="POST">
                    <input type="hidden" name="csrf" value="<?= View::e($csrf) ?>">
                    <input type="hidden" name="id" id="catId" value="">
                    
                    <div class="form-group mb-3" style="margin-bottom: 1rem;">
                        <label for="name" class="form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Nome</label>
                        <input type="text" id="name" name="name" class="form-control" required placeholder="Ex: Penal" style="width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px;">
                    </div>

                    <div class="form-group mb-3" style="margin-bottom: 1rem;">
                        <label for="description" class="form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Descrição (Opcional)</label>
                        <textarea id="description" name="description" class="form-control" rows="3" style="width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px;"></textarea>
                    </div>

                    <div class="form-group mb-3" style="margin-bottom: 1.5rem;">
                        <label for="parent_id" class="form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Categoria Pai</label>
                        <select id="parent_id" name="parent_id" class="form-control" style="width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px;">
                            <option value="0">Nenhuma (Raiz)</option>
                            <?php foreach ($all as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= View::e($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="d-flex justify-end gap-2" style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                        <button type="button" id="btnCancel" class="btn btn-secondary" style="display:none; padding: 0.5rem 1rem; border: 1px solid #ccc; background: white; border-radius: 4px;" onclick="resetForm()">Cancelar</button>
                        <button type="submit" id="btnSubmit" class="btn btn-primary" style="padding: 0.5rem 1rem; background: var(--legal-navy); color: white; border: none; border-radius: 4px;">Criar Categoria</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<script>
function editCategory(id, name, desc, parentId) {
    document.getElementById('formTitle').innerText = 'Editar Categoria';
    document.getElementById('categoryForm').action = '/admin/categories/update';
    document.getElementById('catId').value = id;
    document.getElementById('name').value = name;
    document.getElementById('description').value = desc;
    document.getElementById('parent_id').value = parentId;
    document.getElementById('btnSubmit').innerText = 'Salvar Alterações';
    document.getElementById('btnCancel').style.display = 'block';
    
    // Disable selecting itself as parent
    let options = document.getElementById('parent_id').options;
    for (let i = 0; i < options.length; i++) {
        if (options[i].value == id) {
            options[i].disabled = true;
        } else {
            options[i].disabled = false;
        }
    }
    
    // Scroll to form on mobile
    if (window.innerWidth < 768) {
        document.getElementById('categoryForm').scrollIntoView({behavior: 'smooth'});
    }
}

function resetForm() {
    document.getElementById('formTitle').innerText = 'Nova Categoria';
    document.getElementById('categoryForm').action = '/admin/categories/store';
    document.getElementById('catId').value = '';
    document.getElementById('name').value = '';
    document.getElementById('description').value = '';
    document.getElementById('parent_id').value = 0;
    document.getElementById('btnSubmit').innerText = 'Criar Categoria';
    document.getElementById('btnCancel').style.display = 'none';

    let options = document.getElementById('parent_id').options;
    for (let i = 0; i < options.length; i++) {
        options[i].disabled = false;
    }
}
</script>

<style>
/* Responsive Grid */
@media (min-width: 768px) {
    .grid-layout {
        grid-template-columns: 2fr 1fr !important;
    }
}

.category-tree, .category-children {
    list-style: none;
    padding-left: 0;
    margin: 0;
}
.category-children {
    padding-left: 1.5rem;
    border-left: 1px solid var(--legal-border, #ccc);
    margin-left: 0.75rem;
    margin-top: 0.5rem;
}
.category-item {
    margin-bottom: 0.5rem;
}
.category-content {
    background: #f8f9fa;
    padding: 0.75rem 1rem;
    border-radius: 6px;
    border: 1px solid transparent;
    transition: all 0.2s;
}
.category-content:hover {
    border-color: var(--legal-gold, #c5a065);
    background: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}
.category-content .actions {
    opacity: 0.4;
    transition: opacity 0.2s;
}
.category-content:hover .actions {
    opacity: 1;
}
</style>