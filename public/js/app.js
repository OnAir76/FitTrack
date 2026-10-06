const API_BASE = '/trainingapp/api';

async function api(path, options = {}) {
    const response = await fetch(`${API_BASE}/${path.replace(/^\/+/, '')}`, {
        headers: {'Content-Type': 'application/json', ...(options.headers || {})},
        ...options
    });
    const data = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(data.error || 'Wystąpił błąd.');
    return data;
}

function escapeHtml(value) {
    return String(value ?? '').replaceAll('&','&amp;').replaceAll('<','&lt;')
        .replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'",'&#039;');
}

async function renderHome() {
    const root=document.querySelector('#app');
    if(!root) return;
    try {
        const [today, measurements, settings] = await Promise.all([
            api(`today?date=${new Date().toISOString().slice(0,10)}`),
            api('measurements').catch(()=>[]),
            api('settings')
        ]);

        const weight=measurements[0]?.weight_kg ?? '—';
        const goal=settings.goal_weight_kg ?? '—';

        let card='';
        if(today.status==='none') {
            card=`<section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <p class="text-sm font-semibold text-slate-500">DZISIAJ</p>
                <h2 class="mt-2 text-2xl font-bold">Brak zaplanowanego treningu</h2>
                <a href="/calendar.php" class="mt-5 inline-flex rounded-2xl bg-slate-900 px-5 py-3 font-bold text-white">Otwórz kalendarz</a>
            </section>`;
        } else if(today.status==='rest') {
            card=`<section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <p class="text-sm font-semibold text-slate-500">DZISIAJ</p>
                <h2 class="mt-2 text-2xl font-bold">Dzień odpoczynku</h2>
            </section>`;
        } else {
            card=`<section class="rounded-3xl bg-slate-900 p-6 text-white shadow-lg">
                <p class="text-sm font-semibold text-slate-400">DZISIAJ</p>
                <h2 class="mt-2 text-3xl font-black">${escapeHtml(today.workout_name)}</h2>
                <p class="mt-2 text-slate-400">${today.exercises.length} ćwiczeń</p>
                <a href="/workout.php?scheduled_id=${today.id}" class="mt-6 flex justify-center rounded-2xl bg-white px-5 py-4 font-bold text-slate-900">ROZPOCZNIJ TRENING</a>
            </section>`;
        }

        root.innerHTML=`<div class="space-y-6">
            <div><p class="text-sm font-semibold text-slate-500">FITTRACK</p><h1 class="mt-1 text-3xl font-black">Cześć 👋</h1></div>
            ${card}
            <div class="grid grid-cols-2 gap-3">
                <div class="rounded-3xl bg-white p-5 ring-1 ring-slate-200"><p class="text-sm text-slate-500">Masa</p><p class="mt-2 text-2xl font-black">${escapeHtml(weight)} kg</p><p class="text-xs text-slate-400">Cel ${escapeHtml(goal)} kg</p></div>
                <div class="rounded-3xl bg-white p-5 ring-1 ring-slate-200"><p class="text-sm text-slate-500">Kalorie</p><p class="mt-2 text-2xl font-black">${escapeHtml(settings.daily_calories ?? '—')}</p><p class="text-xs text-slate-400">kcal / dzień</p></div>
            </div>
        </div>`;
    } catch(e) {
        root.innerHTML=`<div class="rounded-3xl bg-red-50 p-6 text-red-700">Nie udało się pobrać danych.<br><small>${escapeHtml(e.message)}</small></div>`;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if(document.body.dataset.page==='home') renderHome();
});
