$(function () {
    //导航以及top的显示与隐藏开始
    $(".navmenu li a").click(function () {
        $(this).next("ul").slideToggle();

    })
    $("header .menu-nav").click(function () {
        $(".navmenu").addClass("on");
        $(".bg").fadeIn(300);

    })
    $(".bg").click(function () {
        $(".navmenu").removeClass("on");
        $(".bg").fadeOut(300);

    })


    var sTop1 = $(window).scrollTop();

    $(window).scroll(function () {

        var sTop2 = $(window).scrollTop();

        if ($(this).scrollTop() >= 100) {
            $("header").addClass("on");

            if (sTop2 > sTop1) {
                $("header").css({
                    top: -60
                });

            } else {
                $("header").css({
                    top: 0
                });

            }
        } else {
            $("header").removeClass("on");
            $("header").css({
                top: 0
            });
        }
        sTop1 = sTop2;
        $(".navmenu").removeClass("on");
        $(".bg").fadeOut(300);


    })
    //导航以及top的显示与隐藏结束


    //box直接显示遮罩效果开始
    $(".box").hover(function () {
        $(this).find(".txt-two").css("display", "block");
    }, function () {
        $(this).find(".txt-two").css("display", "none");
    })
    //box直接显示遮罩效果结束


    var mySwiper = new Swiper('.swiper-container', {
        autoplay: {
            disableOnInteraction: false,
        },
        loop: true,
        parallax: true,
        effect: 'fade',


        // 如果需要分页器
        pagination: {
            el: '.swiper-pagination',
        },

        // 如果需要前进后退按钮
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },

    })



})


//懒加载
$("#myTabs a").click(function (e) {
    e.preventDefault()
    $(this).tab("show")
})

lazyLoadInit({
    coverColor: "white",
    offsetBottom: 0,
    offsetTopm: 0,
    showTime: 1100,
    onLoadBackEnd: function (i, e) {
        console.log("onLoadBackEnd:" + i);
    },
    onLoadBackStart: function (i, e) {
        console.log("onLoadBackStart:" + i);
    }
});





//progect1
$(function () {
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
    $('.filter').on('click', 'li', function () {
        var filterValue = $(this).attr('data-filter');
        $grid.isotope({
            filter: filterValue
        });
        $(this).addClass("active").siblings().removeClass("active"); //切换选中的按钮高亮状态
    });


});





$('#myModal').modal(options)
$('#login,#register').modal(options)

