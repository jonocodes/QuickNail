/* 
 * QuickNail
 * 
 */

function confirmation(op, loc) {
	var answer = confirm("Are you sure you want to " + op + "?");
	if (answer){
		window.location = loc;
	}
}


function deleteimage(pid) {

		var answer = confirm("Are you sure you want to delete the image?");

		if (answer) {
			$.get("updateimage.php",{mode: 'delete', picnum: pid}, function(j){
		
				$("tr#" + pid + " .operations").before('<center><font color=green>' + j + '</font></center>').remove();

				if (j!="invalid mode" && j!="error")
					$("tr#" + pid).fadeOut(2000);
			});
		}
}


function rotateimage(pid, direction) {

		if (direction == "left")
			rmode = "rotateleft";
		else if (direction == "right")
			rmode = "rotateright";
		else
			return;

		$("tr#" + pid + " .operations").hide().before('<center><font color=green>rotating <img src=ajax-loader.gif></font></center>');

		$.get("updateimage.php",{mode: rmode, picnum: pid}, function(j){

			$("tr#" + pid + " .operations").prev().remove();
			$("tr#" + pid + " .operations").show();

			var image = $("tr#" + pid + " img").attr('src');
			$("tr#" + pid + " img").attr("src", image + "?uncached=" + new Date());

		});
	
}



$(document).ready(function(){

	$("input").blur(function () {

		var name = $(this).attr('name').split("__");
		var section = name[0];
		var field = name[1];
		var value = $(this).attr('value');

		$.get("updatesettings.php",{section: section, field: field, value: value}, function(j){

			$("#" + field + "message").html(j).show().fadeOut(2000);
			//if (j!="update")						// undo change if invalid
			//	$(this).attr('value', origval);
		})

    });
});

