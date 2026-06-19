(function() {
    // Evitar múltiplas instâncias
    if (window.LiveChatCaptureInstance) {
        alert("O Capturador de Live já está aberto!");
        return;
    }

    const platform = window.location.hostname.includes("tiktok") ? "tiktok" : "instagram";
    let serverUrl = localStorage.getItem("live_capture_server_url") || (window.location.origin.includes("localhost") || window.location.origin.includes("127.0.0.1") ? window.location.origin : "http://localhost:8000");
    let selectedLiveId = localStorage.getItem("live_capture_live_id") || "";
    let isCapturing = false;
    let observer = null;
    let sentMessages = new Set();
    
    // Seletores padrão
    let selectors = {
        tiktok: {
            container: 'div[class*="DivChatRoomAnimationContainer"], div[class*="DivChatMessageList"], .webcast-chatroom__list',
            message: 'div[class*="DivChatMessage"], .webcast-chatroom__message, div[data-e2e="chat-message"]',
            username: 'span[class*="nickname"], span[class*="username"], .webcast-chatroom__author-name',
            text: 'span[class*="comment"], span[class*="text"], .webcast-chatroom__message-text'
        },
        instagram: {
            container: 'div[role="log"], div[class*="CommentList"]',
            message: 'div[role="log"] > div, div[class*="CommentRow"]',
            username: 'a[href*="/"], span[class*="username"]',
            text: 'span[class*="comment"], span:last-child'
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
            const data = await res.json();
            
            liveSelect.innerHTML = "";
            if (data.length === 0) {
                liveSelect.innerHTML = `<option value="">Nenhuma live encontrada</option>`;
                return;
            }

            data.forEach(live => {
                const opt = document.createElement("option");
                opt.value = live.id;
                opt.textContent = `#${live.id} - ${live.tipo_live_formatado} (${live.data})`;
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
        serverUrl = serverInput.value.trim();
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

    // Calibração interativa de seletores
    calibrateBtn.addEventListener("click", () => {
        alert("Modo de calibração ativado!\n1. Mova o mouse e clique no CONTÊINER do chat.\n2. Em seguida, clique em um COMENTÁRIO dentro dele.");
        let step = 1;
        
        const overlay = document.createElement("div");
        overlay.style.cssText = "position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.1);z-index:999998;cursor:crosshair;";
        document.body.appendChild(overlay);

        function getUniqueSelector(el) {
            if (el.id) return `#${el.id}`;
            let path = [];
            while (el.nodeType === Node.ELEMENT_NODE) {
                let selector = el.nodeName.toLowerCase();
                if (el.className) {
                    selector += "." + Array.from(el.classList).join(".");
                }
                path.unshift(selector);
                el = el.parentNode;
                if (!el || el.nodeName === "BODY") break;
            }
            return path.join(" > ");
        }

        overlay.addEventListener("click", (e) => {
            e.stopPropagation();
            e.preventDefault();
            
            overlay.style.display = "none";
            const clickedElement = document.elementFromPoint(e.clientX, e.clientY);
            overlay.style.display = "block";

            if (!clickedElement) return;

            if (step === 1) {
                selectors.container = getUniqueSelector(clickedElement);
                alert(`Contêiner detectado!\nSeletor: ${selectors.container}\n\nAgora clique em um COMENTÁRIO.`);
                step = 2;
            } else if (step === 2) {
                selectors.message = getUniqueSelector(clickedElement);
                alert(`Comentário detectado!\nSeletor: ${selectors.message}\n\nCalibração concluída!`);
                document.body.removeChild(overlay);
            }
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

        messages.forEach(msgNode => {
            try {
                // Tentar extrair username e texto usando seletores
                let userEl = msgNode.querySelector(selectors.username);
                let textEl = msgNode.querySelector(selectors.text);

                // Fallbacks estruturais se os seletores falharem
                let user = userEl ? userEl.textContent.trim() : "";
                let text = textEl ? textEl.textContent.trim() : "";

                if (!user || !text) {
                    // Fallback para Instagram/TikTok quando classes mudam totalmente
                    // Pega o primeiro elemento âncora/link ou elemento negrito como usuário
                    const boldOrLink = msgNode.querySelector("a, span[style*='bold'], strong, b");
                    if (boldOrLink) {
                        user = boldOrLink.textContent.trim();
                    }
                    // Pega o restante do texto do nó
                    const allSpans = Array.from(msgNode.querySelectorAll("span"));
                    if (allSpans.length > 0) {
                        text = allSpans[allSpans.length - 1].textContent.trim();
                    } else {
                        text = msgNode.textContent.replace(user, "").trim();
                    }
                }

                // Limpar usernames (ex. remover '@' ou ':' adicionados pela plataforma)
                user = user.replace(/^@/, "").replace(/:$/, "").trim();
                text = text.replace(/^:\s*/, "").trim();

                if (user && text) {
                    sendChatMessage(user, text);
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

        sentMessages.clear();
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
