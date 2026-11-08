/**
 * Minha Mania - Service Worker
 * Progressive Web App (PWA)
 */

const CACHE_NAME = 'minhamania-pwa-v1';
const OFFLINE_URL = '/offline.html';

const STATIC_PRECACHE = [
  OFFLINE_URL,
  '/manifest.json',
  '/favicon.ico',
  '/favicon-32x32.png',
  '/favicon-16x16.png',
  '/apple-touch-icon.png',
  '/android-chrome-192x192.png',
  '/android-chrome-512x512.png'
];

// Instalação do Service Worker e pré-cache de recursos vitais
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(STATIC_PRECACHE);
    }).then(() => {
      return self.skipWaiting();
    })
  );
});

// Ativação e limpeza de versões antigas de cache
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((name) => {
          if (name !== CACHE_NAME) {
            return caches.delete(name);
          }
        })
      );
    }).then(() => {
      return self.clients.claim();
    })
  );
});

// Interceptação de requisições
self.addEventListener('fetch', (event) => {
  const request = event.request;
  const url = new URL(request.url);

  // Apenas requisições GET são cacheadas
  if (request.method !== 'GET') {
    return;
  }

  // Não interceptar requisições para rotas administrativas, logout ou extensões
  if (
    url.pathname.startsWith('/admin') ||
    url.pathname.startsWith('/live-admin') ||
    url.pathname.startsWith('/telescope') ||
    url.pathname === '/logout' ||
    url.protocol === 'chrome-extension:'
  ) {
    return;
  }

  // 1. Navegação de páginas HTML (Network First com Fallback para Offline)
  if (request.mode === 'navigate' || request.headers.get('accept')?.includes('text/html')) {
    event.respondWith(
      fetch(request)
        .then((response) => {
          // Se obteve resposta válida, clona e atualiza no cache para navegação offline futura
          if (response && response.status === 200) {
            const responseClone = response.clone();
            caches.open(CACHE_NAME).then((cache) => {
              cache.put(request, responseClone);
            });
          }
          return response;
        })
        .catch(async () => {
          // Se falhou (sem internet), tenta servir a página em cache
          const cachedResponse = await caches.match(request);
          if (cachedResponse) {
            return cachedResponse;
          }
          // Se não houver em cache, exibe a página offline elegante
          const offlinePage = await caches.match(OFFLINE_URL);
          if (offlinePage) {
            return offlinePage;
          }
          return new Response('Você está offline.', {
            headers: { 'Content-Type': 'text/plain; charset=utf-8' }
          });
        })
    );
    return;
  }

  // 2. Assets estáticos (Imagens, Fontes, CSS, JS, Ícones) -> Stale-While-Revalidate
  const isStaticAsset =
    url.pathname.match(/\.(css|js|woff2?|ttf|png|jpe?g|gif|svg|ico|webp)$/i) ||
    url.hostname.includes('cdn.jsdelivr.net') ||
    url.hostname.includes('cdnjs.cloudflare.com') ||
    url.hostname.includes('fonts.googleapis.com') ||
    url.hostname.includes('fonts.gstatic.com');

  if (isStaticAsset) {
    event.respondWith(
      caches.match(request).then((cachedResponse) => {
        const fetchPromise = fetch(request)
          .then((networkResponse) => {
            if (networkResponse && networkResponse.status === 200) {
              const responseToCache = networkResponse.clone();
              caches.open(CACHE_NAME).then((cache) => {
                cache.put(request, responseToCache);
              });
            }
            return networkResponse;
          })
          .catch(() => {
            // Em caso de erro de rede, apenas ignora pois responderemos o cache se houver
          });

        return cachedResponse || fetchPromise;
      })
    );
    return;
  }

  // Comportamento padrão para outras requisições GET
  event.respondWith(
    caches.match(request).then((cached) => {
      return cached || fetch(request);
    })
  );
});
