(function () {
  'use strict';

  var yearEl = document.getElementById('year');
  if (yearEl) {
    yearEl.textContent = String(new Date().getFullYear());
  }
})();

function toggleFaqItem(button) {
  var item = button.closest('.faq-item');
  if (!item) return;

  var isOpen = item.classList.toggle('is-open');
  button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
}
