<?php
use App\Core\View;
use App\Core\Auth;
$activeId = $conversation['id'] ?? 0;
?>
<div class="chat-layout" id="chatRoot" data-csrf="<?= View::e($csrf ?? '') ?>" data-conversation-id="<?= $activeId ?>" data-user-name="<?= View::e(Auth::user()['name']) ?>">
    <!-- Sidebar -->
    <aside class="chat-sidebar">
        <div class="chat-sidebar-header">
            <a href="/chat/new" class="new-chat-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Nova Conversa
            </a>
        </div>
        <div class="chat-history">
            <?php foreach ($conversations as $c): ?>
                <div class="chat-history-row <?= $c['id'] == $activeId ? 'active' : '' ?>">
                    <a href="/chat?id=<?= $c['id'] ?>" class="chat-history-link">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="chat-icon"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                        <span class="chat-title-text"><?= View::e($c['title'] ?? 'Nova Conversa') ?></span>
                    </a>
                    <button class="chat-delete-btn" data-id="<?= $c['id'] ?>" title="Excluir conversa">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="chat-deadlines-section">
            <h4 style="color:#a1a1aa; font-size:0.75rem; text-transform:uppercase; margin:15px 15px 5px; font-weight:600;">Prazos Próximos</h4>
            <div id="deadlinesList" class="deadlines-list" style="padding:0 15px; font-size: 0.85rem; color: #e2e8f0;">
                <div style="font-style:italic; opacity:0.6;">Carregando...</div>
            </div>
        </div>

        <div class="chat-user-profile">
            <div style="width:32px;height:32px;background:#5436da;border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:bold;">
                <?= substr(Auth::user()['name'], 0, 1) ?>
            </div>
            <div style="font-size: 0.9rem; font-weight: 500; color: #ececf1;"><?= View::e(Auth::user()['name']) ?></div>
        </div>
    </aside>

    <!-- Main Chat -->
    <main class="chat-main">
        <header class="chat-main-header">
            <div style="display:flex;align-items:center;gap:10px;">
                <button id="sidebarToggle" class="menu-toggle" style="background:none;border:none;color:white;cursor:pointer;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                </button>
                <div style="font-weight: 500;"><?= View::e($conversation['title'] ?? 'Nova Conversa') ?></div>
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
                <button id="modelSelectBtn" class="model-select-btn" title="Alterar Modelo de IA">
                    <span class="model-icon">⚡</span> 
                    <span class="model-name">Gemini 2.5 Flash</span>
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </button>
                <div class="chat-status">
                    <span class="dot"></span> Online
                </div>
            </div>
        </header>

        <div class="chat-messages" id="messagesList">
            <?php foreach ($messages as $m): ?>
                <?php $isUser = ($m['kind'] === 'user' || $m['kind'] === 'question'); ?>
                <div class="message-row <?= $isUser ? 'user' : 'ai' ?>">
                    <div class="message-wrapper">
                        <div class="message-avatar-container" title="<?= $isUser ? View::e(Auth::user()['name']) : 'Assistente Jurídico' ?>">
                            <div class="message-avatar">
                                <?php if ($isUser): ?>
                                    <div class="avatar-text"><?= substr(Auth::user()['name'], 0, 1) ?></div>
                                <?php else: ?>
                                    <div class="avatar-icon">⚖️</div>
                                <?php endif; ?>
                            </div>
                            <div class="status-indicator online" aria-label="Status: Online"></div>
                        </div>
                        <div class="message-body">
                            <div class="message-info">
                                <span class="message-name"><?= $isUser ? View::e(Auth::user()['name']) : 'Assistente Jurídico' ?></span>
                                <span class="message-time"><?= date('H:i') /* Placeholder, real time would need DB update */ ?></span>
                            </div>
                            <div class="message-content"><?= View::e((string)($m['text'] ?? '')) ?></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="chat-input-container">
            <!-- Module Selector -->
            <div class="chat-modules-bar">
                <button type="button" class="module-btn active" data-module="analysis" title="Análise Jurídica Geral">
                    <span>⚖️</span> Análise
                </button>
                <button type="button" class="module-btn" data-module="legislation" title="Consultor Legislativo">
                    <span>📜</span> Legislação
                </button>
                <button type="button" class="module-btn" data-module="drafting" title="Redator de Peças">
                    <span>📝</span> Redação
                </button>
                <button type="button" class="module-btn" data-module="deadlines" title="Gestor de Prazos">
                    <span>📅</span> Prazos
                </button>
                <button type="button" class="module-btn" data-module="research" title="Pesquisador Doutrinário">
                    <span>📚</span> Doutrina
                </button>
            </div>

            <form id="chatForm" class="chat-input-box">
                <input type="hidden" name="module" id="moduleInput" value="analysis">
                <input type="hidden" name="model" id="modelInput" value="gemini-2.5-flash">
                <input type="hidden" name="provider" id="providerInput" value="gemini">
                <textarea name="text" class="chat-input-textarea" placeholder="Envie uma mensagem..." rows="1" id="messageInput"></textarea>
                <div class="chat-input-actions">
                     <button type="button" class="chat-action-btn" id="emojiBtn" title="Emojis">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M8 14s1.5 2 4 2 4-2 4-2"></path><line x1="9" y1="9" x2="9.01" y2="9"></line><line x1="15" y1="9" x2="15.01" y2="9"></line></svg>
                    </button>
                    <button type="button" class="chat-action-btn" id="attachBtn" title="Anexar">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path></svg>
                    </button>
                    <button type="submit" class="chat-action-btn send" id="sendMsg" disabled>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                    </button>
                </div>
                <input type="file" name="file" id="fileInput" style="display:none">
                <emoji-picker id="emojiPicker" style="display:none"></emoji-picker>
            </form>
        </div>
    </main>
</div>

<!-- Model Selection Modal -->
<div id="modelModal" class="modal-overlay" style="display:none;">
    <div class="modal-content model-modal">
        <div class="modal-header">
            <h3>Selecionar Modelo de IA</h3>
            <button type="button" class="close-modal" id="closeModelModal">&times;</button>
        </div>
        <div class="modal-body">
            <div class="model-cards">
                <!-- Gemini 2.5 Flash -->
                <div class="model-card active" data-model="gemini-2.5-flash" data-provider="gemini" data-icon="⚡" data-name="Gemini 2.5 Flash">
                    <div class="card-header">
                        <span class="card-icon">⚡</span>
                        <div class="card-title">
                            <h4>Gemini 2.5 Flash</h4>
                            <span class="badge badge-speed">Ultrarrápido</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <p>Ideal para tarefas rápidas, análises simples e respostas imediatas. Alta eficiência com menor custo.</p>
                        <ul class="specs-list">
                            <li><strong>Velocidade:</strong> Altíssima</li>
                            <li><strong>Contexto:</strong> 1M tokens</li>
                            <li><strong>Uso:</strong> Perguntas do dia a dia</li>
                        </ul>
                    </div>
                </div>

                <!-- ChatGPT (GPT-4o) -->
                <div class="model-card" data-model="gpt-4o" data-provider="openai" data-icon="🤖" data-name="ChatGPT (GPT-4o)">
                    <div class="card-header">
                        <span class="card-icon">🤖</span>
                        <div class="card-title">
                            <h4>ChatGPT (GPT-4o)</h4>
                            <span class="badge badge-creative">Criativo</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <p>Excelente para redação criativa, nuances linguísticas e conversas longas e humanizadas.</p>
                        <ul class="specs-list">
                            <li><strong>Velocidade:</strong> Alta</li>
                            <li><strong>Contexto:</strong> 128k tokens</li>
                            <li><strong>Uso:</strong> Redação, Brainstorming</li>
                        </ul>
                    </div>
                </div>

                <!-- Gemini Pro (3.0/2.5) -->
                <div class="model-card" data-model="gemini-3-pro-preview" data-provider="gemini" data-icon="🧠" data-name="Gemini 3 Pro">
                    <div class="card-header">
                        <span class="card-icon">🧠</span>
                        <div class="card-title">
                            <h4>Gemini 3 Pro</h4>
                            <span class="badge badge-reasoning">Raciocínio</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <p>O modelo mais avançado para tarefas técnicas complexas, análise profunda de documentos e lógica jurídica.</p>
                        <ul class="specs-list">
                            <li><strong>Velocidade:</strong> Média</li>
                            <li><strong>Contexto:</strong> 2M tokens</li>
                            <li><strong>Uso:</strong> Análise Processual Complexa</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <div class="current-selection">
                Selecionado: <strong id="selectedModelName">Gemini 2.5 Flash</strong>
            </div>
            <button type="button" class="btn-confirm" id="confirmModelBtn">Confirmar Seleção</button>
        </div>
    </div>
</div>

<style>
.model-select-btn {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 6px;
    color: #e2e8f0;
    padding: 6px 12px;
    font-size: 0.85rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
}
.model-select-btn:hover { background: rgba(255,255,255,0.1); }
.model-icon { font-size: 1.1em; }

/* Modal Styles */
.modal-overlay {
    position: fixed; top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.7);
    z-index: 1000;
    display: flex; align-items: center; justify-content: center;
    backdrop-filter: blur(4px);
}
.model-modal {
    background: #1e1e24;
    width: 90%; max-width: 900px;
    border-radius: 12px;
    border: 1px solid #3f3f46;
    box-shadow: 0 20px 50px rgba(0,0,0,0.5);
    display: flex; flex-direction: column;
    max-height: 90vh;
}
.modal-header {
    padding: 20px;
    border-bottom: 1px solid #27272a;
    display: flex; justify-content: space-between; align-items: center;
}
.modal-header h3 { margin: 0; color: #fff; font-size: 1.2rem; }
.close-modal {
    background: none; border: none; color: #a1a1aa; font-size: 1.5rem; cursor: pointer;
}
.modal-body { padding: 20px; overflow-y: auto; }
.model-cards {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;
}
.model-card {
    background: #27272a;
    border: 2px solid transparent;
    border-radius: 10px;
    padding: 20px;
    cursor: pointer;
    transition: all 0.2s;
    position: relative;
}
.model-card:hover { transform: translateY(-2px); background: #3f3f46; }
.model-card.active {
    border-color: #6366f1;
    background: rgba(99, 102, 241, 0.1);
}
.card-header { display: flex; align-items: flex-start; gap: 15px; margin-bottom: 15px; }
.card-icon { font-size: 2rem; background: rgba(255,255,255,0.05); padding: 10px; border-radius: 10px; }
.card-title h4 { margin: 0 0 5px 0; color: #fff; font-size: 1.1rem; }
.badge {
    font-size: 0.7rem; padding: 2px 8px; border-radius: 10px; font-weight: 600; text-transform: uppercase;
}
.badge-speed { background: #059669; color: #d1fae5; }
.badge-creative { background: #7c3aed; color: #ede9fe; }
.badge-reasoning { background: #2563eb; color: #dbeafe; }

.card-body p { color: #a1a1aa; font-size: 0.9rem; line-height: 1.5; margin-bottom: 15px; }
.specs-list { list-style: none; padding: 0; margin: 0; font-size: 0.85rem; color: #d4d4d8; }
.specs-list li { margin-bottom: 5px; }

.modal-footer {
    padding: 20px;
    border-top: 1px solid #27272a;
    display: flex; justify-content: space-between; align-items: center;
}
.current-selection { color: #d4d4d8; }
.btn-confirm {
    background: #6366f1; color: white; border: none; padding: 10px 24px;
    border-radius: 6px; font-weight: 600; cursor: pointer;
    transition: background 0.2s;
}
.btn-confirm:hover { background: #4f46e5; }
</style>
