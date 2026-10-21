// Practical 4 - Academic Highlights Content Slider & Interactive Banner Controls
document.addEventListener('DOMContentLoaded', () => {
    // 1. Interactive Slider / Carousel
    const slides = document.querySelectorAll('.slider-slide');
    const prevBtn = document.getElementById('prevHighlightBtn');
    const nextBtn = document.getElementById('nextHighlightBtn');
    let currentSlide = 0;

    function showSlide(index) {
        if (!slides.length) return;
        slides.forEach((s, i) => {
            s.classList.toggle('active', i === index);
        });
    }

    if (prevBtn && nextBtn && slides.length > 0) {
        prevBtn.addEventListener('click', () => {
            currentSlide = (currentSlide - 1 + slides.length) % slides.length;
            showSlide(currentSlide);
        });

        nextBtn.addEventListener('click', () => {
            currentSlide = (currentSlide + 1) % slides.length;
            showSlide(currentSlide);
        });

        // Initialize first slide
        showSlide(0);
    }

    // 2. Dismissible Notice Banner
    const noticeBanner = document.getElementById('noticeBanner');
    const dismissNoticeBtn = document.getElementById('dismissNoticeBtn');
    if (noticeBanner && dismissNoticeBtn) {
        dismissNoticeBtn.addEventListener('click', () => {
            noticeBanner.style.display = 'none';
        });
    }
});
