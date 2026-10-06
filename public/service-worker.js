const CACHE='fittrack-v12';
const SHELL=['/trainingapp/','/trainingapp/index.php','/trainingapp/public/css/app.css','/trainingapp/public/js/app.js','/trainingapp/public/manifest.json'];
self.addEventListener('install',e=>e.waitUntil(caches.open(CACHE).then(c=>c.addAll(SHELL)).then(()=>self.skipWaiting())));
self.addEventListener('activate',e=>e.waitUntil(caches.keys().then(keys=>Promise.all(keys.filter(k=>k.startsWith('fittrack-')&&k!==CACHE).map(k=>caches.delete(k)))).then(()=>self.clients.claim())));
self.addEventListener('fetch',e=>{
 if(e.request.method!=='GET')return;
 const url=new URL(e.request.url);
 if(url.origin!==self.location.origin)return;
 if(url.pathname.startsWith('/trainingapp/api/')){e.respondWith(fetch(e.request));return}
 e.respondWith(caches.match(e.request).then(cached=>cached||fetch(e.request).then(response=>{if(response.ok&&url.pathname.startsWith('/trainingapp/')){const copy=response.clone();caches.open(CACHE).then(cache=>cache.put(e.request,copy))}return response}).catch(()=>caches.match('/trainingapp/'))));
});