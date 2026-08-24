'use strict';

/**
 * QR Inventory System — Service Worker
 *
 * Cache strategy:
 * - Cache-first: static assets (CSS, JS, icons, fonts, safe images)
 * - Network-first: HTML navigation and dynamic/authenticated content
 * - Never persist authenticated HTML or API responses
 *
 * Production requires HTTPS for service worker registration.
 * localhost is treated as a secure context for development.
 */

const CACHE_VERSION = 'qr-system-v1';
const STATIC_CACHE = `${CACHE_VERSION}-static`;
const RUNTIME_CACHE = `${CACHE_VERSION}-runtime`;

/** Application shell — safe to precache (no user-specific data). */
const PRECACHE_URLS = [
  '/offline.html',
  '/css/app.css',
  '/css/landing.css',
  '/js/app.js',
  '/js/navigation.js',
  '/js/landing.js',
  '/js/pwa.js',
  '/favicon.png',
  '/icons/icon-192.png',
  '/icons/icon-512.png',
  '/icons/apple-touch-icon.png',
];

const STATIC_PATH_PREFIXES = ['/css/', '/js/', '/icons/', '/fonts/'];
const STATIC_EXTENSIONS = ['.css', '.js', '.png', '.jpg', '.jpeg', '.webp', '.svg', '.woff', '.woff2', '.ttf', '.ico'];

/** Paths that must always use the network (never cache responses). */
const NETWORK_ONLY_PREFIXES = [
  '/login',
  '/logout',
  '/station/',
  '/employee/login',
  '/employee/logout',
  '/api/',
];

/** Future push notification hook — register when FCM/backend is configured. */
self.addEventListener('push', (event) => {
  if (!event.data) return;
  // Placeholder: integrate with Firebase Cloud Messaging or Web Push when configured.
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const target = event.notification.data?.url || '/';
  event.waitUntil(clients.openWindow(target));
});

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches
      .open(STATIC_CACHE)
      .then((cache) => cache.addAll(PRECACHE_URLS))
  );
  // Do not skipWaiting here — wait for user confirmation via pwa.js
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches
      .keys()
      .then((keys) =>
        Promise.all(
          keys
            .filter((key) => key.startsWith('qr-system-') && key !== STATIC_CACHE && key !== RUNTIME_CACHE)
            .map((key) => caches.delete(key))
        )
      )
      .then(() => self.clients.claim())
  );
});

self.addEventListener('message', (event) => {
  if (event.data?.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
  if (event.data?.type === 'CLEAR_RUNTIME_CACHE') {
    event.waitUntil(caches.delete(RUNTIME_CACHE));
  }
});

function isStaticAsset(url) {
  const pathname = new URL(url).pathname;
  if (STATIC_PATH_PREFIXES.some((prefix) => pathname.startsWith(prefix))) return true;
  return STATIC_EXTENSIONS.some((ext) => pathname.endsWith(ext));
}

function isNetworkOnly(url, request) {
  const pathname = new URL(url).pathname;
  if (NETWORK_ONLY_PREFIXES.some((prefix) => pathname.startsWith(prefix))) return true;
  if (request.method !== 'GET') return true;
  // PJAX partial navigation — always fetch fresh content
  if (request.headers.get('X-App-Navigation') === 'true') return true;
  // JSON/API requests
  const accept = request.headers.get('Accept') || '';
  if (accept.includes('application/json') && !accept.includes('text/html')) return true;
  return false;
}

function isNavigationRequest(request) {
  return request.mode === 'navigate' || (request.method === 'GET' && request.headers.get('Accept')?.includes('text/html'));
}

async function cacheFirst(request) {
  const cached = await caches.match(request);
  if (cached) return cached;

  try {
    const response = await fetch(request);
    if (response.ok && response.type === 'basic') {
      const cache = await caches.open(RUNTIME_CACHE);
      cache.put(request, response.clone());
    }
    return response;
  } catch (error) {
    const fallback = await caches.match(request);
    if (fallback) return fallback;
    throw error;
  }
}

async function networkFirstNavigation(request) {
  try {
    const response = await fetch(request);
    // Do not cache HTML navigation responses — prevents stale/sensitive page exposure
    return response;
  } catch (error) {
    const offline = await caches.match('/offline.html');
    if (offline) return offline;
    throw error;
  }
}

async function networkFirst(request) {
  try {
    const response = await fetch(request);
    return response;
  } catch (error) {
    if (isStaticAsset(request.url)) {
      const cached = await caches.match(request);
      if (cached) return cached;
    }
    throw error;
  }
}

self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = request.url;

  if (url.startsWith('chrome-extension://') || url.startsWith('moz-extension://')) return;
  if (request.method !== 'GET') return;

  // Skip cross-origin except Google Fonts (cache-first when fetched)
  const origin = self.location.origin;
  const reqOrigin = new URL(url).origin;
  if (reqOrigin !== origin) {
    if (url.includes('fonts.googleapis.com') || url.includes('fonts.gstatic.com')) {
      event.respondWith(cacheFirst(request));
    }
    return;
  }

  if (isNetworkOnly(url, request)) {
    event.respondWith(networkFirst(request));
    return;
  }

  if (isNavigationRequest(request)) {
    event.respondWith(networkFirstNavigation(request));
    return;
  }

  if (isStaticAsset(url)) {
    event.respondWith(cacheFirst(request));
    return;
  }

  // Default: network-first for everything else (dynamic Laravel routes)
  event.respondWith(networkFirst(request));
});
