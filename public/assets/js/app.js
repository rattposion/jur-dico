const forms = document.querySelectorAll('form[novalidate]');
forms.forEach(f=>{
  f.addEventListener('submit',e=>{
    if(!f.checkValidity()){
      e.preventDefault();
      Array.from(f.elements).forEach(el=>{if(el.willValidate){el.reportValidity();}});
    }
    const loader = f.querySelector('.loader');
    if(loader){ loader.style.display='inline-block'; }
  });
  Array.from(f.elements).forEach(el=>{
    if(el.tagName==='INPUT'){
      el.addEventListener('input',()=>{
        const errEl = f.querySelector(`.field-error[data-error-for="${el.name}"]`);
        if(!errEl) return;
        if(el.validationMessage){ errEl.textContent = el.validationMessage; }
        else { errEl.textContent = ''; }
      });
    }
  });
});

const systemStatus = document.querySelector('#systemStatus');
if(systemStatus){
  (async ()=>{
    try {
      const res = await fetch('/api/status');
      if(!res.ok) throw new Error('Falha');
      const data = await res.json();
      
      const setBadge = (id, ok, label)=>{
        const el = document.querySelector(`#${id} .badge`);
        if(el){
          el.className = ok ? 'badge badge-success' : 'badge badge-error';
          el.textContent = label + (ok ? ': Online' : ': Offline');
        }
      };

      setBadge('status-db', data.db, 'Banco de Dados');
      setBadge('status-ai', data.ai, 'IA (Gemini/OpenAI)');
      setBadge('status-datajud', data.datajud, 'DataJud API');
      
    } catch(e) {
      console.error(e);
      document.querySelectorAll('#systemStatus .badge').forEach(b => {
         b.className = 'badge badge-error';
         b.textContent += ' (Erro)';
      });
    }
  })();
}

const root = document.querySelector('#dashboardRoot');
if(root){
  const qs = (name)=>{
    const el = document.querySelector(`#dashFilters [name="${name}"]`);
    return el ? el.value.trim() : '';
  };
  const applyBtn = document.querySelector('#applyFilters');
  const exportCsv = document.querySelector('#exportCsv');
  const exportPdf = document.querySelector('#exportPdf');
  const indTotal = document.querySelector('#indTotal');
  const indCat = document.querySelector('#indCat');
  const indPct = document.querySelector('#indPct');
  const indPend = document.querySelector('#indPend');
  const pie = document.querySelector('#pieChart');
  const line = document.querySelector('#lineChart');
  const colorFor = (name)=>{
    let h = 0; for(let i=0;i<name.length;i++){h=(h*31+name.charCodeAt(i))>>>0;} const hue = h%360; return `hsl(${hue} 70% 45%)`;
  };
  const fetchData = async ()=>{
    const params = new URLSearchParams();
    ['start','end','type','status'].forEach(k=>{const v=qs(k); if(v) params.set(k,v);});
    const r = await fetch(`/admin/dashboard/data?${params.toString()}`);
    return await r.json();
  };
  const drawPie = (cats)=>{
    while(pie.firstChild) pie.removeChild(pie.firstChild);
    const total = cats.reduce((a,b)=>a+Number(b.count||0),0);
    let acc = 0;
    cats.forEach(c=>{
      const v = Number(c.count||0);
      const p = total? v/total : 0;
      const x1 = Math.cos(2*Math.PI*acc);
      const y1 = Math.sin(2*Math.PI*acc);
      acc += p;
      const x2 = Math.cos(2*Math.PI*acc);
      const y2 = Math.sin(2*Math.PI*acc);
      const large = p>0.5?1:0;
      const path = document.createElementNS('http://www.w3.org/2000/svg','path');
      path.setAttribute('d',`M 100 100 L ${100+100*x1} ${100+100*y1} A 100 100 0 ${large} 1 ${100+100*x2} ${100+100*y2} Z`);
      path.setAttribute('fill',colorFor(c.category||''));
      path.setAttribute('tabindex','0');
      path.setAttribute('aria-label',`${c.category||'-'}: ${v}`);
      path.title = `${c.category||'-'}: ${v}`;
      pie.appendChild(path);
    });
  };
  const drawLine = (points)=>{
    while(line.firstChild) line.removeChild(line.firstChild);
    const w=400,h=200,pad=30;
    const xs = points.map(p=>p.ym);
    const ys = points.map(p=>Number(p.count||0));
    const maxY = Math.max(1,...ys);
    const poly = document.createElementNS('http://www.w3.org/2000/svg','polyline');
    const pts = ys.map((y,i)=>{
      const x = pad + (i/(ys.length-1||1))*(w-2*pad);
      const yy = h-pad - (y/maxY)*(h-2*pad);
      return `${x},${yy}`;
    }).join(' ');
    poly.setAttribute('points',pts);
    poly.setAttribute('fill','none');
    poly.setAttribute('stroke','#22c55e');
    poly.setAttribute('stroke-width','2');
    line.appendChild(poly);
    points.forEach((p,i)=>{
      const y=ys[i];
      const x = pad + (i/(ys.length-1||1))*(w-2*pad);
      const yy = h-pad - (y/maxY)*(h-2*pad);
      const c = document.createElementNS('http://www.w3.org/2000/svg','circle');
      c.setAttribute('cx',x);
      c.setAttribute('cy',yy);
      c.setAttribute('r','3');
      c.setAttribute('fill','#22c55e');
      c.title = `${p.ym}: ${y}`;
      c.setAttribute('tabindex','0');
      c.setAttribute('aria-label',`${p.ym}: ${y}`);
      line.appendChild(c);
    });
  };
  const update = async ()=>{
    const data = await fetchData();
    indTotal.textContent = data.stats.total;
    indCat.textContent = data.stats.categorized;
    indPct.textContent = `${data.stats.percentage}%`;
    indPend.textContent = data.stats.pending;
    drawPie(data.categories);
    drawLine(data.timeline);
    const params = new URLSearchParams();
    ['start','end','type','status'].forEach(k=>{const v=qs(k); if(v) params.set(k,v);});
    exportCsv.href = `/admin/export/csv?${params.toString()}`;
    exportPdf.href = `/admin/export/pdf?${params.toString()}`;
  };
  applyBtn.addEventListener('click',update);
  update();
  setInterval(update,5*60*1000);
}

const recordsRoot = document.querySelector('#recordsRoot');
if(recordsRoot){
  const csrf = recordsRoot.getAttribute('data-csrf')||'';
  const isAdmin = recordsRoot.getAttribute('data-admin')==='1';
  if(isAdmin){
    recordsRoot.querySelectorAll('tbody tr').forEach(row=>{
      const id = row.getAttribute('data-id');
      const msg = row.querySelector('.msg');
      const sel = row.querySelector('select[name="category"]');
      const del = row.querySelector('.btn-del');
      if(sel){
        sel.addEventListener('change',async ()=>{
          const cid = sel.value;
          msg.textContent = 'Salvando...';
          sel.disabled = true;
          try{
            const fd = new FormData();
            fd.append('csrf',csrf);
            fd.append('record_id',id);
            fd.append('category_id',cid);
            const r = await fetch('/records/update-category',{method:'POST',body:fd});
            const j = await r.json();
            if(j && j.ok){msg.textContent = 'Salvo';} else {msg.textContent = 'Erro';}
          }catch(e){msg.textContent = 'Erro';}
          sel.disabled = false;
          setTimeout(()=>{msg.textContent='';},2000);
        });
      }
      if(del){
        del.addEventListener('click',async ()=>{
          if(!confirm('Confirmar exclusão?')) return;
          del.disabled = true; msg.textContent = 'Excluindo...';
          try{
            const fd = new FormData();
            fd.append('csrf',csrf);
            fd.append('record_id',id);
            const r = await fetch('/records/delete',{method:'POST',body:fd});
            const j = await r.json();
            if(j && j.ok){ row.remove(); }
            else { msg.textContent = 'Erro'; del.disabled=false; return; }
          }catch(e){ msg.textContent='Erro'; del.disabled=false; return; }
        });
      }
    });
  }
}

const noticeRoot = document.querySelector('#noticeRoot');
if(noticeRoot){
  const list = noticeRoot.querySelector('.notice-list');
  const csrf = noticeRoot.getAttribute('data-csrf')||'';
  const fetchNotices = async ()=>{
    try{
      const r = await fetch('/notifications/active');
      const j = await r.json();
      renderNotices(j.items||[]);
    }catch(e){ renderNotices([]); }
  };
  const renderNotices = (items)=>{
    while(list.firstChild) list.removeChild(list.firstChild);
    if(!items.length){ const p=document.createElement('p'); p.textContent='Nenhuma notificação no momento.'; list.appendChild(p); return; }
    items.forEach(n=>{
      const card = document.createElement('div'); card.className='notice';
      const left = document.createElement('div');
      const h = document.createElement('h4'); h.textContent = n.title;
      const p = document.createElement('p'); p.textContent = n.message;
      left.appendChild(h); left.appendChild(p);
      const actions = document.createElement('div'); actions.className='actions';
      const btn = document.createElement('button'); btn.className='btn'; btn.textContent='Dispensar';
      btn.addEventListener('click', async()=>{
        btn.disabled = true;
        const fd = new FormData(); fd.append('csrf',csrf); fd.append('id', String(n.id));
        try{ const r=await fetch('/notifications/dismiss',{method:'POST',body:fd}); const j=await r.json(); if(j && j.ok){ card.remove(); } else { btn.disabled=false; } }
        catch(e){ btn.disabled=false; }
      });
      actions.appendChild(btn);
      card.appendChild(left); card.appendChild(actions);
      list.appendChild(card);
    });
  };
  fetchNotices();
  setInterval(fetchNotices, 60*1000);
}

const regForm = document.querySelector('#registerForm');
if(regForm){
  const steps = Array.from(regForm.querySelectorAll('[data-step]'));
  const stepper = regForm.previousElementSibling;
  let cur = 1;
  const show=(n)=>{steps.forEach(s=>{s.style.display=Number(s.getAttribute('data-step'))===n?'block':'none'});
    Array.from(stepper.querySelectorAll('.step')).forEach((el,i)=>{el.classList.toggle('active',i===n-1)});
  };
  const next1 = regForm.querySelector('#nextStep');
  const next2 = regForm.querySelector('#nextStep2');
  const prev = regForm.querySelector('#prevStep');
  next1&&next1.addEventListener('click',()=>{ if(!regForm.querySelector('[name="name"]').checkValidity()){ regForm.querySelector('[name="name"]').reportValidity(); return;} cur=2; show(cur); });
  next2&&next2.addEventListener('click',()=>{ const email=regForm.querySelector('[name="email"]'); const pass=regForm.querySelector('[name="password"]'); if(!email.checkValidity()){ email.reportValidity(); return;} if(!pass.checkValidity()){ pass.reportValidity(); return;} cur=3; show(cur); });
  prev&&prev.addEventListener('click',()=>{ cur=1; show(cur); });
  show(cur);
}

const chatRoot = document.querySelector('#chatRoot');
if(chatRoot){
  const csrf = chatRoot.getAttribute('data-csrf')||'';
  const convId = chatRoot.getAttribute('data-conversation-id')||'0';
  const userName = chatRoot.getAttribute('data-user-name')||'Você';
  const msgList = document.querySelector('#messagesList');
  const input = document.querySelector('#messageInput');
  const sendBtn = document.querySelector('#sendMsg');
  const attachBtn = document.querySelector('#attachBtn');
  const fileInput = document.querySelector('#fileInput');
  const emojiBtn = document.querySelector('#emojiBtn');
  const emojiPicker = document.querySelector('emoji-picker');
  const sidebarToggle = document.querySelector('#sidebarToggle');
  const sidebar = document.querySelector('.chat-sidebar');

  if(sidebarToggle && sidebar) {
      sidebarToggle.addEventListener('click', () => {
          sidebar.classList.toggle('open');
      });
  }

  // Delete Chat
  const deleteBtns = document.querySelectorAll('.chat-delete-btn');
  deleteBtns.forEach(btn => {
      btn.addEventListener('click', async (e) => {
          e.preventDefault();
          e.stopPropagation();
          
          if(!confirm('Tem certeza que deseja excluir esta conversa?')) return;
          
          const id = btn.getAttribute('data-id');
          btn.disabled = true;
          
          try {
              const fd = new FormData();
              fd.append('csrf', csrf);
              fd.append('conversation_id', id);
              
              const prefix = window.location.pathname.startsWith('/ADVOGADOS') ? '/ADVOGADOS' : '';
              const r = await fetch(prefix + '/chat/delete-conversation', { method: 'POST', body: fd });
              const j = await r.json();
              
              if(j && j.ok) {
                  const row = btn.closest('.chat-history-row');
                  if(row) row.remove();
                  if(id === convId) {
                      window.location.href = prefix + '/chat';
                  }
              } else {
                  alert('Erro ao excluir conversa');
                  btn.disabled = false;
              }
          } catch(e) {
              alert('Erro de conexão');
              btn.disabled = false;
          }
      });
  });
  
  // Sound Notification
  const playNotification = () => {
    // Create audio context or simple audio element
    // Using a simple beep base64 for now
    const audio = new Audio('data:audio/wav;base64,UklGRl9vT19XQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YU'); 
    audio.play().catch(e=>{});
  };

  const scrollToBottom = () => {
    if(msgList) msgList.scrollTop = msgList.scrollHeight;
  };
  // Initial scroll
  setTimeout(scrollToBottom, 100);

  // Module Selection
  const moduleBtns = document.querySelectorAll('.module-btn');
  const moduleInput = document.getElementById('moduleInput');
  
  if(moduleBtns.length > 0 && moduleInput) {
      moduleBtns.forEach(btn => {
          btn.addEventListener('click', () => {
              // Remove active class from all
              moduleBtns.forEach(b => b.classList.remove('active'));
              // Add active to clicked
              btn.classList.add('active');
              // Update hidden input
              moduleInput.value = btn.getAttribute('data-module');
              
              // Optional: Provide visual feedback or toast
              const moduleName = btn.getAttribute('title');
              console.log('Módulo ativado:', moduleName);
          });
      });
  }

  // Model Selector Logic
  const modelBtn = document.getElementById('modelSelectBtn');
  const modelModal = document.getElementById('modelModal');
  const closeModalBtn = document.getElementById('closeModelModal');
  const confirmModelBtn = document.getElementById('confirmModelBtn');
  const modelCards = document.querySelectorAll('.model-card');
  const modelInput = document.getElementById('modelInput');
  const providerInput = document.getElementById('providerInput');
  const selectedModelName = document.getElementById('selectedModelName');

  if (modelBtn && modelModal) {
      modelBtn.addEventListener('click', () => {
          modelModal.style.display = 'flex';
      });

      const closeModal = () => {
          modelModal.style.display = 'none';
      };

      closeModalBtn.addEventListener('click', closeModal);
      modelModal.addEventListener('click', (e) => {
          if (e.target === modelModal) closeModal();
      });

      modelCards.forEach(card => {
          card.addEventListener('click', () => {
              modelCards.forEach(c => c.classList.remove('active'));
              card.classList.add('active');
              selectedModelName.textContent = card.dataset.name;
          });
      });

      confirmModelBtn.addEventListener('click', () => {
          const activeCard = document.querySelector('.model-card.active');
          if (activeCard) {
              const model = activeCard.dataset.model;
              const provider = activeCard.dataset.provider;
              const name = activeCard.dataset.name;
              const icon = activeCard.dataset.icon;

              modelInput.value = model;
              providerInput.value = provider;

              // Update Button UI
              modelBtn.querySelector('.model-name').textContent = name;
              modelBtn.querySelector('.model-icon').textContent = icon;
          }
          closeModal();
      });
  }

  // Auto-resize textarea
  if(input) {
      input.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
        if(this.value.trim().length > 0) sendBtn.disabled = false;
        else sendBtn.disabled = true;
      });
      input.addEventListener('keydown', (e) => {
        if(e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
      });
  }

  // Markdown Parser (Simplified)
  const parseMarkdown = (text) => {
    if(!text) return '';
    return text
      .replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;")
      .replace(/```([\s\S]*?)```/g, '<pre><code>$1</code></pre>')
      .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
      .replace(/\*([^*]+)\*/g, '<em>$1</em>')
      .replace(/\n/g, '<br>');
  };

  // Initial Markdown Parse for SSR messages
  document.querySelectorAll('.message-content').forEach(el => {
      // Use textContent to get the raw text (including newlines from source)
      // Note: PHP View::e() escapes chars, so textContent will decode them back to raw chars
      // But we want to re-escape them in parseMarkdown to be safe, except the markdown tags.
      // Actually parseMarkdown starts with escaping.
      // So we should get the raw text.
      const raw = el.textContent; 
      el.innerHTML = parseMarkdown(raw);
  });

  const createMsgEl = (m) => {
    const isUser = m.kind === 'user' || m.kind === 'question';
    const div = document.createElement('div');
    div.className = `message-row ${isUser ? 'user' : 'ai'}`;
    
    const wrapper = document.createElement('div');
    wrapper.className = 'message-wrapper';

    // Avatar Container
    const avatarContainer = document.createElement('div');
    avatarContainer.className = 'message-avatar-container';
    avatarContainer.title = isUser ? userName : 'Assistente Jurídico';

    const avatar = document.createElement('div');
    avatar.className = 'message-avatar';
    
    if (isUser) {
        const avatarText = document.createElement('div');
        avatarText.className = 'avatar-text';
        avatarText.textContent = userName.charAt(0).toUpperCase();
        avatar.appendChild(avatarText);
    } else {
        const avatarIcon = document.createElement('div');
        avatarIcon.className = 'avatar-icon';
        avatarIcon.textContent = '⚖️';
        avatar.appendChild(avatarIcon);
    }

    const status = document.createElement('div');
    status.className = 'status-indicator online';
    status.setAttribute('aria-label', 'Status: Online');

    avatarContainer.appendChild(avatar);
    avatarContainer.appendChild(status);

    // Message Body
    const body = document.createElement('div');
    body.className = 'message-body';

    const info = document.createElement('div');
    info.className = 'message-info';

    const nameSpan = document.createElement('span');
    nameSpan.className = 'message-name';
    nameSpan.textContent = isUser ? userName : 'Assistente Jurídico';

    const timeSpan = document.createElement('span');
    timeSpan.className = 'message-time';
    const now = new Date();
    timeSpan.textContent = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');

    info.appendChild(nameSpan);
    info.appendChild(timeSpan);

    const content = document.createElement('div');
    content.className = 'message-content';
    content.innerHTML = parseMarkdown(m.text || '');

    body.appendChild(info);
    body.appendChild(content);

    wrapper.appendChild(avatarContainer);
    wrapper.appendChild(body);
    div.appendChild(wrapper);

    return div;
  };

  const refreshMessages = async () => {
      try {
        const r = await fetch(`/chat/messages?conversation_id=${convId}`);
        const j = await r.json();
        if(j.items) {
            const currentCount = msgList.children.length;
            if(j.items.length > currentCount) {
                // Only append new? For simplicity, re-render if count differs
                // In production, use IDs to diff
                msgList.innerHTML = ''; 
                j.items.forEach(m => msgList.appendChild(createMsgEl(m)));
                scrollToBottom();
                
                // Play sound if last message is AI and it's new
                const last = j.items[j.items.length-1];
                if(last.kind !== 'user') playNotification();
            }
        }
      } catch(e) {}
  };

  // Poll for updates every 3s
  setInterval(refreshMessages, 3000);

  // Emoji
  if(emojiBtn && emojiPicker) {
    emojiBtn.addEventListener('click', () => {
        emojiPicker.style.display = emojiPicker.style.display === 'none' ? 'block' : 'none';
    });
    emojiPicker.addEventListener('emoji-click', event => {
        input.value += event.detail.unicode;
        input.dispatchEvent(new Event('input'));
        emojiPicker.style.display = 'none';
        input.focus();
    });
  }

  // Send Logic
  const sendMessage = async () => {
    const text = input.value.trim();
    if(!text) return;
    
    input.value = '';
    input.style.height = 'auto';
    sendBtn.disabled = true;
    
    // Optimistic Append
    const tempMsg = {kind:'user', text: text, created_at: new Date().toISOString()};
    msgList.appendChild(createMsgEl(tempMsg));
    scrollToBottom();
    
    const fd = new FormData();
    fd.append('csrf', csrf);
    fd.append('conversation_id', convId);
    fd.append('text', text);
    
    try {
        const r = await fetch('/chat/send', {method:'POST', body:fd});
        const j = await r.json();
        if(j.ok) {
            // Trigger immediate refresh to get AI response if ready
            setTimeout(refreshMessages, 1000);
            setTimeout(refreshMessages, 3000); // Check again later
        }
    } catch(e) {
        console.error(e);
        // Remove optimistic? Or show error
    }
  };
  
  if(sendBtn) sendBtn.addEventListener('click', (e)=>{ e.preventDefault(); sendMessage(); });

  // Attachments
  if(attachBtn && fileInput) {
      attachBtn.addEventListener('click', () => fileInput.click());
      fileInput.addEventListener('change', async () => {
          if(fileInput.files.length) {
              const fd = new FormData();
              fd.append('csrf', csrf);
              fd.append('file', fileInput.files[0]);
              // Upload logic...
              // For now just alert
              alert('Upload iniciado...');
          }
      });
  }

  // Deadlines Management
  const deadlinesList = document.querySelector('#deadlinesList');
  const fetchDeadlines = async () => {
    if(!deadlinesList) return;
    try {
        const r = await fetch('/chat/deadlines');
        const j = await r.json();
        renderDeadlines(j.items || []);
    } catch(e) {}
  };

  const renderDeadlines = (items) => {
    if(!deadlinesList) return;
    deadlinesList.innerHTML = '';
    if(!items.length) {
        deadlinesList.innerHTML = '<div style="opacity:0.6; font-style:italic; padding: 10px 0;">Nenhum prazo pendente.</div>';
        return;
    }
    items.forEach(d => {
        const row = document.createElement('div');
        row.style.marginBottom = '8px';
        row.style.padding = '8px';
        row.style.background = 'rgba(255,255,255,0.05)';
        row.style.borderRadius = '4px';
        row.style.display = 'flex';
        row.style.justifyContent = 'space-between';
        row.style.alignItems = 'center';
        
        const info = document.createElement('div');
        let dateStr = 'A definir';
        if (d.due_date) {
            const parts = d.due_date.split(/[- :]/);
            dateStr = parts[2] + '/' + parts[1] + '/' + parts[0]; 
        }

        info.innerHTML = `<div style="font-weight:bold; color:#f87171; font-size:0.8rem;">📅 ${dateStr}</div><div style="font-size:0.85rem; word-break:break-word;">${d.description}</div>`;
        
        const btn = document.createElement('button');
        btn.innerHTML = '✔';
        btn.title = 'Concluir';
        btn.style.background = 'none';
        btn.style.border = '1px solid rgba(74, 222, 128, 0.3)';
        btn.style.borderRadius = '50%';
        btn.style.width = '24px';
        btn.style.height = '24px';
        btn.style.color = '#4ade80';
        btn.style.cursor = 'pointer';
        btn.style.display = 'flex';
        btn.style.alignItems = 'center';
        btn.style.justifyContent = 'center';
        btn.style.fontSize = '12px';
        
        btn.onclick = async () => {
            if(!confirm('Marcar como concluído?')) return;
            const fd = new FormData();
            fd.append('csrf', csrf);
            fd.append('id', d.id);
            await fetch('/chat/dismiss-deadline', { method: 'POST', body: fd });
            fetchDeadlines();
        };
        
        row.appendChild(info);
        row.appendChild(btn);
        deadlinesList.appendChild(row);
    });
  };

  fetchDeadlines();
  setInterval(fetchDeadlines, 30000);
}

const apiForm = document.querySelector('#apiKeyForm');
if(apiForm){
  const provider = apiForm.querySelector('select[name="provider"]');
  const key = apiForm.querySelector('input[name="key"]');
  const test = document.querySelector('#testKey');
  const loader = apiForm.querySelector('.loader');
  const csrf = apiForm.querySelector('input[name="csrf"]').value;
  const err = apiForm.querySelector('.field-error[data-error-for="key"]');
  test.addEventListener('click',async()=>{
    err.textContent='';
    if(!key.value.trim()){ err.textContent='Informe a chave'; return; }
    const fd=new FormData(); fd.append('csrf',csrf); fd.append('provider',provider.value); fd.append('key',key.value.trim());
    loader.style.display='inline-block';
    try{ const r=await fetch('/settings/api-keys/test',{method:'POST',body:fd}); const j=await r.json(); err.textContent = j && j.ok ? 'Chave válida' : (j.msg || 'Chave inválida'); }
    catch(e){ err.textContent='Erro ao testar'; }
    finally{ loader.style.display='none'; }
  });
}
