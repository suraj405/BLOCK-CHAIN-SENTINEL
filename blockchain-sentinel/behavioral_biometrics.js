// behavioral_biometrics.js
// Silent behavioral biometrics collector
// Tracks: keystroke hold times, flight times, typing speed, backspace rate, rhythm
// Device-independent — follows the PERSON not the hardware

(function(global) {

  function BehavioralCollector() {
    this._events      = [];   // {type, key_cat, t}
    this._hold_times  = [];
    this._flight_times= [];
    this._key_down_at = {};
    this._last_up_at  = null;
    this._backspaces  = 0;
    this._total_keys  = 0;
    this._start_time  = null;
    this._fields      = [];
    this._active_field= null;
    this._watching    = false;
    this._keydown_cb  = null;
    this._keyup_cb    = null;
    this._focus_cb    = null;
  }

  // Anonymise key into a broad category — we never store actual characters
  BehavioralCollector.prototype._cat = function(key) {
    if (key === 'Backspace' || key === 'Delete') return 'DEL';
    if (key === ' ')                             return 'SPC';
    if (/^[0-9]$/.test(key))                    return 'NUM';
    if (/^[a-zA-Z]$/.test(key))                 return 'LET';
    if (/^[^a-zA-Z0-9]$/.test(key))             return 'SYM';
    return 'OTH';
  };

  BehavioralCollector.prototype.attach = function(formEl) {
    if (this._watching) return;
    this._watching  = true;
    this._start_time= Date.now();
    var self        = this;

    this._keydown_cb = function(e) {
      var t   = Date.now();
      var cat = self._cat(e.key);
      self._key_down_at[e.code] = t;

      if (cat === 'DEL') {
        self._backspaces++;
      } else {
        self._total_keys++;
        // flight time = gap between last key-up and this key-down
        if (self._last_up_at !== null) {
          var ft = t - self._last_up_at;
          if (ft > 0 && ft < 3000) self._flight_times.push(ft);
        }
      }
    };

    this._keyup_cb = function(e) {
      var t    = Date.now();
      var down = self._key_down_at[e.code];
      if (down !== undefined) {
        var hold = t - down;
        if (hold > 0 && hold < 2000) self._hold_times.push(hold);
        delete self._key_down_at[e.code];
      }
      self._last_up_at = t;
    };

    this._focus_cb = function(e) {
      if (e.target && e.target.name) {
        var fname = e.target.name || e.target.id || 'field';
        if (self._fields[self._fields.length - 1] !== fname) {
          self._fields.push(fname);
        }
        self._active_field = fname;
      }
    };

    // Attach to the form and document
    document.addEventListener('keydown', this._keydown_cb, true);
    document.addEventListener('keyup',   this._keyup_cb,   true);
    if (formEl) {
      var inputs = formEl.querySelectorAll('input, textarea, select');
      inputs.forEach(function(inp) {
        inp.addEventListener('focus', self._focus_cb);
      });
    }
  };

  BehavioralCollector.prototype.detach = function() {
    document.removeEventListener('keydown', this._keydown_cb, true);
    document.removeEventListener('keyup',   this._keyup_cb,   true);
    this._watching = false;
  };

  BehavioralCollector.prototype.collect = function() {
    var total_ms = this._start_time ? (Date.now() - this._start_time) : 1;
    return {
      hold_times:    this._hold_times.slice(),
      flight_times:  this._flight_times.slice(),
      total_keys:    this._total_keys,
      backspaces:    this._backspaces,
      total_time_ms: total_ms,
      field_order:   this._fields.slice(),
      sample_count:  this._hold_times.length,
    };
  };

  BehavioralCollector.prototype.hasEnoughData = function() {
    // Need at least 5 keystrokes for a meaningful profile
    return this._hold_times.length >= 5;
  };

  // Send behavioral data to backend
  BehavioralCollector.prototype.send = async function(reportId, role) {
    var data = this.collect();
    if (!data.sample_count) return null;

    var base = window.location.origin;
    var url  = base + '/blockchain-sentinel/backend/behavioral_scan.php';

    try {
      var resp = await fetch(url, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({
          report_id:  reportId,
          role:       role,
          behavioral: data,
        }),
      });
      return await resp.json();
    } catch(e) {
      return null;
    }
  };

  global.BehavioralCollector = BehavioralCollector;

})(window);
