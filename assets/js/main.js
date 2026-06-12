// assets/main.js — Front public

// Auto-disparition des alertes flash
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-flash]').forEach(el => {
    setTimeout(() => el.style.opacity = '0', 4000);
    setTimeout(() => el.remove(), 4500);
  });

  // Slider homepage (simple, sans lib)
  const track = document.querySelector('.slider-track');
  if (track) {
    const slides = track.querySelectorAll('.slider-slide');
    let current = 0;
    const go = n => {
      slides[current].classList.remove('active');
      current = (n + slides.length) % slides.length;
      slides[current].classList.add('active');
    };
    slides[0]?.classList.add('active');
    setInterval(() => go(current + 1), 5000);
    document.querySelector('.slider-prev')?.addEventListener('click', () => go(current - 1));
    document.querySelector('.slider-next')?.addEventListener('click', () => go(current + 1));
  }
});
