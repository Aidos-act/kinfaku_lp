(function () {
  'use strict';

  var yearEl = document.getElementById('year');
  if (yearEl) {
    yearEl.textContent = String(new Date().getFullYear());
  }
})();

function goToSection(sectionId) {
  var section = document.getElementById(sectionId);
  if (!section) return;

  section.scrollIntoView({ behavior: 'smooth', block: 'start' });

  if (history.replaceState) {
    history.replaceState(null, '', '#' + sectionId);
  } else {
    location.hash = sectionId;
  }
}

function toggleFaqItem(button) {
  var item = button.closest('.faq-item');
  if (!item) return;

  var isOpen = item.classList.toggle('is-open');
  button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
}
