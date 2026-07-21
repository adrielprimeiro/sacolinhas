(function() {
    // Evitar múltiplas instâncias
    if (window.LiveChatCaptureInstance) {
        return;
    }

    const platform = window.location.hostname.includes("tiktok") ? "tiktok" : "instagram";
    let defaultServerUrl = "https://minhamania.net"; // Servidor de Produção Laravel
    
    // Tentar ler da URL caso o script tenha sido atualizado por um script externo
    const scriptEl = document.querySelector('script[src*="live-chat-bookmarklet.js"]');
    if (scriptEl) {
        const src = scriptEl.getAttribute("src");
        const match = src.match(/^(https?:\/\/[^\/]+)/);
        if (match) {
            defaultServerUrl = match[1];
        }
    }

    function safeGetItem(key, defaultVal) {
        try {
            return localStorage.getItem(key) || defaultVal;
        } catch (e) {
            console.warn("localStorage access denied:", e);
            return defaultVal;
        }
    }

    function safeSetItem(key, val) {
        try {
            localStorage.setItem(key, val);
        } catch (e) {
            console.warn("localStorage access denied:", e);
        }
    }

    let serverUrl = safeGetItem("live_capture_server_url", defaultServerUrl);
    if (serverUrl === "http://localhost" || serverUrl === "http://127.0.0.1" || serverUrl.includes("localhost") || serverUrl.includes("127.0.0.1") || serverUrl === "http://localhost:8000") {
        serverUrl = "https://minhamania.net";
        safeSetItem("live_capture_server_url", serverUrl);
    }
    let selectedLiveId = safeGetItem("live_capture_live_id", "");
    let isUiClosedByUser = false;
    let hasLoadedLivesOnce = false;
    let isCapturing = false;
    let observer = null;
    let hasCalibrated = false;
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

    // Função para realizar requisições via Service Worker em background (evita bloqueios de CSP)
    async function bgFetch(url, options = {}) {
        return new Promise((resolve, reject) => {
            chrome.runtime.sendMessage({
                action: "fetch",
                url: url,
                method: options.method || "GET",
                headers: options.headers || {},
                body: options.body ? JSON.parse(options.body) : undefined
            }, response => {
                if (chrome.runtime.lastError) {
                    reject(new Error(chrome.runtime.lastError.message));
                    return;
                }
                if (response && response.success) {
                    resolve({
                        ok: response.result.status >= 200 && response.result.status < 300,
                        status: response.result.status,
                        json: async () => response.result.data
                    });
                } else {
                    reject(new Error(response ? response.error : "Erro de comunicação com o Service Worker"));
                }
            });
        });
    }

    serverUrl = "https://minhamania.net";

    function log(msg, isError = false) {
        if (isError) console.error("[Minha Mania]", msg);
        else console.log("[Minha Mania]", msg);
    }

    function ensureUIInjected() {
        // Agora a extensão é 100% silenciosa, sem janelinha!
        // Apenas iniciamos a captura automaticamente se não estiver rodando.
        if (!isCapturing) {
            setTimeout(startCapture, 2000);
        }
    }

    // UI elements removed for silent extension

    function getContainerSelector(el) {
        if (el.id) return `#${el.id}`;
        if (el.getAttribute('role') && el.getAttribute('role') !== 'button') {
            return `${el.tagName.toLowerCase()}[role="${el.getAttribute('role')}"]`;
        }
        if (el.getAttribute('data-e2e')) {
            return `${el.tagName.toLowerCase()}[data-e2e="${el.getAttribute('data-e2e')}"]`;
        }
        
        let path = [];
        let current = el;
        while (current && current.nodeType === Node.ELEMENT_NODE) {
            if (current.id) {
                path.unshift(`#${current.id}`);
                break;
            }
            let tag = current.nodeName.toLowerCase();
            let role = current.getAttribute('role');
            let rolePart = (role && role !== 'button') ? `[role="${role}"]` : '';
            let curClasses = Array.from(current.classList)
                .filter(c => !/^(?:[0-9]|-[0-9]|--)/.test(c) && c.length > 2 && !c.includes('hover') && !c.includes('active'))
                .slice(0, 2)
                .join('.');
            path.unshift(tag + rolePart + (curClasses ? '.' + curClasses : ''));
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

    function detectChatStructure(clickedEl) {
        let current = clickedEl;
        let path = [];
        while (current && current.nodeName !== "BODY" && current.nodeName !== "HTML") {
            path.push(current);
            current = current.parentNode;
        }

        // 1. Encontrar o item e contêiner com base em repetição de tags de irmãos
        for (let i = 0; i < path.length - 1; i++) {
            const itemCandidate = path[i];
            const containerCandidate = path[i + 1];

            const siblingTag = itemCandidate.tagName;
            const siblings = Array.from(containerCandidate.children).filter(child => child.tagName === siblingTag);

            if (siblings.length >= 3) {
                const containerRole = containerCandidate.getAttribute('role') || '';
                const containerTag = containerCandidate.tagName.toLowerCase();
                const invalidContainerTags = ['button', 'a', 'input', 'textarea', 'p', 'span', 'img', 'svg', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
                const invalidContainerRoles = ['button', 'link', 'checkbox', 'menuitem', 'tab'];

                if (invalidContainerTags.includes(containerTag) || invalidContainerRoles.includes(containerRole.toLowerCase())) {
                    continue;
                }

                return {
                    messageElement: itemCandidate,
                    containerElement: containerCandidate
                };
            }
        }

        // 2. Fallback inteligente - encontrar primeiro ancestral estrutural válido (não botão/link/folha)
        const invalidContainerTags = ['button', 'a', 'input', 'textarea', 'p', 'span', 'img', 'svg', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
        const invalidContainerRoles = ['button', 'link', 'checkbox', 'menuitem', 'tab'];

        let containerElement = null;
        let messageElement = null;

        for (let i = 1; i < path.length; i++) {
            const el = path[i];
            const tag = el.tagName.toLowerCase();
            const role = (el.getAttribute('role') || '').toLowerCase();

            if (!invalidContainerTags.includes(tag) && !invalidContainerRoles.includes(role)) {
                containerElement = el;
                messageElement = path[i - 1];
                break;
            }
        }

        if (containerElement && messageElement) {
            return {
                messageElement: messageElement,
                containerElement: containerElement
            };
        }

        // 3. Fallback absoluto
        return {
            messageElement: clickedEl,
            containerElement: clickedEl.parentNode || clickedEl
        };
    }

    function findCommentsContainer(root) {
        if (!root) return null;
        
        // Contar quantos botões de Responder existem no contêiner
        const replies = root.querySelectorAll('button, span, div');
        let replyCount = 0;
        for (let el of replies) {
            if (el.children.length === 0 && (el.textContent.trim().toLowerCase() === 'responder' || el.textContent.trim().toLowerCase() === 'reply')) {
                replyCount++;
            }
        }
        
        // Se houver botões de resposta, vamos descer a árvore até achar o contêiner rolável real
        if (replyCount > 0) {
            let current = root;
            let children = Array.from(current.children);
            while (children.length > 0) {
                let nextContainer = null;
                for (let child of children) {
                    const childReplies = child.querySelectorAll('button, span, div');
                    let childReplyCount = 0;
                    for (let el of childReplies) {
                        if (el.children.length === 0 && (el.textContent.trim().toLowerCase() === 'responder' || el.textContent.trim().toLowerCase() === 'reply')) {
                            childReplyCount++;
                        }
                    }
                    
                    // Se um único filho contiver a grande maioria/todos os botões, ele é o contêiner real
                    if (childReplyCount === replyCount && childReplyCount > 0) {
                        nextContainer = child;
                        break;
                    }
                }
                if (nextContainer) {
                    current = nextContainer;
                    children = Array.from(current.children);
                } else {
                    break;
                }
            }
            console.log("[Capturador] Contêiner de comentários resolvido via contagem de botões:", current);
            return current;
        }
        
        // Fallback: se não houver comentários em tela ainda, procurar por um div com id/classe scroll ou scrollview
        const scrollview = root.querySelector('#scrollview, [id*="scrollview"], [class*="scrollview"], [style*="overflow"]');
        if (scrollview) {
            console.log("[Capturador] Encontrado scrollview por fallback de ID/classe:", scrollview);
            return scrollview;
        }
        
        return root;
    }

    function nameFromAlt(img){
        if (!img || !img.alt){ return ""; }
        var alt = (img.alt + "").trim();
        var englishName = alt
            .replace(/['’]s profile picture.*$/i, "")
            .replace(/['’]s profile photo.*$/i, "")
            .trim();
        if (englishName && (englishName !== alt)){ return englishName; }

        var localizedName = alt
            .replace(/^.*\bperfil\b\s+(de|del|do|da)\s+/i, "")
            .replace(/^.*\bprofil\b\s+(de|du|von)\s+/i, "")
            .replace(/^.*\bprofile\b\s+(of)\s+/i, "")
            .trim();
        if (localizedName && (localizedName !== alt)){ return localizedName; }
        return alt;
    }

    function isLikelyProfileImage(img){
        if (!img || !img.src){ return false; }
        
        var alt = (img.alt || "").trim();
        // Emojis têm alt curto sem letras ou números
        if (alt && alt.length <= 2) {
            if (!/[a-zA-Z0-9]/.test(alt)) {
                return false;
            }
        }
        
        // Classes conhecidas de emojis
        var className = (img.className || "").toLowerCase();
        if (className.includes("emoji")) return false;
        
        // Dimensões muito pequenas
        if (img.width > 0 && img.width < 24) return false;
        if (img.height > 0 && img.height < 24) return false;
        
        return true;
    }

    function getProfileImgCandidates(scope){
        var candidates = [];
        if (!scope || !scope.querySelectorAll){ return candidates; }
        var imgs = scope.querySelectorAll("img[src]");
        for (var i = 0; i < imgs.length; i++){
            if (isLikelyProfileImage(imgs[i])){ candidates.push(imgs[i]); }
        }
        return candidates;
    }

    function getProfileImgFromRow(row){
        if (!row || !row.querySelector){ return null; }
        var img = row.querySelector("img[alt*='profile picture'], img[alt*='profile photo']");
        if (img){ return img; }
        var candidates = getProfileImgCandidates(row);
        if (candidates.length){ return candidates[0]; }
        return row.querySelector("img[src]");
    }

    function normalizeLiveText(text){
        return (text || "").replace(/\u00a0/g, " ").replace(/\s+/g, " ").trim();
    }

    function plainLiveText(text){
        return normalizeLiveText((text || "")
            .replace(/<[^>]*>/g, "")
            .replace(/&nbsp;/gi, " ")
            .replace(/&hellip;|&#8230;|&#x2026;/gi, "\u2026"));
    }

    function isLivePlaceholderText(text){
        var value = plainLiveText(text);
        return (value === "...") || (value === "\u2026") || (value === "\u22EF");
    }

    function isLiveUIMarkerText(text){
        var value = normalizeLiveText(text);
        if (!value){ return false; }
        if ((value === "\u22EF") || (value === "\u2026")){ return true; }
        if (/^\.{2,}$/.test(value)){ return true; }
        return false;
    }

    function collectTextLeaves(row){
        var leaves = [];
        var walker = row.querySelectorAll("span, div");
        for (var i = 0; i < walker.length; i++){
            var el = walker[i];
            if (el.querySelector("img")){ continue; }
            var childHasText = false;
            for (var c = 0; c < el.children.length; c++){
                if ((el.children[c].textContent || "").trim().length){ childHasText = true; break; }
            }
            if (childHasText){ continue; }
            var text = (el.textContent || "").trim();
            if (!text){ continue; }
            if (isLiveUIMarkerText(text)){ continue; }
            leaves.push(el);
        }
        return leaves;
    }

    function findLiveUsername(row, profileImg){
        var altName = nameFromAlt(profileImg);
        var leaves = collectTextLeaves(row);
        for (var i = 0; i < leaves.length; i++){
            var text = normalizeLiveText(leaves[i].textContent || "");
            if (!text){ continue; }
            if (altName && (text.toLowerCase() === altName.toLowerCase())){ return text; }
            if (text.length > 80){ continue; }
            return text;
        }
        return altName;
    }

    function findLiveMessageLeaf(leaves, chatname){
        var placeholderLeaf = null;
        for (var i = leaves.length - 1; i >= 0; i--){
            var leaf = leaves[i];
            var text = normalizeLiveText(leaf.textContent || "");
            if (!text){ continue; }
            if (chatname && (text.toLowerCase() === chatname.toLowerCase())){ continue; }
            if (isLivePlaceholderText(text)){
                if (!placeholderLeaf){ placeholderLeaf = leaf; }
                continue;
            }
            return leaf;
        }
        return placeholderLeaf;
    }

    function rowFromImage(img){
        if (!img) return null;
        let current = img.parentNode;
        let bestRow = null;
        
        while (current && current !== document.body) {
            if (looksLikeRow(current)) {
                bestRow = current;
            } else if (bestRow) {
                break;
            }
            current = current.parentNode;
        }
        return bestRow;
    }

    function looksLikeRow(node){
        if (!node || !node.querySelector){ return false; }
        if (node.querySelector("header, video, footer")){ return false; }
        var pics = getProfileImgCandidates(node);
        if (pics.length !== 1){ return false; }
        return true;
    }

    function findLiveRows(targetSection){
        var rows = [];
        var seen = new Set();
        var scope = targetSection || document.querySelector("section");
        if (!scope) return rows;
        var imgs = scope.querySelectorAll("img[src]");
        for (var i = 0; i < imgs.length; i++){
            var row = rowFromImage(imgs[i]);
            if (!row){ continue; }
            if (seen.has(row)){ continue; }
            seen.add(row);
            if (!looksLikeRow(row)){ continue; }
            rows.push(row);
        }
        return rows;
    }

    function getAllContentNodes(element) {
        let resp = "";
        if (!element) return resp;
        if (!element.childNodes || !element.childNodes.length) {
            return element.textContent || "";
        }
        element.childNodes.forEach(node => {
            if (node.childNodes && node.childNodes.length) {
                resp += getAllContentNodes(node);
            } else if (node.nodeType === 3) {
                resp += node.textContent;
            } else if (node.nodeType === 1) {
                if (node.nodeName === "IMG") {
                    resp += node.getAttribute("alt") || "";
                } else {
                    resp += node.textContent || "";
                }
            }
        });
        return resp;
    }

    function parseLiveRow(row){
        if (!row || !row.querySelector){ return null; }

        var profileImg = getProfileImgFromRow(row) || row.querySelector("img[src]");
        var chatname = findLiveUsername(row, profileImg);
        var chatmessage = "";
        var messageLeaf = null;

        var fullText = normalizeLiveText(row.textContent || "");
        if (chatname && fullText.toLowerCase().indexOf(chatname.toLowerCase()) === 0){
            var rest = fullText.slice(chatname.length).trim();
            if (rest){
                var leaves = collectTextLeaves(row);
                var msgLeaf = findLiveMessageLeaf(leaves, chatname);
                messageLeaf = msgLeaf;
                chatmessage = msgLeaf ? getAllContentNodes(msgLeaf) : rest;
            }
        }

        if (!chatname || !chatmessage){
            var leavesFb = collectTextLeaves(row);
            if (leavesFb.length >= 2){
                if (!chatname){ chatname = leavesFb[0].textContent.trim(); }
                if (!chatmessage){
                    messageLeaf = findLiveMessageLeaf(leavesFb, chatname) || leavesFb[leavesFb.length - 1];
                    chatmessage = getAllContentNodes(messageLeaf);
                }
            } else if (leavesFb.length === 1){
                var text = leavesFb[0].textContent.trim();
                var split = text.indexOf(" ");
                if (split > 0){
                    if (!chatname){ chatname = text.slice(0, split).trim(); }
                    if (!chatmessage){
                        messageLeaf = leavesFb[0];
                        chatmessage = text.slice(split + 1).trim();
                    }
                }
            }
        }

        chatname = (chatname || "").replace(/[,:\s]+$/, "").trim();
        chatmessage = (chatmessage || "").trim();

        if (!chatname || !chatmessage){ return null; }
        if (chatname.toLowerCase() === chatmessage.toLowerCase()){ return null; }

        return {
            chatname: chatname,
            chatmessage: chatmessage
        };
    }

    // Calibração interativa de seletores inteligente (1 clique)
    calibrateBtn.addEventListener("click", () => {
        alert("Modo de calibração inteligente ativado!\nClique em QUALQUER COMENTÁRIO no chat da live.");
        
        const overlay = document.createElement("div");
        overlay.style.cssText = "position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.1);z-index:2147483647;cursor:crosshair;";
        document.body.appendChild(overlay);

        overlay.addEventListener("click", (e) => {
            e.stopPropagation();
            e.preventDefault();
            
            overlay.style.display = "none";
            const clickedElement = document.elementFromPoint(e.clientX, e.clientY);
            overlay.style.display = "block";

            if (!clickedElement) return;

            // Tentar primeiro calibrar usando o contêiner detectado automaticamente
            let containerElement = autoDetectContainer();

            // Se a detecção automática falhou ou o clique foi fora do contêiner
            if (!containerElement || !containerElement.contains(clickedElement)) {
                const structure = detectChatStructure(clickedElement);
                containerElement = structure.containerElement;
            }
            
            const containerSel = getContainerSelector(containerElement);
            selectors.container = containerSel;
            selectors.message = `${containerSel} > *`; // Usar seletor filho direto universal
            hasCalibrated = true;
            
            alert(`Calibração Inteligente Concluída!\n\n` +
                  `1. Contêiner detectado: ${selectors.container}\n` +
                  `2. Seletor de mensagens: ${selectors.message}`);
                  
            document.body.removeChild(overlay);
        });
    });

    function isValidUsername(val) {
        if (!val) return false;
        val = val.trim();
        if (val.startsWith('@')) val = val.substring(1);
        if (val.length < 2 || val.length > 30) return false;
        if (/\s/.test(val)) return false;
        if (!/^[a-zA-Z0-9_.]+$/.test(val)) return false;
        return true;
    }

    // Função para tratar novas mensagens
    function processNewMessageNode(node, chatContainer) {
        if (node.nodeType !== Node.ELEMENT_NODE) return;

        if (!chatContainer) chatContainer = document.querySelector(selectors.container);
        if (!chatContainer) return;

        // Se o próprio nó adicionado for o contêiner, processar todos os seus filhos
        if (node === chatContainer) {
            Array.from(chatContainer.children).forEach(child => processNewMessageNode(child));
            return;
        }

        // Subir a partir do nó adicionado até achar o elemento da mensagem (usando o seletor)
        let rowElement = node;
        let found = false;
        while (rowElement && rowElement !== chatContainer && rowElement !== document.body) {
            if (rowElement.matches && selectors.message && rowElement.matches(selectors.message)) {
                found = true;
                break;
            }
            rowElement = rowElement.parentNode;
        }

        // Fallback: se não encontrou pelo seletor de mensagem, usar o filho direto do contêiner
        if (!found) {
            rowElement = node;
            while (rowElement && rowElement.parentNode !== chatContainer && rowElement !== document.body) {
                rowElement = rowElement.parentNode;
            }
        }

        if (!rowElement || !chatContainer.contains(rowElement)) {
            return;
        }

        // Evitar processar o mesmo nó de comentário mais de uma vez
        if (rowElement.dataset.captured === "true") return;
        rowElement.dataset.captured = "true";

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
            
            getLeafTextNodes(rowElement);

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
            
            // Usar o seletor do username configurado se disponível
            if (selectors && selectors.username) {
                const userEl = rowElement.querySelector(selectors.username);
                if (userEl) {
                    const potentialUser = userEl.textContent.trim();
                    if (!isBadgeText(potentialUser)) {
                        user = potentialUser;
                    }
                }
            }
            
            // Seletor genérico fallback para username
            if (!user) {
                const userEl = rowElement.querySelector('a[href*="/"], span[class*="username"], .username, span[class*="nickname"], .webcast-chatroom__author-name, span[class*="author"]');
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

            // Validar username para evitar coletar cabeçalhos de sistema ou ruídos
            if (!isValidUsername(user)) {
                return;
            }

            // 3. Extrair a mensagem (todos os leafs que vêm APÓS o username)
            let text = "";
            if (user) {
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

            // Ignorar mensagens de sistema / eventos do Instagram Live
            const lowerText = text.toLowerCase();
            if (lowerText === 'entrou' || lowerText === 'joined' || lowerText.includes('acenou') || lowerText.includes('waved') || lowerText.includes('solicitou') || lowerText.includes('participar')) {
                console.log(`[Capturador] Mensagem de sistema ignorada: @${user} -> "${text}"`);
                return;
            }

            console.log(`[Capturador] Tentando processar - Usuário: "${user}", Texto: "${text}"`);

            if (user && text) {
                sendChatMessage(user, text);
            } else {
                console.warn("[Capturador] Não foi possível extrair usuário e texto válidos do elemento:", rowElement);
            }
        } catch (err) {
            console.error("Erro ao ler nó de chat: ", err);
        }
    }

    // Enviar mensagem para o Laravel
    async function sendChatMessage(username, message) {
        const msgKey = `${username}:${message}`;
        if (sentMessages.has(msgKey)) return; // Evitar duplicatas
        sentMessages.add(msgKey);

        const url = "https://minhamania.net";
        const liveId = "auto";

        try {
            const res = await bgFetch(`${url}/api/live-chat/message`, {
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
                log(`Erro Laravel: ${data.error || 'Rejeitado'}`, true);
            }
        } catch (e) {
            console.error("Erro ao enviar mensagem para o Laravel:", e);
            log(`Erro de rede: ${e.message}`, true);
        }
    }

    function scanAndProcessInstagramComments(targetSection) {
        const scope = document.body;

        // 0. Estratégia do Social Stream Ninja (sobreposição explícita / atributos de dados ou classes do Social Stream)
        const socialStreamRows = scope.querySelectorAll('[data-chatname], .chat-message, .social-stream-row, #chat-room > div, [class*="chat-item"]');
        socialStreamRows.forEach(row => {
            if (row.dataset.captured === "true") return;
            const userAttr = row.getAttribute("data-chatname") || row.querySelector("[data-chatname]")?.getAttribute("data-chatname");
            const msgAttr = row.getAttribute("data-chatmessage") || row.querySelector("[data-chatmessage]")?.getAttribute("data-chatmessage");
            if (userAttr && msgAttr && isValidUsername(userAttr)) {
                row.dataset.captured = "true";
                sendChatMessage(userAttr, msgAttr);
                return;
            }
        });

        // 1. Estratégia de linhas estruturadas (findLiveRows)
        findLiveRows(scope).forEach(row => {
            if (row.dataset.captured === "true") return;

            const parsed = parseLiveRow(row);
            if (parsed && parsed.chatname && parsed.chatmessage) {
                const user = parsed.chatname;
                const text = parsed.chatmessage;

                // Ignorar mensagens de sistema / eventos do Instagram Live
                const lowerText = text.toLowerCase();
                if (lowerText === 'entrou' || lowerText === 'joined' || lowerText.includes('acenou') || lowerText.includes('waved') || lowerText.includes('solicitou') || lowerText.includes('participar')) {
                    row.dataset.captured = "true";
                    return;
                }

                if (isValidUsername(user)) {
                    row.dataset.captured = "true";
                    sendChatMessage(user, text);
                }
            }
        });

        // 2. Estratégia direta por imagens de perfil e blocos DOM (à prova de falhas do Instagram / Producer)
        try {
            const imgs = scope.querySelectorAll("img[alt*='profile'], img[src*='s150x150'], img[src*='instagram'], img[src*='cdninstagram']");
            imgs.forEach(img => {
                let row = img.closest("div[class], section, li") || img.parentElement;
                for (let depth = 0; row && depth < 5; depth++) {
                    if (row.dataset.captured === "true") break;
                    const text = (row.textContent || "").trim();
                    if (text && text.length > 2 && text.length < 300) {
                        const leaves = row.querySelectorAll("span, div, a");
                        let username = "";
                        let message = "";
                        for (let i = 0; i < leaves.length; i++) {
                            let t = (leaves[i].textContent || "").trim();
                            if (!t || t.toLowerCase() === 'responder' || t.toLowerCase() === 'reply' || t.includes("entrou") || t.includes("joined") || t === "...") continue;
                            if (!username && t.length < 30 && !t.includes(" ")) {
                                username = t;
                            } else if (username && t !== username) {
                                message = t;
                                break;
                            }
                        }
                        if (username && message && isValidUsername(username)) {
                            const lowerMsg = message.toLowerCase();
                            if (lowerMsg !== 'entrou' && lowerMsg !== 'joined' && !lowerMsg.includes('acenou') && !lowerMsg.includes('participar')) {
                                row.dataset.captured = "true";
                                sendChatMessage(username.replace(/^@/, ''), message);
                                break;
                            }
                        }
                    }
                    row = row.parentElement;
                }
            });
        } catch (e) {}

        // 3. Estratégia de botões "Responder" / "Reply" (específica e super estável para Instagram Web)
        try {
            const replyBtns = scope.querySelectorAll('div, span, button, a');
            replyBtns.forEach(btn => {
                if (btn.children.length === 0 && (btn.textContent.trim().toLowerCase() === 'responder' || btn.textContent.trim().toLowerCase() === 'reply')) {
                    let commentContainer = btn.closest('li, div[role="row"], div[class*="comment"], div[class*="message"], div[class*="item"]') || btn.parentElement?.parentElement;
                    if (!commentContainer || commentContainer.dataset.captured === "true") return;

                    let fullText = (commentContainer.textContent || "").trim();
                    if (!fullText || fullText.length < 3) return;

                    fullText = fullText.replace(/responder$/i, "").replace(/reply$/i, "").trim();

                    const subEls = commentContainer.querySelectorAll('span, a, strong');
                    let username = "";
                    let message = "";

                    for (let i = 0; i < subEls.length; i++) {
                        let txt = (subEls[i].textContent || "").trim();
                        if (!txt || txt.toLowerCase() === 'responder' || txt.toLowerCase() === 'reply' || txt.includes("entrou") || txt === "...") continue;
                        if (!username && isValidUsername(txt)) {
                            username = txt;
                        } else if (username && txt !== username && !username.includes(txt)) {
                            message = txt;
                            break;
                        }
                    }

                    if (!message && username && fullText.toLowerCase().startsWith(username.toLowerCase())) {
                        message = fullText.slice(username.length).trim();
                    }

                    if (username && message && isValidUsername(username)) {
                        const lowerMsg = message.toLowerCase();
                        if (lowerMsg !== 'entrou' && lowerMsg !== 'joined' && !lowerMsg.includes('acenou') && !lowerMsg.includes('participar')) {
                            commentContainer.dataset.captured = "true";
                            sendChatMessage(username.replace(/^@/, ''), message);
                        }
                    }
                }
            });
        } catch (e) {}
    }

    function autoDetectContainer() {
        if (platform === "tiktok") {
            return document.querySelector(selectors.container);
        }

        // 1. Heurística de botões "Responder" / "Reply" (específica e super estável para Instagram)
        const replyEls = [];
        const candidates = document.querySelectorAll('div, span, button, a');
        for (const el of candidates) {
            if (el.children.length === 0) { // Apenas nós folhas
                const txt = el.textContent.trim().toLowerCase();
                if (txt === 'responder' || txt === 'reply') {
                    replyEls.push(el);
                }
            }
        }
        if (replyEls.length > 0) {
            // Achar o menor ancestral comum que contém pelo menos 2 botões responder
            if (replyEls.length === 1) {
                let parent = replyEls[0].parentElement;
                for (let i = 0; i < 6 && parent; i++) {
                    if (parent.scrollHeight > 150) return parent;
                    parent = parent.parentElement;
                }
            } else {
                let p1 = replyEls[0];
                const p2 = replyEls[replyEls.length - 1];
                const parents1 = new Set();
                while (p1) { parents1.add(p1); p1 = p1.parentElement; }
                let curr = p2;
                while (curr) {
                    if (parents1.has(curr) && curr !== document.body) {
                        return curr;
                    }
                    curr = curr.parentElement;
                }
            }
        }

        // 2. Fallback: contêiner com múltiplos elementos e rolagem (genérico)
        let bestCandidate = null;
        let maxChildren = 0;
        const allDivs = document.querySelectorAll("div, ul, section, main");
        
        for (const div of allDivs) {
            if (div === document.body || div.id === "live-chat-capture-ui") continue;
            const style = window.getComputedStyle(div);
            const isScrollable = (style.overflowY === 'auto' || style.overflowY === 'scroll' || div.scrollHeight > div.clientHeight + 20);
            if (isScrollable && div.clientHeight > 100 && div.clientHeight < window.innerHeight * 0.95) {
                const childCount = div.children.length;
                if (childCount > maxChildren) {
                    maxChildren = childCount;
                    bestCandidate = div;
                }
            }
        }
        return bestCandidate || document.body;
    }

    function getElementDepth(element) {
        let depth = 0;
        let curr = element;
        while (curr.parentElement) {
            depth++;
            curr = curr.parentElement;
        }
        return depth;
    }

    // Iniciar monitoramento
    function startCapture() {
        console.log("[Capturador] Iniciando captura...");
        
        if (platform === "instagram") {
            console.log("[Capturador] Usando estratégia do Social Stream Ninja + Producer para Instagram (document.body)...");
            
            isCapturing = true;
            log("Captura iniciada com sucesso!");

            // Executar varredura inicial
            scanAndProcessInstagramComments(document.body);

            // Observar mudanças no document.body inteiro
            let scanTimeout = null;
            observer = new MutationObserver(() => {
                if (!scanTimeout) {
                    scanTimeout = setTimeout(() => {
                        scanAndProcessInstagramComments(document.body);
                        scanTimeout = null;
                    }, 100);
                }
            });
            observer.observe(document.body, { 
                childList: true, 
                subtree: true,
                characterData: true
            });
            return;
        }

        // TikTok e fluxo padrão legados (já funcionais)
        let chatContainer = null;
        if (hasCalibrated) {
            console.log("[Capturador] Usando seletores calibrados manualmente...");
            chatContainer = document.querySelector(selectors.container);
        } else {
            console.log("[Capturador] Tentando seletores padrão...");
            chatContainer = document.querySelector(selectors.container);
            if (!chatContainer) {
                console.log("[Capturador] Seletores padrão falharam. Tentando auto-detecção inteligente...");
                const detected = autoDetectContainer();
                if (detected) {
                    chatContainer = detected;
                    console.log("[Capturador] Auto-detecção bem-sucedida!");
                } else {
                    console.log("[Capturador] Auto-detecção falhou.");
                }
            }
        }

        if (chatContainer) {
            chatContainer = findCommentsContainer(chatContainer);
            selectors.container = getContainerSelector(chatContainer);
            selectors.message = `${selectors.container} > *`; // Usar seletor filho direto universal
            console.log("[Capturador] Contêiner definitivo resolvido:", selectors.container);
            console.log("[Capturador] Seletor de mensagens:", selectors.message);
        }

        if (!chatContainer) {
            console.error("[Capturador] Contêiner do chat não pôde ser localizado pelo seletor:", selectors.container);
            log("Contêiner do chat não encontrado! Tente 'Calibrar'.", true);
            alert("Aba de chat não localizada! Certifique-se de que a live está aberta e o painel de chat visível.");
            return;
        }

        console.log("[Capturador] Contêiner encontrado:", chatContainer.tagName, chatContainer.className);
        console.log("[Capturador] Seletor de mensagens:", selectors.message);

        isCapturing = true;
        log("Captura iniciada com sucesso!");

        // Processar mensagens que já estão na tela
        let existingMessages = selectors.message ? Array.from(chatContainer.querySelectorAll(selectors.message)) : [];
        if (existingMessages.length === 0) {
            existingMessages = Array.from(chatContainer.children);
        }
        console.log(`[Capturador] Encontrados ${existingMessages.length} elementos de mensagem existentes.`);
        existingMessages.forEach((msg, idx) => {
            console.log(`[Capturador] Mensagem existente [${idx}]:`, msg.tagName, msg.className, "Texto:", msg.innerText);
            processNewMessageNode(msg, chatContainer);
        });

        // Observar novas mensagens
        observer = new MutationObserver((mutations) => {
            mutations.forEach(mutation => {
                mutation.addedNodes.forEach(node => {
                    processNewMessageNode(node, chatContainer);
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
        log("Captura parada.");
    }

    // Inicialização direta silenciosa
    setTimeout(startCapture, 2000);
})();
