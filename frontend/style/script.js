
// -----------------------------------------------------------------------------
// Collapsing blocks
// -----------------------------------------------------------------------------

$(document).ready(function(){
	$(".collapsable .title").click(function(){
		$(".content",this.parentNode).slideToggle(200,function(){
			if (this.style.display == 'none') {
				$(this.parentNode).addClass("collapsed");
			} else {
				$(this.parentNode).removeClass("collapsed");
			}
		});
	});
});

// -----------------------------------------------------------------------------
// Autosugest user filter
// -----------------------------------------------------------------------------

$(document).ready(function(){
	$("#user_filter").autocomplete("ajax_user_list.php",{
		minChars: 3,
		formatItem: function(item) {
			return item[0] + " <small>(" + item[1] + ")</small>";
		},
		onItemSelect: function(data) {
			//if (user_filter_handler) {
			//	location.href = load_url + data.extra;
			//}
			$("#user_filter").focus();
		}
	});
});

