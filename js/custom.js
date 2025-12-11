(function($) {
    "use strict";
	
	/* ..............................................
	   Loader 
	   ................................................. */
	$(window).on('load', function() {
		$('.preloader').fadeOut();
		$('#preloader').delay(550).fadeOut('slow');
		$('body').delay(450).css({
			'overflow': 'visible'
		});
	});

	/* ..............................................
	   Fixed Menu
	   ................................................. */

	$(window).on('scroll', function() {
		if ($(window).scrollTop() > 50) {
			$('.main-header').addClass('fixed-menu');
		} else {
			$('.main-header').removeClass('fixed-menu');
		}
	});

	/* ..............................................
	   Gallery
	   ................................................. */

	$('#slides-shop').superslides({
		inherit_width_from: '.cover-slides',
		inherit_height_from: '.cover-slides',
		play: 5000,
		animation: 'fade',
	});

	$(".cover-slides ul li").append("<div class='overlay-background'></div>");

	/* ..............................................
	   Map Full
	   ................................................. */

	$(document).ready(function() {
		$(window).on('scroll', function() {
			if ($(this).scrollTop() > 100) {
				$('#back-to-top').fadeIn();
			} else {
				$('#back-to-top').fadeOut();
			}
		});
		$('#back-to-top').click(function() {
			$("html, body").animate({
				scrollTop: 0
			}, 600);
			return false;
		});
	});

	/* ..............................................
	   Special Menu
	   ................................................. */

	var Container = $('.container');
	Container.imagesLoaded(function() {
		var portfolio = $('.special-menu');
		portfolio.on('click', 'button', function() {
			$(this).addClass('active').siblings().removeClass('active');
			var filterValue = $(this).attr('data-filter');
			$grid.isotope({
				filter: filterValue
			});
		});
		var $grid = $('.special-list').isotope({
			itemSelector: '.special-grid'
		});
	});

	/* ..............................................
	   BaguetteBox
	   ................................................. */

	baguetteBox.run('.tz-gallery', {
		animation: 'fadeIn',
		noScrollbars: true
	});

	/* ..............................................
	   Offer Box
	   ................................................. */

	$('.offer-box').inewsticker({
		speed: 3000,
		effect: 'fade',
		dir: 'ltr',
		font_size: 13,
		color: '#ffffff',
		font_family: 'Montserrat, sans-serif',
		delay_after: 1000
	});

	/* ..............................................
	   Tooltip
	   ................................................. */

	$(document).ready(function() {
		$('[data-toggle="tooltip"]').tooltip();
	});

	/* ..............................................
	   Owl Carousel Instagram Feed
	   ................................................. */

	$('.main-instagram').owlCarousel({
		loop: true,
		margin: 0,
		dots: false,
		autoplay: true,
		autoplayTimeout: 3000,
		autoplayHoverPause: true,
		navText: ["<i class='fas fa-arrow-left'></i>", "<i class='fas fa-arrow-right'></i>"],
		responsive: {
			0: {
				items: 2,
				nav: true
			},
			600: {
				items: 3,
				nav: true
			},
			1000: {
				items: 5,
				nav: true,
				loop: true
			}
		}
	});

	/* ..............................................
	   Featured Products
	   ................................................. */

	$('.featured-products-box').owlCarousel({
		loop: true,
		margin: 15,
		dots: false,
		autoplay: true,
		autoplayTimeout: 3000,
		autoplayHoverPause: true,
		navText: ["<i class='fas fa-arrow-left'></i>", "<i class='fas fa-arrow-right'></i>"],
		responsive: {
			0: {
				items: 1,
				nav: true
			},
			600: {
				items: 3,
				nav: true
			},
			1000: {
				items: 4,
				nav: true,
				loop: true
			}
		}
	});

	/* ..............................................
	   Scroll
	   ................................................. */

	$(document).ready(function() {
		$(window).on('scroll', function() {
			if ($(this).scrollTop() > 100) {
				$('#back-to-top').fadeIn();
			} else {
				$('#back-to-top').fadeOut();
			}
		});
		$('#back-to-top').click(function() {
			$("html, body").animate({
				scrollTop: 0
			}, 600);
			return false;
		});
	});


	/* ..............................................
	   Slider Range
	   ................................................. */

	$(function() {
		$("#slider-range").slider({
			range: true,
			min: 0,
			max: 4000,
			values: [1000, 3000],
			slide: function(event, ui) {
				$("#amount").val("$" + ui.values[0] + " - $" + ui.values[1]);
			}
		});
		$("#amount").val("$" + $("#slider-range").slider("values", 0) +
			" - $" + $("#slider-range").slider("values", 1));
	});

	/* ..............................................
	   NiceScroll
	   ................................................. */

	$(".brand-box").niceScroll({
		cursorcolor: "#9b9b9c",
	});

	$(document).ready(function() {
		var authBoxes = document.querySelectorAll('[data-auth-box]');
		if (!authBoxes.length) {
			return;
		}

		var renderAuthState = function(payload) {
			if (!payload || !payload.authenticated) {
				return;
			}
			Array.prototype.forEach.call(authBoxes, function(box) {
				box.classList.add('authenticated');
				var nameEl = box.querySelector('[data-user-name]');
				if (nameEl) {
					nameEl.textContent = payload.name || 'Cliente';
				}
				var idEl = box.querySelector('[data-user-id]');
				if (idEl) {
					idEl.textContent = 'ID #' + (payload.id || 0);
				}
				var pointsEl = box.querySelector('[data-user-points]');
				if (pointsEl) {
					var points = typeof payload.points === 'number' ? payload.points : parseInt(payload.points || 0, 10);
					pointsEl.textContent = (isNaN(points) ? 0 : points) + ' pts';
				}
			});
		};

		var handleError = function(error) {
			if (window && window.console && console.warn) {
				console.warn('No se pudo obtener la sesión del usuario', error);
			}
		};

		var requestUrl = 'php/session-info.php?ts=' + Date.now();

		if (window.fetch) {
			fetch(requestUrl, {
				credentials: 'include',
				headers: {
					'Accept': 'application/json'
				}
			}).then(function(response) {
				if (!response.ok) {
					throw new Error('Respuesta no válida');
				}
				return response.json();
			}).then(renderAuthState).catch(function(error) {
				handleError(error);
				$.ajax({
					url: requestUrl,
					dataType: 'json',
					cache: false
				}).done(renderAuthState).fail(handleError);
			});
		} else {
			$.ajax({
				url: requestUrl,
				dataType: 'json',
				cache: false
			}).done(renderAuthState).fail(handleError);
		}
	});
	
}(jQuery));

// Cargar comportamiento de carrito en todas las páginas de forma diferida
(function() {
    var script = document.createElement('script');
    script.src = 'js/cart.js';
    script.defer = true;
    document.body.appendChild(script);
})();