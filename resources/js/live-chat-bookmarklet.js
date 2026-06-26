(function() {
    // Evitar múltiplas instâncias
    if (window.LiveChatCaptureInstance) {
        alert("O Capturador de Live já está aberto!");
        return;
    }

    const platform = window.location.hostname.includes("tiktok") ? "tiktok" : "instagram";
    
    // Sanitiza a URL para manter apenas protocolo e host (ex. http://127.0.0.1:8000)
    function sanitizeServerUrl(url) {
        if (!url) return "";
        try {
            const parsed = new URL(url.trim());
            return parsed.origin;
        } catch (e) {
            const match = url.trim().match(/^(https?:\/\/[^\/]+)/);
            return match ? match[1] : url.trim();
        }
    }

    // Auto-detectar URL do servidor a partir de onde o script foi carregado
    let defaultServerUrl = "http://localhost:8000";
    const scriptEl = document.querySelector('script[src*="live-chat-bookmarklet.js"]');
    if (scriptEl) {
        const src = scriptEl.getAttribute("src");
        const match = src.match(/^(https?:\/\/[^\/]+)/);
        if (match) {
            defaultServerUrl = match[1];
        }
    }

    let serverUrl = sanitizeServerUrl(localStorage.getItem("live_capture_server_url") || defaultServerUrl);
    let selectedLiveId = localStorage.getItem("live_capture_live_id") || "";
    let isCapturing = false;
    let observer = null;
    const sentMessages = new Set();
    
    // Seletores padrão
    let selectors = {
        tiktok: {
            container: 'div[class*="DivChatRoomAnimationContainer"], div[class*="DivChatMessageList"], div[class*="DivChatRoom"], .webcast-chatroom__list, div[class*="ChatList"], div[class*="chat-list"]',
            message: 'div[class*="DivChatMessage"], .webcast-chatroom__message, div[data-e2e="chat-message"], div[class*="chat-message"]',
            username: 'span[class*="nickname"], span[class*="username"], .webcast-chatroom__author-name, span[class*="author"]',
            text: 'span[class*="comment"], span[class*="text"], .webcast-chatroom__message-text, span[class*="text-content"]'
        },
        instagram: {
            container: '[role="log"], div[class*="CommentList"], div[class*="comment-list"], div[class*="LiveChat"], div[class*="LiveCommentList"]',
            message: '[role="log"] > *, div[class*="CommentRow"], div[class*="comment-row"], div[class*="LiveComment"]',
            username: 'a[href*="/"], span[class*="username"], .username',
            text: 'span[class*="comment"], span:last-child, span[class*="text"]'
        }
    }[platform];

    // Criar UI Flutuante
    const ui = document.createElement("div");
    ui.id = "live-chat-capture-ui";
    ui.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        width: 320px;
        background: rgba(30, 41, 59, 0.95);
        color: #f8fafc;
        border: 1px solid #475569;
        border-radius: 12px;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3), 0 8px 10px -6px rgba(0, 0, 0, 0.3);
        z-index: 999999;
        font-family: system-ui, -apple-system, sans-serif;
        padding: 16px;
        font-size: 13px;
        backdrop-filter: blur(8px);
    `;

    ui.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid #475569; padding-bottom: 8px;">
            <strong style="font-size: 14px; color: #38bdf8; display: flex; align-items: center; gap: 6px;">
                <span style="display: inline-block; width: 8px; height: 8px; background: #ef4444; border-radius: 50%; animation: pulse 1.5s infinite;"></span>
                Capturador Live (${platform === "tiktok" ? "TikTok" : "Instagram"})
            </strong>
            <button id="close-capture-ui" style="background: none; border: none; color: #94a3b8; cursor: pointer; font-size: 16px;">&times;</button>
        </div>
        
        <div style="margin-bottom: 10px;">
            <label style="display: block; margin-bottom: 4px; color: #94a3b8; font-weight: 500;">Servidor Laravel:</label>
            <input type="text" id="capture-server-url" value="${serverUrl}" style="width: 100%; padding: 6px; border-radius: 6px; border: 1px solid #475569; background: #0f172a; color: #fff; box-sizing: border-box;">
        </div>

        <div style="margin-bottom: 12px;">
            <label style="display: block; margin-bottom: 4px; color: #94a3b8; font-weight: 500;">Selecionar Live:</label>
            <select id="capture-live-select" style="width: 100%; padding: 6px; border-radius: 6px; border: 1px solid #475569; background: #0f172a; color: #fff; box-sizing: border-box;">
                <option value="">Carregando lives...</option>
            </select>
        </div>

        <div style="margin-bottom: 12px; display: flex; gap: 8px;">
            <button id="toggle-capture-btn" style="flex: 1; padding: 8px; border-radius: 6px; border: none; background: #0284c7; color: #fff; font-weight: bold; cursor: pointer; transition: background 0.2s;">Iniciar Captura</button>
            <button id="calibrate-selectors-btn" style="padding: 8px; border-radius: 6px; border: 1px solid #475569; background: #334155; color: #fff; cursor: pointer;">Calibrar</button>
        </div>

        <div id="capture-status-log" style="max-height: 80px; overflow-y: auto; background: #0f172a; padding: 6px; border-radius: 6px; font-family: monospace; font-size: 11px; color: #10b981; border: 1px solid #1e293b; margin-top: 8px;">
            Aguardando início...
        </div>

        <style>
            @keyframes pulse {
                0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
                70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(239, 68, 68, 0); }
                100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
            }
            #toggle-capture-btn:hover { background: #0369a1; }
            #close-capture-ui:hover { color: #fff; }
        </style>
    `;

    document.body.appendChild(ui);
    window.LiveChatCaptureInstance = ui;

    const logEl = ui.querySelector("#capture-status-log");
    const liveSelect = ui.querySelector("#capture-live-select");
    const serverInput = ui.querySelector("#capture-server-url");
    const toggleBtn = ui.querySelector("#toggle-capture-btn");
    const closeBtn = ui.querySelector("#close-capture-ui");
    const calibrateBtn = ui.querySelector("#calibrate-selectors-btn");

    function log(msg, error = false) {
        logEl.style.color = error ? "#f87171" : "#34d399";
        logEl.innerHTML = `[${new Date().toLocaleTimeString()}] ${msg}`;
    }

    // Carregar lives do servidor
    async function loadLives() {
        const url = serverInput.value.trim().replace(/\/$/, "");
        try {
            log("Conectando ao Laravel...");
            const res = await fetch(`${url}/api/lives/all`);
            if (!res.ok) throw new Error("Erro HTTP: " + res.status);
            const responseData = await res.json();
            
            const livesList = responseData.data || [];
            
            liveSelect.innerHTML = "";
            if (livesList.length === 0) {
                liveSelect.innerHTML = `<option value="">Nenhuma live encontrada</option>`;
                return;
            }

            livesList.forEach(live => {
                const opt = document.createElement("option");
                opt.value = live.id;
                
                const tipoTranslate = {
                    'loja-aberta': 'Loja Aberta',
                    'leilao': 'Leilão',
                    'precinho': 'Precinho'
                };
                const tipo = tipoTranslate[live.tipo_live] || live.tipo_live || 'Live';
                const dateClean = live.data ? live.data.split('T')[0].split('-').reverse().join('/') : '';
                
                opt.textContent = `#${live.id} - ${tipo} (${dateClean})`;
                if (String(live.id) === String(selectedLiveId)) opt.selected = true;
                liveSelect.appendChild(opt);
            });
            log("Lives carregadas com sucesso!");
        } catch (e) {
            log("Erro ao carregar lives: " + e.message, true);
            liveSelect.innerHTML = `<option value="">Erro ao carregar lives</option>`;
        }
    }

    serverInput.addEventListener("change", () => {
        serverUrl = sanitizeServerUrl(serverInput.value.trim());
        serverInput.value = serverUrl;
        localStorage.setItem("live_capture_server_url", serverUrl);
        loadLives();
    });

    liveSelect.addEventListener("change", () => {
        selectedLiveId = liveSelect.value;
        localStorage.setItem("live_capture_live_id", selectedLiveId);
    });

    closeBtn.addEventListener("click", () => {
        if (isCapturing) stopCapture();
        ui.remove();
        window.LiveChatCaptureInstance = null;
    });

    // Calibração interativa de seletores inteligente (1 clique)
    calibrateBtn.addEventListener("click", () => {
        alert("Modo de calibração inteligente ativado!\nClique em QUALQUER COMENTÁRIO no chat da live.");
        
        const overlay = document.createElement("div");
        overlay.style.cssText = "position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.1);z-index:999998;cursor:crosshair;";
        document.body.appendChild(overlay);

        function detectChatStructure(clickedEl) {
            // 1. Verificar se algum ancestral já corresponde ao seletor de mensagem padrão
            let temp = clickedEl;
            while (temp && temp.nodeName !== "BODY" && temp.nodeName !== "HTML") {
                if (temp.matches && temp.matches(selectors.message)) {
                    return {
                        messageElement: temp,
                        containerElement: temp.parentNode
                    };
                }
                temp = temp.parentNode;
            }

            // 2. Caso contrário, usar heurística subindo os níveis
            let path = [];
            temp = clickedEl;
            for (let i = 0; i < 5; i++) {
                if (!temp || temp.nodeName === "BODY" || temp.nodeName === "HTML") break;
                path.push(temp);
                temp = temp.parentNode;
            }

            for (let i = 1; i < path.length; i++) {
                let el = path[i];
                let role = el.getAttribute('role') || '';
                let className = el.className ? String(el.className).toLowerCase() : '';
                
                // O contêiner de mensagens real costuma ter pelo menos 3 filhos
                // e não deve ser um elemento de linha individual (evitar classes como "item", "row", "message")
                let isCommentRow = (className.includes('message') || className.includes('comment') || className.includes('row') || className.includes('item')) &&
                                   !(className.includes('list') || className.includes('container') || className.includes('log') || className.includes('chat'));
                
                let isList = (role === 'log' || 
                              className.includes('list') || 
                              className.includes('scroll') || 
                              className.includes('container') ||
                              className.includes('log') ||
                              className.includes('chat') ||
                              className.includes('view')) && !isCommentRow;
                             
                if (isList && el.children.length >= 3) {
                    return {
                        messageElement: path[i-1],
                        containerElement: el
                    };
                }
            }
            
            if (path.length >= 3) {
                return {
                    messageElement: path[path.length - 2],
                    containerElement: path[path.length - 1]
                };
            }
            
            return {
                messageElement: clickedEl,
                containerElement: clickedEl.parentNode || clickedEl
            };
        }

        function getContainerSelector(el) {
            if (el.id) return `#${el.id}`;
            if (el.getAttribute('role')) return `${el.tagName.toLowerCase()}[role="${el.getAttribute('role')}"]`;
            if (el.getAttribute('data-e2e')) return `${el.tagName.toLowerCase()}[data-e2e="${el.getAttribute('data-e2e')}"]`;
            
            let classes = Array.from(el.classList)
                .filter(c => !/^(?:[0-9]|-[0-9]|--)/.test(c) && !c.includes('hover') && !c.includes('active'))
                .join('.');
            if (classes) return `${el.tagName.toLowerCase()}.${classes}`;
            
            let path = [];
            let current = el;
            while (current && current.nodeType === Node.ELEMENT_NODE) {
                if (current.id) {
                    path.unshift(`#${current.id}`);
                    break;
                }
                if (current.getAttribute('role')) {
                    path.unshift(`${current.tagName.toLowerCase()}[role="${current.getAttribute('role')}"]`);
                    break;
                }
                let tag = current.nodeName.toLowerCase();
                let curClasses = Array.from(current.classList)
                    .filter(c => !/^(?:[0-9]|-[0-9]|--)/.test(c) && !c.includes('hover') && !c.includes('active'))
                    .join('.');
                path.unshift(tag + (curClasses ? '.' + curClasses : ''));
                current = current.parentNode;
                if (!current || current.nodeName === "BODY" || current.nodeName === "HTML") break;
            }
            return path.join(" > ");
        }

        function getCommentSelector(el, containerEl, containerSelector) {
            let tag = el.nodeName.toLowerCase();
            if (containerEl && el.parentNode === containerEl) {
                return `${containerSelector} > ${tag}`;
            }
            let classes = Array.from(el.classList)
                .filter(c => !/^(?:[0-9]|-[0-9]|--)/.test(c) && c.length > 2 && !c.includes('hover') && !c.includes('active'))
                .slice(0, 2);
            let classPart = classes.length > 0 ? `.${classes.join('.')}` : '';
            return `${containerSelector} ${tag}${classPart}`;
        }

        overlay.addEventListener("click", (e) => {
            e.stopPropagation();
            e.preventDefault();
            
            overlay.style.display = "none";
            const clickedElement = document.elementFromPoint(e.clientX, e.clientY);
            overlay.style.display = "block";

            if (!clickedElement) return;

            const structure = detectChatStructure(clickedElement);
            
            const containerSel = getContainerSelector(structure.containerElement);
            selectors.container = containerSel;
            selectors.message = getCommentSelector(structure.messageElement, structure.containerElement, containerSel);
            
            alert(`Calibração Inteligente Concluída!\n\n` +
                  `1. Contêiner detectado: ${selectors.container}\n` +
                  `2. Linha do comentário: ${selectors.message}`);
                  
            document.body.removeChild(overlay);
        });
    });

    // Função para tratar novas mensagens
    function processNewMessageNode(node) {
        if (node.nodeType !== Node.ELEMENT_NODE) return;

        // Verificar se o próprio nó é a mensagem ou se precisamos buscar as mensagens dentro dele
        let messages = [node];
        if (!node.matches(selectors.message)) {
            messages = Array.from(node.querySelectorAll(selectors.message));
        }

        if (messages.length > 0) {
            console.log(`[Capturador] Detectados ${messages.length} nós de comentário para processar.`);
        }

        messages.forEach(msgNode => {
            // Evitar processar o mesmo nó de mensagem mais de uma vez
            if (msgNode.dataset.captured === "true") return;
            msgNode.dataset.captured = "true";

            try {
                // 1. Coletar todas as folhas de texto do nó
                let leafs = [];
                function getLeafTextNodes(el) {
                    if (el.nodeType === Node.ELEMENT_NODE) {
                        const tag = el.nodeName.toLowerCase();
                        const role = el.getAttribute('role') || '';
                        const className = el.className ? String(el.className).toLowerCase() : '';
                        
                        // Ignorar botões, SVGs, menus de ação e botões de resposta
                        if (tag === 'button' || tag === 'svg' || tag === 'path' || 
                            role === 'button' || 
                            className.includes('action') ||
                            el.textContent.trim() === '...' ||
                            el.textContent.trim() === '…' ||
                            el.textContent.trim().toLowerCase() === 'responder' ||
                            el.textContent.trim().toLowerCase() === 'reply') {
                            return;
                        }
                    }
                    
                    if (el.children && el.children.length > 0) {
                        Array.from(el.children).forEach(child => getLeafTextNodes(child));
                    } else {
                        let textVal = el.textContent.trim();
                        if (textVal.length > 0) {
                            leafs.push(textVal);
                        }
                    }
                }
                
                getLeafTextNodes(msgNode);

                // Helper para verificar se um texto se parece com badge/nível
                function isBadgeText(val) {
                    if (!val) return false;
                    val = val.trim();
                    if (/^\d+$/.test(val)) return true; // número puro
                    if (/^(lv|level)\.?\s*\d+$/i.test(val)) return true; // Lv. 3, Level 15
                    if (/^(mod|sub|ad|membro|member|moderador|host|creator|criador)$/i.test(val)) return true;
                    return false;
                }

                // 2. Detectar o username
                let user = "";
                
                // Usar o seletor do username configurado na calibração/padrão se disponível
                if (selectors && selectors.username) {
                    const userEl = msgNode.querySelector(selectors.username);
                    if (userEl) {
                        const potentialUser = userEl.textContent.trim();
                        if (!isBadgeText(potentialUser)) {
                            user = potentialUser;
                        }
                    }
                }
                
                // Seletor genérico fallback para username
                if (!user) {
                    const userEl = msgNode.querySelector('a[href*="/"], span[class*="username"], .username, span[class*="nickname"], .webcast-chatroom__author-name, span[class*="author"]');
                    if (userEl) {
                        const potentialUser = userEl.textContent.trim();
                        if (!isBadgeText(potentialUser)) {
                            user = potentialUser;
                        }
                    }
                }

                // Se ainda não detectamos, pegamos a primeira folha de texto que não seja badge
                if (!user) {
                    for (let i = 0; i < leafs.length; i++) {
                        if (!isBadgeText(leafs[i])) {
                            user = leafs[i];
                            break;
                        }
                    }
                }

                // Limpar username
                user = user ? user.replace(/^@/, "").replace(/:$/, "").trim() : "";

                // 3. Extrair a mensagem (todos os leafs que vêm APÓS o username)
                let text = "";
                if (user) {
                    // Encontrar o índice do username na lista de leafs
                    let userIdx = -1;
                    for (let i = 0; i < leafs.length; i++) {
                        if (leafs[i].replace(/^@/, "").replace(/:$/, "").trim() === user) {
                            userIdx = i;
                            break;
                        }
                    }
                    
                    if (userIdx !== -1) {
                        let messageParts = [];
                        const timeRegex = /^(agora|now|\d+\s*(s|m|h|d|min|seg|hor|dia|sec|minuto|hora|day)s?)$/i;
                        
                        for (let i = userIdx + 1; i < leafs.length; i++) {
                            let val = leafs[i].trim();
                            let valLower = val.toLowerCase();
                            
                            // Pular caracteres de separação e botões de controle comuns
                            if (val === ":" || val === "-" || val === "：" || val === "·" || val === "•") continue;
                            if (valLower === 'curtir' || valLower === 'like' || valLower === 'responder' || valLower === 'reply') continue;
                            if (timeRegex.test(val)) continue;
                            
                            messageParts.push(leafs[i]);
                        }
                        text = messageParts.join(" ").trim();
                    }
                }

                text = text ? text.replace(/^:\s*/, "").trim() : "";

                console.log(`[Capturador] Tentando processar - Usuário: "${user}", Texto: "${text}"`);

                if (user && text) {
                    sendChatMessage(user, text);
                } else {
                    console.warn("[Capturador] Não foi possível extrair usuário e texto válidos do elemento:", msgNode);
                }
            } catch (err) {
                console.error("Erro ao ler nó de chat: ", err);
            }
        });
    }

    // Enviar mensagem para o Laravel
    async function sendChatMessage(username, message) {
        const msgKey = `${username}:${message}`;
        if (sentMessages.has(msgKey)) return; // Evitar duplicatas
        sentMessages.add(msgKey);

        const url = serverInput.value.trim().replace(/\/$/, "");
        const liveId = liveSelect.value;
        if (!liveId) return;

        try {
            const res = await fetch(`${url}/api/live-chat/message`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json"
                },
                body: JSON.stringify({
                    live_id: liveId,
                    platform: platform,
                    username: username,
                    message: message
                })
            });
            const data = await res.json();
            if (data.success) {
                log(`Enviado: @${username} -> "${message}"`);
            } else {
                console.warn("Laravel rejeitou a mensagem:", data.error);
            }
        } catch (e) {
            console.error("Erro ao enviar mensagem para o Laravel:", e);
        }
    }

    // Iniciar monitoramento
    function startCapture() {
        const chatContainer = document.querySelector(selectors.container);
        if (!chatContainer) {
            log("Contêiner do chat não encontrado! Tente 'Calibrar'.", true);
            alert("Aba de chat não localizada! Certifique-se de que a live está aberta e o painel de chat visível.");
            return;
        }

        isCapturing = true;
        toggleBtn.textContent = "Parar Captura";
        toggleBtn.style.background = "#dc2626";
        log("Captura iniciada com sucesso!");

        // Processar mensagens que já estão na tela
        const existingMessages = chatContainer.querySelectorAll(selectors.message);
        existingMessages.forEach(msg => processNewMessageNode(msg));

        // Observar novas mensagens
        observer = new MutationObserver((mutations) => {
            mutations.forEach(mutation => {
                mutation.addedNodes.forEach(node => {
                    processNewMessageNode(node);
                });
            });
        });

        observer.observe(chatContainer, { childList: true, subtree: true });
    }

    // Parar monitoramento
    function stopCapture() {
        if (observer) {
            observer.disconnect();
            observer = null;
        }
        isCapturing = false;
        toggleBtn.textContent = "Iniciar Captura";
        toggleBtn.style.background = "#0284c7";
        log("Captura parada.");
    }

    toggleBtn.addEventListener("click", () => {
        if (isCapturing) {
            stopCapture();
        } else {
            if (!liveSelect.value) {
                alert("Selecione uma live primeiro!");
                return;
            }
            startCapture();
        }
    });

    // Inicialização
    loadLives();
})();
