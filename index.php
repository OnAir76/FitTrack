<!doctype html>
<html lang="pl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#0f172a">
<link rel="manifest" href="/trainingapp/public/manifest.json">
<link rel="stylesheet" href="/trainingapp/public/css/app.css">
<script src="https://cdn.tailwindcss.com"></script>
<title>FitTrack</title>
</head>
<body data-page="home" class="bg-slate-50 text-slate-900 antialiased">
<main class="mx-auto min-h-screen max-w-3xl px-4 pb-28 pt-6">
<div id="app"><div class="animate-pulse"><div class="h-8 w-40 rounded bg-slate-200"></div></div></div>
</main>
<nav class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/95 backdrop-blur">
<div class="mx-auto grid max-w-3xl grid-cols-5">
<a href="/trainingapp" class="flex flex-col items-center gap-1 px-2 py-3 text-xs font-bold">⌂<span>Home</span></a>
<a href="/trainingapp/calendar.php" class="flex flex-col items-center gap-1 px-2 py-3 text-xs text-slate-500">▦<span>Kalendarz</span></a>
<a href="/trainingapp/workouts.php" class="flex flex-col items-center gap-1 px-2 py-3 text-xs text-slate-500">◈<span>Treningi</span></a>
<a href="/trainingapp/progress.php" class="flex flex-col items-center gap-1 px-2 py-3 text-xs text-slate-500">↗<span>Progres</span></a>
<a href="/trainingapp/nutrition.php" class="flex flex-col items-center gap-1 px-2 py-3 text-xs text-slate-500">◉<span>Dieta</span></a>
</div></nav>
<script>if('serviceWorker' in navigator) navigator.serviceWorker.register('/trainingapp/public/service-worker.js');</script>
<script src="/trainingapp/public/js/app.js"></script>
</body>
</html>
