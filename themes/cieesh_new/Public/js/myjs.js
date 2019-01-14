$(document).ready(function() {
            $('#horizontalTab').easyResponsiveTabs({
                type: 'default', //Types: default, vertical, accordion
                width: 'auto', //auto or any width like 600px
                fit: true, // 100% fit in a container
                closed: 'accordion', // Start closed if in accordion view
                activate: function(event) { // Callback function if tab is switched
                    var $tab = $(this);
                    var $info = $('#tabInfo');
                    var $name = $('span', $info);

                    $name.text($tab.text());

                    $info.show();

                }
            });

            $('#ab').easyResponsiveTabs({
                type: 'vertical',
                width: 'auto',
                fit: true,
            });


            $('#cmt').easyResponsiveTabs({
                type: 'vertical',
                width: 'auto',
                fit: true,
            });
        });       

 function showhide() {
            var div = document.getElementById("newpost");
            if (div.style.display !== "none") {
                div.style.display = "none";
            } else {
                div.style.display = "block";
            }
        }



$(window).load(function() {
            $('.btn-nav').on('click tap', function() {
                $('.nav-content').toggleClass('showNav hideNav').removeClass('hidden');
                $(this).toggleClass('animated');
            });
            $('.nav-list').on('click tap', function() {

            })
            $(".item-activity").click(function() {
                $('#menu-container .homepage').fadeOut(1000, function() {
                    $('#menu-container .services').fadeIn(1000);
                    $('#menu-container .portfolio').fadeOut("fast");
                    $('#menu-container .testimonial').fadeOut("fast");
                    $('#menu-container .about').fadeOut("fast");
                    $('#menu-container .contact').fadeOut("fast");
                    $('#menu-container .academy').fadeOut("fast");
                    $('#menu-container .visa').fadeOut("fast");
                });
                $('.nav-content').toggleClass('showNav hideNav').removeClass('hidden');
                $('.btn-nav').toggleClass('animated');
            });

            $(".item-notice").click(function() {
                $('#menu-container .homepage').fadeOut(1000, function() {
                    $('#menu-container .portfolio').fadeIn(1000);
                    $('#menu-container .services').fadeOut("fast");
                    $('#menu-container .testimonial').fadeOut("fast");
                    $('#menu-container .about').fadeOut("fast");
                    $('#menu-container .contact').fadeOut("fast");
                    $('#menu-container .academy').fadeOut("fast");
                    $('#menu-container .visa').fadeOut("fast");
                });
                $('.nav-content').toggleClass('showNav hideNav').removeClass('hidden');
                $('.btn-nav').toggleClass('animated');
            });

            $(".item-qa").click(function() {
                $('#menu-container .homepage').fadeOut(1000, function() {
                    $('#menu-container .testimonial').fadeIn(1000);
                    $('#menu-container .services').fadeOut("fast");
                    $('#menu-container .portfolio').fadeOut("fast");
                    $('#menu-container .about').fadeOut("fast");
                    $('#menu-container .contact').fadeOut("fast");
                    $('#menu-container .academy').fadeOut("fast");
                    $('#menu-container .visa').fadeOut("fast");
                });
                $('.nav-content').toggleClass('showNav hideNav').removeClass('hidden');
                $('.btn-nav').toggleClass('animated');
            });

            $(".item-contact").click(function() {
                $('#menu-container .homepage').fadeOut(1000, function() {
                    $('#menu-container .about').fadeIn(1000);
                    $('#menu-container .services').fadeOut("fast");
                    $('#menu-container .portfolio').fadeOut("fast");
                    $('#menu-container .testimonial').fadeOut("fast");
                    $('#menu-container .contact').fadeOut("fast");
                    $('#menu-container .academy').fadeOut("fast");
                    $('#menu-container .visa').fadeOut("fast");
                });
                $('.nav-content').toggleClass('showNav hideNav').removeClass('hidden');
                $('.btn-nav').toggleClass('animated');
            });


            $(".item-visa").click(function() {
                $('.nav-content').toggleClass('showNav hideNav').removeClass('hidden');
                $('.btn-nav').toggleClass('animated');
                window.open("pdf.html");
            });

            $(".item-academy").click(function() {
                $('#menu-container .homepage').fadeOut(1000, function() {
                    $('#menu-container .academy').fadeIn(1000);
                    $('#menu-container .services').fadeOut("fast");
                    $('#menu-container .portfolio').fadeOut("fast");
                    $('#menu-container .testimonial').fadeOut("fast");
                    $('#menu-container .about').fadeOut("fast");
                    $('#menu-container .contact').fadeOut("fast");
                    $('#menu-container .visa').fadeOut("fast");
                });
                $('.nav-content').toggleClass('showNav hideNav').removeClass('hidden');
                $('.btn-nav').toggleClass('animated');
            });

            $(".item-login").click(function() {
                $('.nav-content').toggleClass('showNav hideNav').removeClass('hidden');
                $('.btn-nav').toggleClass('animated');
                window.open("login.html");

            });

        });