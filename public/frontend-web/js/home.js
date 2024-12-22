// document.addEventListener("DOMContentLoaded", function () {
// Khởi tạo Swiper cho Trending
const swiper = new Swiper(".swiper.trending", {
    loop: true,
    width: 400,
    autoplay: {
        delay: 5000,
    },
    slidesPerView: 1,
    breakpoints: {
        width: 640,
        640: {
            slidesPerView: 2,
        },
        1024: {
            width: 1440,
            slidesPerView: 3,
        },
    },
    direction: "horizontal",
    navigation: {
        nextEl: ".trending-button-next",
        prevEl: ".trending-button-prev",
    },
});

// Khởi tạo Swiper cho Top xem nhiều
const $mostViewed = $("#most-viewed");
let swipers = {
    day: new Swiper(
        "#most-viewed .tab-content[data-name='day'] .swiper.most-viewed",
        {
            pagination: {
                el: "#most-viewed .swiper-pagination",
                type: "progressbar",
            },
            slidesPerView: "auto",
        }
    ),
    week: null,
    month: null,
};

$mostViewed.find(".tab").click(function () {
    $mostViewed.find(".tab").removeClass("active");
    $(this).addClass("active");
    const tabName = $(this).data("name");
    $mostViewed.find(".tab-content").hide();
    $mostViewed.find(`.tab-content[data-name="${tabName}"]`).fadeIn(300);
    if (!swipers[tabName]) {
        swipers[tabName] = new Swiper(
            `#most-viewed .tab-content[data-name='${tabName}'] .swiper.most-viewed`,
            {
                pagination: {
                    el: "#most-viewed .swiper-pagination",
                    type: "progressbar",
                },
                slidesPerView: "auto",
            }
        );
    }
});

// Khởi tạo Swiper cho Top Xem Nhiều và Truyện mới
new Swiper(".swiper.completed", {
    pagination: {
        el: ".completed-pagination",
        type: "bullets",
        clickable: true,
        bulletClass: "swiper-pagination-bullet",
        bulletActiveClass: "swiper-pagination-bullet-active",
    },
    slidesPerView: "auto",
    navigation: {
        nextEl: ".complete-button-next",
        prevEl: ".complete-button-prev",
    },
});

// Function to initialize Tooltipster (hover cho các manga trong truyện mới cập nhật)
function initializeTooltipster() {
    $(".tooltipstered").tooltipster({
        contentCloning: false,
        interactive: true,
        side: "right",
    });
}
initializeTooltipster();

// });
