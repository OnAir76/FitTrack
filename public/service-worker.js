const CACHE='fittrack-v1';
const SHELL=['/trainingapp/','/trainingapp/index.php','/trainingapp/public/css/app.css','/trainingapp/public/js/app.js','/trainingapp/public/manifest.json'];
self.addEventListener('install',e=>e.waitUntil(caches.open(CACHE).then(c=>c.addAll(SHELL))));
self.addEventListener('activate',e=>e.waitUntil(self.clients.claim()));
self.addEventListener('fetch',e=>{
    if(e.request.method!=='GET') return;
    e.respondWith(caches.match(e.request).then(cached=>cached||fetch(e.request).catch(()=>caches.match('/trainingapp/'))));
});
