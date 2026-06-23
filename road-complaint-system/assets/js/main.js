// ============================================================
// assets/js/main.js — Main JavaScript
// ============================================================

$(function () {

  // ---- THEME TOGGLE ----------------------------------------
  const $toggle = $('#themeToggle');
  const $html   = $('html');

  $toggle.on('click', function () {
    const current = $html.attr('data-theme');
    const next    = current === 'dark' ? 'light' : 'dark';
    $html.attr('data-theme', next);
    $('body').removeClass('light-mode dark-mode').addClass(next + '-mode');
    $toggle.find('i').toggleClass('fa-moon fa-sun');
    // Store in cookie (1 year)
    document.cookie = `theme=${next};path=/;max-age=${60*60*24*365}`;
    // Also tell server via AJAX
    $.post(window.APP_URL + '/api/save_preference.php', { theme: next });
  });

  // ---- MOBILE HAMBURGER ------------------------------------
  $('#hamburger').on('click', function () {
    $('#mobileMenu').toggleClass('open');
  });

  // ---- AUTO-DISMISS ALERTS ---------------------------------
  setTimeout(function () {
    $('.alert').fadeOut(400);
  }, 5000);

  // ---- DEPENDENT DROPDOWNS (Ward → Area → Spot) ------------
  function loadAreas(wardId, $areaSelect, $spotSelect) {
    $areaSelect.html('<option value="">Loading…</option>').prop('disabled', true);
    $spotSelect.html('<option value="">Select Area first</option>').prop('disabled', true);
    if (!wardId) {
      $areaSelect.html('<option value="">Select Area</option>');
      return;
    }
    $.getJSON(window.APP_URL + '/api/get_areas.php', { ward_id: wardId }, function (data) {
      $areaSelect.html('<option value="">Select Area</option>');
      $.each(data, function (i, area) {
        $areaSelect.append(`<option value="${area.id}">${area.name}</option>`);
      });
      $areaSelect.prop('disabled', false);
    }).fail(function () {
      $areaSelect.html('<option value="">Error loading areas</option>');
    });
  }

  function loadSpots(areaId, $spotSelect) {
    $spotSelect.html('<option value="">Loading…</option>').prop('disabled', true);
    if (!areaId) {
      $spotSelect.html('<option value="">Select Spot</option>');
      return;
    }
    $.getJSON(window.APP_URL + '/api/get_spots.php', { area_id: areaId }, function (data) {
      $spotSelect.html('<option value="">Select Spot</option>');
      $.each(data, function (i, spot) {
        $spotSelect.append(`<option value="${spot.id}">${spot.name}</option>`);
      });
      $spotSelect.prop('disabled', false);
    }).fail(function () {
      $spotSelect.html('<option value="">Error loading spots</option>');
    });
  }

  // Ward change
  $(document).on('change', '#ward_id', function () {
    const wardId = $(this).val();
    const $area  = $('#area_id');
    const $spot  = $('#spot_id');
    loadAreas(wardId, $area, $spot);
    checkDuplicate();
  });

  // Area change
  $(document).on('change', '#area_id', function () {
    const areaId = $(this).val();
    const $spot  = $('#spot_id');
    loadSpots(areaId, $spot);
    checkDuplicate();
  });

  // ---- DUPLICATE COMPLAINT DETECTION -----------------------
  let dupTimeout = null;

  function checkDuplicate() {
    const catId  = $('#category_id').val();
    const areaId = $('#area_id').val();
    const $warn  = $('#duplicateWarning');

    if (!catId || !areaId) { $warn.hide(); return; }

    clearTimeout(dupTimeout);
    dupTimeout = setTimeout(function () {
      $.getJSON(window.APP_URL + '/api/check_duplicate.php',
        { category_id: catId, area_id: areaId },
        function (data) {
          if (data.is_duplicate) {
            $warn.show().find('.dup-msg').text(
              `Similar complaint #${data.complaint_no} was filed ${data.days_ago} day(s) ago in this area. This will be marked as Repeated Complaint.`
            );
          } else {
            $warn.hide();
          }
        }
      );
    }, 500);
  }

  $(document).on('change', '#category_id', checkDuplicate);

  // ---- LIVE COMPLAINT TRACKING -----------------------------
  function refreshComplaintStatus() {
    const $tracker = $('#liveTracker');
    if (!$tracker.length) return;

    const complaintId = $tracker.data('complaint-id');
    $.getJSON(window.APP_URL + '/api/complaint_status.php', { id: complaintId }, function (data) {
      if (data.error) return;
      $tracker.find('.live-status').html(
        `<span class="badge badge-${data.status}">${data.status.replace(/_/g,' ')}</span>`
      );
      $tracker.find('.live-updated').text('Last updated: ' + data.updated_at);
    });
  }

  // Refresh every 30 seconds
  if ($('#liveTracker').length) {
    refreshComplaintStatus();
    setInterval(refreshComplaintStatus, 30000);
  }

  // ---- CLIENT-SIDE FORM VALIDATION -------------------------
  $(document).on('submit', '#complaintForm', function (e) {
    let valid = true;

    // Clear old errors
    $('.form-error').remove();
    $('.form-control').removeClass('error');

    // Required fields
    const required = ['title', 'description', 'category_id', 'ward_id', 'area_id', 'spot_id'];
    required.forEach(function (field) {
      const $el  = $(`#${field}`);
      const val  = $el.val().trim();
      if (!val) {
        $el.addClass('error');
        $el.after(`<span class="form-error"><i class="fa fa-exclamation-circle"></i> This field is required.</span>`);
        valid = false;
      }
    });

    // Title min length
    const $title = $('#title');
    if ($title.val().trim().length < 10) {
      $title.addClass('error');
      $title.after(`<span class="form-error">Title must be at least 10 characters.</span>`);
      valid = false;
    }

    // Description min length
    const $desc = $('#description');
    if ($desc.val().trim().length < 20) {
      $desc.addClass('error');
      $desc.after(`<span class="form-error">Description must be at least 20 characters.</span>`);
      valid = false;
    }

    // File validation
    const $file = $('#attachment');
    if ($file.length && $file[0].files.length > 0) {
      const file = $file[0].files[0];
      const allowed = ['image/jpeg', 'image/png', 'application/pdf'];
      const maxSize = 5 * 1024 * 1024;
      if (!allowed.includes(file.type)) {
        $file.addClass('error');
        $file.after('<span class="form-error">Only JPG, PNG, PDF allowed.</span>');
        valid = false;
      } else if (file.size > maxSize) {
        $file.addClass('error');
        $file.after('<span class="form-error">File size must be under 5MB.</span>');
        valid = false;
      }
    }

    if (!valid) {
      e.preventDefault();
      $('html, body').animate({ scrollTop: $('.form-error:first').offset().top - 100 }, 400);
    }
  });

  // ---- COMPLAINT SEARCH (live filter in list) --------------
  $('#searchInput').on('keyup', function () {
    const term = $(this).val().toLowerCase();
    $('tbody tr').each(function () {
      const txt = $(this).text().toLowerCase();
      $(this).toggle(txt.includes(term));
    });
  });

  // ---- CONFIRM DIALOGS ------------------------------------
  $(document).on('click', '.confirm-action', function (e) {
    const msg = $(this).data('confirm') || 'Are you sure?';
    if (!confirm(msg)) e.preventDefault();
  });

  // ---- DARK MODE on page load ---------------------------
  const savedTheme = getCookie('theme');
  if (savedTheme === 'dark') {
    $html.attr('data-theme', 'dark');
    $('body').addClass('dark-mode');
  }

});

// Cookie helper
function getCookie(name) {
  const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
  return match ? match[2] : null;
}

// Expose APP_URL globally — set via inline script in pages
window.APP_URL = window.APP_URL || '';
