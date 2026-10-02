
document.querySelectorAll('.fake-search').forEach(form => {
  form.addEventListener('submit', e => {
    e.preventDefault();
    window.location.href = 'hasil-pencarian.html';
  });
});
