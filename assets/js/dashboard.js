// assets/dashboard.js — Espace connecté & admin

document.addEventListener('DOMContentLoaded', () => {

  // Marque le lien actif dans la sidebar
  const links = document.querySelectorAll('.sidebar-item');
  links.forEach(link => {
    if (link.href && window.location.href.includes(link.getAttribute('href'))) {
      link.classList.add('active');
    }
  });

  // Toast auto-fermeture
  document.querySelectorAll('.alert').forEach(el => {
    if (!el.dataset.persistent) {
      setTimeout(() => { el.style.transition = 'opacity .4s'; el.style.opacity = '0'; }, 5000);
      setTimeout(() => el.remove(), 5500);
    }
  });

  // Confirmation suppression
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', e => {
      if (!confirm(el.dataset.confirm || 'Confirmer cette action ?')) e.preventDefault();
    });
  });

});
