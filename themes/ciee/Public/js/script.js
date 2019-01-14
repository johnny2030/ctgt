(function($) {
		
	
	var mainwindow = $(window);
	
    // rev-slider
    if (jQuery("#slider1").length) {
        jQuery("#slider1").revolution({
            sliderType:"standard",
            sliderLayout:"fullwidth",
            delay:5000,
            navigation: {
                  keyboardNavigation:"on", 
                  keyboard_direction:"horizontal",
                  mouseScrollNavigation:"off",   
                  onHoverStop:"on",
                  arrows: {
						style: 'zeus',
						tmp: '<div class="tp-title-wrap"><div class="tp-arr-imgholder"></div></div>',
                     enable:true,
                     rtl:false,
                     hide_onmobile:false,
                     hide_onleave:false,
                     hide_delay:200,
                     hide_delay_mobile:1200,
                     hide_under:0,
                     hide_over:9999,
                     tmp: ''
                  }
                },
			 parallax: {
						type: "scroll",
						origo: "slidercenter",
						speed: 1000,
						levels: [5, 10, 15, 20, 25, 30, 35, 40, 45, 46, 47, 48, 49, 50, 100, 55],
						type: "scroll",
					},
			responsiveLevels:[1240,1024,778,480],
			visibilityLevels:[1240,1024,778,480],
			gridwidth:[1170,1024,778,480],
			gridheight:[720,768,960,720]		

        });
    };
	
	    // rev-slider
    if (jQuery("#slider2").length) {
        jQuery("#slider2").revolution({
            sliderType:"standard",
            sliderLayout:"fullwidth",
            delay:5000,
            navigation: {
                  keyboardNavigation:"on", 
                  keyboard_direction:"horizontal",
                  mouseScrollNavigation:"off",   
                  onHoverStop:"on",
                  arrows: {
						style: 'zeus',
						tmp: '<div class="tp-title-wrap"><div class="tp-arr-imgholder"></div></div>',
                     enable:true,
                     rtl:false,
                     hide_onmobile:false,
                     hide_onleave:false,
                     hide_delay:200,
                     hide_delay_mobile:1200,
                     hide_under:0,
                     hide_over:9999,
                     tmp: ''
                  }
                },
			 parallax: {
						type: "scroll",
						origo: "slidercenter",
						speed: 1000,
						levels: [5, 10, 15, 20, 25, 30, 35, 40, 45, 46, 47, 48, 49, 50, 100, 55],
						type: "scroll",
					},
			responsiveLevels:[1240,1024,778,480],
			visibilityLevels:[1240,1024,778,480],
			gridwidth:[1170,1024,778,480],
			gridheight:[720,768,960,720]
        });
    };

	// rev-slider
    if (jQuery("#slider3").length) {
        jQuery("#slider3").revolution({
            sliderType:"standard",
            sliderLayout:"fullwidth",
            delay:5000,
            navigation: {
                  keyboardNavigation:"on", 
                  keyboard_direction:"horizontal",
                  mouseScrollNavigation:"off",   
                  onHoverStop:"on",
                  arrows: {
						style: 'zeus',
						tmp: '<div class="tp-title-wrap"><div class="tp-arr-imgholder"></div></div>',
                     enable:true,
                     rtl:false,
                     hide_onmobile:false,
                     hide_onleave:false,
                     hide_delay:200,
                     hide_delay_mobile:1200,
                     hide_under:0,
                     hide_over:9999,
                     tmp: ''
                  }
                },
			 parallax: {
						type: "scroll",
						origo: "slidercenter",
						speed: 1000,
						levels: [5, 10, 15, 20, 25, 30, 35, 40, 45, 46, 47, 48, 49, 50, 100, 55],
						type: "scroll",
					},
			responsiveLevels:[1240,1024,778,480],
			visibilityLevels:[1240,1024,778,480],
			gridwidth:[1170,1024,778,480],
			gridheight:[800,768,960,720]
        });
    };
	
	//Hide Loading Box (Preloader)
	function stylePreloader() {
		if($('.preloader').length){
			$('.preloader').delay(200).fadeOut(500);
		}		
    }	
			
	
	


	$(function(){
		var $grid = $('.list-project').isotope({
		   itemSelector: '.item-project',
			//layoutMode: 'fitRows'
			 filter: '.project_category-snow-removal',
			 percentPosition: true,
			 masonry: {
						columnWidth: '.item-project'
					  }
		});
		// filter items on button click
		$('.filter').on( 'click', 'li', function() {
		  var filterValue = $(this).attr('data-filter');
		  $grid.isotope({ filter: filterValue });
		  $(this).addClass('active');
		});	
			
		
	});
	
	
	
	
	//Accordion Box
	if($('.accordion-box').length){
		$(".accordion-box").on('click', '.acc-btn', function() {
			
			var outerBox = $(this).parents('.accordion-box');
			var target = $(this).parents('.accordion');
			
			if($(this).hasClass('active')!==true){
			$('.accordion .acc-btn').removeClass('active');
			
			}
			
			if ($(this).next('.acc-content').is(':visible')){
				return false;
			}else{
				$(this).addClass('active');
				$(outerBox).children('.accordion').removeClass('active-block');
				$(outerBox).children('.accordion').children('.acc-content').slideUp(300);
				target.addClass('active-block');
				$(this).next('.acc-content').slideDown(300);	
			}
		});	
	}
	

	  if ( $('#accordion > .panel').length) {
		$('#accordion > .panel').on('show.bs.collapse', function (e) {
			  var heading = $(this).find('.panel-heading');
			  heading.addClass("active-panel");
			  
	});
	$('#accordion > .panel').on('hidden.bs.collapse', function (e) {
        var heading = $(this).find('.panel-heading');
          heading.removeClass("active-panel");
    });
	}
	
	
  
	
	
	//service-list--
	if ($('.testimonial-1 .testi-list').length) {
		$('.testimonial-1 .testi-list').owlCarousel({
			loop:true,
			margin:0,
			nav:true,
			smartSpeed: 500,
			autoplay: 4000,
			items:1,
			dots:false,
			navText: [ '<span class="fa fa-angle-left"></span>', '<span class="fa fa-angle-right"></span>' ],
			responsive:{
				0:{
					items:1
				},
				600:{
					items:1
				},
				800:{
					items:1
				},
				1024:{
					items:1
				},
				1200:{
					items:1
				}
			}
		});    		
	}

    //letest-project--
	if ($('.fh-project-carousel  .list-project').length) {
		$('.fh-project-carousel  .list-project').owlCarousel({
			loop:true,
			margin:0,
			nav:true,
			smartSpeed: 500,
			autoplay: 4000,
			items:1,
			dots:false,
			navText: [ '<span class="fa fa-angle-left"></span>', '<span class="fa fa-angle-right"></span>' ],
			responsive:{
				0:{
					items:1
				},
				600:{
					items:2
				},
				800:{
					items:2
				},
				1024:{
					items:5
				},
				1200:{
					items:5
				}
			}
		});    		
	}	

   //partener--
	if ($('.list-item').length) {
		$('.list-item').owlCarousel({
			loop:true,
			margin:0,
			nav:false,
			smartSpeed: 500,
			autoplay: 4000,
			items:1,
			dots:false,
			navText: [ '<span class="fa fa-angle-left"></span>', '<span class="fa fa-angle-right"></span>' ],
			responsive:{
				0:{
					items:2
				},
				600:{
					items:3
				},
				800:{
					items:4
				},
				1024:{
					items:5
				},
				1200:{
					items:5
				}
			}
		});    		
	}		
	
	//testimonial1--
	if ($('.testimonial1 .fh-testimonials').length) {
		$('.testimonial1 .fh-testimonials').owlCarousel({
			loop:true,
			margin:30,
			nav:false,
			smartSpeed: 500,
			autoplay: 4000,
			items:1,
			dots:true,
			navText: [ '<span class="fa fa-angle-left"></span>', '<span class="fa fa-angle-right"></span>' ],
			responsive:{
				0:{
					items:1
				},
				600:{
					items:2
				},
				800:{
					items:2
				},
				1024:{
					items:3
				},
				1200:{
					items:3
				}
			}
		});    		
	}
	
	//testimonial1--
	if ($('.testmonial-3 .testi-list').length) {
		$('.testmonial-3 .testi-list').owlCarousel({
			loop:true,
			margin:0,
			nav:true,
			smartSpeed: 500,
			autoplay: 4000,
			items:1,
			dots:false,
			navText: [ '<span class="fa fa-angle-left"></span>', '<span class="fa fa-angle-right"></span>' ],
			responsive:{
				0:{
					items:1
				},
				600:{
					items:1
				},
				800:{
					items:2
				},
				1024:{
					items:2
				},
				1200:{
					items:2
				}
			}
		});    		
	}
	
	//testimonial1--
	if ($('.testimonial-4 .testi-list').length) {
		$('.testimonial-4 .testi-list').owlCarousel({
			loop:true,
			margin:0,
			nav:true,
			smartSpeed: 500,
			autoplay: 4000,
			items:1,
			dots:false,
			navText: [ '<span class="fa fa-angle-left"></span>', '<span class="fa fa-angle-right"></span>' ],
			responsive:{
				0:{
					items:1
				},
				600:{
					items:1
				},
				800:{
					items:1
				},
				1024:{
					items:1
				},
				1200:{
					items:1
				}
			}
		});    		
	}	
	
//testimonial1--
	if ($('.fh-testimonials-carousel .testi-list').length) {
		$('.fh-testimonials-carousel .testi-list').owlCarousel({
			loop:true,
			margin:0,
			nav:false,
			smartSpeed: 500,
			autoplay: 4000,
			items:1,
			dots:true,
			navText: [ '<span class="fa fa-angle-left"></span>', '<span class="fa fa-angle-right"></span>' ],
			responsive:{
				0:{
					items:1
				},
				600:{
					items:1
				},
				800:{
					items:1
				},
				1024:{
					items:1
				},
				1200:{
					items:1
				}
			}
		});    		
	}	
	
	//letest news--
	if ($('.letestnews2 .post-list').length) {
		$('.letestnews2 .post-list').owlCarousel({
			loop:true,
			margin:0,
			nav:false,
			smartSpeed: 500,
			autoplay: 4000,
			items:1,
			dots:false,
			navText: [ '<span class="fa fa-angle-left"></span>', '<span class="fa fa-angle-right"></span>' ],
			responsive:{
				0:{
					items:1
				},
				600:{
					items:2
				},
				800:{
					items:2
				},
				1024:{
					items:2
				},
				1200:{
					items:2
				}
			}
		});    		
	}	
		

	//letest news--
	if ($('.ourjourney .history-list').length) {
		$('.ourjourney .history-list').owlCarousel({
			loop:true,
			margin:0,
			nav:true,
			smartSpeed: 500,
			autoplay: 4000,
			items:1,
			dots:false,
			navText: [ '<span class="fa fa-angle-left"></span>', '<span class="fa fa-angle-right"></span>' ],
			responsive:{
				0:{
					items:1
				},
				600:{
					items:2
				},
				800:{
					items:2
				},
				1024:{
					items:3
				},
				1200:{
					items:3
				}
			}
		});    		
	}	
   	
	//Contact Form Validation
	if($('#contact-form').length){
		$('#contact-form').validate({
			rules: {
				username: {
					required: true
				},
				email: {
					required: true,
					email: true
				},
				phone: {
					required: true
				},
				message: {
					required: true
				}
			}
		});
	}
	
	
	
		// FAQs
	if($('.fp-faq').length){			
			$( '.fp-faq' ).on( 'click', 'h2', function() {
				var $faq = $( this ).closest( '.fp-faq' );

				$faq.find( '.toggle-content' ).slideToggle( 500, function() {
					$faq.toggleClass( 'active' );
				} );
			} );
	}
	if($('.fp-accordion').length){		
		
		$( '.fp-accordion' ).on( 'click', 'h2, .icons', function() {
			var $faq = $( this ).closest( '.fp-accordion' );

			$faq.find( '.toggle-content' ).slideToggle( 500, function() {
				$faq.toggleClass( 'active' );
			} );
		} );
		}	
	
	
	

	//Update header style + Scroll to Top
	function scrolltotop() {
		if($('.scroll-to-top').length){
			var windowpos = mainwindow.scrollTop();
			if (windowpos >= 250) {
				$('.scroll-to-top').fadeIn(300);
			} else {
				$('.scroll-to-top').fadeOut(300);
			}
		}
	}
	
	// Scroll to a Specific Div
	if($('.scroll-to-target').length){
		$(".scroll-to-target").on('click', function() {
			var target = $(this).attr('data-target');
		   // animate
		   $('html, body').animate({
			   scrollTop: $(target).offset().top
			 }, 1000);
	
		});
	}
	
	// slider-range
	if($('#slider-range').length){
	  $( function() {
		$( "#slider-range" ).slider({
		  range: true,
		  min: 0,
		  max: 500,
		  values: [ 75, 300 ],
		  slide: function( event, ui ) {
			$( "#amount" ).val( "$" + ui.values[ 0 ] + " - $" + ui.values[ 1 ] );
		  }
		});
		$( "#amount" ).val( "$" + $( "#slider-range" ).slider( "values", 0 ) +
		  " - $" + $( "#slider-range" ).slider( "values", 1 ) );
	  });	
	 }

 
    //Input Quantity Up & Down
    function quantity_changer() {
		$('#quantity-holder').on('click', '.quantity-plus', function () {
			var $holder = $(this).parents('.quantity-holder');
			var $target = $holder.find('input.quantity-input');
			var $quantity = parseInt($target.val(),10);
			if ($.isNumeric($quantity) && $quantity > 0) {
				$quantity = $quantity + 1;
				$target.val($quantity);
			} else {
				$target.val($quantity);
			}
		}).on('click', '.quantity-minus', function () {
			var $holder = $(this).parents('.quantity-holder');
			var $target = $holder.find('input.quantity-input');
			var $quantity = parseInt($target.val(),10);
			if ($.isNumeric($quantity) && $quantity >= 2) {
				$quantity = $quantity - 1;
				$target.val($quantity);
			} else {
				$target.val(1);
			}
		});

	}


	
	//counter number changer
	function counter_number() {
		var timer = $('.timer');
		if(timer.length) {
			timer.appear(function () {
				timer.countTo();
			})
		}
	}
	
			
	
	  
/* ==========================================================================
   When document is ready, do
   ========================================================================== */
   
  $(document).on('ready', function() {
    counter_number();
    quantity_changer();
  });

  
  
/* ==========================================================================
   When document is Scrollig, do
   ========================================================================== */
  
  mainwindow.on('scroll', function() {
	scrolltotop();
    
  });
  
/* ==========================================================================
   When document is loading, do
   ========================================================================== */
  
  mainwindow.on('load', function() {
    stylePreloader();
  });
  

/* ==========================================================================
   When Window is resizing, do
   ========================================================================== */
  
  mainwindow.on('resize', function() {
  });	  
	  
	  
	  	

})(window.jQuery);