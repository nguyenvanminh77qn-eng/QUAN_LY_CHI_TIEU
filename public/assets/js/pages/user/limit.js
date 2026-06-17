function formatLimitInput(input) {
  var val = input.value.replace(/[^0-9]/g, '');
  input.value = val !== '' ? parseInt(val, 10).toLocaleString('de-DE') : '';
}

document.addEventListener('DOMContentLoaded', function () {
  var inputs = document.querySelectorAll('.limit-input');

  inputs.forEach(function (inp) {
    inp.addEventListener('input', function () {
      formatLimitInput(this);
    });
  });

  var form = document.getElementById('limitForm');
  if (form) {
    form.addEventListener('submit', function () {
      inputs.forEach(function (inp) {
        inp.value = inp.value.replace(/\./g, '');
      });
    });
  }
});
