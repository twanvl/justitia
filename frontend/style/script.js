
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
		// yes, there is a new status, so we can stop refreshing
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

// -----------------------------------------------------------------------------
// Greyed form fields
// -----------------------------------------------------------------------------

function enable_password_fields() {
	var auth_method = $("input:radio[name=user_auth_method]:checked").val();
	if (auth_method == undefined) return;
	if (auth_method == 'pass') {
		$("input[id^=user_password]").removeAttr("disabled");
	} else {
		$("input[id^=user_password]").attr("disabled","disabled");
	}
}
$(document).ready(function(){
	$("input:radio[name=user_auth_method]").change(enable_password_fields);
	enable_password_fields();
});

// -----------------------------------------------------------------------------
// Multiple file upload
// -----------------------------------------------------------------------------

function file_multiple_changed() {
 	var file = document.createElement("input");
 	file.setAttribute("type", "file");
 	file.setAttribute("name", this.name);
 	file.setAttribute("class", "multi-upload");
 	file.addEventListener("change", file_multiple_changed, false);
 	this.parentNode.appendChild(file);
}
$(document).ready(function(){
	$("input:file[multiple]").change(file_multiple_changed);
});

// -----------------------------------------------------------------------------
// Documentation fancyness
// -----------------------------------------------------------------------------

$(document).ready(function(){
	var active_to_viewer = null;
	$(".to-viewer").click(function(){
		var body = unescape(this.href).replace("data:text/html,","");
		$("#viewer").html(body);
		if (active_to_viewer) {
			$(active_to_viewer).removeClass("shown");
		}
		active_to_viewer = this;
		$(active_to_viewer).addClass("shown");
		return false;
	});
});

