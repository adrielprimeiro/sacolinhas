// Background Service Worker para realizar requisições fora do escopo do CSP da página
chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
    if (request.action === "fetch") {
        const fetchOptions = {
            method: request.method || "GET",
            headers: request.headers || {}
        };

        if (request.body && (request.method === "POST" || request.method === "PUT")) {
            fetchOptions.body = typeof request.body === "string" ? request.body : JSON.stringify(request.body);
        }

        fetch(request.url, fetchOptions)
            .then(async response => {
                const contentType = response.headers.get("content-type");
                let data = null;
                if (contentType && contentType.includes("application/json")) {
                    data = await response.json();
                } else {
                    data = await response.text();
                }
                
                sendResponse({
                    success: true,
                    result: {
                        status: response.status,
                        data: data
                    }
                });
            })
            .catch(error => {
                console.warn("Erro no fetch em background:", error);
                sendResponse({
                    success: false,
                    error: error.message
                });
            });

        return true; // Indica que a resposta será enviada assincronamente
    }
});
