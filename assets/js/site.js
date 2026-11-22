(function () {
  // Mobile menu
  var btn = document.querySelector('.menu-btn');
  var nav = document.getElementById('site-nav');
  if (btn && nav) {
    btn.addEventListener('click', function () {
      var open = nav.classList.toggle('open');
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }
  // Commission illustration
  var range = document.getElementById('spend');
  if (range) {
    var fmt = function (n) { return '£' + Math.round(n).toLocaleString('en-GB'); };
    var out = document.getElementById('spend-out');
    var low = document.getElementById('calc-low');
    var high = document.getElementById('calc-high');
    var update = function () {
      var v = Number(range.value);
      out.textContent = fmt(v);
      low.textContent = fmt(v * 0.07);
      high.textContent = fmt(v * 0.10);
    };
    range.addEventListener('input', update);
    update();
  }
  // Timestamp for simple bot check
  document.querySelectorAll('input[name="t"]').forEach(function (el) { el.value = String(Date.now()); });
})();
(function () {
  // Show form errors passed back by send.php
  var p = new URLSearchParams(location.search);
  var err = p.get('error');
  if (!err) return;
  var msgs = {
    email: 'Please check your email address and try again.',
    name: 'Please add your name and try again.',
    consent: 'Please tick the box so we can reply to you.',
    busy: 'Too many messages from this connection. Please try again in ten minutes or email info@youshouldtravelwell.com.'
  };
  var box = document.querySelector('.form-error');
  if (box) { box.textContent = msgs[err] || 'Something went wrong. Please try again.'; box.hidden = false; box.focus(); }
})();
