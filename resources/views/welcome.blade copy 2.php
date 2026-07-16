<!DOCTYPE html>
<html lang="en" x-data="{ 
    darkMode: false, 
    mobileMenuOpen: false, 
    activeSlide: 0, 
    totalSlides: 4,
    scrolled: false 
}" 
:class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AmazonBlue Capital | Premium Financial Solutions</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .slide-1 {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), 
                        url('https://images.unsplash.com/photo-1548013146-72479768bada?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }
        .slide-2 {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), 
                        url('https://images.unsplash.com/photo-1563013544-824ae1b704d3?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }
        .slide-3 {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), 
                        url('https://images.unsplash.com/photo-1590856029826-c7a73142bbf1?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }
        .slide-4 {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), 
                        url('https://images.unsplash.com/photo-1579530190412-b35a65e17c8d?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }
        .full-screen-menu {
            transform: translateX(100%);
            transition: transform 0.3s ease-in-out;
        }
        .full-screen-menu.open {
            transform: translateX(0);
        }
        .section-min-height {
            min-height: 100vh;
        }
        .half-section-min-height {
            min-height: 50vh;
        }
        .transition-all-300 {
            transition: all 0.3s ease;
        }
        .carousel-slide {
            transition: transform 0.8s ease-in-out, opacity 0.8s ease-in-out;
        }
        .slide-enter {
            opacity: 0;
            transform: translateY(50px);
        }
        .slide-leave {
            opacity: 0;
            transform: translateY(-50px);
        }
        .hero-slide {
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.8s ease, transform 0.8s ease;
        }
        .hero-slide.active {
            opacity: 1;
            transform: translateY(0);
        }
        .nav-scrolled {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        .nav-scrolled.dark-nav {
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(10px);
        }
        .scroll-indicator {
            position: absolute;
            bottom: 40px;
            left: 50%;
            transform: translateX(-50%);
            width: 30px;
            height: 50px;
            border: 2px solid rgba(255, 255, 255, 0.5);
            border-radius: 15px;
            display: flex;
            justify-content: center;
            padding-top: 10px;
        }
        .scroll-indicator::before {
            content: '';
            width: 4px;
            height: 10px;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 2px;
            animation: scrollBounce 2s infinite;
        }
        @keyframes scrollBounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(10px); }
        }
    </style>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: '#1e3a8a', // Navy Blue
                        secondary: '#0f4c81', // Darker Blue
                        accent: '#0ea5e9',   // Sky Blue
                        light: '#f8fafc',
                        dark: '#0f172a'
                    },
                    fontFamily: {
                        sans: ['Outfit', 'system-ui', '-apple-system', 'sans-serif'],
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-in-out',
                        'slide-up': 'slideUp 0.3s ease-out',
                        'slide-in-right': 'slideInRight 0.5s ease-out',
                        'slide-in-up': 'slideInUp 0.7s ease-out'
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' }
                        },
                        slideUp: {
                            '0%': { transform: 'translateY(20px)', opacity: '0' },
                            '100%': { transform: 'translateY(0)', opacity: '1' }
                        },
                        slideInRight: {
                            '0%': { transform: 'translateX(50px)', opacity: '0' },
                            '100%': { transform: 'translateX(0)', opacity: '1' }
                        },
                        slideInUp: {
                            '0%': { transform: 'translateY(50px)', opacity: '0' },
                            '100%': { transform: 'translateY(0)', opacity: '1' }
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="font-sans text-gray-700 bg-white dark:bg-dark dark:text-gray-300 transition-colors duration-300">

    <nav x-data="{
            lastScroll: 0,
            showNav: true
        }"
        x-init="
            window.addEventListener('scroll', () => {
                let currentScroll = window.pageYOffset;
                showNav = currentScroll < lastScroll || currentScroll < 50;
                lastScroll = currentScroll;
                
                // Update scrolled state for white background
                const scrolled = currentScroll > 50;
                if (scrolled !== Alpine.$data.scrolled) {
                    Alpine.$data.scrolled = scrolled;
                }
            })
        "
        :class="[
            showNav ? 'translate-y-0' : '-translate-y-full',
            scrolled ? (darkMode ? 'dark-nav' : 'nav-scrolled') : 'bg-transparent'
        ]"
        class="w-full fixed top-0 z-50 transition-all duration-300 ease-in-out"
    >
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <!-- Logo -->
                <div class="flex items-center">
                    <div class="bg-primary w-10 h-10 rounded-lg flex items-center justify-center">
                        <span class="text-white font-bold text-xl">A</span>
                    </div>
                    <span class="ml-3 text-xl font-bold text-primary dark:text-white">
                        AmazonBlue<span class="text-accent">Capital</span>
                    </span>
                </div>

                <!-- Desktop Navigation -->
                <div class="hidden lg:flex items-center space-x-8">
                    <a href="#slide-0" @click="activeSlide = 0" class="font-medium hover:text-accent transition-colors" :class="activeSlide === 0 ? 'text-accent' : (darkMode ? 'text-white' : (scrolled ? 'text-gray-700' : 'text-white'))">Emergency</a>
                    <a href="#slide-1" @click="activeSlide = 1" class="font-medium hover:text-accent transition-colors" :class="activeSlide === 1 ? 'text-accent' : (darkMode ? 'text-white' : (scrolled ? 'text-gray-700' : 'text-white'))">CRB Solutions</a>
                    <a href="#slide-2" @click="activeSlide = 2" class="font-medium hover:text-accent transition-colors" :class="activeSlide === 2 ? 'text-accent' : (darkMode ? 'text-white' : (scrolled ? 'text-gray-700' : 'text-white'))">Asset Finance</a>
                    <a href="#slide-3" @click="activeSlide = 3" class="font-medium hover:text-accent transition-colors" :class="activeSlide === 3 ? 'text-accent' : (darkMode ? 'text-white' : (scrolled ? 'text-gray-700' : 'text-white'))">Commodities</a>
                    <a href="#services" class="font-medium hover:text-accent transition-colors" :class="darkMode ? 'text-white' : (scrolled ? 'text-gray-700' : 'text-white')">Services</a>
                    <a href="#about" class="font-medium hover:text-accent transition-colors" :class="darkMode ? 'text-white' : (scrolled ? 'text-gray-700' : 'text-white')">About</a>
                    <a href="#contact" class="font-medium hover:text-accent transition-colors" :class="darkMode ? 'text-white' : (scrolled ? 'text-gray-700' : 'text-white')">Contact</a>

                    <div class="flex items-center space-x-4">
                        <button
                            @click="darkMode = !darkMode"
                            class="p-2 rounded-lg bg-gray-100/70 dark:bg-gray-800/70 backdrop-blur"
                        >
                            <i x-show="!darkMode" class="fas fa-moon text-gray-600"></i>
                            <i x-show="darkMode" class="fas fa-sun text-yellow-400"></i>
                        </button>

                        <a
                            href="{{ route('login') }}"
                            class="px-5 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:border-primary dark:hover:border-accent transition-all" :class="darkMode ? 'text-white' : (scrolled ? 'text-gray-700' : 'text-white')"
                        >
                            Log in
                        </a>
                    </div>
                </div>

                <!-- Mobile -->
                <div class="lg:hidden flex items-center space-x-4">
                    <button @click="darkMode = !darkMode" class="p-2 rounded-lg bg-gray-100/70 dark:bg-gray-800/70 backdrop-blur">
                        <i x-show="!darkMode" class="fas fa-moon text-gray-600"></i>
                        <i x-show="darkMode" class="fas fa-sun text-yellow-400"></i>
                    </button>
                    <button @click="mobileMenuOpen = true" :class="darkMode ? 'text-white' : (scrolled ? 'text-gray-700' : 'text-white')">
                        <i class="fas fa-bars text-2xl"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Full Screen Mobile Menu -->
    <div x-show="mobileMenuOpen" class="full-screen-menu fixed inset-0 bg-white dark:bg-dark z-50 overflow-y-auto">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="flex justify-between items-center h-20 mb-12">
                <div class="flex items-center">
                    <div class="bg-primary w-10 h-10 rounded-lg flex items-center justify-center">
                        <span class="text-white font-bold text-xl">A</span>
                    </div>
                    <span class="ml-3 text-xl font-bold text-primary dark:text-white">AmazonBlue<span class="text-accent">Capital</span></span>
                </div>
                <button @click="mobileMenuOpen = false" class="text-gray-700 dark:text-gray-300">
                    <i class="fas fa-times text-3xl"></i>
                </button>
            </div>
            
            <!-- Navigation Links -->
            <div class="space-y-8 mb-16">
                <a href="#slide-0" @click="activeSlide = 0; mobileMenuOpen = false" class="block text-3xl font-bold text-gray-800 dark:text-white hover:text-accent transition-colors">Emergency Solutions</a>
                <a href="#slide-1" @click="activeSlide = 1; mobileMenuOpen = false" class="block text-3xl font-bold text-gray-800 dark:text-white hover:text-accent transition-colors">CRB Solutions</a>
                <a href="#slide-2" @click="activeSlide = 2; mobileMenuOpen = false" class="block text-3xl font-bold text-gray-800 dark:text-white hover:text-accent transition-colors">Asset Finance</a>
                <a href="#slide-3" @click="activeSlide = 3; mobileMenuOpen = false" class="block text-3xl font-bold text-gray-800 dark:text-white hover:text-accent transition-colors">Commodities</a>
                <a href="#services" @click="mobileMenuOpen = false" class="block text-3xl font-bold text-gray-800 dark:text-white hover:text-accent transition-colors">Services</a>
                <a href="#about" @click="mobileMenuOpen = false" class="block text-3xl font-bold text-gray-800 dark:text-white hover:text-accent transition-colors">About</a>
                <a href="#contact" @click="mobileMenuOpen = false" class="block text-3xl font-bold text-gray-800 dark:text-white hover:text-accent transition-colors">Contact</a>
            </div>
            
            <!-- Social Media -->
            <div class="mb-12">
                <h3 class="text-xl font-bold mb-6 text-gray-800 dark:text-white">Connect With Us</h3>
                <div class="flex space-x-6">
                    <a href="#" class="w-12 h-12 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center hover:bg-primary hover:text-white transition-all-300">
                        <i class="fab fa-linkedin-in text-lg"></i>
                    </a>
                    <a href="#" class="w-12 h-12 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center hover:bg-blue-400 hover:text-white transition-all-300">
                        <i class="fab fa-twitter text-lg"></i>
                    </a>
                    <a href="#" class="w-12 h-12 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center hover:bg-blue-600 hover:text-white transition-all-300">
                        <i class="fab fa-facebook-f text-lg"></i>
                    </a>
                    <a href="#" class="w-12 h-12 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center hover:bg-pink-600 hover:text-white transition-all-300">
                        <i class="fab fa-instagram text-lg"></i>
                    </a>
                </div>
            </div>
            
            <!-- Login Button -->
            <div>
                <a href="{{ route('login') }}" class="block w-full py-4 bg-primary text-white text-center rounded-lg font-bold hover:bg-secondary transition-all-300">
                    Log in to Portal
                </a>
            </div>
        </div>
    </div>

    <!-- Main Hero Carousel - Vertical Scroll Sections -->
    <div id="carousel-sections">
        <!-- Slide 0 - Emergency Solutions -->
        <section id="slide-0" class="relative overflow-hidden section-min-height slide-1 flex items-center">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="max-w-3xl">
                    <div class="animate-slide-in-up">
                        <span class="inline-block px-4 py-2 bg-accent text-white rounded-full text-sm font-semibold mb-6">Emergency Financial Support</span>
                        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-white mb-6 leading-tight">
                            Urgent Liquidity Solutions
                            <span class="block text-accent mt-2">Within 24 Hours</span>
                        </h1>
                        <p class="text-xl text-gray-200 mb-10 max-w-2xl">
                            When time is critical, AmazonBlue Capital provides emergency bridge financing across East Africa. Fast-track approvals for businesses and individuals facing urgent financial needs.
                        </p>
                        <div class="flex flex-col sm:flex-row gap-4">
                            <a href="#contact" class="px-8 py-4 bg-accent text-white rounded-lg font-bold hover:bg-blue-400 transition-all-300 transform hover:-translate-y-1 inline-flex items-center justify-center">
                                <i class="fas fa-phone-alt mr-3"></i> Call Emergency Line
                            </a>
                            <a href="#services" class="px-8 py-4 border-2 border-white text-white rounded-lg font-bold hover:bg-white hover:text-dark transition-all-300 inline-flex items-center justify-center">
                                <i class="fas fa-clock mr-3"></i> 24/7 Service
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="scroll-indicator" x-show="activeSlide === 0"></div>
        </section>

        <!-- Slide 1 - Credit Rehabilitation -->
        <section id="slide-1" class="relative overflow-hidden section-min-height slide-2 flex items-center">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="max-w-3xl">
                    <div class="animate-slide-in-up">
                        <span class="inline-block px-4 py-2 bg-accent text-white rounded-full text-sm font-semibold mb-6">Credit Bureau Solutions</span>
                        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-white mb-6 leading-tight">
                            CRB Blacklist Resolution
                            <span class="block text-accent mt-2">Confidential & Swift</span>
                        </h1>
                        <p class="text-xl text-gray-200 mb-10 max-w-2xl">
                            Restore your financial reputation with our discreet CRB clearance services. Working across Kenya's credit bureaus to provide fast, confidential solutions for credit rehabilitation.
                        </p>
                        <div class="flex flex-col sm:flex-row gap-4">
                            <a href="#contact" class="px-8 py-4 bg-accent text-white rounded-lg font-bold hover:bg-blue-400 transition-all-300 transform hover:-translate-y-1 inline-flex items-center justify-center">
                                <i class="fas fa-shield-alt mr-3"></i> Restore Credit
                            </a>
                            <a href="#services" class="px-8 py-4 border-2 border-white text-white rounded-lg font-bold hover:bg-white hover:text-dark transition-all-300 inline-flex items-center justify-center">
                                <i class="fas fa-user-check mr-3"></i> Bank Clearance
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="scroll-indicator" x-show="activeSlide === 1"></div>
        </section>

        <!-- Slide 2 - Asset Financing -->
        <section id="slide-2" class="relative overflow-hidden section-min-height slide-3 flex items-center">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="max-w-3xl">
                    <div class="animate-slide-in-up">
                        <span class="inline-block px-4 py-2 bg-accent text-white rounded-full text-sm font-semibold mb-6">Asset-Based Financing</span>
                        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-white mb-6 leading-tight">
                            Unlock Your Asset Value
                            <span class="block text-accent mt-2">Across East Africa</span>
                        </h1>
                        <p class="text-xl text-gray-200 mb-10 max-w-2xl">
                            Transform your property, vehicles, and valuable assets into working capital. Our asset-backed lending solutions provide liquidity while you retain ownership.
                        </p>
                        <div class="flex flex-col sm:flex-row gap-4">
                            <a href="#contact" class="px-8 py-4 bg-accent text-white rounded-lg font-bold hover:bg-blue-400 transition-all-300 transform hover:-translate-y-1 inline-flex items-center justify-center">
                                <i class="fas fa-home mr-3"></i> Property Loans
                            </a>
                            <a href="#services" class="px-8 py-4 border-2 border-white text-white rounded-lg font-bold hover:bg-white hover:text-dark transition-all-300 inline-flex items-center justify-center">
                                <i class="fas fa-car mr-3"></i> Vehicle Financing
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="scroll-indicator" x-show="activeSlide === 2"></div>
        </section>

        <!-- Slide 3 - Commodity Trade -->
        <section id="slide-3" class="relative overflow-hidden section-min-height slide-4 flex items-center">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="max-w-3xl">
                    <div class="animate-slide-in-up">
                        <span class="inline-block px-4 py-2 bg-accent text-white rounded-full text-sm font-semibold mb-6">Commodity & Trade Finance</span>
                        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-white mb-6 leading-tight">
                            East African Trade Solutions
                            <span class="block text-accent mt-2">Agricultural & Mineral</span>
                        </h1>
                        <p class="text-xl text-gray-200 mb-10 max-w-2xl">
                            Specialized financing for East Africa's key commodities - from tea, coffee, and horticulture to minerals and manufactured goods. We understand local markets and global trade dynamics.
                        </p>
                        <div class="flex flex-col sm:flex-row gap-4">
                            <a href="#contact" class="px-8 py-4 bg-accent text-white rounded-lg font-bold hover:bg-blue-400 transition-all-300 transform hover:-translate-y-1 inline-flex items-center justify-center">
                                <i class="fas fa-seedling mr-3"></i> Agri-Finance
                            </a>
                            <a href="#services" class="px-8 py-4 border-2 border-white text-white rounded-lg font-bold hover:bg-white hover:text-dark transition-all-300 inline-flex items-center justify-center">
                                <i class="fas fa-ship mr-3"></i> Trade Finance
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="scroll-indicator" x-show="activeSlide === 3"></div>
        </section>
    </div>

    <!-- Services Section -->
    <section id="services" class="py-20 bg-white dark:bg-gray-900 section-min-height">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-3xl sm:text-4xl font-bold text-dark dark:text-white mb-4">Our Financial Services</h2>
                <p class="text-lg text-gray-600 dark:text-gray-400">Specialized solutions for complex financial situations across East Africa</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Service 1 -->
                <div class="group cursor-pointer overflow-hidden rounded-2xl shadow-lg transition-all duration-300 hover:shadow-2xl hover:-translate-y-2">
                    <div class="relative h-64 bg-gradient-to-r from-primary to-secondary">
                        <div class="absolute inset-0 flex items-center justify-center">
                            <i class="fas fa-shield-alt text-6xl text-white opacity-80"></i>
                        </div>
                        <div class="absolute bottom-0 left-0 right-0 p-6 bg-gradient-to-t from-black/70 to-transparent">
                            <h3 class="text-2xl font-bold text-white mb-2">CRB Resolution</h3>
                            <p class="text-gray-200">Immediate credit bureau rehabilitation</p>
                        </div>
                    </div>
                    <div class="p-6 bg-white dark:bg-gray-800">
                        <p class="text-gray-600 dark:text-gray-400 mb-4">Swift resolution of credit bureau issues with complete confidentiality and rapid turnaround times across Kenyan credit bureaus.</p>
                        <ul class="space-y-2 mb-6">
                            <li class="flex items-center text-gray-600 dark:text-gray-400">
                                <i class="fas fa-check text-accent mr-2"></i>
                                24-48 hour processing
                            </li>
                            <li class="flex items-center text-gray-600 dark:text-gray-400">
                                <i class="fas fa-check text-accent mr-2"></i>
                                Complete discretion guaranteed
                            </li>
                            <li class="flex items-center text-gray-600 dark:text-gray-400">
                                <i class="fas fa-check text-accent mr-2"></i>
                                Bank clearance certificates
                            </li>
                        </ul>
                        <a href="#contact" class="text-primary dark:text-accent font-semibold hover:underline inline-flex items-center">
                            Get Started <i class="fas fa-arrow-right ml-2"></i>
                        </a>
                    </div>
                </div>
                
                <!-- Service 2 -->
                <div class="group cursor-pointer overflow-hidden rounded-2xl shadow-lg transition-all duration-300 hover:shadow-2xl hover:-translate-y-2">
                    <div class="relative h-64 bg-gradient-to-r from-blue-600 to-accent">
                        <div class="absolute inset-0 flex items-center justify-center">
                            <i class="fas fa-bolt text-6xl text-white opacity-80"></i>
                        </div>
                        <div class="absolute bottom-0 left-0 right-0 p-6 bg-gradient-to-t from-black/70 to-transparent">
                            <h3 class="text-2xl font-bold text-white mb-2">Emergency Liquidity</h3>
                            <p class="text-gray-200">10-day bridge financing solutions</p>
                        </div>
                    </div>
                    <div class="p-6 bg-white dark:bg-gray-800">
                        <p class="text-gray-600 dark:text-gray-400 mb-4">Short-term capital injections for urgent business and personal financial requirements across East Africa.</p>
                        <ul class="space-y-2 mb-6">
                            <li class="flex items-center text-gray-600 dark:text-gray-400">
                                <i class="fas fa-check text-accent mr-2"></i>
                                Fast-track approval within 24h
                            </li>
                            <li class="flex items-center text-gray-600 dark:text-gray-400">
                                <i class="fas fa-check text-accent mr-2"></i>
                                Emergency bridge financing
                            </li>
                            <li class="flex items-center text-gray-600 dark:text-gray-400">
                                <i class="fas fa-check text-accent mr-2"></i>
                                Minimal documentation
                            </li>
                        </ul>
                        <a href="#contact" class="text-primary dark:text-accent font-semibold hover:underline inline-flex items-center">
                            Get Emergency Funds <i class="fas fa-arrow-right ml-2"></i>
                        </a>
                    </div>
                </div>
                
                <!-- Service 3 -->
                <div class="group cursor-pointer overflow-hidden rounded-2xl shadow-lg transition-all duration-300 hover:shadow-2xl hover:-translate-y-2">
                    <div class="relative h-64 bg-gradient-to-r from-secondary to-primary">
                        <div class="absolute inset-0 flex items-center justify-center">
                            <i class="fas fa-landmark text-6xl text-white opacity-80"></i>
                        </div>
                        <div class="absolute bottom-0 left-0 right-0 p-6 bg-gradient-to-t from-black/70 to-transparent">
                            <h3 class="text-2xl font-bold text-white mb-2">Asset-Based Lending</h3>
                            <p class="text-gray-200">Leverage your assets for liquidity</p>
                        </div>
                    </div>
                    <div class="p-6 bg-white dark:bg-gray-800">
                        <p class="text-gray-600 dark:text-gray-400 mb-4">Maximize the value of your real estate, vehicles, and valuable assets for immediate capital needs across East Africa.</p>
                        <ul class="space-y-2 mb-6">
                            <li class="flex items-center text-gray-600 dark:text-gray-400">
                                <i class="fas fa-check text-accent mr-2"></i>
                                Real estate-backed loans
                            </li>
                            <li class="flex items-center text-gray-600 dark:text-gray-400">
                                <i class="fas fa-check text-accent mr-2"></i>
                                Vehicle asset financing
                            </li>
                            <li class="flex items-center text-gray-600 dark:text-gray-400">
                                <i class="fas fa-check text-accent mr-2"></i>
                                Luxury asset collateral
                            </li>
                        </ul>
                        <a href="#contact" class="text-primary dark:text-accent font-semibold hover:underline inline-flex items-center">
                            Unlock Asset Value <i class="fas fa-arrow-right ml-2"></i>
                        </a>
                    </div>
                </div>
                
                <!-- Service 4 -->
                <div class="group cursor-pointer overflow-hidden rounded-2xl shadow-lg transition-all duration-300 hover:shadow-2xl hover:-translate-y-2">
                    <div class="relative h-64 bg-gradient-to-r from-green-600 to-emerald-500">
                        <div class="absolute inset-0 flex items-center justify-center">
                            <i class="fas fa-globe-africa text-6xl text-white opacity-80"></i>
                        </div>
                        <div class="absolute bottom-0 left-0 right-0 p-6 bg-gradient-to-t from-black/70 to-transparent">
                            <h3 class="text-2xl font-bold text-white mb-2">Commodity Financing</h3>
                            <p class="text-gray-200">Trade and commodity-based solutions</p>
                        </div>
                    </div>
                    <div class="p-6 bg-white dark:bg-gray-800">
                        <p class="text-gray-600 dark:text-gray-400 mb-4">Financial products structured around agricultural, mineral, and trade commodities across East African markets.</p>
                        <ul class="space-y-2 mb-6">
                            <li class="flex items-center text-gray-600 dark:text-gray-400">
                                <i class="fas fa-check text-accent mr-2"></i>
                                Agricultural commodity finance
                            </li>
                            <li class="flex items-center text-gray-600 dark:text-gray-400">
                                <i class="fas fa-check text-accent mr-2"></i>
                                Mineral and mining finance
                            </li>
                            <li class="flex items-center text-gray-600 dark:text-gray-400">
                                <i class="fas fa-check text-accent mr-2"></i>
                                Import/export trade finance
                            </li>
                        </ul>
                        <a href="#contact" class="text-primary dark:text-accent font-semibold hover:underline inline-flex items-center">
                            Finance Your Trade <i class="fas fa-arrow-right ml-2"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Projects/Results Section -->
    <section id="projects" class="py-20 bg-gray-50 dark:bg-gray-800 half-section-min-height">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-3xl sm:text-4xl font-bold text-dark dark:text-white mb-4">Recent Success Stories</h2>
                <p class="text-lg text-gray-600 dark:text-gray-400">Transforming financial challenges into opportunities across East Africa</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white dark:bg-gray-900 rounded-xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-2">
                    <div class="text-4xl font-bold text-primary dark:text-accent mb-4">98%</div>
                    <h3 class="text-xl font-bold text-dark dark:text-white mb-2">Client Satisfaction Rate</h3>
                    <p class="text-gray-600 dark:text-gray-400">Across all financial services provided in 2024</p>
                </div>
                
                <div class="bg-white dark:bg-gray-900 rounded-xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-2">
                    <div class="text-4xl font-bold text-primary dark:text-accent mb-4">24h</div>
                    <h3 class="text-xl font-bold text-dark dark:text-white mb-2">Average Processing Time</h3>
                    <p class="text-gray-600 dark:text-gray-400">For emergency liquidity solutions</p>
                </div>
                
                <div class="bg-white dark:bg-gray-900 rounded-xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-2">
                    <div class="text-4xl font-bold text-primary dark:text-accent mb-4">10k+</div>
                    <h3 class="text-xl font-bold text-dark dark:text-white mb-2">Financial Resolutions</h3>
                    <p class="text-gray-600 dark:text-gray-400">Successfully completed since 2025</p>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-20 bg-white dark:bg-gray-900 half-section-min-height">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <h2 class="text-3xl sm:text-4xl font-bold text-dark dark:text-white mb-6">About AmazonBlue Capital</h2>
                    <p class="text-lg text-gray-600 dark:text-gray-400 mb-6">
                        Founded in Nairobi, 2025, AmazonBlue Capital has established itself as a premier provider of specialized financial solutions across East Africa. 
                        We combine deep local market expertise with innovative approaches to solve complex financial challenges unique to the African business landscape.
                    </p>
                    <p class="text-lg text-gray-600 dark:text-gray-400 mb-8">
                        Our team of financial experts operates with the discretion, speed, and precision needed in today's dynamic African markets, 
                        ensuring our clients receive tailored solutions that address their unique circumstances while maintaining the highest standards of confidentiality.
                    </p>
                    <a href="#contact" class="px-6 py-3 bg-primary text-white rounded-lg font-semibold hover:bg-secondary transition-all-300 inline-flex items-center">
                        Connect With Our Team <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                </div>
                <div class="relative">
                    <img src="https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?ixlib=rb-4.0.3&auto=format&fit=crop&w=2670&q=80" 
                         alt="AmazonBlue Capital Nairobi Office" 
                         class="rounded-xl shadow-2xl">
                    <div class="absolute -bottom-6 -right-6 w-32 h-32 bg-accent rounded-xl opacity-20"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-20 bg-gray-50 dark:bg-gray-800 half-section-min-height">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-4xl mx-auto">
                <div class="text-center mb-16">
                    <h2 class="text-3xl sm:text-4xl font-bold text-dark dark:text-white mb-4">Get In Touch</h2>
                    <p class="text-lg text-gray-600 dark:text-gray-400">Contact us for confidential financial consultations</p>
                </div>
                
                <div class="bg-white dark:bg-gray-900 rounded-2xl p-8 shadow-lg">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <h3 class="text-2xl font-bold text-dark dark:text-white mb-6">Contact Information</h3>
                            <div class="space-y-6">
                                <div class="flex items-start">
                                    <div class="bg-primary/10 text-primary p-3 rounded-lg mr-4">
                                        <i class="fas fa-phone-alt"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-dark dark:text-white">24/7 Emergency Line</h4>
                                        <p class="text-gray-600 dark:text-gray-400">+254 701 607 959</p>
                                    </div>
                                </div>
                                
                                <div class="flex items-start">
                                    <div class="bg-primary/10 text-primary p-3 rounded-lg mr-4">
                                        <i class="fas fa-envelope"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-dark dark:text-white">Email</h4>
                                        <p class="text-gray-600 dark:text-gray-400">contact@amazonbluecapital.com</p>
                                    </div>
                                </div>
                                
                                <div class="flex items-start">
                                    <div class="bg-primary/10 text-primary p-3 rounded-lg mr-4">
                                        <i class="fas fa-map-marker-alt"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-dark dark:text-white">Location</h4>
                                        <p class="text-gray-600 dark:text-gray-400">Nairobi, Kenya</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <h3 class="text-2xl font-bold text-dark dark:text-white mb-6">Send a Message</h3>
                            <form class="space-y-4">
                                <input type="text" placeholder="Your Name" class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-transparent focus:outline-none focus:ring-2 focus:ring-primary">
                                <input type="email" placeholder="Your Email" class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-transparent focus:outline-none focus:ring-2 focus:ring-primary">
                                <textarea placeholder="Your Message" rows="4" class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-transparent focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
                                <button type="submit" class="w-full py-3 bg-primary text-white rounded-lg font-semibold hover:bg-secondary transition-all-300">
                                    Send Message
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-800">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <!-- Top Section -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8 mb-12">
                <div>
                    <div class="flex items-center mb-6">
                        <div class="bg-primary w-10 h-10 rounded-lg flex items-center justify-center">
                            <span class="text-white font-bold text-xl">A</span>
                        </div>
                        <span class="ml-3 text-xl font-bold text-primary dark:text-white">AmazonBlue<span class="text-accent">Capital</span></span>
                    </div>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        Premium financial solutions with discretion and speed since 2025.
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-primary dark:hover:text-accent transition-colors" aria-label="LinkedIn">
                            <i class="fab fa-linkedin-in text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-blue-400 transition-colors" aria-label="Twitter">
                            <i class="fab fa-twitter text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-blue-600 transition-colors" aria-label="Facebook">
                            <i class="fab fa-facebook-f text-xl"></i>
                        </a>
                    </div>
                </div>
                
                <div>
                    <h3 class="text-lg font-bold mb-6 text-dark dark:text-white">Services</h3>
                    <ul class="space-y-3">
                        <li><a href="#slide-0" @click="activeSlide = 0" class="text-gray-600 dark:text-gray-400 hover:text-primary dark:hover:text-accent transition-colors">Emergency Solutions</a></li>
                        <li><a href="#slide-1" @click="activeSlide = 1" class="text-gray-600 dark:text-gray-400 hover:text-primary dark:hover:text-accent transition-colors">CRB Solutions</a></li>
                        <li><a href="#slide-2" @click="activeSlide = 2" class="text-gray-600 dark:text-gray-400 hover:text-primary dark:hover:text-accent transition-colors">Asset Finance</a></li>
                        <li><a href="#slide-3" @click="activeSlide = 3" class="text-gray-600 dark:text-gray-400 hover:text-primary dark:hover:text-accent transition-colors">Commodities</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="text-lg font-bold mb-6 text-dark dark:text-white">Company</h3>
                    <ul class="space-y-3">
                        <li><a href="#about" class="text-gray-600 dark:text-gray-400 hover:text-primary dark:hover:text-accent transition-colors">About Us</a></li>
                        <li><a href="#services" class="text-gray-600 dark:text-gray-400 hover:text-primary dark:hover:text-accent transition-colors">Services</a></li>
                        <li><a href="#contact" class="text-gray-600 dark:text-gray-400 hover:text-primary dark:hover:text-accent transition-colors">Contact</a></li>
                        <li><a href="#" class="text-gray-600 dark:text-gray-400 hover:text-primary dark:hover:text-accent transition-colors">Careers</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="text-lg font-bold mb-6 text-dark dark:text-white">Contact Us</h3>
                    <ul class="space-y-3 text-gray-600 dark:text-gray-400">
                        <li class="flex items-start">
                            <i class="fas fa-phone-alt mt-1 mr-3 text-accent"></i>
                            <span>+254 701 607 959</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-envelope mt-1 mr-3 text-accent"></i>
                            <span>contact@amazonbluecapital.com</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-map-marker-alt mt-1 mr-3 text-accent"></i>
                            <span>Nairobi, Kenya</span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Important Notice -->
                <h4 class="font-bold mb-2">Important Disclaimer</h4>
                <p class="text-sm">
                    This website is solely for informational and reference purposes only and does not constitute an offer to sell 
                    or a solicitation of an offer to buy any securities and may not be used or relied upon in evaluating the merit 
                    of any investment. All financial services are subject to terms and conditions, and eligibility criteria apply.
                </p><br />
            
            <!-- Bottom Section -->
            <div class="pt-8 border-t border-gray-200 dark:border-gray-800">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div class="text-gray-600 dark:text-gray-400 text-sm mb-4 md:mb-0">
                        © 2026 AmazonBlue Capital. All rights reserved.
                    </div>
                    <div class="flex flex-wrap gap-6 text-sm">
                        <a href="#" class="text-gray-600 dark:text-gray-400 hover:text-primary dark:hover:text-accent transition-colors">Privacy Notice</a>
                        <a href="#" class="text-gray-600 dark:text-gray-400 hover:text-primary dark:hover:text-accent transition-colors">Personal Data Request</a>
                        <a href="#" class="text-gray-600 dark:text-gray-400 hover:text-primary dark:hover:text-accent transition-colors">Legal Disclaimer</a>
                        <a href="#" class="text-gray-600 dark:text-gray-400 hover:text-primary dark:hover:text-accent transition-colors">Terms of Service</a>
                        <a href="#" class="text-gray-600 dark:text-gray-400 hover:text-primary dark:hover:text-accent transition-colors">Cookie Policy</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Scroll-based carousel navigation
        let isScrolling = false;
        let slideElements = [];
        
        function updateActiveSlideOnScroll() {
            if (isScrolling) return;
            
            isScrolling = true;
            
            const scrollPosition = window.scrollY + window.innerHeight / 3;
            
            for (let i = 0; i < slideElements.length; i++) {
                const slide = slideElements[i];
                const slideTop = slide.offsetTop;
                const slideBottom = slideTop + slide.offsetHeight;
                
                if (scrollPosition >= slideTop && scrollPosition < slideBottom) {
                    if (Alpine.$data.activeSlide !== i) {
                        Alpine.$data.activeSlide = i;
                    }
                    break;
                }
            }
            
            setTimeout(() => {
                isScrolling = false;
            }, 100);
        }
        
        // Initialize slide elements and scroll listener
        document.addEventListener('DOMContentLoaded', () => {
            // Get all slide sections
            slideElements = [
                document.getElementById('slide-0'),
                document.getElementById('slide-1'),
                document.getElementById('slide-2'),
                document.getElementById('slide-3')
            ];
            
            // Add scroll event listener
            window.addEventListener('scroll', updateActiveSlideOnScroll);
            
            // Initial check
            updateActiveSlideOnScroll();
            
            // Smooth scroll to slide when clicking nav links
            document.querySelectorAll('a[href^="#slide-"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href');
                    const targetElement = document.querySelector(targetId);
                    
                    if (targetElement) {
                        window.scrollTo({
                            top: targetElement.offsetTop,
                            behavior: 'smooth'
                        });
                    }
                });
            });
            
            // Smooth scrolling for other anchor links
            document.querySelectorAll('a[href^="#"]:not([href^="#slide-"])').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', (event) => {
            const mobileMenu = document.querySelector('[x-show="mobileMenuOpen"]');
            const mobileMenuButton = document.querySelector('button[aria-label*="menu"]');
            
            if (mobileMenu && mobileMenu.contains(event.target) === false && 
                mobileMenuButton && mobileMenuButton.contains(event.target) === false &&
                mobileMenu.__x.$data.mobileMenuOpen) {
                mobileMenu.__x.$data.mobileMenuOpen = false;
            }
        });

        // Initialize dark mode from localStorage
        document.addEventListener('alpine:init', () => {
            Alpine.data('theme', () => ({
                init() {
                    const savedTheme = localStorage.getItem('darkMode');
                    if (savedTheme) {
                        this.darkMode = savedTheme === 'true';
                    } else {
                        // Check system preference
                        this.darkMode = window.matchMedia('(prefers-color-scheme: dark)').matches;
                    }
                    
                    // Watch for changes
                    this.$watch('darkMode', (value) => {
                        localStorage.setItem('darkMode', value);
                    });
                }
            }));
        });
    </script>
</body>
</html>