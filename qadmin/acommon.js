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

	$(function(){

		var answer = confirm("Are you sure you want to delete the image?");

		if (answer) {
			$.get("updateimage.php",{mode: 'delete', picnum: pid}, function(j){
		
				$("tr#" + pid + " .operations").before('<center><font color=green>' + j + '</font></center>').remove();

				if (j!="invalid mode" && j!="error")
					$("tr#" + pid).fadeOut(2000);
			});
		}
	});
}


function rotateimage(pid, direction) {


	$(function(){

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
	
	});
}

