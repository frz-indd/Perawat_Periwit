// facility-autocomplete.js
// Autocomplete for facility name input in admin modal.
// Shows matching wisata names from the server when admin types ≥2 characters.

document.addEventListener('DOMContentLoaded', function () {
  const nameInput = document.getElementById('facilityName');
  const dataList = document.getElementById('facilityNames');
  const addBtn = document.getElementById('addFacilityButton');
  if (!nameInput || !dataList) return;

  let timer;
  let allFacilities = [];
  let loaded = false;

  async function loadFacilities() {
    try {
      // use existing facilityRequest to get facilities with auth headers
      const result = await facilityRequest();
      allFacilities = result.data || [];
      loaded = true;
    } catch (e) {
      console.error('Failed to load facilities for autocomplete', e);
    }
  }

  function populate() {
    const term = nameInput.value.trim().toLowerCase();
    const matches = allFacilities.filter(f => {
      const name = (f.name || f.title || '').toString().toLowerCase();
      return name.includes(term);
    });
    dataList.innerHTML = matches.map(f => `<option value="${f.name || f.title || ''}"></option>`).join('');
  }

  nameInput.addEventListener('input', () => {
    const term = nameInput.value.trim();
    if (term.length < 2) {
      dataList.innerHTML = '';
      return;
    }
    clearTimeout(timer);
    timer = setTimeout(() => {
      if (!loaded) {
        loadFacilities().then(populate);
      } else {
        populate();
      }
    }, 300);
  });

  // preload when modal opens (add button click)
  if (addBtn) {
    addBtn.addEventListener('click', () => {
      if (!loaded) loadFacilities();
    });
  }
});
