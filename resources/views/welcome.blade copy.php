@extends('layouts.frontend')

@section('title', 'AmazonBlue Capital | We Sell Convenience')

@section('content')
    <!-- Header/Hero Section -->
    <header class="position-relative min-vh-100 d-flex flex-column">
        <!-- Background Image -->
        <div class="position-absolute top-0 start-0 w-100 h-100">
            <img src="{{ asset('frontend/images/hero-bg.jpg') }}" 
                 class="w-100 h-100 object-fit-cover" 
                 alt="AmazonBlue Capital Background"
                 style="filter: brightness(0.4);">
        </div>

        <div class="container position-relative flex-grow-1 d-flex flex-column py-4">
            <!-- Navigation -->
            <nav class="d-flex justify-content-between align-items-center py-3">
                <div class="text-white content-title fw-bold" data-aos="fade-right" data-aos-duration="1000">
                    <div class="d-flex align-items-center">
                        <div class="bg-accent-blue rounded-circle d-flex align-items-center justify-content-center me-2 me-md-3" 
                            style="width: 35px; height: 35px; min-width: 35px;">
                            <span class="text-white fw-bold" style="font-size: 0.9rem;">A</span>
                        </div>
                        <span class="fs-4 d-none d-md-inline">AmazonBlue<span class="text-accent-blue">Capital</span></span>
                        <span class="fs-5 d-md-none">ABC</span>
                    </div>
                </div>

                <!-- Menu Toggle Button -->
                <button class="menu-toggle-btn btn d-flex align-items-center gap-2" 
                        id="menuToggle" 
                        type="button"
                        aria-label="Toggle menu"
                        data-aos="fade-left" data-aos-duration="1000">
                    <span class="text-white fw-bold d-none d-md-inline">MENU</span>
                    <i class="fas fa-bars text-white fs-4"></i>
                </button>
            </nav>

            <!-- Mobile Menu (Hidden by default) -->
            <div class="mobile-menu" id="mobileMenu" style="display: none;">
                <div class="container h-100 d-flex flex-column p-4">
                    <div class="d-flex justify-content-between align-items-center py-3">
                        <div class="text-white content-title fw-bold">
                            <div class="d-flex align-items-center">
                                <div class="bg-accent-blue rounded-circle d-flex align-items-center justify-content-center me-3" 
                                    style="width: 40px; height: 40px;">
                                    <span class="text-white fw-bold">A</span>
                                </div>
                                <span class="fs-4">AmazonBlue<span class="text-accent-blue">Capital</span></span>
                            </div>
                        </div>
                        <button class="menu-close-btn" type="button" aria-label="Close menu" id="menuClose">
                            <i class="fas fa-times text-white fs-3"></i>
                        </button>
                    </div>
                    
                    <div class="flex-grow-1 d-flex flex-column justify-content-center">
                        <ul class="list-unstyled px-3">
                            <li class="mb-4">
                                <a href="#about" class="text-white fs-3 fw-bold d-block py-2 menu-link">About</a>
                            </li>
                            <li class="mb-4">
                                <a href="#services" class="text-white fs-3 fw-bold d-block py-2 menu-link">Services</a>
                            </li>
                            <li class="mb-4">
                                <a href="#performance" class="text-white fs-3 fw-bold d-block py-2 menu-link">Performance</a>
                            </li>
                            <li class="mb-4">
                                <a href="#investment" class="text-white fs-3 fw-bold d-block py-2 menu-link">Investment</a>
                            </li>
                            <li class="mb-4">
                                <a href="#contact" class="text-white fs-3 fw-bold d-block py-2 menu-link">Contact</a>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="py-4 px-3">
                        <div class="text-white mb-3">
                            <i class="fas fa-phone me-2"></i> +254 701 607 959
                        </div>
                        <div class="text-white">
                            <i class="fas fa-envelope me-2"></i> investors@amazonbluecapital.com
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hero Content -->
            <div class="flex-grow-1 d-flex flex-column justify-content-center">
                <div class="w-100 text-center overflow-hidden">
                    <div class="syne-font hero-title text-accent-blue" data-aos="fade-up">AmazonBlue</div>
                    <div class="syne-font hero-title hero-bg-text" data-aos="fade-up" data-aos-delay="200">Capital</div>
                    <div class="syne-font hero-title hero-bg-text" data-aos="fade-up" data-aos-delay="400">Financial Solutions</div>
                    <div class="syne-font hero-title hero-bg-text" data-aos="fade-up" data-aos-delay="600">When You Need Them</div>
                </div>
            </div>
        </div>
    </header>

    <!-- About Section -->
    <section id="about" class="py-5 my-5 position-relative">
        <div class="container">
            <div class="row g-lg-5">
                <div class="banner-image col-md-6" data-aos="clip-bottom" data-aos-duration="1000">
                    <img src="{{ asset('frontend/images/financial-solutions.jpg') }}" class="img-fluid rounded-4" alt="AmazonBlue Capital" />
                </div>
                <div class="col-md-6" data-aos="clip-top" data-aos-delay="1000">
                    <div class="content-title mb-4 fw-bold text-dark-green">About AmazonBlue Capital</div>
                    <blockquote class="fst-italic fs-4 mb-4 text-blue">
                        "We Sell Convenience through Predictable Yield and Proven Recovery Systems"
                    </blockquote>
                    <p class="mb-4">
                        AmazonBlue Capital is a Nairobi-based alternative private credit fund specializing in short-duration, high-velocity lending to underserved SMEs and individuals. Our proprietary 10-day emergency credit model generates exceptional yield while maintaining disciplined recovery outcomes.
                    </p>
                    <p class="mb-5 text-dark-green">
                        • Target Returns: 3% Monthly<br />
                        • Loan Cycle: 10–30 Days<br />
                        • Historical Recovery Rate: 93.7%<br />
                        • Proven Capital Velocity: 9.6x annually
                    </p>
                    <button class="btn btn-custom d-flex align-items-center gap-2">
                        <span>Investment Details</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Performance Section -->
    <section id="performance" class="py-5 my-5 position-relative overflow-hidden">
        <div class="container position-relative h-100">
            <div class="position-absolute top-0 start-100 translate-middle-x">
                <div class="syne-font section-title" data-aos="fade-up">Performance</div>
                <div class="syne-font section-title section-title-bg" data-aos="fade-up" data-aos-delay="200">Performance</div>
                <div class="syne-font section-title section-title-bg" data-aos="fade-up" data-aos-delay="400">Performance</div>
            </div>

            <div class="container">
                <div class="row">
                    <div class="col-md-6 py-5">
                        <div class="bg-light-alt p-5 mt-5 rounded-4 overflow-hidden" data-aos="clip-top" data-aos-delay="1000">
                            <div class="content-title mb-4 fw-bold text-dark-green">Track Record & Validation</div>

                            <div class="mb-5">
                                <div class="date-text mb-2 text-blue">2025–2026</div>
                                <h3 class="subtitle mb-2 text-dark-green">Capital Growth History</h3>
                                <p class="text-dark-green">
                                    Capital grew 5x in 9 months purely through lending profits and reinvestment. Total disbursements: KES 9M+ with 93.7% recovery rate.
                                </p>
                            </div>

                            <div>
                                <div class="date-text mb-2 text-blue">Operational Performance</div>
                                <h3 class="subtitle mb-2 text-dark-green">11 Months Results</h3>
                                <p class="text-dark-green">
                                    Net Interest Margin: 22.3% after all costs<br />
                                    Capital Velocity: 9.6 rotations annually<br />
                                    Repeat Borrowers: 41% (growing ~8% quarterly)
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="py-5 my-5 position-relative overflow-hidden">
        <div class="container">
            <div class="position-absolute top-0 start-50 translate-middle-x">
                <div class="syne-font section-title" data-aos="fade-up">Services</div>
                <div class="syne-font section-title section-title-bg" data-aos="fade-up" data-aos-delay="200">Services</div>
                <div class="syne-font section-title section-title-bg" data-aos="fade-up" data-aos-delay="400">Services</div>
            </div>

            <div class="row">
                <div class="col-md-6"></div>
                <div class="col-md-6 py-5">
                    <div class="bg-light-alt p-5 rounded-4" data-aos="clip-bottom" data-aos-duration="1000">
                        <div class="content-title mb-4 fw-bold text-dark-green">Our Services</div>

                        <div class="mb-5">
                            <div class="date-text mb-2 text-blue">CRB Bail Outs</div>
                            <h3 class="subtitle mb-2 text-dark-green">24/7 Credit Restoration</h3>
                            <p class="text-dark-green">
                                Immediate financial assistance to resolve credit bureau issues. Rapid resolution with confidential service.
                            </p>
                        </div>

                        <div class="mb-5">
                            <div class="date-text mb-2 text-blue">Emergency Loans</div>
                            <h3 class="subtitle mb-2 text-dark-green">10-Day Quick Cash</h3>
                            <p class="text-dark-green">
                                Funds in 1 hour with no credit checks. Flexible amounts for urgent situations.
                            </p>
                        </div>

                        <div>
                            <div class="date-text mb-2 text-blue">Asset & Commodity Loans</div>
                            <h3 class="subtitle mb-2 text-dark-green">Value-Based Financing</h3>
                            <p class="text-dark-green">
                                Loans secured against real estate, vehicles, jewelry, and commodities including import/export.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Investment Section -->
    <section id="investment" class="py-5 my-5 position-relative">
        <div class="container overflow-hidden">
            <div class="position-absolute top-0 start-50 translate-middle-x">
                <div class="syne-font section-title" data-aos="fade-up">Investment</div>
                <div class="syne-font section-title section-title-bg" data-aos="fade-up" data-aos-delay="200">Investment</div>
                <div class="syne-font section-title section-title-bg" data-aos="fade-up" data-aos-delay="400">Investment</div>
            </div>

            <div class="row">
                <div class="col-md-6 py-5">
                    <div class="bg-light-alt p-5 rounded-4" data-aos="clip-bottom" data-aos-duration="1000">
                        <div class="content-title mb-4 fw-bold text-dark-green">Investment Highlights</div>

                        <div class="mb-5">
                            <div class="date-text mb-2 text-blue">1.8%-3% Monthly Yield</div>
                            <h3 class="subtitle mb-2 text-dark-green">Predictable Returns</h3>
                            <p class="text-dark-green">
                                (21-36% annualized), paid quarterly. Short loan cycles (10–30 days) reduce macro and borrower risk.
                            </p>
                        </div>

                        <div>
                            <div class="date-text mb-2 text-blue">Capital Protection</div>
                            <h3 class="subtitle mb-2 text-dark-green">Secure Structure</h3>
                            <p class="text-dark-green">
                                Capital protected through layered reserves and first-claim structure. Founder capital materially invested alongside investors.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6"></div>
            </div>
        </div>
    </section>

    <!-- Removed Testimonials Section -->

    <!-- Latest Works Section with Swiper -->
    <section class="py-5 my-5 position-relative">
        <div class="bg-light-alt position-absolute bottom-0 start-0 w-100" style="height: 50%; border-radius: 20px;"></div>

        <div class="container position-relative">
            <h2 class="content-title text-center mb-5 text-dark-green" data-aos="fade-up">Our Services Gallery</h2>

            <!-- Swiper Works -->
            <div class="swiper worksSwiper mb-5" data-aos="fade-up" data-aos-delay="200">
                <div class="swiper-wrapper">
                    <div class="swiper-slide">
                        <div class="card card-custom">
                            <img src="{{ asset('frontend/images/portfolio-thumb-1.jpg') }}" class="img-fluid" alt="CRB Bail Outs" />
                            <div class="card-body text-center">
                                <div class="date-text mb-2 text-blue">CRB Services</div>
                                <h3 class="subtitle text-dark-green">Credit Restoration</h3>
                            </div>
                        </div>
                    </div>

                    <div class="swiper-slide">
                        <div class="card card-custom">
                            <img src="{{ asset('frontend/images/portfolio-thumb-2.jpg') }}" class="img-fluid" alt="Emergency Loans" />
                            <div class="card-body text-center">
                                <div class="date-text mb-2 text-blue">Quick Cash</div>
                                <h3 class="subtitle text-dark-green">10-Day Loans</h3>
                            </div>
                        </div>
                    </div>

                    <div class="swiper-slide">
                        <div class="card card-custom">
                            <img src="{{ asset('frontend/images/portfolio-thumb-3.jpg') }}" class="img-fluid" alt="Asset Loans" />
                            <div class="card-body text-center">
                                <div class="date-text mb-2 text-blue">Asset Financing</div>
                                <h3 class="subtitle text-dark-green">Value-Based Loans</h3>
                            </div>
                        </div>
                    </div>

                    <div class="swiper-slide">
                        <div class="card card-custom">
                            <img src="{{ asset('frontend/images/portfolio-thumb-4.jpg') }}" class="img-fluid" alt="Commodity Loans" />
                            <div class="card-body text-center">
                                <div class="date-text mb-2 text-blue">Commodities</div>
                                <h3 class="subtitle text-dark-green">Trade Financing</h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add Pagination -->
                <div class="swiper-pagination"></div>

                <!-- Add Navigation -->
                <div class="swiper-button-next text-dark-green"></div>
                <div class="swiper-button-prev text-dark-green"></div>
            </div>
        </div>
    </section>

    <!-- News Articles Section with Swiper -->
    <section class="py-5 my-5" data-aos="fade-up">
        <div class="container">
            <h2 class="content-title text-center mb-5 text-dark-green">Financial Insights</h2>

            <!-- Swiper Articles -->
            <div class="swiper articlesSwiper" data-aos="fade-up" data-aos-delay="200">
                <div class="swiper-wrapper">
                    <div class="swiper-slide">
                        <div class="position-relative h-100">
                            <div class="bg-light-alt position-absolute bottom-0 start-0 w-100"
                                style="height: 50%; border-radius: 20px;"></div>

                            <div class="position-relative">
                                <img src="{{ asset('frontend/images/post-thumb-1.jpg') }}" class="img-fluid rounded-top-4"
                                    alt="Financial Insight 1" />
                                <div class="p-4">
                                    <div class="d-flex gap-3 date-text mb-3 text-blue">
                                        <span>Dec 22, 2024</span>
                                        <span>-</span>
                                        <span>Credit Management</span>
                                    </div>
                                    <h3 class="subtitle text-dark-green">How To Elevate Your Credit Score</h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="swiper-slide">
                        <div class="position-relative h-100">
                            <div class="bg-light-alt position-absolute bottom-0 start-0 w-100"
                                style="height: 50%; border-radius: 20px;"></div>

                            <div class="position-relative">
                                <img src="{{ asset('frontend/images/post-thumb-2.jpg') }}" class="img-fluid rounded-top-4"
                                    alt="Financial Insight 2" />
                                <div class="p-4">
                                    <div class="d-flex gap-3 date-text mb-3 text-blue">
                                        <span>Jan 15, 2025</span>
                                        <span>-</span>
                                        <span>Asset Management</span>
                                    </div>
                                    <h3 class="subtitle text-dark-green">Leveraging Assets for Quick Capital</h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="swiper-slide">
                        <div class="position-relative h-100">
                            <div class="bg-light-alt position-absolute bottom-0 start-0 w-100"
                                style="height: 50%; border-radius: 20px;"></div>

                            <div class="position-relative">
                                <img src="{{ asset('frontend/images/post-thumb-3.jpg') }}" class="img-fluid rounded-top-4"
                                    alt="Financial Insight 3" />
                                <div class="p-4">
                                    <div class="d-flex gap-3 date-text mb-3 text-blue">
                                        <span>Feb 8, 2025</span>
                                        <span>-</span>
                                        <span>Emergency Funding</span>
                                    </div>
                                    <h3 class="subtitle text-dark-green">Navigating Financial Emergencies</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add Pagination -->
                <div class="swiper-pagination"></div>

                <!-- Add Navigation -->
                <div class="swiper-button-next text-dark-green"></div>
                <div class="swiper-button-prev text-dark-green"></div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section id="contact" class="py-5 my-5 bg-light-alt rounded-5 m-4" data-aos="clip-bottom">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6" data-aos="fade-right">
                    <h2 class="content-title mb-4 fw-bold text-dark-green">Leave A Message</h2>
                    <p class="mb-4 text-dark-green">
                        Ready to discuss your financial needs or investment opportunities? Contact us for confidential discussions and personalized solutions.
                    </p>
                    <div class="mb-4">
                        <h5 class="fw-bold text-blue">Contact Information</h5>
                        <ul class="list-unstyled text-dark-green">
                            <li class="mb-2"><i class="fas fa-phone text-blue me-2"></i> +254 701 607 959</li>
                            <li class="mb-2"><i class="fas fa-envelope text-blue me-2"></i> investors@amazonbluecapital.com</li>
                            <li><i class="fas fa-clock text-blue me-2"></i> Available 24/7</li>
                        </ul>
                    </div>
                </div>

                <div class="col-md-6" data-aos="fade-left">
                    <form class="d-flex flex-column gap-4">
                        <input type="text" class="form-control-custom form-control" placeholder="Your name here..." />
                        <input type="email" class="form-control-custom form-control" placeholder="Your email here..." />
                        <input type="tel" class="form-control-custom form-control" placeholder="Your phone number..." />
                        <textarea class="form-control-custom form-control" placeholder="Your message here..." rows="4"></textarea>
                        <button class="btn btn-custom d-flex align-items-center justify-content-center gap-2">
                            <span>Send Message</span>
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section with Swiper -->
    <section class="py-5 my-5 position-relative" data-aos="fade-up">
        <div class="container position-relative">
            <div class="position-absolute top-0 start-50 translate-middle-x w-100">
                <div class="syne-font section-title text-center" data-aos="fade-up">Stats</div>
                <div class="syne-font section-title section-title-bg text-center" data-aos="fade-up" data-aos-delay="200">Stats</div>
                <div class="syne-font section-title section-title-bg text-center" data-aos="fade-up" data-aos-delay="400">Stats</div>
            </div>
            
            <div class="pt-5 mt-5">
                <!-- Swiper Stats -->
                <div class="swiper statsSwiper">
                    <div class="swiper-wrapper align-items-center">
                        <div class="swiper-slide text-center">
                            <div class="stat-item bg-light-alt p-4 rounded-4 shadow-sm">
                                <div class="fs-1 fw-bold text-accent-blue">24/7</div>
                                <div class="text-blue">Availability</div>
                            </div>
                        </div>
                        <div class="swiper-slide text-center">
                            <div class="stat-item bg-light-alt p-4 rounded-4 shadow-sm">
                                <div class="fs-1 fw-bold text-accent-blue">10-30</div>
                                <div class="text-blue">Loan Days</div>
                            </div>
                        </div>
                        <div class="swiper-slide text-center">
                            <div class="stat-item bg-light-alt p-4 rounded-4 shadow-sm">
                                <div class="fs-1 fw-bold text-accent-blue">1-3%</div>
                                <div class="text-blue">Monthly Yield</div>
                            </div>
                        </div>
                        <div class="swiper-slide text-center">
                            <div class="stat-item bg-light-alt p-4 rounded-4 shadow-sm">
                                <div class="fs-1 fw-bold text-accent-blue">KES 9M+</div>
                                <div class="text-blue">Disbursed</div>
                            </div>
                        </div>
                        <div class="swiper-slide text-center">
                            <div class="stat-item bg-light-alt p-4 rounded-4 shadow-sm">
                                <div class="fs-1 fw-bold text-accent-blue">93.7%</div>
                                <div class="text-blue">Recovery Rate</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- @include('partials.footer') --}}
@endsection

@push('styles')
<style>
    /* Mobile Menu Styles */
    .mobile-menu {
        background: rgba(0, 51, 34, 0.98) !important;
        backdrop-filter: blur(10px);
        transition: all 0.3s ease;
        opacity: 0;
        transform: translateX(-100%);
    }
    
    .mobile-menu.show {
        opacity: 1;
        transform: translateX(0);
    }
    
    .menu-link {
        transition: all 0.3s ease;
        border-left: 3px solid transparent;
        padding-left: 20px;
    }
    
    .menu-link:hover {
        color: #3BB3BD !important;
        border-left: 3px solid #3BB3BD;
        transform: translateX(10px);
    }
    
    .menu-close-btn {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    /* Header adjustments for full image */
    .object-fit-cover {
        object-fit: cover;
    }
    
    /* Ensure content stays above background image */
    header > .container {
        z-index: 1;
    }
</style>
@endpush

@push('scripts')
<script>
    // Initialize AOS
    AOS.init({
        duration: 1000,
        once: true
    });

    // Mobile Menu Toggle
    const menuToggle = document.getElementById('menuToggle');
    const mobileMenu = document.querySelector('.mobile-menu');
    const menuCloseBtn = document.querySelector('.menu-close-btn');
    const menuLinks = document.querySelectorAll('.menu-link');

    menuToggle.addEventListener('click', () => {
        mobileMenu.classList.remove('d-none');
        setTimeout(() => {
            mobileMenu.classList.add('show');
            document.body.style.overflow = 'hidden'; // Prevent scrolling when menu is open
        }, 10);
    });

    menuCloseBtn.addEventListener('click', () => {
        closeMenu();
    });

    menuLinks.forEach(link => {
        link.addEventListener('click', () => {
            closeMenu();
        });
    });

    function closeMenu() {
        mobileMenu.classList.remove('show');
        setTimeout(() => {
            mobileMenu.classList.add('d-none');
            document.body.style.overflow = 'auto'; // Restore scrolling
        }, 300);
    }

    // Close menu on escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && mobileMenu.classList.contains('show')) {
            closeMenu();
        }
    });

    // Initialize Swipers
    document.addEventListener('DOMContentLoaded', function() {
        // Services Swiper
        const servicesSwiper = new Swiper('.servicesSwiper', {
            direction: 'vertical',
            loop: true,
            autoplay: {
                delay: 3000,
                disableOnInteraction: false,
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
        });

        // Works Swiper
        const worksSwiper = new Swiper('.worksSwiper', {
            slidesPerView: 1,
            spaceBetween: 30,
            loop: true,
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
                dynamicBullets: true,
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
            breakpoints: {
                640: {
                    slidesPerView: 2,
                },
                768: {
                    slidesPerView: 3,
                },
                1024: {
                    slidesPerView: 4,
                },
            },
        });

        // Articles Swiper
        const articlesSwiper = new Swiper('.articlesSwiper', {
            slidesPerView: 1,
            spaceBetween: 30,
            loop: true,
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
                dynamicBullets: true,
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
            breakpoints: {
                768: {
                    slidesPerView: 2,
                },
                1024: {
                    slidesPerView: 3,
                },
            },
        });

        // Stats Swiper
        const statsSwiper = new Swiper('.statsSwiper', {
            slidesPerView: 2,
            spaceBetween: 30,
            loop: true,
            autoplay: {
                delay: 2000,
                disableOnInteraction: false,
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            breakpoints: {
                640: {
                    slidesPerView: 3,
                },
                768: {
                    slidesPerView: 4,
                },
                1024: {
                    slidesPerView: 5,
                },
            },
        });
    });
</script>
@endpush