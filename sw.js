// Service Worker pour Gestionnaire de Dépenses
const CACHE_NAME = 'depenses-cache-v1';
const ASSETS = [
  '/',
  '/dashboard',
  '/index.php',
  '/manifest.json',
  '/icon.svg',
  '/icon.html',
  '/icons/icon-192x192.png',
  '/icons/icon-512x512.png',
  '/icons/maskable-icon.png',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css',
  'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js',
  'https://cdn.jsdelivr.net/npm/chart.js'
];

// Installation du Service Worker
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('Cache ouvert');
        return cache.addAll(ASSETS);
      })
      .then(() => self.skipWaiting())
  );
});

// Activation du Service Worker
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME) {
            console.log('Suppression de l\'ancien cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// Stratégie de cache : Network First avec fallback sur le cache
self.addEventListener('fetch', (event) => {
  // Ignorer les requêtes non GET
  if (event.request.method !== 'GET') return;
  
  // Ignorer les requêtes d'analyse ou de tracking
  if (event.request.url.includes('analytics') || event.request.url.includes('tracking')) return;
  
  event.respondWith(
    fetch(event.request)
      .then((networkResponse) => {
        // Si la requête réseau réussit, mettre à jour le cache
        if (networkResponse.ok) {
          const clonedResponse = networkResponse.clone();
          caches.open(CACHE_NAME)
            .then((cache) => {
              cache.put(event.request, clonedResponse);
            });
        }
        return networkResponse;
      })
      .catch(() => {
        // Si la requête réseau échoue, essayer de récupérer depuis le cache
        return caches.match(event.request)
          .then((cachedResponse) => {
            if (cachedResponse) {
              return cachedResponse;
            }
            
            // Si la ressource n'est pas dans le cache, essayer de servir une page offline
            if (event.request.headers.get('accept').includes('text/html')) {
              return caches.match('/offline.html');
            }
            
            // Sinon, retourner une erreur
            return new Response('Ressource non disponible hors ligne', {
              status: 503,
              statusText: 'Service Unavailable',
              headers: new Headers({
                'Content-Type': 'text/plain'
              })
            });
          });
      })
  );
});

// Gestion des notifications push
self.addEventListener('push', (event) => {
  if (!event.data) return;
  
  const data = event.data.json();
  const options = {
    body: data.body || 'Nouvelle notification',
    icon: '/icons/icon-192x192.png',
    badge: '/icons/badge-icon.png',
    vibrate: [100, 50, 100],
    data: {
      url: data.url || '/dashboard'
    }
  };
  
  event.waitUntil(
    self.registration.showNotification(data.title || 'Gestionnaire de Dépenses', options)
  );
});

// Gestion du clic sur les notifications
self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  
  event.waitUntil(
    clients.matchAll({ type: 'window' })
      .then((clientList) => {
        // Si une fenêtre est déjà ouverte, la focaliser
        for (const client of clientList) {
          if (client.url === event.notification.data.url && 'focus' in client) {
            return client.focus();
          }
        }
        // Sinon, ouvrir une nouvelle fenêtre
        if (clients.openWindow) {
          return clients.openWindow(event.notification.data.url);
        }
      })
  );
});

// Synchronisation en arrière-plan
self.addEventListener('sync', (event) => {
  if (event.tag === 'sync-transactions') {
    event.waitUntil(syncTransactions());
  }
});

// Fonction pour synchroniser les transactions
async function syncTransactions() {
  try {
    const db = await openDatabase();
    const pendingTransactions = await db.getAll('pending-transactions');
    
    if (pendingTransactions.length === 0) return;
    
    // Envoyer les transactions au serveur
    const responses = await Promise.all(
      pendingTransactions.map(async (transaction) => {
        try {
          const response = await fetch('/api/transactions', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify(transaction)
          });
          
          if (response.ok) {
            return { success: true, id: transaction.id };
          }
          return { success: false, id: transaction.id };
        } catch (error) {
          return { success: false, id: transaction.id };
        }
      })
    );
    
    // Supprimer les transactions synchronisées avec succès
    const successfulIds = responses
      .filter(r => r.success)
      .map(r => r.id);
    
    if (successfulIds.length > 0) {
      const tx = db.transaction('pending-transactions', 'readwrite');
      successfulIds.forEach(id => {
        tx.store.delete(id);
      });
      await tx.done;
    }
    
    // Notifier l'utilisateur
    if (successfulIds.length > 0) {
      self.registration.showNotification('Synchronisation terminée', {
        body: `${successfulIds.length} transaction(s) synchronisée(s)`,
        icon: '/icons/icon-192x192.png'
      });
    }
  } catch (error) {
    console.error('Erreur lors de la synchronisation:', error);
  }
}

// Fonction pour ouvrir la base de données IndexedDB
function openDatabase() {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open('depenses-db', 1);
    
    request.onupgradeneeded = (event) => {
      const db = event.target.result;
      if (!db.objectStoreNames.contains('pending-transactions')) {
        db.createObjectStore('pending-transactions', { keyPath: 'id' });
      }
    };
    
    request.onsuccess = (event) => {
      resolve(event.target.result);
    };
    
    request.onerror = (event) => {
      reject(event.target.error);
    };
  });
} 