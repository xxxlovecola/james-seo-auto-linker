(function($) {
    $(function() {
        $('.tags input').on('focusout', function() {
            var txt = this.value.replace(/[^a-zA-Z0-9\+\-\.\#\s]/g, '').trim();
            if (txt) {
                $(this).before('<span class="tag" tabindex="0" role="button" aria-label="Remove ' + txt.toLowerCase() + '">' + txt.toLowerCase() + '</span>');
            }
            this.value = "";
        }).on('keydown', function(e) {
            // Enter or comma
            if (e.which === 13 || e.which === 188) {
                e.preventDefault();
                $(this).blur().focus();
            }
            // Backspace on empty input - remove last tag
            if (e.which === 8 && this.value === '') {
                $(this).prev('.tag').remove();
            }
        });
        
        // Click to remove
        $('.tags').on('click', '.tag', function() {
            $(this).fadeOut(200, function() {
                $(this).remove();
            });
        });
        
        // Keyboard support for tag removal
        $('.tags').on('keydown', '.tag', function(e) {
            if (e.which === 13 || e.which === 32 || e.which === 46) { // Enter, Space, or Delete
                e.preventDefault();
                $(this).fadeOut(200, function() {
                    $(this).remove();
                });
            }
        });
    });
})(jQuery);

jQuery(function($) {
	$("#seoautoform").submit(function(e) {
	  var self = this;
	  e.preventDefault();
	  $('.tags').each(function() {
		var strng = "";
		$(this).children('span').each(function(){
		  if(strng !== ''){
			strng = strng + ', ' + $(this).text();
		  }
		  else {
			strng = $(this).text();
		  }
		  $(this).remove();
		});
		$(this).children('input').val(strng);
	  });
	  self.submit();
	});
});

jQuery(document).ready(function($) {
	$('.tags').each(function() {
     if($(this).children('input').val() !== '') {
       var array = $(this).children('input').val().split(",");
       var self = $(this).children('input');
       $.each(array,function(i){
         $('<span class="tag">'+array[i]+'</span>').insertBefore(self);
         self.val('');
       });
     }
    });
});