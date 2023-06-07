/*
 * @package    EPS 301 Redirects
 * @author     WebFactory Ltd
 */

jQuery(document).ready(function($) {
  /**
   * Loads the relevant sub-selector based on the primary selector.
   */
  $(document).on('change', 'select.type-select', function() {
    var input_type = $(this).val();
    $(this)
      .siblings()
      .hide();
    $(this)
      .parent('td')
      .find('.select-' + input_type)
      .show();
  });

  /**
   * When a select box is changed, send that new value to our input.
   */
  $(document).on('change', 'select.url-selector', function() {
    $(this)
      .parent('td')
      .find('.eps-url-input')
      .val($(this).val());
  });

  /**
   * Edit a Redirect
   * Gets the redirect edit form, and replaces the row.
   */
  $('.eps-table').on('click', '.redirect-actions a.eps-redirect-edit', function(
    e
  ) {
    e.preventDefault();
    var redirect_id = $(this).data('id');
    // Do the request
    $.ajax({
      type: 'POST',
      url: ajaxurl,
      data: {
        action: 'eps_redirect_get_inline_edit_entry',
        _ajax_nonce: eps_301.nonce_get_inline_edit_entry,
        redirect_id: redirect_id
      },
      success: function(data) {
        var data = jQuery.parseJSON(data);
        $('#eps-redirect-edit').remove();
        $('tr.redirect-entry').removeClass('active');
        $('tr.redirect-entry[data-id=' + data.redirect_id + ']').addClass(
          'active'
        );
        $(data.html).insertAfter(
          'tr.redirect-entry[data-id=' + data.redirect_id + ']'
        );
        $('#eps-redirect-add').show();
      },
      error: function() {
        // failed request; give feedback to user
        alert("Couldn't find this redirect.");
      }
    });
  });

  /**
   * Cancel an Edit.
   * Cancels the Edit implement on a redirect entry.
   */
  $('.eps-table').on('click', 'a.eps-redirect-cancel', function(e) {
    e.preventDefault();
    $('#eps-redirect-edit').remove();
    $('tr.redirect-entry').removeClass('active');
    $('#eps-redirect-add').show();
  });

  /**
   * AJAX Save a New or Existing Redirect.
   * Checks for a form submission, then handles it VIA ajax.
   */
  $('.eps-table').on('submit', '#eps-redirect-save', function(e) {
    e.preventDefault();

    $('#eps-redirect-save input[type="submit"]').prop('disabled', true); // Disable button to disallow multiple submissions.

    $.ajax({
      type: 'POST',
      url: ajaxurl,
      data: {
        action: 'eps_redirect_save',
        _ajax_nonce: eps_301.nonce_save_redirect,
        id: $('#eps-redirect-save input[name="redirect[id][]"]').val(),
        status: $('#eps-redirect-save select[name="redirect[status][]"]').val(),
        url_from: $(
          '#eps-redirect-save input[name="redirect[url_from][]"]'
        ).val(),
        url_to: $('#eps-redirect-save input[name="redirect[url_to][]"]').val()
      },
      success: function(data) {
        // Successful Request:
        var data = jQuery.parseJSON(data);

        $('#eps-redirect-edit').remove();
        $('tr.redirect-entry').removeClass('active');
        if ($('tr.redirect-entry[data-id=' + data.redirect_id + ']').length) {
          // entry exists, so update it
          if ($('tr.redirect-entry[data-status="404"]').length) {
            $('tr.redirect-entry[data-id=' + data.redirect_id + ']').hide();
          } else {
            $(
              'tr.redirect-entry[data-id=' + data.redirect_id + ']'
            ).replaceWith(data.html);
          }
        } else {
          // new entry, add it
          $(data.html).insertAfter('tr#eps-redirect-add');
          $('#eps-redirect-add').show();
        }
        $('#eps-redirect-save input[type="submit"]').prop('disabled', false); // Re-enable button.
      },
      error: function() {
        // failed request; give feedback to user
        alert("Couldn't find this redirect.");
        $('#eps-redirect-save input[type="submit"]').prop('disabled', false); // Re-enable button.
      }
    });
  });

  /**
   * New Redirect.
   * Get a new blank edit form for a new redirect.
   * We expect to receive an id of 0 returned from the Ajax query.
   *
   */
  $('.eps-table #eps-redirect-new').on('click', function(e) {
    e.preventDefault();
    $(this).prop('disabled', true);
    $(this).attr('disabled', 'disabled'); // Disable button to disallow multiple submissions.

    $.ajax({
      type: 'POST',
      url: ajaxurl,
      data: {
        action: 'eps_redirect_get_inline_edit_entry',
        _ajax_nonce: eps_301.nonce_get_inline_edit_entry,
        redirect_id: false
      },
      success: function(data) {
        var data = jQuery.parseJSON(data);
        $('#eps-redirect-edit').remove();
        $('tr.redirect-entry').removeClass('active');

        if (data.redirect_id == 0) {
          // If it's new, do a new.
          $('#eps-redirect-add').hide();
          $(data.html).insertBefore('tr#eps-redirect-add');
        } else {
          alert('Something strange happened. A new entry could not be loaded.');
        }
        $('#eps-redirect-new').removeProp('disabled');
        $('#eps-redirect-new').attr('disabled', false); // Disable button to disallow multiple submissions.
      },
      error: function() {
        // failed request; give feedback to user
        alert('A new entry form could not be loaded.');
        $('#eps-redirect-new').removeProp('disabled');
        $('#eps-redirect-new').attr('disabled', false); // Disable button to disallow multiple submissions.
      }
    });
  });

  /**
   * Delete an entry.
   */
  $('.redirect-actions a.eps-redirect-remove').on('click', function(e) {
    e.preventDefault();

    if (!confirm('Are you sure you want to delete this redirect rule? There is no undo!')) {
      return false;
    }

    if ($(this).attr('disabled')) return false;

    $(this).prop('disabled', true);
    $(this).attr('disabled', 'disabled'); // Disable button to disallow multiple submissions.

    var request = $.post(ajaxurl, {
      action: 'eps_redirect_delete_entry',
      _ajax_nonce: eps_301.nonce_delete_entry,
      id: $(this).data('id')
    });
    request.done(function(data) {
      var response = JSON.parse(data);
      $('tr.redirect-entry.id-' + response.id).fadeOut();
      $(this).prop('disabled', false);
      $(this).attr('disabled', false); // Disable button to disallow multiple submissions.
    });
  });

  /**
   * Delete all redirect rules
   */
   $('#eps_delete_rules').on('click', function(e) {
    if (confirm('Are you sure you want to delete ALL redirect rules? There is NO undo!')) {
      return true;
    } else {
      e.preventDefault();
      return false;
    }
  });

  /**
   * Reset redirect stats
   */
   $('#eps_reset_stats').on('click', function(e) {
    if (confirm('Are you sure you want to reset hits count on all redirect rules? There is NO undo!')) {
      return true;
    } else {
      e.preventDefault();
      return false;
    }
  });

  /**
   * Tabs
   */
  $('#eps-tab-nav .eps-tab-nav-item').on('click', function(e) {
    var target = $(this).attr('href');

    $('#eps-tabs .eps-tab').hide();

    $(target + '-pane')
      .show()
      .height('auto');

    $('#eps-tab-nav .eps-tab-nav-item').removeClass('active');
    $(this).addClass('active');
    //return false;
  });

  // PRO related stuff
  $('.nav-tab-wrapper a.pro-ad').on('click', function(e) {
    e.preventDefault();
    pro_feature = 'tab';

    $('#eps-pro-dialog').dialog('open');

    $('#eps-pro-table .button-buy').each(function(ind, el) {
      tmp = $(el).data('href-org');
      tmp = tmp.replace('pricing-table', pro_feature);
      $(el).attr('href', tmp);
    });

    return false;
  });

  $('#wpwrap').on('click', '.open-301-pro-dialog', function(e) {
    e.preventDefault();

    $('#eps-pro-dialog').dialog('open');

    pro_feature = $(this).data('pro-feature');
    if (!pro_feature) {
      pro_feature = 'unknown';
    }

    $('#eps-pro-table .button-buy').each(function(ind, el) {
      tmp = $(el).data('href-org');
      tmp = tmp.replace('pricing-table', pro_feature);
      $(el).attr('href', tmp);
    });

    return false;
  });

  $('#eps-pro-dialog').dialog({
    dialogClass: 'wp-dialog eps-pro-dialog',
    modal: true,
    resizable: false,
    width: 850,
    height: 'auto',
    show: 'fade',
    hide: 'fade',
    close: function (event, ui) {
    },
    open: function (event, ui) {
      $(this).siblings().find('span.ui-dialog-title').html('WP 301 Redirects PRO is here!');
      eps_fix_dialog_close(event, ui);
    },
    autoOpen: false,
    closeOnEscape: true,
  });

  if (eps_301.auto_open_pro_dialog) {
    $('#eps-pro-dialog').dialog('open');
  }

  if(window.location.hash == '#open-pro-dialog' && !eps_301.auto_open_pro_dialog) {
    pro_feature = 'url-hash';

    $('#eps-pro-dialog').dialog('open');

    $('#eps-pro-table .button-buy').each(function(ind, el) {
      tmp = $(el).data('href-org');
      tmp = tmp.replace('pricing-table', pro_feature);
      $(el).attr('href', tmp);
    });
  };
}); // on ready

function eps_fix_dialog_close(event, ui) {
  jQuery('.ui-widget-overlay').bind('click', function () {
    jQuery('#' + event.target.id).dialog('close');
  });
} // eps_fix_dialog_close
