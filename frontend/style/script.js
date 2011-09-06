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
// Update feed for latest submissions
// -----------------------------------------------------------------------------

function latest_submission_updates() {
	// update page
	$('#newsubmissionsbox').hide();
	// entity
	var entity;
	index = window.location.pathname.indexOf("admin_submissions.php");
	if(index > 0) {
		entity = window.location.pathname.substring(index + "admin_submissions.php".length);
	}
	setInterval(function() {
		// submission
		var subm;
		$("div.title span").each(function() {
			if($(this).text().indexOf(":") > 0) {
				subm = $(this).text().substring(0, $(this).text().indexOf(":"));
				return false;
			}
		});
		// ajax
		var url = "ajax_latestsubmissions.php?entity=" + entity + "&submissionid=" + subm;
		$.getJSON(url, function(data) {
			new_submissions = data.new_ids;
			update_box();
			update_title();
		});
	}, 5000);
	// bind button
	$('#newsubmissionsbox').click(function(event) {
		event.preventDefault();
		load_submissions();
		$(this).hide();
		update_title();
		return false;
	});
}

function update_box() {
	if(new_submissions.length > 0) {
		$('#newsubmissionsbox').show();
		if(new_submissions.length == 1) {
			$('#newsubmissionsbox').text('There is 1 new submission. Click here to load.');
		} else {
			$('#newsubmissionsbox').text('There are '+new_submissions.length+' new submissions. Click here to load.');
		}
	}
}

function update_title() {
	if(new_submissions == null || new_submissions.length == 0) {
		document.title = "Latest submissions";
	} else {
		document.title = "(" + new_submissions.length + ") Latest submissions";
	}
}

function load_submissions() {
	var submissions = new_submissions.reverse();
	new_submissions = null;
	for(i = 0; i < submissions.length; i++) {
		$.ajax({
			url: "ajax_submission.php?submissionid="+submissions[i]+"&write_block=true",
			async: false,
			success: function(data, textStatus, jqXHR){
				$(data).insertAfter('#newsubmissionsbox');
			}
		});
	}
	$(".submission.pending .content").each(refresh_submission);
}

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
 	$(file).change(file_multiple_changed);
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

