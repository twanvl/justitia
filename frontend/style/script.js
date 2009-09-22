
// -----------------------------------------------------------------------------
// Collapsing blocks
// -----------------------------------------------------------------------------

function update_collapsed() {
	if (this.style.display == 'none') {
		$(this.parentNode).addClass("collapsed");
	} else {
		$(this.parentNode).removeClass("collapsed");
	}
}

// -----------------------------------------------------------------------------
// Autorefresh
// -----------------------------------------------------------------------------

function refresh_submission() {
	//if (this.id.match(/submission-\d*/) && $(".pending",this).size() > 0) {
	var subm = this.parentNode;
	var content = this;
	if (subm.id.match(/submission-\d*/) && subm.className.match(/pending/)) {
		var submissionid = subm.id.substr(11);
		setTimeout(function(){
			$(content).load("ajax_submission.php?submissionid=" + submissionid, [], refresh_submission_on_load);
		}, 1000);
	}
}
function refresh_submission_on_load() {
	var subm = this.parentNode;
	// set new class
	var newstatus = $(".newstatus",this).get(0);
	if (newstatus && !newstatus.className.match(/pending/)) {
		$(subm).removeClass("pending")
		       .addClass(newstatus.className);
	} else {
		refresh_submission.call(this);
	}
}

$(document).ready(function(){
	// collapse/uncollapse block
	$(".collapsable .title").click(function(){
		$(".content",this.parentNode).slideToggle(200,update_collapsed);
	});
	// appearing submissions
	$(".appear").hide();
	$(".appear").fadeIn(600);
	// refresh pending submissions
	//$(".refreshing-submission").each(refresh_submission);
	$(".submission.pending .content").each(refresh_submission);
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

