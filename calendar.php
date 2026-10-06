<!doctype html>
<html lang="pl">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="theme-color" content="#0f172a">
<link rel="manifest" href="/trainingapp/public/manifest.json"><script src="https://cdn.tailwindcss.com"></script><link rel="stylesheet" href="/trainingapp/public/css/app.css"><title>FitTrack — Kalendarz</title>
</head>
<body class="bg-slate-50 text-slate-900">
<main class="mx-auto max-w-3xl px-4 pb-28 pt-6 space-y-6">
<header><a class="text-sm font-bold text-slate-500" href="/trainingapp/">← FitTrack</a><h1 class="mt-2 text-3xl font-black">Kalendarz</h1><p class="mt-1 text-slate-500">Wybierz dzień, aby zaplanować trening albo odpoczynek. Kliknij istniejący wpis, aby go edytować.</p></header>
<section class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200 space-y-4">
<div><h2 class="text-xl font-bold">Kalendarz treningów</h2><p class="text-sm text-slate-500">Pod datą zobaczysz nazwę planu albo informację „Odpoczynek”.</p></div>
<div class="flex items-center justify-between gap-3"><button id="prevMonth" type="button" class="rounded-xl border px-4 py-2 font-bold" aria-label="Poprzedni miesiąc">←</button><h3 id="monthLabel" class="text-lg font-black capitalize"></h3><button id="nextMonth" type="button" class="rounded-xl border px-4 py-2 font-bold" aria-label="Następny miesiąc">→</button></div>
<div class="grid grid-cols-7 gap-1 text-center text-xs font-bold text-slate-500"><div>Pn</div><div>Wt</div><div>Śr</div><div>Cz</div><div>Pt</div><div>So</div><div>Nd</div></div>
<div id="calendarGrid" class="grid grid-cols-7 gap-1.5"></div>
<div class="flex flex-wrap gap-3 text-xs text-slate-500"><span>● Zaplanowany dzień</span><span class="text-emerald-700">■ Wybrany dzień</span></div>
</section>
<section class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200 space-y-4">
<div><h2 class="text-xl font-bold">Ustaw wybrany dzień</h2><p class="text-sm text-slate-500">Jeden wpis na dzień. Zapisanie zmian aktualizuje istniejący wpis.</p></div>
<div id="dayStatus" class="rounded-xl bg-slate-50 p-3 text-sm text-slate-600">Wybierz dzień w kalendarzu.</div>
<form id="planForm" class="space-y-3">
<label class="block text-sm font-semibold">Data<input class="mt-1 w-full rounded-xl border bg-slate-100 p-3" type="date" name="scheduled_date" id="scheduledDate" readonly required></label>
<label class="block text-sm font-semibold">Typ dnia<select class="mt-1 w-full rounded-xl border p-3" name="status" id="dayType"><option value="planned">Trening zaplanowany</option><option value="rest">Odpoczynek</option></select></label>
<label id="templateField" class="block text-sm font-semibold">Plan treningowy<select class="mt-1 w-full rounded-xl border p-3" id="templateSelect" name="workout_template_id"><option value="">Wybierz plan treningowy</option></select></label>
<label class="block text-sm font-semibold">Notatka<textarea class="mt-1 w-full rounded-xl border p-3" name="notes" rows="2" placeholder="Opcjonalnie"></textarea></label>
<button id="saveButton" class="w-full rounded-xl bg-slate-900 p-3 font-bold text-white">Zapisz dzień</button>
<button id="cancelEdit" type="button" class="hidden w-full rounded-xl border p-3 font-bold">Wyczyść formularz</button>
<p id="notice" class="text-sm"></p>
</form>
</section>
</main>
<nav class="fixed inset-x-0 bottom-0 z-40 border-t bg-white/95 backdrop-blur"><div class="mx-auto grid max-w-3xl grid-cols-5 text-center text-xs font-bold"><a class="p-3" href="/trainingapp/">⌂<br>Home</a><a class="p-3 text-slate-900" href="/trainingapp/calendar.php">▦<br>Kalendarz</a><a class="p-3 text-slate-500" href="/trainingapp/workouts.php">◈<br>Treningi</a><a class="p-3 text-slate-500" href="/trainingapp/progress.php">↗<br>Progres</a><a class="p-3 text-slate-500" href="/trainingapp/nutrition.php">◉<br>Dieta</a></div></nav>
<script>
const API='/trainingapp/api';
const esc=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
async function api(path,opts={}){const r=await fetch(API+'/'+path,{headers:{'Content-Type':'application/json'},...opts});const d=await r.json().catch(()=>({}));if(!r.ok)throw Error(d.error||'Błąd API');return d}
const pad=n=>String(n).padStart(2,'0');
function localDate(d=new Date()){return d.getFullYear()+'-'+pad(d.getMonth()+1)+'-'+pad(d.getDate())}
const today=localDate();
let templates=[],entries=[],selectedDate=today,calendarMonth=new Date(new Date().getFullYear(),new Date().getMonth(),1);
function recordFor(date){return entries.find(x=>x.scheduled_date===date)||null}
function labelFor(x){if(!x)return '';if(x.status==='rest')return 'Odpoczynek';return x.workout_name||'Trening'}
function showError(e){const n=document.querySelector('#notice');n.textContent=e.message;n.className='text-sm text-red-600'}
function setForm(date,record=recordFor(date)){
 selectedDate=date;document.querySelector('#scheduledDate').value=date;
 document.querySelector('#dayType').value=record?.status==='rest'?'rest':'planned';
 document.querySelector('#templateSelect').value=record?.workout_template_id??'';
 document.querySelector('[name=notes]').value=record?.notes??'';
 updateTypeField();
 const status=document.querySelector('#dayStatus');
 status.textContent=record?'Wpis dla '+date+' jest zapisany ('+labelFor(record)+'). Możesz zmienić typ dnia lub plan.': 'Brak wpisu dla '+date+'. Wybierz odpoczynek albo zaplanuj trening.';
 status.className='rounded-xl p-3 text-sm '+(record?'bg-emerald-50 text-emerald-800':'bg-slate-50 text-slate-600');
 document.querySelector('#saveButton').textContent=record?'Zapisz zmiany':'Zapisz dzień';
 document.querySelector('#cancelEdit').classList.toggle('hidden',!record);
 document.querySelector('#notice').textContent='';
 renderCalendar();
}
function updateTypeField(){
 const rest=document.querySelector('#dayType').value==='rest';
 document.querySelector('#templateField').classList.toggle('hidden',rest);
 document.querySelector('#templateSelect').disabled=rest;
 if(rest)document.querySelector('#templateSelect').value='';
}
function renderCalendar(){
 const y=calendarMonth.getFullYear(),m=calendarMonth.getMonth();
 document.querySelector('#monthLabel').textContent=calendarMonth.toLocaleDateString('pl-PL',{month:'long',year:'numeric'});
 const first=new Date(y,m,1),offset=(first.getDay()+6)%7,days=new Date(y,m+1,0).getDate(),grid=document.querySelector('#calendarGrid');let html='';
 for(let i=0;i<offset;i++)html+='<div class="min-h-16"></div>';
 for(let day=1;day<=days;day++){
  const date=y+'-'+pad(m+1)+'-'+pad(day),rec=recordFor(date),selected=date===selectedDate,label=labelFor(rec);
  html+='<button type="button" data-date="'+date+'" class="flex min-h-16 min-w-0 flex-col items-center justify-start rounded-xl border px-0.5 py-1.5 text-sm transition '+(selected?'border-emerald-700 bg-emerald-700 text-white shadow':'border-slate-100 bg-white hover:border-slate-300')+'"><span class="font-bold">'+day+'</span><span class="mt-1 w-full truncate text-center text-[9px] leading-tight '+(selected?'text-white':'text-slate-500')+'" title="'+esc(label)+'">'+(label?esc(label):'&nbsp;')+'</span>'+(rec?'<span class="mt-0.5 text-[8px] '+(selected?'text-white':'text-emerald-600')+'">●</span>':'')+'</button>';
 }
 grid.innerHTML=html;
 grid.querySelectorAll('button[data-date]').forEach(b=>b.onclick=()=>{setForm(b.dataset.date);document.querySelector('#planForm').scrollIntoView({behavior:'smooth',block:'start'})});
}
async function loadMonth(){
 const month=calendarMonth.getFullYear()+'-'+pad(calendarMonth.getMonth()+1),[y,m]=month.split('-').map(Number);
 const from=month+'-01',to=month+'-'+pad(new Date(y,m,0).getDate());
 entries=await api('calendar?from='+from+'&to='+to);
 renderCalendar();
 setForm(selectedDate,recordFor(selectedDate));
}
document.querySelector('#prevMonth').onclick=async()=>{calendarMonth=new Date(calendarMonth.getFullYear(),calendarMonth.getMonth()-1,1);selectedDate=calendarMonth.getFullYear()+'-'+pad(calendarMonth.getMonth()+1)+'-01';await loadMonth().catch(showError)};
document.querySelector('#nextMonth').onclick=async()=>{calendarMonth=new Date(calendarMonth.getFullYear(),calendarMonth.getMonth()+1,1);selectedDate=calendarMonth.getFullYear()+'-'+pad(calendarMonth.getMonth()+1)+'-01';await loadMonth().catch(showError)};
document.querySelector('#dayType').onchange=updateTypeField;
document.querySelector('#cancelEdit').onclick=()=>{setForm(selectedDate,null);document.querySelector('#planForm').scrollIntoView({behavior:'smooth',block:'start'})};
document.querySelector('#planForm').onsubmit=async e=>{
 e.preventDefault();
 const f=new FormData(e.target),status=f.get('status'),templateId=status==='rest'?'':(f.get('workout_template_id')||'');
 if(status==='planned'&&!templateId){showError(new Error('Wybierz plan treningowy dla zaplanowanego treningu.'));return}
 const body={scheduled_date:selectedDate,status,workout_template_id:templateId||null,notes:f.get('notes')||''};
 try{await api('calendar',{method:'POST',body:JSON.stringify(body)});await loadMonth();document.querySelector('#notice').textContent='Zmiany zostały zapisane.';document.querySelector('#notice').className='text-sm text-emerald-700'}catch(err){showError(err)}
};
(async()=>{try{templates=await api('workout-templates');document.querySelector('#templateSelect').innerHTML='<option value="">Wybierz plan treningowy</option>'+templates.map(t=>'<option value="'+t.id+'">'+esc(t.name)+'</option>').join('');await loadMonth()}catch(e){showError(e);document.querySelector('#calendarGrid').innerHTML='<p class="col-span-7 text-sm text-red-600">Nie udało się pobrać kalendarza. Sprawdź połączenie z bazą danych.</p>'}})();
</script></body></html>