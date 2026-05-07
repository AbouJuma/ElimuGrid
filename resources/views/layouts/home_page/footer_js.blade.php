<script src="{{ asset('assets/home_page/js/script.js') }}"></script>


<!-- bootstrap  -->
{{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script> --}}
<!-- fontawesome icons   -->
<script src="{{ asset('assets/home_page/js/1d2a297b20.js') }}"></script>
<script src="{{ asset('/assets/jquery-toast-plugin/jquery.toast.min.js') }}"></script>
<script src="{{ asset('/assets/js/jquery.validate.min.js') }}"></script>
<script src="{{ asset('/assets/js/custom/function.js') }}"></script>
<script src="{{ asset('/assets/js/custom/common.js') }}"></script>
<script src="{{ asset('/assets/js/custom/custom.js') }}"></script>

{{-- Sliders --}}
<script src="{{ asset('assets/home_page/js/owl.carousel.min.js') }}"></script>

<script src="{{ asset('/assets/js/sweetalert2.all.min.js') }}"></script>



<!-- custom script  -->
{{-- <script src="script.js"> --}}

</script>

<!-- bootstrap  -->
{{-- FAQs --}}
<script src="{{ asset('assets/home_page/js/bootstrap.bundle.min.js') }}"> </script>



<!-- swiper  -->
{{-- <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-element-bundle.min.js"></script> --}}

<!-- swiper  -->
{{-- <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script> --}}

<script>
    $(document).ready(function() {
        // Initialize each carousel separately
        $(".swiperSect .slider-content.owl-carousel").each(function() {
            var owl = $(this).owlCarousel({
                loop: true,
                autoplay: true,
                autoplayTimeout: 1500,
                autoplaySpeed: 2000,
                margin: 30,
                nav: false,
                responsive: {
                    0: {
                        items: 1,
                    },
                    600: {
                        items: 3,
                    },
                    1000: {
                        items: 5,
                    },
                },
            });

            // Custom navigation buttons for this specific carousel
            $(this)
                .closest(".commonSlider")
                .find(".prev")
                .click(function() {
                    owl.trigger("prev.owl.carousel");
                });

            $(this)
                .closest(".commonSlider")
                .find(".next")
                .click(function() {
                    owl.trigger("next.owl.carousel");
                });
        });
    });

    // for pricingSection
    $(document).ready(function() {
        // Initialize each carousel separately
        $(".pricing .slider-content.owl-carousel").each(function() {
            var owl = $(this).owlCarousel({
                loop: false,
                autoplay: false,
                autoplayTimeout: 1000,
                autoplaySpeed: 2000,
                margin: 30,
                nav: false,
                dots: true,
                responsive: {
                    0: {
                        items: 1,
                    },
                    600: {
                        items: 2,
                    },
                    1000: {
                        items: 3,
                    },
                },
            });

            // Custom navigation buttons for this specific carousel
            $(this)
                .closest(".commonSlider")
                .find(".prev")
                .click(function() {
                    owl.trigger("prev.owl.carousel");
                });

            $(this)
                .closest(".commonSlider")
                .find(".next")
                .click(function() {
                    owl.trigger("next.owl.carousel");
                });
        });
    });

    // Hero Slider
    $(document).ready(function() {
        var $heroSlider = $(".hero-slider");
        var $slides = $heroSlider.find('.hero-slide');
        var slideCount = $slides.length;
        var currentSlide = 0;
        
        console.log('Hero slider found:', $heroSlider.length, 'Slides:', slideCount);
        
        if ($heroSlider.length && slideCount > 1) {
            // Show all slides but hide non-active ones with opacity
            $slides.css('opacity', 0).css('display', 'flex');
            $slides.eq(0).css('opacity', 1);
            
            // Simple fade slider
            function showSlide(index) {
                $slides.animate({ opacity: 0 }, 400, function() {
                    $(this).css('z-index', 1);
                });
                $slides.eq(index).css('z-index', 10).animate({ opacity: 1 }, 400);
                currentSlide = index;
                console.log('Showing slide:', index);
            }
            
            // Auto rotate
            setInterval(function() {
                var next = (currentSlide + 1) % slideCount;
                showSlide(next);
            }, 5000);
            
            // Navigation buttons
            $(".hero-prev").click(function(e) {
                e.preventDefault();
                var prev = (currentSlide - 1 + slideCount) % slideCount;
                showSlide(prev);
            });

            $(".hero-next").click(function(e) {
                e.preventDefault();
                var next = (currentSlide + 1) % slideCount;
                showSlide(next);
            });
            
            console.log('Hero slider initialized');
        }
    });




    // for counter

    document.addEventListener("DOMContentLoaded", function() {
        const counters = document.querySelectorAll('.numb');

        const observer = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const target = +entry.target.getAttribute('data-target');
                    entry.target.innerText = 0;
                    const updateCounter = () => {
                        const value = +entry.target.innerText;
                        const increment = target /
                        150; // Adjust the speed of the counter by changing the denominator

                        if (value < target) {
                            entry.target.innerText = Math.ceil(value + increment);
                            setTimeout(updateCounter,
                            10); // Adjust the interval for smoother animation
                        } else {
                            entry.target.innerText = target;
                        }
                    };

                    updateCounter();
                    observer.unobserve(entry.target);
                }
            });
        });

        counters.forEach(counter => {
            observer.observe(counter);
        });
    });

    const lang_view_more_features = "{{__('view_more_features')}}"
    const lang_view_less_features = "{{__('view_less_features')}}"
    const please_wait = "{{__('Please wait')}}"
    const processing_your_request = "{{__('Processing your request')}}"
</script>
<script>
    

 </script>
<script type='text/javascript'>
    @if ($errors->any())
    @foreach ($errors->all() as $error)
    $.toast({
        text: '{{ $error }}',
        showHideTransition: 'slide',
        icon: 'error',
        loaderBg: '#f2a654',
        position: 'top-right'
    });
    @endforeach
    @endif

    @if (Session::has('success'))
    $.toast({
        text: '{{ Session::get('success') }}',
        showHideTransition: 'slide',
        icon: 'success',
        loaderBg: '#f96868',
        position: 'top-right'
    });
    @endif

    @if (Session::has('error'))
    $.toast({
        text: '{{ Session::get('error') }}',
        showHideTransition: 'slide',
        icon: 'error',
        loaderBg: '#f2a654',
        position: 'top-right'
    });
    @endif
</script>
<script>
    var baseUrl = "{{ URL::to('/') }}";
    const onErrorImage = (e) => {
        e.target.src = "{{ asset('/assets/no_image_available.jpg') }}";
    };
</script>