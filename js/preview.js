(function (Drupal, once) {
  'use strict';

  function debounce(fn, delay) {
    var timer = null;
    return function () {
      var args = arguments;
      var context = this;
      clearTimeout(timer);
      timer = setTimeout(function () {
        fn.apply(context, args);
      }, delay);
    };
  }

  function updatePreview(iframe) {
    var baseUrl = iframe.dataset.baseUrl;
    var uuid = iframe.dataset.uuid;
    var subPage = iframe.dataset.subPage || 'catering';
    var token = iframe.dataset.token;
    var height = iframe.getAttribute('height');

    var uuidField = document.querySelector('[data-preview-field="uuid"]');
    var tokenField = document.querySelector('[data-preview-field="token"]');
    var baseUrlField = document.querySelector('[data-preview-field="base_url"]');
    var subPageField = document.querySelector('[data-preview-field="sub_page"]');
    var heightField = document.querySelector('[data-preview-field="height"]');

    if (uuidField) {
      uuid = uuidField.value;
    }
    if (tokenField) {
      token = tokenField.value;
    }
    if (baseUrlField) {
      baseUrl = baseUrlField.value;
    }
    if (subPageField) {
      subPage = subPageField.value || subPage;
    }
    if (heightField) {
      height = heightField.value;
    }

    if (!baseUrl || !uuid) {
      iframe.src = 'about:blank';
      return;
    }

    var url = baseUrl.replace(/\/$/, '') + '/widget/institutions/' + uuid + '/' + subPage;
    if (token) {
      url += '?token=' + encodeURIComponent(token);
    }

    iframe.src = url;
    if (height) {
      iframe.setAttribute('height', height);
    }
  }

  Drupal.behaviors.gladWidgetsPreview = {
    attach: function (context) {
      once('glad-widgets-preview', '#glad-widgets-preview-iframe', context).forEach(function (iframe) {
        var refresh = debounce(function () {
          updatePreview(iframe);
        }, 400);

        document.querySelectorAll('.glad-widgets-preview-trigger').forEach(function (field) {
          field.addEventListener('input', refresh);
          field.addEventListener('change', refresh);
        });
      });
    }
  };

})(Drupal, once);
