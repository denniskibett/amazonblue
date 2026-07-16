    <!DOCTYPE html>
    <html lang="en" x-data="{
        darkMode: false,
        mobileMenuOpen: false,
        scrolled: false,
        activeTab: 'features',
        selectedPlan: 'builder'
    }">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>AmazonBlue Capital | Premium Financial Solutions</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
        
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            
            body {
                font-family: 'Outfit', -apple-system, BlinkMacSystemFont, sans-serif;
                background: #ffffff;
                color: #1a1a2e;
            }
            
            .gradient-primary {
                background: linear-gradient(135deg, #1a365d 0%, #2563eb 50%, #3b82f6 100%);
            }
            
            .gradient-accent {
                background: linear-gradient(135deg, #2563eb 0%, #1a365d 100%);
            }
            
            .gradient-text {
                background: linear-gradient(135deg, #2563eb, #3b82f6);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
            }
            
            .gradient-border {
                position: relative;
                border: 2px solid transparent;
                background-clip: padding-box;
            }
            
            .gradient-border::before {
                content: '';
                position: absolute;
                inset: -2px;
                border-radius: inherit;
                padding: 2px;
                background: linear-gradient(135deg, #2563eb, #60a5fa);
                -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
                mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
                -webkit-mask-composite: xor;
                mask-composite: exclude;
                pointer-events: none;
            }
            
            .nav-blur {
                backdrop-filter: blur(16px);
                -webkit-backdrop-filter: blur(16px);
            }
            
            .nav-scrolled {
                background: rgba(255, 255, 255, 0.95);
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
            }
            
            .mobile-menu {
                transform: translateX(100%);
                transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            }
            
            .mobile-menu.open {
                transform: translateX(0);
            }
            
            .plan-card {
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                border: 2px solid transparent;
            }
            
            .plan-card:hover {
                transform: translateY(-8px);
            }
            
            .plan-card.popular {
                border-color: #2563eb;
                box-shadow: 0 20px 40px -12px rgba(37, 99, 235, 0.25);
            }
            
            .feature-icon {
                width: 48px;
                height: 48px;
                border-radius: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 20px;
                transition: all 0.3s ease;
            }
            
            .feature-card {
                transition: all 0.3s ease;
                border: 1px solid #e2e8f0;
            }
            
            .feature-card:hover {
                transform: translateY(-4px);
                box-shadow: 0 12px 24px -8px rgba(0, 0, 0, 0.08);
            }
            
            .testimonial-card {
                transition: all 0.3s ease;
            }
            
            .testimonial-card:hover {
                transform: translateY(-4px);
            }
            
            .pulse-dot {
                animation: pulse 2s infinite;
            }
            
            @keyframes pulse {
                0%, 100% { opacity: 0.6; }
                50% { opacity: 1; transform: scale(1.1); }
            }
            
            .faq-item summary::-webkit-details-marker {
                display: none;
            }
            
            .faq-item summary {
                list-style: none;
                cursor: pointer;
            }
            
            .input-focus {
                transition: all 0.2s ease;
            }
            
            .input-focus:focus {
                box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
                border-color: #2563eb;
            }
            
            .scroll-indicator {
                position: absolute;
                bottom: 32px;
                left: 50%;
                transform: translateX(-50%);
                width: 28px;
                height: 44px;
                border: 2px solid rgba(255, 255, 255, 0.3);
                border-radius: 14px;
                display: flex;
                justify-content: center;
                padding-top: 8px;
            }
            
            .scroll-indicator::before {
                content: '';
                width: 3px;
                height: 10px;
                background: rgba(255, 255, 255, 0.6);
                border-radius: 2px;
                animation: bounce 2s infinite;
            }
            
            @keyframes bounce {
                0%, 100% { transform: translateY(0); opacity: 0.4; }
                50% { transform: translateY(12px); opacity: 1; }
            }
            
            .stat-number {
                font-size: 3.5rem;
                font-weight: 900;
                line-height: 1;
            }
            
            @media (max-width: 640px) {
                .stat-number {
                    font-size: 2.5rem;
                }
            }
            
            .bg-soft-blue {
                background: #eff6ff;
            }
            
            .border-soft-blue {
                border-color: #bfdbfe;
            }
        </style>
    </head>
    <body>

        <!-- ========== NAVIGATION ========== -->
        <nav x-data="{
            lastScroll: 0,
            showNav: true
        }"
        x-init="
            window.addEventListener('scroll', () => {
                let currentScroll = window.pageYOffset;
                showNav = currentScroll < lastScroll || currentScroll < 60;
                lastScroll = currentScroll;
                const scrolled = currentScroll > 60;
                if (scrolled !== Alpine.$data.scrolled) {
                    Alpine.$data.scrolled = scrolled;
                }
            })
        "
        :class="[
            showNav ? 'translate-y-0' : '-translate-y-full',
            scrolled ? 'nav-scrolled' : 'bg-transparent'
        ]"
        class="fixed top-0 w-full z-50 transition-all duration-300 ease-in-out nav-blur">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <!-- Logo -->
                    <a href="#" class="flex items-center space-x-3">
                        <div class="w-9 h-9 rounded-xl bg-blue-600 flex items-center justify-center shadow-lg shadow-blue-600/20">
                            <span class="text-white font-bold text-lg">A</span>
                        </div>
                        <span class="text-lg font-bold tracking-tight text-slate-900">
                           <span class="text-white"> AmazonBlue Capital</span><span class="text-blue-600"></span>
                        </span>
                    </a>

                    <!-- Desktop Menu -->
                    <div class="hidden md:flex items-center space-x-8">
                        <a href="#features" class="text-sm font-medium transition-colors hover:text-blue-600"
                        :class="scrolled ? 'text-slate-700' : 'text-white'">Features</a>
                        <a href="#plans" class="text-sm font-medium transition-colors hover:text-blue-600"
                        :class="scrolled ? 'text-slate-700' : 'text-white'">Plans</a>
                        <a href="#testimonials" class="text-sm font-medium transition-colors hover:text-blue-600"
                        :class="scrolled ? 'text-slate-700' : 'text-white'">Testimonials</a>
                        <a href="#faq" class="text-sm font-medium transition-colors hover:text-blue-600"
                        :class="scrolled ? 'text-slate-700' : 'text-white'">FAQ</a>
                        
                        <div class="flex items-center space-x-3 pl-4 border-l border-slate-200">
                            <a href="{{ route('login') }}"
                            class="px-5 py-2 text-sm font-medium rounded-lg transition-all duration-200 bg-blue-600 text-white hover:bg-blue-700 shadow-lg shadow-blue-600/25">
                                Get Started
                            </a>
                        </div>
                    </div>

                    <!-- Mobile Menu Button -->
                    <div class="md:hidden flex items-center space-x-3">
                        <button @click="mobileMenuOpen = true"
                                class="p-2 rounded-lg hover:bg-slate-100 transition-colors"
                                :class="scrolled ? 'text-slate-600' : 'text-white'">
                            <i class="fas fa-bars text-xl"></i>
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <!-- ========== MOBILE MENU ========== -->
        <div x-show="mobileMenuOpen" class="mobile-menu fixed inset-0 z-50 bg-white overflow-y-auto" style="display: none;">
            <div class="flex flex-col h-full">
                <div class="flex items-center justify-between p-4 border-b border-slate-200">
                    <span class="text-lg font-bold text-slate-900">Menu</span>
                    <button @click="mobileMenuOpen = false" class="p-2 rounded-lg hover:bg-slate-100">
                        <i class="fas fa-times text-xl text-slate-600"></i>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto p-6 space-y-6">
                    <a href="#features" @click="mobileMenuOpen = false" class="block text-2xl font-semibold text-slate-900 hover:text-blue-600 transition-colors">Features</a>
                    <a href="#plans" @click="mobileMenuOpen = false" class="block text-2xl font-semibold text-slate-900 hover:text-blue-600 transition-colors">Plans</a>
                    <a href="#testimonials" @click="mobileMenuOpen = false" class="block text-2xl font-semibold text-slate-900 hover:text-blue-600 transition-colors">Testimonials</a>
                    <a href="#faq" @click="mobileMenuOpen = false" class="block text-2xl font-semibold text-slate-900 hover:text-blue-600 transition-colors">FAQ</a>
                </div>
                <div class="p-6 border-t border-slate-200 space-y-3">
                    <a href="{{ route('login') }}" class="block w-full py-3 text-center text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
                        Get Started
                    </a>
                    <a href="{{ route('login') }}" class="block w-full py-3 text-center text-sm font-semibold text-slate-700 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                        Sign In
                    </a>
                </div>
            </div>
        </div>

        <!-- ========== HERO SECTION ========== -->
        <section class="relative overflow-hidden min-h-screen flex items-center gradient-primary">
            <!-- Decorative elements -->
            <div class="absolute top-20 right-10 w-72 h-72 bg-white/10 rounded-full blur-3xl"></div>
            <div class="absolute bottom-20 left-10 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>
            
            <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                    <div>
                        <div class="inline-flex items-center px-3 py-1 bg-white/10 backdrop-blur-sm rounded-full text-sm text-white/90 mb-6">
                            <span class="w-2 h-2 rounded-full bg-green-400 pulse-dot mr-2"></span>
                            Trusted by 500+ clients
                        </div>
                        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-white leading-tight">
                            Financial Solutions
                            <span class="block text-transparent bg-clip-text bg-gradient-to-r from-blue-200 to-white">Delivered at Speed</span>
                        </h1>
                        <p class="text-lg text-white/80 mt-6 max-w-lg">
                            Emergency funding, credit rehabilitation, asset financing, and commodity trade finance — all in one place.
                        </p>
                        <div class="flex flex-wrap gap-4 mt-8">
                            <a href="#plans" class="px-6 py-3 bg-white text-blue-600 font-semibold rounded-lg hover:bg-blue-50 transition-all shadow-lg shadow-white/25">
                                Get Started <i class="fas fa-arrow-right ml-2"></i>
                            </a>
                            <a href="#features" class="px-6 py-3 border border-white/20 text-white font-semibold rounded-lg hover:bg-white/10 transition-all">
                                Learn More
                            </a>
                        </div>
                        
                        <!-- Trust badges -->
                        <div class="flex flex-wrap items-center gap-6 mt-10 pt-8 border-t border-white/10">
                            <div class="flex items-center space-x-2 text-white/70">
                                <i class="fas fa-shield-alt text-blue-300"></i>
                                <span class="text-sm">Confidential</span>
                            </div>
                            <div class="flex items-center space-x-2 text-white/70">
                                <i class="fas fa-bolt text-blue-300"></i>
                                <span class="text-sm">Fast-Track</span>
                            </div>
                            <div class="flex items-center space-x-2 text-white/70">
                                <i class="fas fa-globe-africa text-blue-300"></i>
                                <span class="text-sm">East Africa</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Hero Right - Stats -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-white/5 backdrop-blur-sm rounded-2xl p-6 border border-white/10">
                            <div class="text-3xl font-extrabold text-white">98%</div>
                            <p class="text-sm text-white/70 mt-1">Client Satisfaction</p>
                        </div>
                        <div class="bg-white/5 backdrop-blur-sm rounded-2xl p-6 border border-white/10">
                            <div class="text-3xl font-extrabold text-white">24h</div>
                            <p class="text-sm text-white/70 mt-1">Average Processing</p>
                        </div>
                        <div class="bg-white/5 backdrop-blur-sm rounded-2xl p-6 border border-white/10">
                            <div class="text-3xl font-extrabold text-white">500+</div>
                            <p class="text-sm text-white/70 mt-1">Clients Served</p>
                        </div>
                        <div class="bg-white/5 backdrop-blur-sm rounded-2xl p-6 border border-white/10">
                            <div class="text-3xl font-extrabold text-white">KES 2B+</div>
                            <p class="text-sm text-white/70 mt-1">Capital Deployed</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="scroll-indicator"></div>
        </section>

        <!-- ========== FEATURES ========== -->
        <section id="features" class="py-24 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-2xl mx-auto">
                    <span class="text-blue-600 text-sm font-semibold tracking-wider uppercase">Elevate Your Business</span>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mt-2 mb-4">
                        Where You Find Capital Fast
                    </h2>
                    <p class="text-lg text-slate-600">
                        Financing Built for Time-Critical Business Needs
                    </p>
                </div>

                <!-- Feature Tabs -->
                <div class="mt-12 flex justify-center">
                    <div class="inline-flex rounded-xl bg-slate-100 p-1">
                        <button @click="activeTab = 'features'" 
                                class="px-6 py-2.5 text-sm font-medium rounded-lg transition-all"
                                :class="activeTab === 'features' ? 'bg-white shadow-sm text-slate-900' : 'text-slate-600 hover:text-slate-900'">
                            <i class="fas fa-th-large mr-2"></i> Features
                        </button>
                        <button @click="activeTab = 'services'" 
                                class="px-6 py-2.5 text-sm font-medium rounded-lg transition-all"
                                :class="activeTab === 'services' ? 'bg-white shadow-sm text-slate-900' : 'text-slate-600 hover:text-slate-900'">
                            <i class="fas fa-briefcase mr-2"></i> Services
                        </button>
                    </div>
                </div>

                <!-- Features Grid -->
                <div x-show="activeTab === 'features'" class="mt-12 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="feature-card bg-white rounded-2xl p-6">
                        <div class="feature-icon bg-blue-50 text-blue-600">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mt-4">Emergency Liquidity</h3>
                        <p class="text-sm text-slate-600 mt-2">Fast-track bridge financing within 24 hours for urgent needs.</p>
                    </div>
                    
                    <div class="feature-card bg-white rounded-2xl p-6">
                        <div class="feature-icon bg-blue-50 text-blue-600">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mt-4">CRB Resolution</h3>
                        <p class="text-sm text-slate-600 mt-2">Immediate credit bureau rehabilitation with complete discretion.</p>
                    </div>
                    
                    <div class="feature-card bg-white rounded-2xl p-6">
                        <div class="feature-icon bg-blue-50 text-blue-600">
                            <i class="fas fa-landmark"></i>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mt-4">Asset-Based Lending</h3>
                        <p class="text-sm text-slate-600 mt-2">Leverage real estate, vehicles, and valuable assets.</p>
                    </div>
                    
                    <div class="feature-card bg-white rounded-2xl p-6">
                        <div class="feature-icon bg-blue-50 text-blue-600">
                            <i class="fas fa-globe-africa"></i>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mt-4">Commodity Financing</h3>
                        <p class="text-sm text-slate-600 mt-2">Agricultural, mineral, and trade commodity solutions.</p>
                    </div>
                    
                    <div class="feature-card bg-white rounded-2xl p-6">
                        <div class="feature-icon bg-blue-50 text-blue-600">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mt-4">24/7 Support</h3>
                        <p class="text-sm text-slate-600 mt-2">Real human support available around the clock.</p>
                    </div>
                    
                    <div class="feature-card bg-white rounded-2xl p-6">
                        <div class="feature-icon bg-blue-50 text-blue-600">
                            <i class="fas fa-file-signature"></i>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mt-4">Confidential Service</h3>
                        <p class="text-sm text-slate-600 mt-2">Complete discretion guaranteed for all clients.</p>
                    </div>
                </div>

                <!-- Services Grid -->
                <div x-show="activeTab === 'services'" class="mt-12 grid grid-cols-1 md:grid-cols-2 gap-6" style="display: none;">
                    <div class="feature-card bg-white rounded-2xl p-6 flex items-start space-x-4">
                        <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600 flex-shrink-0">
                            <i class="fas fa-hand-holding-usd"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-slate-900">Emergency Funding</h4>
                            <p class="text-sm text-slate-600">10-day bridge financing for urgent needs</p>
                        </div>
                    </div>
                    <div class="feature-card bg-white rounded-2xl p-6 flex items-start space-x-4">
                        <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600 flex-shrink-0">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-slate-900">CRB Clearance</h4>
                            <p class="text-sm text-slate-600">Full credit bureau rehabilitation</p>
                        </div>
                    </div>
                    <div class="feature-card bg-white rounded-2xl p-6 flex items-start space-x-4">
                        <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600 flex-shrink-0">
                            <i class="fas fa-home"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-slate-900">Asset Finance</h4>
                            <p class="text-sm text-slate-600">Property, vehicle & luxury asset lending</p>
                        </div>
                    </div>
                    <div class="feature-card bg-white rounded-2xl p-6 flex items-start space-x-4">
                        <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600 flex-shrink-0">
                            <i class="fas fa-seedling"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-slate-900">Commodity Trade</h4>
                            <p class="text-sm text-slate-600">Agricultural & mineral trade finance</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
<!-- ========== CREDIT SOLUTIONS ========== -->
<section id="solutions" class="py-24 bg-slate-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto">
            <span class="text-blue-600 text-sm font-semibold tracking-wider uppercase">Choose Your Plan</span>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mt-2 mb-4">
                Flexible Financial Solutions
            </h2>
            <p class="text-lg text-slate-600">
                Plans designed for every financial need
            </p>
        </div>

        <div class="mt-12 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 max-w-7xl mx-auto">
            <!-- Emergency Bridge Finance - Most Popular -->
            <div class="plan-card popular bg-white rounded-2xl p-6 shadow-lg relative border-2 border-blue-600">
                <div class="absolute -top-3 left-1/2 transform -translate-x-1/2 px-4 py-1 bg-blue-600 text-white text-xs font-semibold rounded-full whitespace-nowrap">
                    <i class="fas fa-star mr-1"></i> Most Popular
                </div>
                <div class="text-center mt-2">
                    <h3 class="text-lg font-bold text-slate-900">Emergency Bridge Finance</h3>
                    <div class="mt-2">
                        <span class="text-3xl font-extrabold text-slate-900">20% Flat</span>
                    </div>
                    <p class="text-sm text-slate-500 mt-1">10 Day Facility</p>
                </div>
                <ul class="mt-6 space-y-3 text-sm">
                    <li class="flex items-center text-slate-600">
                        <i class="fas fa-check text-blue-600 mr-3"></i>
                        KES 100K – KES 20 Million
                    </li>
                    <li class="flex items-center text-slate-600">
                        <i class="fas fa-check text-blue-600 mr-3"></i>
                        Collateral: Optional on amount
                    </li>
                    <li class="flex items-center text-slate-600">
                        <i class="fas fa-check text-blue-600 mr-3"></i>
                        30-45 Minutes Processing
                    </li>
                    <li class="flex items-center text-slate-600">
                        <i class="fas fa-check text-blue-600 mr-3"></i>
                        24/7 Emergency Support
                    </li>
                </ul>
                <a href="#contact" class="mt-6 block w-full py-2.5 text-center text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-all shadow-lg shadow-blue-600/25">
                    Request Funding
                </a>
            </div>

            <!-- Asset-Backed Lending -->
            <div class="plan-card bg-white rounded-2xl p-6 shadow-sm">
                <div class="text-center">
                    <h3 class="text-lg font-bold text-slate-900">Asset-Backed Lending</h3>
                    <div class="mt-2">
                        <span class="text-3xl font-extrabold text-slate-900">Up to 70% LTV</span>
                    </div>
                    <p class="text-sm text-slate-500 mt-1">Up to 3 Months</p>
                </div>
                <ul class="mt-6 space-y-3 text-sm">
                    <li class="flex items-center text-slate-600">
                        <i class="fas fa-check text-blue-600 mr-3"></i>
                        4x The Asset Value
                    </li>
                    <li class="flex items-center text-slate-600">
                        <i class="fas fa-check text-blue-600 mr-3"></i>
                        Fast Valuation & Disbursement
                    </li>
                    <li class="flex items-center text-slate-600">
                        <i class="fas fa-check text-blue-600 mr-3"></i>
                        Flexible Repayment Terms
                    </li>
                    <li class="flex items-center text-slate-600">
                        <i class="fas fa-check text-blue-600 mr-3"></i>
                        Property, Vehicles & Machinery
                    </li>
                </ul>
                <a href="#contact" class="mt-6 block w-full py-2.5 text-center text-sm font-semibold border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                    Apply Now
                </a>
            </div>

            <!-- CRB Bailout -->
            <div class="plan-card bg-white rounded-2xl p-6 shadow-sm">
                <div class="text-center">
                    <h3 class="text-lg font-bold text-slate-900">CRB Bailout</h3>
                    <div class="mt-2">
                        <span class="text-3xl font-extrabold text-slate-900">24-48 Hours</span>
                    </div>
                    <p class="text-sm text-slate-500 mt-1">Credit Rehabilitation</p>
                </div>
                <ul class="mt-6 space-y-3 text-sm">
                    <li class="flex items-center text-slate-600">
                        <i class="fas fa-check text-blue-600 mr-3"></i>
                        Full CRB Clearance
                    </li>
                    <li class="flex items-center text-slate-600">
                        <i class="fas fa-check text-blue-600 mr-3"></i>
                        100% Confidential
                    </li>
                    <li class="flex items-center text-slate-600">
                        <i class="fas fa-check text-blue-600 mr-3"></i>
                        Bank Clearance Certificate
                    </li>
                    <li class="flex items-center text-slate-600">
                        <i class="fas fa-check text-blue-600 mr-3"></i>
                        All Kenyan Lenders Supported
                    </li>
                </ul>
                <a href="#contact" class="mt-6 block w-full py-2.5 text-center text-sm font-semibold border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                    Restore Credit
                </a>
            </div>

            <!-- Trade Finance -->
            <div class="plan-card bg-white rounded-2xl p-6 shadow-sm">
                <div class="text-center">
                    <h3 class="text-lg font-bold text-slate-900">Trade Finance</h3>
                    <div class="mt-2">
                        <span class="text-3xl font-extrabold text-slate-900">Custom</span>
                    </div>
                    <p class="text-sm text-slate-500 mt-1">Tailor Made Credit For Your Business</p>
                </div>
                <ul class="mt-6 space-y-3 text-sm">
                    <li class="flex items-center text-slate-600">
                        <i class="fas fa-check text-blue-600 mr-3"></i>
                        Letters of Credit
                    </li>
                    <li class="flex items-center text-slate-600">
                        <i class="fas fa-check text-blue-600 mr-3"></i>
                        Purchase Order Financing
                    </li>
                    <li class="flex items-center text-slate-600">
                        <i class="fas fa-check text-blue-600 mr-3"></i>
                        Agricultural Commodity Finance
                    </li>
                    <li class="flex items-center text-slate-600">
                        <i class="fas fa-check text-blue-600 mr-3"></i>
                        Import Export Trade Solutions
                    </li>
                </ul>
                <a href="#contact" class="mt-6 block w-full py-2.5 text-center text-sm font-semibold border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                    Inquire Now
                </a>
            </div>
        </div>

        <!-- Comparison Table -->
        <div class="mt-16 bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 bg-slate-50 border-b border-slate-200">
                <h3 class="text-lg font-bold text-slate-900">Quick Comparison</h3>
                <p class="text-sm text-slate-500">Compare our financing solutions at a glance</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-white">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Loan Term</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Interest</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Max Facility</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Collateral</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Processing</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <tr class="bg-blue-50/30">
                            <td class="px-6 py-3 text-sm font-medium text-slate-900">
                                <span class="inline-flex items-center">
                                    Emergency Bridge Finance
                                    <span class="ml-2 px-2 py-0.5 bg-blue-600 text-white text-[10px] font-semibold rounded-full">Popular</span>
                                </span>
                            </td>
                            <td class="px-6 py-3 text-sm text-slate-600">10 Days</td>
                            <td class="px-6 py-3 text-sm font-semibold text-blue-600">20% Flat</td>
                            <td class="px-6 py-3 text-sm text-slate-600">KES 20M</td>
                            <td class="px-6 py-3 text-sm text-slate-600">Optional</td>
                            <td class="px-6 py-3 text-sm text-slate-600">30-45 Min</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-3 text-sm font-medium text-slate-900">Asset-Backed Lending</td>
                            <td class="px-6 py-3 text-sm text-slate-600">Up to 3 Months</td>
                            <td class="px-6 py-3 text-sm text-slate-600">70% LTV</td>
                            <td class="px-6 py-3 text-sm text-slate-600">4x Asset Value</td>
                            <td class="px-6 py-3 text-sm text-slate-600">Required</td>
                            <td class="px-6 py-3 text-sm text-slate-600">Fast</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-3 text-sm font-medium text-slate-900">CRB Bailout</td>
                            <td class="px-6 py-3 text-sm text-slate-600">24-48 Hours</td>
                            <td class="px-6 py-3 text-sm text-slate-600">Service Fee</td>
                            <td class="px-6 py-3 text-sm text-slate-600">Custom</td>
                            <td class="px-6 py-3 text-sm text-slate-600">N/A</td>
                            <td class="px-6 py-3 text-sm text-slate-600">24-48 Hours</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-3 text-sm font-medium text-slate-900">Trade Finance</td>
                            <td class="px-6 py-3 text-sm text-slate-600">Flexible</td>
                            <td class="px-6 py-3 text-sm text-slate-600">Negotiable</td>
                            <td class="px-6 py-3 text-sm text-slate-600">Custom</td>
                            <td class="px-6 py-3 text-sm text-slate-600">Trade Docs</td>
                            <td class="px-6 py-3 text-sm text-slate-600">3-5 Days</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200">
                <p class="text-xs text-slate-500">
                    <i class="fas fa-info-circle mr-1"></i>
                    Rates shown are indicative and subject to due diligence, facility size, collateral, and risk assessment. 
                    Final pricing is confirmed upon approval.
                </p>
            </div>
        </div>
    </div>
</section>

        <!-- ========== TESTIMONIALS ========== -->
        <section id="testimonials" class="py-24 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-2xl mx-auto">
                    <span class="text-blue-600 text-sm font-semibold tracking-wider uppercase">Testimonials</span>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mt-2 mb-4">
                        What Our Clients Say
                    </h2>
                    <p class="text-lg text-slate-600">
                        Trusted by businesses and individuals across East Africa
                    </p>
                </div>

                <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="testimonial-card bg-slate-50 rounded-2xl p-6 border border-slate-200">
                        <div class="flex items-center gap-1 text-yellow-400 mb-3">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p class="text-slate-600 text-sm">
                            "AmazonBlue Capital delivered emergency funding within 12 hours when we needed it most."
                        </p>
                        <div class="mt-4 flex items-center">
                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold">
                                JM
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-semibold text-slate-900">James Mwangi</p>
                                <p class="text-xs text-slate-500">CEO, Nairobi Logistics</p>
                            </div>
                        </div>
                    </div>

                    <div class="testimonial-card bg-slate-50 rounded-2xl p-6 border border-slate-200">
                        <div class="flex items-center gap-1 text-yellow-400 mb-3">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p class="text-slate-600 text-sm">
                            "They cleared my CRB listing in just 48 hours. Completely transformed my financial standing."
                        </p>
                        <div class="mt-4 flex items-center">
                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold">
                                SK
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-semibold text-slate-900">Sarah Kariuki</p>
                                <p class="text-xs text-slate-500">Entrepreneur</p>
                            </div>
                        </div>
                    </div>

                    <div class="testimonial-card bg-slate-50 rounded-2xl p-6 border border-slate-200">
                        <div class="flex items-center gap-1 text-yellow-400 mb-3">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p class="text-slate-600 text-sm">
                            "The asset financing helped us expand our fleet without tying up our working capital."
                        </p>
                        <div class="mt-4 flex items-center">
                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold">
                                MO
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-semibold text-slate-900">Michael Ochieng</p>
                                <p class="text-xs text-slate-500">Transport Manager</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========== FAQ ========== -->
        <section id="faq" class="py-24 bg-slate-50">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-2xl mx-auto">
                    <span class="text-blue-600 text-sm font-semibold tracking-wider uppercase">FAQ</span>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mt-2 mb-4">
                        Frequently Asked Questions
                    </h2>
                    <p class="text-lg text-slate-600">
                        Find answers to common questions about our services
                    </p>
                </div>

                <div class="mt-12 space-y-4">
                    <details class="faq-item bg-white rounded-xl p-6 border border-slate-200">
                        <summary class="flex items-center justify-between cursor-pointer">
                            <span class="font-semibold text-slate-900">How quickly can I get emergency funding?</span>
                            <i class="fas fa-chevron-down text-blue-600 transition-transform"></i>
                        </summary>
                        <p class="mt-4 text-sm text-slate-600">
                            Most emergency funding requests are processed within 24 hours. We prioritize urgent cases and can expedite based on your specific situation.
                        </p>
                    </details>

                    <details class="faq-item bg-white rounded-xl p-6 border border-slate-200">
                        <summary class="flex items-center justify-between cursor-pointer">
                            <span class="font-semibold text-slate-900">What is the CRB clearance process?</span>
                            <i class="fas fa-chevron-down text-blue-600 transition-transform"></i>
                        </summary>
                        <p class="mt-4 text-sm text-slate-600">
                            We work directly with Kenyan credit bureaus to resolve negative listings. The process typically takes 24-48 hours and is completely confidential.
                        </p>
                    </details>

                    <details class="faq-item bg-white rounded-xl p-6 border border-slate-200">
                        <summary class="flex items-center justify-between cursor-pointer">
                            <span class="font-semibold text-slate-900">What types of assets can be used for financing?</span>
                            <i class="fas fa-chevron-down text-blue-600 transition-transform"></i>
                        </summary>
                        <p class="mt-4 text-sm text-slate-600">
                            We accept real estate, vehicles, luxury assets, and other valuable collateral. Each case is evaluated individually based on asset value and condition.
                        </p>
                    </details>

                    <details class="faq-item bg-white rounded-xl p-6 border border-slate-200">
                        <summary class="flex items-center justify-between cursor-pointer">
                            <span class="font-semibold text-slate-900">Is my information kept confidential?</span>
                            <i class="fas fa-chevron-down text-blue-600 transition-transform"></i>
                        </summary>
                        <p class="mt-4 text-sm text-slate-600">
                            Absolutely. We maintain strict confidentiality for all client information. Our services are designed with discretion and privacy as core principles.
                        </p>
                    </details>

                    <details class="faq-item bg-white rounded-xl p-6 border border-slate-200">
                        <summary class="flex items-center justify-between cursor-pointer">
                            <span class="font-semibold text-slate-900">How do I get started?</span>
                            <i class="fas fa-chevron-down text-blue-600 transition-transform"></i>
                        </summary>
                        <p class="mt-4 text-sm text-slate-600">
                            Simply reach out through our contact form or call our emergency line. Our team will guide you through the process and find the right solution for your needs.
                        </p>
                    </details>
                </div>
            </div>
        </section>

        <!-- ========== CTA SECTION ========== -->
        <section class="py-20 gradient-primary relative overflow-hidden">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>
            
            <div class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-3xl sm:text-4xl font-extrabold text-white">
                    Ready to Transform Your Financial Future?
                </h2>
                <p class="text-lg text-white/80 mt-4 max-w-2xl mx-auto">
                    Join 500+ clients who trust AmazonBlue Capital for their financial needs.
                </p>
                <div class="mt-8 flex flex-wrap justify-center gap-4">
                    <a href="#contact" class="px-8 py-3 bg-white text-blue-600 font-semibold rounded-lg hover:bg-blue-50 transition-all shadow-lg shadow-white/25">
                        Get Started <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                    <a href="#features" class="px-8 py-3 border border-white/20 text-white font-semibold rounded-lg hover:bg-white/10 transition-all">
                        Learn More
                    </a>
                </div>
                <p class="text-sm text-white/60 mt-6">
                    <i class="fas fa-shield-alt mr-2"></i> 100% confidential consultation
                </p>
            </div>
        </section>

        <!-- ========== FOOTER ========== -->
        <footer class="bg-slate-900 text-slate-400">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                    <div>
                        <div class="flex items-center space-x-3 mb-4">
                            <div class="w-8 h-8 rounded-xl bg-blue-600 flex items-center justify-center">
                                <span class="text-white font-bold text-sm">A</span>
                            </div>
                            <span class="text-lg font-bold text-white">AmazonBlue<span class="text-blue-400">Capital</span></span>
                        </div>
                        <p class="text-sm">Premium financial solutions with discretion and speed since 2025.</p>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-white mb-4">Services</h4>
                        <ul class="space-y-2 text-sm">
                            <li><a href="#" class="hover:text-blue-400 transition-colors">Emergency Solutions</a></li>
                            <li><a href="#" class="hover:text-blue-400 transition-colors">CRB Solutions</a></li>
                            <li><a href="#" class="hover:text-blue-400 transition-colors">Asset Finance</a></li>
                            <li><a href="#" class="hover:text-blue-400 transition-colors">Commodities</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-white mb-4">Company</h4>
                        <ul class="space-y-2 text-sm">
                            <li><a href="#" class="hover:text-blue-400 transition-colors">About</a></li>
                            <li><a href="#" class="hover:text-blue-400 transition-colors">Contact</a></li>
                            <li><a href="#" class="hover:text-blue-400 transition-colors">Careers</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-white mb-4">Connect</h4>
                        <div class="flex space-x-4">
                            <a href="#" class="w-9 h-9 rounded-lg bg-slate-800 flex items-center justify-center hover:bg-blue-600 transition-colors">
                                <i class="fab fa-linkedin-in text-sm"></i>
                            </a>
                            <a href="#" class="w-9 h-9 rounded-lg bg-slate-800 flex items-center justify-center hover:bg-blue-400 transition-colors">
                                <i class="fab fa-twitter text-sm"></i>
                            </a>
                            <a href="#" class="w-9 h-9 rounded-lg bg-slate-800 flex items-center justify-center hover:bg-blue-600 transition-colors">
                                <i class="fab fa-facebook-f text-sm"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="mt-8 pt-8 border-t border-slate-800">
                    <div class="flex flex-col md:flex-row justify-between items-center text-sm">
                        <p>© 2026 AmazonBlue Capital. All rights reserved.</p>
                        <div class="flex flex-wrap gap-4 mt-2 md:mt-0">
                            <a href="#" class="hover:text-blue-400 transition-colors">Privacy Policy</a>
                            <a href="#" class="hover:text-blue-400 transition-colors">Terms of Service</a>
                            <a href="#" class="hover:text-blue-400 transition-colors">Disclaimer</a>
                        </div>
                    </div>
                </div>
            </div>
        </footer>

        <script>
            // Smooth scroll for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    const href = this.getAttribute('href');
                    if (href === '#') return;
                    e.preventDefault();
                    const target = document.querySelector(href);
                    if (target) {
                        target.scrollIntoView({ behavior: 'smooth' });
                    }
                });
            });
        </script>
    </body>
    </html>