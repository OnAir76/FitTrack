<!doctype html>
<html lang="pl">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="theme-color" content="#0f172a">
<link rel="manifest" href="/trainingapp/public/manifest.json"><script src="https://cdn.tailwindcss.com"></script><link rel="stylesheet" href="/trainingapp/public/css/app.css"><title>FitTrack — Dieta</title>
</head>
<body class="bg-slate-50 text-slate-900">
<main class="mx-auto max-w-3xl px-4 pb-28 pt-6 space-y-6">
<header><a class="text-sm font-bold text-slate-500" href="/trainingapp/">← FitTrack</a><h1 class="mt-2 text-3xl font-black">Dieta i makro</h1><p class="mt-1 text-slate-500">Wybierz dzień w kalendarzu, aby dodać lub edytować kalorie, makro, kroki i dane z zegarka.</p></header>
<section class="grid grid-cols-2 gap-3"><div class="rounded-2xl bg-white p-4 ring-1 ring-slate-200"><p class="text-sm text-slate-500">Cel kalorii</p><p id="goalCal" class="mt-1 text-2xl font-black">—</p></div><div class="rounded-2xl bg-white p-4 ring-1 ring-slate-200"><p class="text-sm text-slate-500">Cel białka</p><p id="goalProtein" class="mt-1 text-2xl font-black">—</p></div></section>
<section class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200 space-y-4">
<div><h2 class="text-xl font-bold">Kalendarz diety</h2><p class="text-sm text-slate-500">Wybierz datę. Dni z zapisanym wpisem są oznaczone kropką. Jeden wpis na dzień — możesz go później edytować.</p></div>
<div class="flex items-center justify-between gap-3"><button id="prevMonth" type="button" class="rounded-xl border px-4 py-2 font-bold" aria-label="Poprzedni miesiąc">←</button><h3 id="monthLabel" class="text-lg font-black capitalize"></h3><button id="nextMonth" type="button" class="rounded-xl border px-4 py-2 font-bold" aria-label="Następny miesiąc">→</button></div>
<div class="grid grid-cols-7 gap-1 text-center text-xs font-bold text-slate-500"><div>Pn</div><div>Wt</div><div>Śr</div><div>Cz</div><div>Pt</div><div>So</div><div>Nd</div></div><div id="calendarGrid" class="grid grid-cols-7 gap-1.5"></div>
<div class="flex flex-wrap gap-3 text-xs text-slate-500"><span>● Zapisany wpis</span><span class="text-emerald-700">■ Wybrany dzień</span></div>
</section>
<section class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200 space-y-4">
<div><h2 class="text-xl font-bold">Dzienny wpis</h2><p class="text-sm text-slate-500">Jeśli wpis dla wybranego dnia już istnieje, formularz wczyta jego dane i zapisze zmiany zamiast tworzyć drugi wpis.</p></div>
<div id="dayStatus" class="rounded-xl bg-slate-50 p-3 text-sm text-slate-600">Wybierz dzień…</div>
<form id="form" class="grid grid-cols-2 gap-3">
<label class="col-span-2 text-sm font-semibold">Wybrana data<input id="entryDate" name="entry_date" type="date" readonly required class="mt-1 w-full rounded-xl border bg-slate-100 p-3"></label>
<label class="text-sm font-semibold">Kalorie<input name="calories" type="number" min="0" required value="0" class="mt-1 w-full rounded-xl border p-3"></label>
<label class="text-sm font-semibold">Białko (g)<input name="protein_g" type="number" min="0" step="0.1" value="0" class="mt-1 w-full rounded-xl border p-3"></label>
<label class="text-sm font-semibold">Węglowodany (g)<input name="carbs_g" type="number" min="0" step="0.1" value="0" class="mt-1 w-full rounded-xl border p-3"></label>
<label class="text-sm font-semibold">Tłuszcze (g)<input name="fats_g" type="number" min="0" step="0.1" value="0" class="mt-1 w-full rounded-xl border p-3"></label>
<label class="text-sm font-semibold">Kroki<input name="steps" type="number" min="0" value="0" class="mt-1 w-full rounded-xl border p-3"></label>
<label class="text-sm font-semibold">Spalone kcal z zegarka<input name="burned_calories" type="number" min="0" value="0" class="mt-1 w-full rounded-xl border p-3"><span class="mt-1 block text-xs font-normal text-slate-500">Wpisuj konsekwentnie ten sam typ danych: kalorie całkowite albo aktywne.</span></label>
<label class="col-span-2 text-sm font-semibold">Notatka<textarea name="notes" rows="2" class="mt-1 w-full rounded-xl border p-3"></textarea></label>
<button id="saveButton" class="col-span-2 rounded-xl bg-slate-900 p-3 font-bold text-white">Zapisz dzień</button>
<button id="cancelEdit" type="button" class="col-span-2 hidden rounded-xl border p-3 font-bold">Wróć do dzisiejszego wpisu</button>
<p id="notice" class="col-span-2 text-sm"></p>
</form></section>
<section class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200 space-y-3"><div class="flex items-center justify-between gap-3"><h2 class="text-xl font-bold">Historia wpisów</h2><p id="historyCount" class="text-sm text-slate-500"></p></div><div id="history" class="space-y-3">Ładowanie…</div></section>
</main>
<nav class="fixed inset-x-0 bottom-0 z-40 border-t bg-white/95 backdrop-blur"><div class="mx-auto grid max-w-3xl grid-cols-5 text-center text-xs font-bold"><a class="p-3" href="/trainingapp/">⌂<br>Home</a><a class="p-3 text-slate-500" href="/trainingapp/calendar.php">▦<br>Kalendarz</a><a class="p-3 text-slate-500" href="/trainingapp/workouts.php">◈<br>Treningi</a><a class="p-3 text-slate-500" href="/trainingapp/progress.php">↗<br>Progres</a><a class="p-3" href="/trainingapp/nutrition.php">◉<br>Dieta</a></div></nav>
<script>
const API='/trainingapp/api';
const esc=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
async function api(p,o={}){const r=await fetch(API+'/'+p,{headers:{'Content-Type':'application/json'},...o});const d=await r.json().catch(()=>({}));if(!r.ok)throw Error(d.error||'Błąd API');return d}
const pad=n=>String(n).padStart(2,'0');
function localDate(d=new Date()){return d.getFullYear()+'-'+pad(d.getMonth()+1)+'-'+pad(d.getDate())}
const today=localDate();
let rows=[],selectedDate=today,calendarMonth=new Date(new Date().getFullYear(),new Date().getMonth(),1);
function dateRecord(date){return rows.find(x=>x.entry_date===date)||null}
function showError(e){const n=document.querySelector('#notice');n.textContent=e.message;n.className='col-span-2 text-sm text-red-600'}
function setFormRecord(date,record=dateRecord(date)){
 selectedDate=date;document.querySelector('#entryDate').value=date;
 for(const k of ['calories','protein_g','carbs_g','fats_g','steps','burned_calories','notes'])document.querySelector('[name="'+k+'"]').value=record?.[k]??(k==='notes'?'':0);
 const future=date>today;document.querySelector('#saveButton').textContent=record?'Zapisz zmiany':'Zapisz dzień';document.querySelector('#saveButton').disabled=future;document.querySelector('#saveButton').classList.toggle('opacity-50',future);document.querySelector('#cancelEdit').classList.toggle('hidden',date===today);
 const status=document.querySelector('#dayStatus');status.textContent=record?'Wpis dla '+date+' jest już zapisany. Możesz edytować wartości — nie powstanie drugi wpis.':(future?'Nie można zapisywać danych z przyszłych dat.':'Brak wpisu dla '+date+'. Wypełnij formularz, aby zapisać dane.');
 status.className='rounded-xl p-3 text-sm '+(record?'bg-emerald-50 text-emerald-800':future?'bg-amber-50 text-amber-800':'bg-slate-50 text-slate-600');
 document.querySelector('#notice').textContent='';renderCalendar();
}
function renderCalendar(){
 const y=calendarMonth.getFullYear(),m=calendarMonth.getMonth();document.querySelector('#monthLabel').textContent=calendarMonth.toLocaleDateString('pl-PL',{month:'long',year:'numeric'});
 const first=new Date(y,m,1),offset=(first.getDay()+6)%7,days=new Date(y,m+1,0).getDate(),grid=document.querySelector('#calendarGrid');let html='';
 for(let i=0;i<offset;i++)html+='<div class="min-h-12"></div>';
 for(let day=1;day<=days;day++){const date=y+'-'+pad(m+1)+'-'+pad(day),rec=dateRecord(date),future=date>today,selected=date===selectedDate;html+='<button type="button" data-date="'+date+'" '+(future?'disabled':'')+' class="relative flex min-h-12 flex-col items-center justify-center rounded-xl border text-sm font-bold transition '+(selected?'border-emerald-700 bg-emerald-700 text-white shadow':'border-slate-100 bg-white hover:border-slate-300')+(future?' cursor-not-allowed opacity-30':'')+'">'+day+(rec?'<span class="absolute bottom-1 h-1.5 w-1.5 rounded-full '+(selected?'bg-white':'bg-emerald-500')+'"></span>':'')+'</button>'}
 grid.innerHTML=html;grid.querySelectorAll('button[data-date]').forEach(b=>b.onclick=()=>setFormRecord(b.dataset.date));
 const currentMonth=new Date(today.slice(0,7)+'-01T12:00:00');document.querySelector('#nextMonth').disabled=calendarMonth>=currentMonth;document.querySelector('#nextMonth').classList.toggle('opacity-40',calendarMonth>=currentMonth);
}
function renderHistory(){
 const root=document.querySelector('#history');document.querySelector('#historyCount').textContent=rows.length+' dni';
 root.innerHTML=rows.slice().reverse().map(x=>'<article class="rounded-2xl border p-4"><div class="flex items-center justify-between gap-2"><h3 class="font-bold">'+esc(x.entry_date)+'</h3><button type="button" class="edit rounded-lg border px-3 py-1 text-sm" data-date="'+x.entry_date+'">Edytuj</button></div><p class="mt-2 text-lg font-black">'+Number(x.calories||0).toLocaleString('pl-PL')+' kcal</p><p class="text-sm text-slate-500">Białko '+Number(x.protein_g||0)+' g · Węglowodany '+Number(x.carbs_g||0)+' g · Tłuszcze '+Number(x.fats_g||0)+' g</p><p class="text-sm text-slate-500">Kroki: '+(x.steps??'—')+(Number(x.burned_calories)>0?' · Spalone: '+x.burned_calories+' kcal':'')+'</p>'+(x.notes?'<p class="mt-2 text-sm">'+esc(x.notes)+'</p>':'')+'</article>').join('')||'<p class="text-slate-500">Brak wpisów w tym miesiącu.</p>';
 root.querySelectorAll('.edit').forEach(b=>b.onclick=()=>{setFormRecord(b.dataset.date);window.scrollTo({top:0,behavior:'smooth'})});
}
async function loadMonth(){
 const settings=await api('settings');document.querySelector('#goalCal').textContent=(settings.daily_calories||2500)+' kcal';document.querySelector('#goalProtein').textContent=(settings.daily_protein_g||190)+' g';
 const month=calendarMonth.getFullYear()+'-'+pad(calendarMonth.getMonth()+1),[y,m]=month.split('-').map(Number);
 rows=await api('nutrition?from='+month+'-01&to='+month+'-'+pad(new Date(y,m,0).getDate()));
 renderHistory();renderCalendar();setFormRecord(selectedDate,dateRecord(selectedDate));
}
document.querySelector('#prevMonth').onclick=async()=>{calendarMonth=new Date(calendarMonth.getFullYear(),calendarMonth.getMonth()-1,1);selectedDate=calendarMonth.getFullYear()+'-'+pad(calendarMonth.getMonth()+1)+'-01';await loadMonth()};
document.querySelector('#nextMonth').onclick=async()=>{const next=new Date(calendarMonth.getFullYear(),calendarMonth.getMonth()+1,1),max=new Date(today.slice(0,7)+'-01T12:00:00');if(next<=max){calendarMonth=next;selectedDate=next.getFullYear()+'-'+pad(next.getMonth()+1)+'-01';if(selectedDate.slice(0,7)===today.slice(0,7))selectedDate=today;await loadMonth()}};
document.querySelector('#cancelEdit').onclick=async()=>{selectedDate=today;calendarMonth=new Date(new Date().getFullYear(),new Date().getMonth(),1);await loadMonth();window.scrollTo({top:0,behavior:'smooth'})};
document.querySelector('#form').onsubmit=async e=>{e.preventDefault();const f=new FormData(e.target),body=Object.fromEntries(f.entries());for(const k of ['calories','protein_g','carbs_g','fats_g','steps','burned_calories'])body[k]=body[k]===''?null:Number(body[k]);try{const result=await api('nutrition',{method:'POST',body:JSON.stringify(body)});document.querySelector('#notice').textContent=result.updated?'Zmiany zostały zapisane.':'Wpis został zapisany.';document.querySelector('#notice').className='col-span-2 text-sm text-emerald-700';await loadMonth();}catch(err){showError(err)}};
loadMonth().catch(showError);
</script></body></html>