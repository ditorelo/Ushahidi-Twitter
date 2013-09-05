function twitterTest() {
	$(document.getElementById('test_loading')).html('<img src="<?php echo url::file_loc('img')."media/img/loading_g.gif"; ?>">');
	var test_status = $('#test_status');
	test_status.html('');
	$.post("<?php echo url::site() . 'admin/settings/socialmedia/twitter/test' ?>", {  },
		function(data){
			if (data.status == 'success'){
				test_status.html(data.message);
			} else {
				test_status.html(data.message);
			}
			$(document.getElementById('test_loading')).html('');
	  	}, "json");
}
