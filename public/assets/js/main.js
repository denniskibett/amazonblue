/* Aimzone Ventures — site behaviour */
(function () {
  var doc = document.documentElement,
    reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  doc.classList.add('load');

  // header shadow + mobile menu
  var header = document.querySelector('.site-header');
  if (header) {
    var onScroll = function () {
      header.classList.toggle('scrolled', window.scrollY > 8);
    };
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
    var btn = header.querySelector('.menu-btn');
    if (btn) {
      btn.addEventListener('click', function () {
        var open = !header.classList.contains('nav-open');
        header.classList.toggle('nav-open', open);
        btn.setAttribute('aria-expanded', String(open));
        document.body.style.overflow = open ? 'hidden' : '';
      });
      window.addEventListener('resize', function () {
        if (window.innerWidth > 900 && header.classList.contains('nav-open')) btn.click();
      });
      header.querySelectorAll('.nav a').forEach(function (a) {
        a.addEventListener('click', function () {
          header.classList.remove('nav-open');
          btn.setAttribute('aria-expanded', 'false');
          document.body.style.overflow = '';
        });
      });
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && header.classList.contains('nav-open')) {
          btn.click();
          btn.focus();
        }
      });
    }
  }

  // photos: fade in once loaded (the blue gradient shows until then, or if a file is missing)
  doc.classList.add('js');
  document.querySelectorAll('.media>img').forEach(function (img) {
    var done = function () {
      img.classList.add('loaded');
    };
    if (img.complete && img.naturalWidth) done();
    else {
      img.addEventListener('load', done);
      img.addEventListener('error', function () {
        img.remove();
      });
    }
  });

  // hero slider
  var slider = document.querySelector('[data-slider]');
  if (slider) {
    var slides = [].slice.call(slider.querySelectorAll('.slide')),
      tabs = [].slice.call(slider.querySelectorAll('.tab')),
      dots = [].slice.call(slider.querySelectorAll('.slider-dot')),
      compact = window.matchMedia('(max-width: 900px)');
    var i = 0,
      timer = null,
      DUR = 7000;
    slider.style.setProperty('--dur', DUR / 1000 + 's');
    function go(n, focus) {
      i = (n + slides.length) % slides.length;
      slides.forEach(function (s, k) {
        var on = k === i;
        s.classList.toggle('is-active', on);
        s.setAttribute('aria-hidden', String(!on));
        s.querySelectorAll('a,button').forEach(function (x) {
          x.tabIndex = on ? 0 : -1;
        });
      });
      tabs.forEach(function (t, k) {
        var on = k === i;
        t.setAttribute('aria-selected', String(on));
        t.tabIndex = on ? 0 : -1;
        var bar = t.querySelector('.progress i');
        if (bar) {
          bar.style.animation = 'none';
          bar.offsetWidth;
          bar.style.animation = '';
        }
      });
      // re-adding the class restarts the progress fill, even when the same dot is picked again
      dots.forEach(function (d, k) {
        d.classList.remove('is-active');
        d.setAttribute('aria-current', String(k === i));
      });
      if (dots[i]) {
        dots[i].offsetWidth;
        dots[i].classList.add('is-active');
      }
      if (focus) tabs[i].focus();
      restart();
    }
    function restart() {
      clearTimeout(timer);
      if (!reduce && !slider.classList.contains('paused'))
        timer = setTimeout(function () {
          go(i + 1);
        }, DUR);
    }
    tabs.forEach(function (t, k) {
      t.addEventListener('click', function () {
        go(k);
      });
      t.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowRight') {
          e.preventDefault();
          go(i + 1, true);
        }
        if (e.key === 'ArrowLeft') {
          e.preventDefault();
          go(i - 1, true);
        }
      });
    });
    function pause(p) {
      slider.classList.toggle('paused', p);
      if (p) clearTimeout(timer);
      else restart();
    }
    // phones and tablets: arrows and progress dots replace the tabs, and tapping the photo
    // moves like a story (left third goes back, anywhere else goes forward)
    dots.forEach(function (d, k) {
      d.addEventListener('click', function () {
        go(k);
      });
    });
    slider.querySelectorAll('.slider-arrow').forEach(function (b) {
      b.addEventListener('click', function () {
        go(i + parseInt(b.getAttribute('data-step'), 10));
      });
    });
    slider.querySelector('.slides').addEventListener('click', function (e) {
      if (!compact.matches || e.target.closest('a,button')) return;
      var r = slides[i].getBoundingClientRect();
      go(e.clientX - r.left < r.width / 3 ? i - 1 : i + 1);
    });
    // pause while a mouse hovers (touch taps also fire mouse events, which would stop autoplay for good)
    slider.addEventListener('pointerenter', function (e) {
      if (e.pointerType === 'mouse') pause(true);
    });
    slider.addEventListener('pointerleave', function (e) {
      if (e.pointerType === 'mouse') pause(false);
    });
    // keyboard focus pauses; focus left behind by a tap on an arrow or dot does not
    slider.addEventListener('focusin', function (e) {
      if (e.target.matches(':focus-visible')) pause(true);
    });
    slider.addEventListener('focusout', function (e) {
      if (!slider.contains(e.relatedTarget)) pause(false);
    });
    document.addEventListener('visibilitychange', function () {
      pause(document.hidden);
    });
    var x0 = null;
    slider.addEventListener(
      'touchstart',
      function (e) {
        x0 = e.touches[0].clientX;
      },
      { passive: true },
    );
    slider.addEventListener('touchend', function (e) {
      if (x0 === null) return;
      var dx = e.changedTouches[0].clientX - x0;
      if (Math.abs(dx) > 50) go(i + (dx < 0 ? 1 : -1));
      x0 = null;
    });
    if (reduce) slider.classList.add('paused');
    go(0);
  }

  // count-up figures (once)
  var counters = document.querySelectorAll('[data-count]');
  if ('IntersectionObserver' in window) {
    var co = new IntersectionObserver(
      function (es) {
        es.forEach(function (e) {
          if (!e.isIntersecting) return;
          co.unobserve(e.target);
          var el = e.target,
            to = parseFloat(el.getAttribute('data-count')),
            pre = el.getAttribute('data-pre') || '',
            suf = el.getAttribute('data-suf') || '';
          if (reduce) {
            el.textContent = pre + to + suf;
            return;
          }
          var t0 = null;
          function step(t) {
            if (!t0) t0 = t;
            var p = Math.min(1, (t - t0) / 1200),
              v = Math.round(to * (1 - Math.pow(1 - p, 3)));
            el.textContent = pre + v + suf;
            if (p < 1) requestAnimationFrame(step);
          }
          requestAnimationFrame(step);
        });
      },
      { threshold: 0.6 },
    );
    counters.forEach(function (c) {
      co.observe(c);
    });
    // cards and list items rise in one after another within their group
    if (!reduce) {
      document
        .querySelectorAll(
          'main .grid>.card,main .grid>.plan,main .grid>.step,main .grid>.mini-stat,.stats .stat,.roles>.role,.faq>details',
        )
        .forEach(function (el) {
          var k = [].indexOf.call(el.parentNode.children, el);
          el.classList.add('reveal-item');
          el.style.setProperty('--d', Math.min(k * 0.08, 0.4) + 's');
        });
    }
    var ro = new IntersectionObserver(
      function (es) {
        es.forEach(function (e) {
          if (e.isIntersecting) {
            e.target.classList.add('in');
            ro.unobserve(e.target);
          }
        });
      },
      { threshold: 0.15, rootMargin: '0px 0px -40px 0px' },
    );
    document.querySelectorAll('.reveal,.reveal-item').forEach(function (r) {
      ro.observe(r);
    });
  } else {
    document.querySelectorAll('.reveal').forEach(function (r) {
      r.classList.add('in');
    });
  }

  // card rows marked data-carousel (testimonials, how-it-works steps, other services): on phones the
  // grid becomes a one-at-a-time carousel that advances on its own, swipes by hand, and pauses after a
  // touch or while off screen. data-carousel="step" names the cards for the dot buttons.
  document.querySelectorAll('[data-carousel]').forEach(function (track) {
    var label = track.getAttribute('data-carousel') || 'testimonial',
      cards = [].slice.call(track.children),
      mq = window.matchMedia('(max-width: 760px)'),
      dotsWrap = document.createElement('div'),
      k = 0,
      timer = null,
      held = 0,
      visible = false;
    if (cards.length < 2) return;
    dotsWrap.className = 'carousel-dots';
    var dots = cards.map(function (c, n) {
      var d = document.createElement('button');
      d.type = 'button';
      d.setAttribute('aria-label', 'Show ' + label + ' ' + (n + 1));
      d.addEventListener('click', function () {
        hold();
        show(n);
      });
      dotsWrap.appendChild(d);
      return d;
    });
    track.after(dotsWrap);
    function paint() {
      dots.forEach(function (d, n) {
        d.classList.toggle('is-active', n === k);
      });
    }
    function show(n) {
      k = (n + cards.length) % cards.length;
      track.scrollTo({ left: cards[k].offsetLeft - cards[0].offsetLeft, behavior: reduce ? 'auto' : 'smooth' });
      paint();
    }
    function hold() {
      held = Date.now();
    }
    function tick() {
      clearInterval(timer);
      timer = null;
      if (!mq.matches || reduce || !visible) return;
      timer = setInterval(function () {
        if (Date.now() - held > 8000) show(k + 1);
      }, 5000);
    }
    track.addEventListener(
      'scroll',
      function () {
        var n = Math.round(track.scrollLeft / (cards[1].offsetLeft - cards[0].offsetLeft));
        if (n !== k && n >= 0 && n < cards.length) {
          k = n;
          paint();
        }
      },
      { passive: true },
    );
    ['touchstart', 'pointerdown', 'wheel'].forEach(function (ev) {
      track.addEventListener(ev, hold, { passive: true });
    });
    if ('IntersectionObserver' in window) {
      new IntersectionObserver(
        function (es) {
          visible = es[0].isIntersecting;
          tick();
        },
        { threshold: 0.5 },
      ).observe(track);
    }
    mq.addEventListener('change', tick);
    paint();
  });

  // enquiry form: the desk cards and links like contact.html?topic=investment#message pick the enquiry type,
  // which sets the form title, the message prompt and shows investor-only fields
  var svc = document.getElementById('c-svc');
  if (svc) {
    var title = document.querySelector('[data-form-title]'),
      msg = document.getElementById('c-msg'),
      DESKS = {
        investment: {
          title: 'Investor relations enquiry',
          prompt: 'Tell us about yourself or your organisation and the opportunity you are interested in.',
        },
        partnership: {
          title: 'Partnership enquiry',
          prompt: 'Tell us about your organisation and the partnership you have in mind.',
        },
        other: { title: 'General enquiry', prompt: 'How can we help?' },
      },
      CLIENT = { title: 'Funding enquiry', prompt: 'What do you need funding for, how much and by when?' };
    var applyTopic = function () {
      var d = DESKS[svc.value] || CLIENT,
        route = document.getElementById('c-route'),
        opt = svc.options[svc.selectedIndex];
      // route_to tells the backend which desk inbox receives this enquiry
      if (route && opt && opt.getAttribute('data-route')) route.value = opt.getAttribute('data-route');
      if (title) title.textContent = d.title;
      if (msg) msg.placeholder = d.prompt;
      document.querySelectorAll('[data-show-for]').forEach(function (el) {
        var on = el.getAttribute('data-show-for') === svc.value;
        el.hidden = !on;
        el.querySelectorAll('select,input').forEach(function (i) {
          i.disabled = !on;
        });
      });
      document.querySelectorAll('[data-topic]').forEach(function (c) {
        var t = c.getAttribute('data-topic');
        c.classList.toggle('is-active', t === svc.value || (t === 'bridge' && !DESKS[svc.value]));
      });
    };
    var topic = new URLSearchParams(location.search).get('topic');
    if (topic && svc.querySelector('option[value="' + topic + '"]')) svc.value = topic;
    svc.addEventListener('change', applyTopic);
    document.querySelectorAll('[data-topic]').forEach(function (c) {
      c.addEventListener('click', function () {
        svc.value = c.getAttribute('data-topic');
        applyTopic();
      });
    });
    applyTopic();
  }

  // toast
  function toast(msg) {
    var t = document.querySelector('.toast');
    if (!t) return;
    t.querySelector('[data-msg]').textContent = msg;
    t.classList.add('show');
    setTimeout(function () {
      t.classList.remove('show');
    }, 3800);
  }

  // simple validation for forms marked data-validate
  document.querySelectorAll('form[data-validate]').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      var ok = true;
      f.querySelectorAll('[required]').forEach(function (inp) {
        var fld = inp.closest('.field');
        var bad =
          inp.type === 'checkbox'
            ? !inp.checked
            : !inp.value.trim() || (inp.type === 'email' && !/^\S+@\S+\.\S+$/.test(inp.value));
        if (fld) fld.classList.toggle('invalid', bad);
        inp.setAttribute('aria-invalid', String(bad));
        if (bad && ok) {
          inp.focus();
          ok = false;
        }
      });
      // forms with data-server post to Laravel once they pass the checks above
      if (ok && f.hasAttribute('data-server')) return;
      e.preventDefault();
      if (ok) {
        toast(f.getAttribute('data-success') || 'Done.');
        var go = f.getAttribute('data-next');
        if (go)
          setTimeout(function () {
            location.href = go;
          }, 900);
        else {
          f.reset();
          f.querySelectorAll('select').forEach(function (s) {
            s.dispatchEvent(new Event('change'));
          });
        }
      }
    });
  });

  // password reveal
  document.querySelectorAll('.pw button').forEach(function (b) {
    b.addEventListener('click', function () {
      var i = b.parentNode.querySelector('input');
      var show = i.type === 'password';
      i.type = show ? 'text' : 'password';
      b.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    });
  });

  // password strength
  var pw = document.querySelector('[data-strength]');
  if (pw) {
    var bars = document.querySelectorAll('.strength i'),
      rules = document.querySelectorAll('.rules li');
    pw.addEventListener('input', function () {
      var v = pw.value,
        tests = [v.length >= 8, /\d/.test(v), /[A-Z]/.test(v), /[^A-Za-z0-9]/.test(v)],
        score = tests.filter(Boolean).length;
      bars.forEach(function (b, k) {
        b.style.background = k < score ? (score < 3 ? '#9A4A1C' : '#2F6B4F') : '';
      });
      rules.forEach(function (r, k) {
        r.classList.toggle('ok', tests[k]);
      });
    });
  }

  // OTP boxes
  var otp = document.querySelectorAll('.otp input');
  otp.forEach(function (inp, k) {
    inp.addEventListener('input', function () {
      inp.value = inp.value.replace(/\D/g, '').slice(0, 1);
      if (inp.value && otp[k + 1]) otp[k + 1].focus();
    });
    inp.addEventListener('keydown', function (e) {
      if (e.key === 'Backspace' && !inp.value && otp[k - 1]) otp[k - 1].focus();
    });
    inp.addEventListener('paste', function (e) {
      var d = (e.clipboardData.getData('text') || '').replace(/\D/g, '').slice(0, 6);
      if (!d) return;
      e.preventDefault();
      d.split('').forEach(function (c, j) {
        if (otp[j]) otp[j].value = c;
      });
      (otp[d.length] || otp[5]).focus();
    });
  });
  var cd = document.querySelector('[data-countdown]');
  if (cd) {
    var s = parseInt(cd.getAttribute('data-countdown'), 10);
    var tick = setInterval(function () {
      s--;
      if (s <= 0) {
        clearInterval(tick);
        cd.parentNode.innerHTML = '<a href="#" data-resend>Resend code</a>';
        return;
      }
      cd.textContent = '0:' + String(s).padStart(2, '0');
    }, 1000);
  }

  var y = document.querySelector('[data-year]');
  if (y) y.textContent = new Date().getFullYear();
})();
/* table of contents: highlight the section in view */
(function () {
  var links = [].slice.call(document.querySelectorAll('.toc a'));
  if (!links.length || !('IntersectionObserver' in window)) return;
  var map = {};
  links.forEach(function (a) {
    map[a.getAttribute('href').slice(1)] = a;
  });
  var current = null;
  var io = new IntersectionObserver(
    function (es) {
      es.forEach(function (e) {
        if (e.isIntersecting) {
          if (current) current.classList.remove('active');
          current = map[e.target.id];
          if (current) current.classList.add('active');
        }
      });
    },
    { rootMargin: '-30% 0px -60% 0px' },
  );
  Object.keys(map).forEach(function (id) {
    var s = document.getElementById(id);
    if (s) io.observe(s);
  });
  links[0].classList.add('active');
  current = links[0];
})();
/* confirm password must match */
(function () {
  document.querySelectorAll('[data-match]').forEach(function (c) {
    var other = document.getElementById(c.getAttribute('data-match'));
    var f = c.closest('form');
    if (!other || !f) return;
    var fld = c.closest('.field'),
      err = fld.querySelector('.error');
    f.addEventListener(
      'submit',
      function (e) {
        if (c.value && c.value !== other.value) {
          e.stopImmediatePropagation();
          e.preventDefault();
          fld.classList.add('invalid');
          err.textContent = 'Passwords don\u2019t match.';
          c.setAttribute('aria-invalid', 'true');
          c.focus();
        }
      },
      true,
    );
  });
})();
/* KYC: file names + signature pad */
(function () {
  document.querySelectorAll('.u-input').forEach(function (inp) {
    inp.addEventListener('change', function () {
      var row = inp.closest('.upload'),
        hint = row && row.querySelector('.u-hint'),
        f = inp.files && inp.files[0];
      if (!row || !f) return;
      if (f.size > 10 * 1024 * 1024) {
        row.classList.remove('done');
        hint.textContent = 'That file is over 10 MB. Choose a smaller one.';
        hint.style.color = '#9B2C3B';
        return;
      }
      row.classList.add('done');
      hint.style.color = '';
      hint.textContent = 'Uploaded · ' + f.name;
      var b = row.querySelector('.u-btn');
      if (b) b.textContent = 'Replace';
    });
  });
  var c = document.querySelector('[data-sign]');
  if (c) {
    var ctx = c.getContext('2d'),
      drawing = false,
      last = null;
    function size() {
      var r = c.getBoundingClientRect(),
        d = window.devicePixelRatio || 1;
      c.width = r.width * d;
      c.height = r.height * d;
      ctx.scale(d, d);
      ctx.lineWidth = 2.4;
      ctx.lineCap = 'round';
      ctx.lineJoin = 'round';
      ctx.strokeStyle = '#1B2B6B';
    }
    size();
    window.addEventListener('resize', size);
    function pos(e) {
      var r = c.getBoundingClientRect();
      return { x: e.clientX - r.left, y: e.clientY - r.top };
    }
    c.addEventListener('pointerdown', function (e) {
      drawing = true;
      last = pos(e);
      c.setPointerCapture(e.pointerId);
    });
    c.addEventListener('pointermove', function (e) {
      if (!drawing) return;
      var p = pos(e);
      ctx.beginPath();
      ctx.moveTo(last.x, last.y);
      ctx.lineTo(p.x, p.y);
      ctx.stroke();
      last = p;
      var row = c.closest('.upload');
      if (row) row.classList.add('done');
    });
    ['pointerup', 'pointercancel', 'pointerleave'].forEach(function (ev) {
      c.addEventListener(ev, function () {
        drawing = false;
      });
    });
    // send the drawn signature with the form as a PNG data URL
    var sigOut = document.querySelector('[data-sign-out]'),
      sigForm = c.closest('form'),
      signed = false;
    c.addEventListener('pointerdown', function () {
      signed = true;
    });
    if (sigOut && sigForm)
      sigForm.addEventListener('submit', function () {
        sigOut.value = signed ? c.toDataURL('image/png') : '';
      }, true);
    var clr = document.querySelector('[data-sign-clear]');
    if (clr)
      clr.addEventListener('click', function () {
        signed = false;
        ctx.clearRect(0, 0, c.width, c.height);
        var row = c.closest('.upload');
        if (row && !row.querySelector('.u-input').files.length) row.classList.remove('done');
      });
  }
})();
/* segmented sign-up: validate the visible step before moving on, and only require business fields for businesses */
(function () {
  var f = document.querySelector('form.rg');
  if (!f) return;
  var biz = document.getElementById('at-biz');
  function sync() {
    f.querySelectorAll('[data-biz]').forEach(function (i) {
      i.required = biz.checked;
    });
  }
  f.querySelectorAll('input[name=atype]').forEach(function (r) {
    r.addEventListener('change', function () {
      sync();
      var sb = document.getElementById('sg-b');
      if (!biz.checked && sb.checked) document.getElementById('sg-p').checked = true;
    });
  });
  sync();
  f.querySelectorAll('.rg-next').forEach(function (btn) {
    function go(e) {
      var pn = btn.closest('.rg-pn'),
        ok = true;
      pn.querySelectorAll('[required]').forEach(function (inp) {
        if (inp.offsetParent === null) return;
        var fld = inp.closest('.field'),
          bad = !inp.value.trim() || (inp.type === 'email' && !/^\S+@\S+\.\S+$/.test(inp.value));
        if (fld) fld.classList.toggle('invalid', bad);
        if (bad && ok) {
          ok = false;
          inp.focus();
        }
      });
      if (!ok) {
        e.preventDefault();
        return;
      }
      document.getElementById(btn.getAttribute('for')).checked = true;
      e.preventDefault();
      var first = f.querySelector('.rg-pn:not([hidden]) input:not([type=radio]):not([type=checkbox])');
    }
    btn.addEventListener('click', go);
    btn.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') go(e);
    });
  });
  f.addEventListener(
    'submit',
    function (e) {
      var s = document.getElementById('sg-s');
      if (!s.checked) {
        e.preventDefault();
        e.stopImmediatePropagation();
        s.checked = true;
      }
    },
    true,
  );
})();
/* KYC: remember answers across steps, restore when going back, and summarise on Review */
(function () {
  // server-backed KYC pages fill fields and summaries from the database instead
  if (!document.querySelector('.kyc2') || document.querySelector('.kyc2[data-server]')) return;
  var KEY = 'aimzone-kyc',
    data = {};
  try {
    data = JSON.parse(localStorage.getItem(KEY) || '{}');
  } catch (e) {}
  function save() {
    try {
      localStorage.setItem(KEY, JSON.stringify(data));
    } catch (e) {}
  }
  var fields = document.querySelectorAll(
    '.kyc2 input:not([type=file]):not([type=checkbox]), .kyc2 select, .kyc2 textarea',
  );
  fields.forEach(function (el) {
    if (!el.id) return;
    if (el.type === 'radio') {
      if (data[el.name] === el.id) el.checked = true;
      el.addEventListener('change', function () {
        if (el.checked) {
          data[el.name] = el.id;
          var l = document.querySelector('label[for="' + el.id + '"]');
          data[el.name + '_label'] = l ? l.textContent.trim() : el.id;
          save();
        }
      });
      if (el.checked && !data[el.name]) {
        data[el.name] = el.id;
        var l = document.querySelector('label[for="' + el.id + '"]');
        data[el.name + '_label'] = l ? l.textContent.trim() : el.id;
      }
      return;
    }
    if (data[el.id] != null && el.value === '') el.value = data[el.id];
    if (el.tagName === 'SELECT') {
      if (data[el.id] != null) el.value = data[el.id];
      else data[el.id] = el.value;
    }
    ['input', 'change'].forEach(function (ev) {
      el.addEventListener(ev, function () {
        data[el.id] = el.value;
        save();
      });
    });
  });
  document.querySelectorAll('.kyc2 .u-input').forEach(function (inp) {
    var row = inp.closest('.upload'),
      hint = row && row.querySelector('.u-hint');
    if (data['file_' + inp.id] && row) {
      row.classList.add('done');
      if (hint) hint.textContent = 'Uploaded · ' + data['file_' + inp.id];
      var b = row.querySelector('.u-btn');
      if (b) b.textContent = 'Replace';
    }
    inp.addEventListener('change', function () {
      var f = inp.files && inp.files[0];
      if (f && f.size <= 10 * 1024 * 1024) {
        data['file_' + inp.id] = f.name;
        save();
      }
    });
  });
  var sig = document.querySelector('[data-sign]');
  if (sig) {
    sig.addEventListener('pointerup', function () {
      data.signed = true;
      save();
    });
    var row = sig.closest('.upload');
    if (data.signed && row) row.classList.add('done');
  }
  save();
  function v(k) {
    return (data[k] || '').toString().trim();
  }
  function mask(x, n) {
    x = x || '';
    return x.length > n ? '•'.repeat(Math.max(0, x.length - n)) + x.slice(-n) : x;
  }
  var sums = {
    personal: function () {
      var n = (v('k-first') + ' ' + v('k-last')).trim();
      if (!n) return '';
      return [n, v('k-id') && 'ID ' + mask(v('k-id'), 4), v('k-kra') && 'KRA PIN ' + mask(v('k-kra'), 3), v('k-county')]
        .filter(Boolean)
        .join(' · ');
    },
    docs: function () {
      var d = data.doctype === 'dt-pp' ? 'Passport bio page' : 'National ID front & back';
      var parts = [d];
      parts.push(data['file_u-photo'] ? 'photo' : 'photo missing');
      parts.push(data.signed || data['file_u-sign'] ? 'signature' : 'signature missing');
      return parts.join(' · ');
    },
    income: function () {
      if (!data.emp_label && !v('k-inc')) return '';
      return [
        data.emp_label,
        v('k-inc') && 'KES ' + v('k-inc'),
        v('k-mpesa') && 'M-Pesa ' + mask(v('k-mpesa').replace(/\s/g, ''), 3),
      ]
        .filter(Boolean)
        .join(' · ');
    },
    biz: function () {
      return [v('b-name'), v('b-reg'), data.btype_label].filter(Boolean).join(' · ');
    },
    cdocs: function () {
      var ids = ['c-inc', 'c-cr12', 'c-kra', 'c-res', 'c-addr'];
      var n = ids.filter(function (i) {
        return data['file_' + i];
      }).length;
      return n ? n + ' of 5 uploaded' : '';
    },
    fin: function () {
      return [v('f-to') && 'KES ' + v('f-to') + ' turnover', v('f-act'), v('f-bank')].filter(Boolean).join(' · ');
    },
  };
  document.querySelectorAll('[data-sum]').forEach(function (el) {
    var f = sums[el.getAttribute('data-sum')];
    var t = f && f();
    if (t) el.textContent = t;
  });
  var fn = document.querySelector('[data-first-name]');
  if (fn && v('k-first')) fn.textContent = ', ' + v('k-first');
})();
/* hand sign-up details to KYC so clients don't type them twice */
(function () {
  var f = document.querySelector('form.rg');
  if (!f) return;
  f.addEventListener(
    'submit',
    function () {
      var d = {};
      try {
        d = JSON.parse(localStorage.getItem('aimzone-kyc') || '{}');
      } catch (e) {}
      var g = function (id) {
        var el = document.getElementById(id);
        return el ? el.value.trim() : '';
      };
      var name = g('name').split(/\s+/);
      if (name[0]) {
        d['k-first'] = d['k-first'] || name[0];
        if (name.length > 1) d['k-last'] = d['k-last'] || name.slice(1).join(' ');
      }
      if (g('national-id')) d['k-id'] = d['k-id'] || g('national-id');
      if (g('phone')) d['k-mpesa'] = d['k-mpesa'] || g('phone');
      d.accountType =
        document.getElementById('at-biz') && document.getElementById('at-biz').checked ? 'business' : 'individual';
      if (d.accountType === 'business') {
        d['b-name'] = d['b-name'] || g('biz-name');
        d['b-reg'] = d['b-reg'] || g('biz-reg');
      }
      try {
        localStorage.setItem('aimzone-kyc', JSON.stringify(d));
      } catch (e) {}
    },
    true,
  );
})();
/* business sign-ups continue to business verification after individual KYC */
(function () {
  var d = {};
  try {
    d = JSON.parse(localStorage.getItem('aimzone-kyc') || '{}');
  } catch (e) {}
  if (d.accountType !== 'business' || document.querySelector('.kyc2[data-server]')) return;
  var done = document.querySelector('.kyc-card.done .actions');
  if (done && location.pathname.indexOf('kyc-submitted') > -1) {
    done.innerHTML =
      '<a class="btn btn-primary" href="kyb-start.html">Continue to business verification</a><a class="btn btn-ghost" href="index.html">Go to dashboard</a>';
  }
})();
/* sign-up (KYC design): step numbers, done states and arrow buttons */
(function () {
  var f = document.querySelector('form.rg2');
  if (!f) return;
  var biz = document.getElementById('at-biz');
  function order() {
    return biz.checked ? ['sg-p', 'sg-b', 'sg-d', 'sg-s'] : ['sg-p', 'sg-d', 'sg-s'];
  }
  var map = { 'sg-p': 't-p', 'sg-b': 't-b', 'sg-d': 't-d', 'sg-s': 't-s' };
  function paint() {
    var o = order(),
      cur = o.findIndex(function (id) {
        return document.getElementById(id).checked;
      });
    if (cur < 0) {
      document.getElementById('sg-p').checked = true;
      cur = 0;
    }
    o.forEach(function (id, k) {
      var t = f.querySelector('.' + map[id]);
      t.classList.toggle('done', k < cur);
      var n = t.querySelector('span');
      n.innerHTML =
        k < cur
          ? '<svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10"/></svg>'
          : String(k + 1);
    });
    f.querySelector('.segnav.back').style.opacity = cur === 0 ? 0.35 : 1;
    f.querySelector('.segnav[data-step="1"]').style.opacity = cur === o.length - 1 ? 0.35 : 1;
  }
  f.querySelectorAll('input[name=step],input[name=atype]').forEach(function (r) {
    r.addEventListener('change', paint);
  });
  f.querySelectorAll('.segnav').forEach(function (b) {
    b.addEventListener('click', function () {
      var o = order(),
        cur = o.findIndex(function (id) {
          return document.getElementById(id).checked;
        }),
        n = cur + parseInt(b.getAttribute('data-step'), 10);
      if (n < 0 || n >= o.length) return;
      if (n > cur) {
        var nx = f.querySelector('.rg-pn:not([style*="none"]) .rg-next');
        var pn = f.querySelectorAll('.rg-pn')[['sg-p', 'sg-b', 'sg-d', 'sg-s'].indexOf(o[cur])];
        var btn =
          pn &&
          [].find.call(pn.querySelectorAll('.rg-next'), function (x) {
            return x.offsetParent !== null;
          });
        if (btn) {
          btn.click();
          paint();
          return;
        }
      }
      document.getElementById(o[n]).checked = true;
      paint();
    });
  });
  f.addEventListener('change', function () {
    setTimeout(paint, 0);
  });
  f.querySelectorAll('.rg-next').forEach(function (x) {
    x.addEventListener('click', function () {
      setTimeout(paint, 0);
    });
  });
  paint();
})();
/* register: route to the chosen journey with a short slide, and prefill the next step */
(function () {
  var f = document.querySelector('form.reg3');
  if (!f) return;
  var biz = document.getElementById('rt-biz');
  function route() {
    f.setAttribute('data-next', biz.checked ? 'kyb-start.html' : 'kyc-personal.html');
  }
  f.querySelectorAll('input[name=rtype]').forEach(function (r) {
    r.addEventListener('change', route);
  });
  route();
  f.addEventListener(
    'submit',
    function () {
      var ok = [].every.call(f.querySelectorAll('[required]'), function (i) {
        return i.type === 'checkbox' ? i.checked : i.value.trim();
      });
      if (!ok) return;
      var d = {};
      try {
        d = JSON.parse(localStorage.getItem('aimzone-kyc') || '{}');
      } catch (e) {}
      var n = document.getElementById('rg-name').value.trim().split(/\s+/);
      d['k-first'] = d['k-first'] || n[0] || '';
      d['k-last'] = d['k-last'] || n.slice(1).join(' ');
      d['k-mpesa'] = d['k-mpesa'] || document.getElementById('rg-phone').value;
      d.account = biz.checked ? 'business' : 'individual';
      try {
        localStorage.setItem('aimzone-kyc', JSON.stringify(d));
      } catch (e) {}
      if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) f.classList.add('leaving');
    },
    true,
  );
})();
