jQuery(document).ready(function($){
    var buttons = $('input[name="lists[]"]')
      , toggler = $('input.toggle_select_lists')
      , btnChange = function() {
        var count = buttons.length
          , checked = buttons.filter(':checked').length;
        if ( count == checked ) {
            toggler.prop('checked','checked');
        } else {
            toggler.prop('checked',false);          
        }
      }, tglChange = function(e) {
        if ( $(this).prop('checked') ) {
            buttons.prop('checked', 'checked');
            toggler.prop('checked', 'checked');
        } else {
            buttons.prop('checked', false);
            toggler.prop('checked', false);
        }
        e.preventDefault();
      }
    buttons.change(function(e){
        e.preventDefault();
        return btnChange();
    }).first().change();
    toggler.change(tglChange);
    $(document).on('click', 'label[for="mc-fetch"]', function(e){
        $(this).text(function(){
            return $(this).attr('data-loading') || $(this).text();
        });
    });
});