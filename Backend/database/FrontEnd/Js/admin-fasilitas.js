(() => {
 'use strict';
 const token = localStorage.getItem('admin_token') || sessionStorage.getItem('admin_token') || '';
 const $ = (id) => document.getElementById(id);
 const icons = ['fa-square-parking','fa-mosque','fa-restroom','fa-camera','fa-wifi'];
 const iconNames = {'fa-square-parking':'Area Parkir','fa-mosque':'Mushola','fa-restroom':'Toilet','fa-camera':'Spot foto','fa-wifi':'Wifi'};
 let facilities = [], editingId = null, currentPage = 1;
 const pageSize = 5;
 const modal = new bootstrap.Modal($('facilityModal'));
 const esc = (v) => String(v ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
 function toast(message, error=false) { const el=$('facilityToast'); el.textContent=message; el.classList.toggle('error',error); el.style.display='block'; clearTimeout(toast.timer); toast.timer=setTimeout(()=>el.style.display='none',3800); }
 function parseIcons(value) { if(!value) return []; return String(value).split(',').map(v=>v.trim()).filter(Boolean); }
 function iconMarkup(value) {
  const selected=parseIcons(value);
  const files={
   'fa-square-parking':'facility-parking.svg',
   'fa-mosque':'facility-mosque.svg',
   'fa-restroom':'facility-toilet.svg',
   'fa-camera':'facility-camera.svg',
   'fa-wifi':'facility-wifi.svg'
  };
  const names={
   'fa-square-parking':'Area Parkir',
   'fa-mosque':'Mushola',
   'fa-restroom':'Toilet',
   'fa-camera':'Spot Foto',
   'fa-wifi':'WiFi'
  };
  return `<div class="facility-entries">${selected.map(code=>files[code]?`<div class="facility-entry"><span class="facility-icon-chip"><img src="../assets/icons/${files[code]}" alt=""></span><span>${esc(names[code]||code)}</span></div>`:'').join('') || '<span>—</span>'}</div>`;
 }
 function render() {
  $('resourceHead').innerHTML='<tr><th>No</th><th>Nama Wisata</th><th>Nama Fasilitas</th><th>Aksi</th></tr>';
  const start=(currentPage-1)*pageSize, visible=facilities.slice(start,start+pageSize);
  $('resourceRows').innerHTML=visible.length?visible.map((r,i)=>`<tr><td class="facility-number">${start+i+1}</td><td class="facility-destination">${esc(r.tourism_name || '—')}</td><td class="facility-icons-cell">${iconMarkup(r.icon_code)}</td><td><div class="facility-actions"><button type="button" class="facility-action-button is-edit" data-edit="${esc(r.id)}" title="Edit" aria-label="Edit fasilitas"><img src="../assets/icons/action-edit.svg" alt=""></button><button type="button" class="facility-action-button is-delete" data-delete="${esc(r.id)}" title="Hapus" aria-label="Hapus fasilitas"><img src="../assets/icons/action-delete.svg" alt=""></button></div></td></tr>`).join(''):'<tr><td colspan="4" class="facility-empty">Belum ada data fasilitas.</td></tr>';
  $('facilityRange').textContent=`Menampilkan ${facilities.length?start+1:0}-${Math.min(start+pageSize,facilities.length)} dari ${facilities.length} data fasilitas`;
  const pages=Math.max(1,Math.ceil(facilities.length/pageSize)); let html=`<button class="facility-page-button" data-page="${Math.max(1,currentPage-1)}" aria-label="Sebelumnya"><i class="bi bi-chevron-left"></i></button>`;
  for(let p=1;p<=pages;p++) html+=`<button class="facility-page-button ${p===currentPage?'is-active':''}" data-page="${p}">${p}</button>`;
  html+=`<button class="facility-page-button" data-page="${Math.min(pages,currentPage+1)}" aria-label="Berikutnya"><i class="bi bi-chevron-right"></i></button>`; $('facilityPagination').innerHTML=html;
 }
 async function request(url, options={}) { const res=await fetch(url,{...options,headers:{Authorization:'Bearer '+token,Accept:'application/json','Content-Type':'application/json',...(options.headers||{})}}); let data={}; try{data=await res.json()}catch{} if(!res.ok) throw new Error(data.message||'Permintaan gagal diproses.'); return data; }
 async function load() { if(!token){location.replace(facilityConfig.loginUrl);return;} $('resourceMessage').textContent='Memuat data fasilitas...'; try { const result=await request(facilityConfig.apiUrl); facilities=Array.isArray(result.data)?result.data:[]; currentPage=Math.min(currentPage,Math.max(1,Math.ceil(facilities.length/pageSize))); $('resourceMessage').textContent=''; render(); } catch(e) { $('resourceMessage').textContent=''; document.querySelector('.facility-heading').style.display='none'; document.querySelector('.facility-section').style.display='none'; let state=$('facilityServerError'); if(!state){state=document.createElement('section');state.id='facilityServerError';state.className='facility-server-error';document.querySelector('.admin-content').appendChild(state);} state.innerHTML='<h2>Terjadi kesalahan pada server</h2><p>Maaf, server sedang bermasalah. Silahkan<br class="desktop-break"> coba beberapa saat lagi.</p><button type="button" id="retryFacility">Coba lagi</button>'; $('retryFacility').onclick=()=>{state.remove();document.querySelector('.facility-heading').style.display='flex';document.querySelector('.facility-section').style.display='block';load();}; } }
 function resetForm() { $('facilityForm').reset(); editingId=null; $('facilityModalTitle').textContent='Tambah Fasilitas'; $('facilitySaveButton').textContent='Simpan'; document.querySelectorAll('.facility-icon-option').forEach(b=>b.classList.remove('selected')); ['facilityNameError','facilityIconError','facilityDescriptionError'].forEach(id=>$(id).textContent=''); }
 function openForm(row=null) { resetForm(); if(row){editingId=row.id; $('facilityModalTitle').textContent='Edit Fasilitas'; $('facilitySaveButton').textContent='Simpan Perubahan'; $('facilityName').value=row.name||''; $('facilityDescription').value=row.description||''; parseIcons(row.icon_code).forEach(code=>document.querySelector(`.facility-icon-option[data-icon="${CSS.escape(code)}"]`)?.classList.add('selected'));} modal.show(); }
 document.querySelectorAll('.facility-icon-option').forEach(btn=>btn.addEventListener('click',()=>{btn.classList.toggle('selected');$('facilityIconError').textContent='';}));
 $('addFacilityButton').addEventListener('click',()=>openForm());
 $('facilityForm').addEventListener('submit',async e=>{e.preventDefault(); const name=$('facilityName').value.trim(),description=$('facilityDescription').value.trim(),selected=[...document.querySelectorAll('.facility-icon-option.selected')].map(b=>b.dataset.icon); let valid=true;
  $('facilityNameError').textContent='';$('facilityIconError').textContent='';$('facilityDescriptionError').textContent='';
  if(!name){$('facilityNameError').textContent='Nama fasilitas wajib diisi.';valid=false;} else if(name.length>50){$('facilityNameError').textContent='Nama fasilitas maksimal 50 karakter.';valid=false;} else if(facilities.some(f=>f.name.toLocaleLowerCase()===name.toLocaleLowerCase()&&String(f.id)!==String(editingId))){$('facilityNameError').textContent='Nama fasilitas sudah tersedia. Gunakan nama lain.';valid=false;}
  if(!selected.length){$('facilityIconError').textContent='Silakan pilih minimal satu ikon fasilitas.';valid=false;} if(!description){$('facilityDescriptionError').textContent='Keterangan wajib diisi.';valid=false;} if(!valid)return;
  const button=$('facilitySaveButton');button.disabled=true;button.textContent='Menyimpan...';try{const payload={name,icon_code:selected.join(','),description};await request(editingId?`${facilityConfig.apiUrl}?id=${encodeURIComponent(editingId)}`:facilityConfig.apiUrl,{method:editingId?'PUT':'POST',body:JSON.stringify(payload)});modal.hide();toast(editingId?'Fasilitas berhasil diperbarui.':'Fasilitas berhasil ditambahkan.');await load();}catch(err){toast(err.message||'Gagal menyimpan fasilitas.',true);}finally{button.disabled=false;button.textContent=editingId?'Simpan Perubahan':'Simpan';}
 });
 $('resourceRows').addEventListener('click',async e=>{const edit=e.target.closest('[data-edit]'),del=e.target.closest('[data-delete]');if(edit){const row=facilities.find(f=>String(f.id)===edit.dataset.edit);if(row)openForm(row);}if(del){const row=facilities.find(f=>String(f.id)===del.dataset.delete);if(!row)return;if(!confirm(`Hapus fasilitas "${row.name}"?`))return;try{await request(`${facilityConfig.apiUrl}?id=${encodeURIComponent(row.id)}`,{method:'DELETE'});toast('Fasilitas berhasil dihapus.');await load();}catch(err){toast(err.message||'Gagal menghapus fasilitas.',true);}}});
 $('facilityPagination').addEventListener('click',e=>{const btn=e.target.closest('[data-page]');if(!btn)return;currentPage=Number(btn.dataset.page);render();});
 load();
})();
