/*
 * EPS 301 Redirects
 * Backend GUI pointers
 * (c) WebFactory Ltd
 */


jQuery(document).ready(function($){
  if (typeof eps_pointers  == 'undefined') {
    return;
  }

  $.each(eps_pointers, function(index, pointer) {
    if (index.charAt(0) == '_') {
      return true;
    }
    $(pointer.target).pointer({
        content: '<h3>301 Redirects</h3><p>' + pointer.content + '</p>',
        position: {
            edge: pointer.edge,
            align: pointer.align
        },
        width: 320,
        close: function() {
                $.post(ajaxurl, {
                    pointer_name: index,
                    _ajax_nonce: eps_pointers._nonce_dismiss_pointer,
                    action: 'eps_dismiss_pointer'
                });
        }
      }).pointer('open');
  });
});
