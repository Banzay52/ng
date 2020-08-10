jQuery(document).ready(function($){
	$('.nasa-gallery').slick({
		dots: true,
		infinite: true,
		speed: 300,
		slidesToShow: 3,
		slidesToScroll: 1,
		arrows: true,
		adaptiveHeight: true
	});
});