
// -----------------------------------------------------------------------------
// Collapsing things
// -----------------------------------------------------------------------------

$(document).ready(function(){
	$(".collapsable .title").click(function(){
		$(".content",this.parentNode).slideToggle(200);
	});
});
