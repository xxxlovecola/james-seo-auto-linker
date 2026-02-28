
jQuery(document).ready(function($) {
  
	$('#adminmenu > li').each(function() {
		var checker_niddle = 'seo';
		var not_us = 'James SEO Auto Linker';
		var liData = $(this).html();
        //console.log(liData);
		if (!liData.includes(not_us) && !liData.includes('themes') && liData.includes(checker_niddle))
        {
			//console.log($(this).text());
			$(this).find('ul').append('<li><a href="options-general.php?page=james-seo-auto-linker">James SEO Auto Linker</a></li>');
		}
	});
});
