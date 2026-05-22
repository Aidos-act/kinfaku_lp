(function () {
  'use strict';

  var yearEl = document.getElementById('year');
  if (yearEl) {
    yearEl.textContent = String(new Date().getFullYear());
  }
})();

(function () {
  'use strict';

  var headerButtons = document.querySelector('.header-buttons');
  if (!headerButtons) {
    return;
  }

  var mobileQuery = window.matchMedia('(max-width: 700px)');
  var scrollThreshold = 100;

  function updateHeaderButtonsVisibility() {
    if (!mobileQuery.matches) {
      headerButtons.classList.remove('is-scroll-visible');
      return;
    }

    if (window.scrollY >= scrollThreshold) {
      headerButtons.classList.add('is-scroll-visible');
    } else {
      headerButtons.classList.remove('is-scroll-visible');
    }
  }

  window.addEventListener('scroll', updateHeaderButtonsVisibility, { passive: true });
  mobileQuery.addEventListener('change', updateHeaderButtonsVisibility);
  updateHeaderButtonsVisibility();
})();

(function () {
  'use strict';

  var SERVER_ERROR_MESSAGES = {
    invalid: '入力内容をご確認ください。各項目を再度ご確認のうえ、お送りください。',
    send: 'メールの送信に失敗しました。時間をおいて再度お試しください。',
    spam: '送信に失敗しました。'
  };

  var form = document.querySelector('.trial-form');
  if (!form) {
    return;
  }

  var errorsEl = form.querySelector('.form-errors');
  var formBody = form.querySelector('.form-body');
  var confirmPanel = form.querySelector('.form-confirm');
  var confirmList = form.querySelector('.form-confirm-list');
  var confirmBtn = form.querySelector('.js-form-to-confirm');
  var backBtn = form.querySelector('.js-form-back');
  var sendBtn = form.querySelector('.js-form-send');
  var formHeading = document.querySelector('#form_section h4');
  var isConfirmStep = false;
  var defaultFormHeading = formHeading ? formHeading.textContent : '';

  function trim(value) {
    return String(value || '').trim();
  }

  function isValidEmail(email) {
    var input = document.createElement('input');
    input.type = 'email';
    input.required = true;
    input.value = email;
    return input.checkValidity();
  }

  function escapeHtml(text) {
    var el = document.createElement('div');
    el.textContent = text;
    return el.innerHTML;
  }

  function validateTrialForm() {
    var data = new FormData(form);
    var errors = [];

    if (trim(data.get('company_url'))) {
      errors.push('送信に失敗しました。');
      return errors;
    }

    var name = trim(data.get('name'));
    var furigana = trim(data.get('furigana'));
    var company = trim(data.get('company'));
    var email = trim(data.get('email'));
    var tel = trim(data.get('tel'));

    if (!name) {
      errors.push('氏名を入力してください。');
    }
    if (!furigana) {
      errors.push('ふりがなを入力してください。');
    }
    if (!company) {
      errors.push('会社名を入力してください。');
    }
    if (!email) {
      errors.push('メールアドレスを入力してください。');
    } else if (!isValidEmail(email)) {
      errors.push('メールアドレスの形式が正しくありません。');
    }
    if (!tel) {
      errors.push('電話番号を入力してください。');
    }

    return errors;
  }

  function showFormErrors(messages) {
    if (!errorsEl) {
      return;
    }

    if (!messages.length) {
      errorsEl.hidden = true;
      errorsEl.innerHTML = '';
      return;
    }

    errorsEl.hidden = false;
    errorsEl.innerHTML =
      '<ul>' +
      messages.map(function (message) {
        return '<li>' + escapeHtml(message) + '</li>';
      }).join('') +
      '</ul>';
  }

  function scrollToErrors() {
    if (!errorsEl || errorsEl.hidden) {
      return;
    }

    errorsEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
    errorsEl.focus({ preventScroll: true });
  }

  function formatOptional(value) {
    return trim(value) || '（未入力）';
  }

  function getFormSummaryItems() {
    var data = new FormData(form);

    return [
      { label: '氏名', value: trim(data.get('name')) },
      { label: 'ふりがな', value: trim(data.get('furigana')) },
      { label: '会社名', value: trim(data.get('company')) },
      { label: '部署名', value: formatOptional(data.get('department')) },
      { label: 'メールアドレス', value: trim(data.get('email')) },
      { label: '電話番号', value: trim(data.get('tel')) }
    ];
  }

  function renderConfirmList() {
    if (!confirmList) {
      return;
    }

    confirmList.innerHTML = getFormSummaryItems()
      .map(function (item) {
        var valueHtml = escapeHtml(item.value).replace(/\n/g, '<br>');
        return (
          '<div class="form-confirm-row" role="listitem">' +
          '<p class="form-confirm-label">' + escapeHtml(item.label) + '</p>' +
          '<p class="form-confirm-value">' + valueHtml + '</p>' +
          '</div>'
        );
      })
      .join('');
  }

  function scrollToFormSection() {
    var section = document.getElementById('form_section');
    if (!section) {
      return;
    }
    section.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  function showInputStep() {
    isConfirmStep = false;
    form.classList.remove('is-confirm-step');
    if (formBody) {
      formBody.hidden = false;
    }
    if (confirmPanel) {
      confirmPanel.hidden = true;
    }
    if (formHeading) {
      formHeading.textContent = defaultFormHeading;
    }
    if (sendBtn) {
      sendBtn.disabled = false;
      sendBtn.textContent = 'メールを送信';
    }
  }

  function showConfirmStep() {
    isConfirmStep = true;
    form.classList.add('is-confirm-step');
    if (formBody) {
      formBody.hidden = true;
    }
    renderConfirmList();
    if (confirmPanel) {
      confirmPanel.hidden = false;
    }
    if (formHeading) {
      formHeading.textContent = '入力内容の確認';
    }
    showFormErrors([]);
    scrollToFormSection();
    if (confirmPanel) {
      confirmPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }

  function clearQueryParam() {
    if (!history.replaceState) {
      return;
    }

    var url = new URL(window.location.href);
    if (!url.searchParams.has('form_error')) {
      return;
    }

    url.searchParams.delete('form_error');
    history.replaceState(null, '', url.pathname + url.search + url.hash);
  }

  function showServerError() {
    var code = new URLSearchParams(window.location.search).get('form_error');
    if (!code || !SERVER_ERROR_MESSAGES[code]) {
      return;
    }

    showFormErrors([SERVER_ERROR_MESSAGES[code]]);
    scrollToErrors();
    clearQueryParam();
  }

  if (confirmBtn) {
    confirmBtn.addEventListener('click', function () {
      var errors = validateTrialForm();
      if (errors.length) {
        showFormErrors(errors);
        scrollToErrors();
        return;
      }
      showConfirmStep();
    });
  }

  if (backBtn) {
    backBtn.addEventListener('click', function () {
      showInputStep();
      scrollToFormSection();
    });
  }

  form.addEventListener('submit', function (event) {
    if (!isConfirmStep) {
      event.preventDefault();
      return;
    }

    var errors = validateTrialForm();
    if (errors.length) {
      event.preventDefault();
      showInputStep();
      showFormErrors(errors);
      scrollToErrors();
      return;
    }

    showFormErrors([]);
    if (sendBtn) {
      sendBtn.disabled = true;
      sendBtn.textContent = '送信中...';
    }
  });

  form.addEventListener('input', function () {
    if (errorsEl && !errorsEl.hidden) {
      showFormErrors([]);
    }
  });

  showServerError();
  showInputStep();
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
