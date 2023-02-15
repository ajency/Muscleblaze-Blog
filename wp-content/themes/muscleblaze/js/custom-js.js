/*search toggle */
$(".search-toogle").click(function (e) {
	$(".search-form").toggleClass("show");
	return false;
});
// Clicking outside the search form closes it
$(document).click(function (event) {
	var target = $(event.target);

	if (!target.is(".search-toogle")) {
		$(".search-form").removeClass("show");
	}
});

/* header menu toggle */

$(document).ready(function () {
	$(".cross").hide();
	// $(".mobile-menu-container-expandable").hide();
	$(".hamburger").click(function () {
		$(".mobile-menu-container-expandable").slideToggle("slow", function () {
			$(".hamburger").hide();
			$(".cross").show();
		});
	});

	$(".cross").click(function () {
		$(".mobile-menu-container-expandable").slideToggle("slow", function () {
			$(".cross").hide();
			$(".hamburger").show();
		});
	});
});
