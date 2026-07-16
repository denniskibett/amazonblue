<!DOCTYPE html>
<html lang="en" x-data="{
    mobileMenuOpen: false,
    scrolled: false,
    activeTab: 'features',
    selectedPlan: 'builder'
}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AmazonBlue Capital | Emergency Fund for Anything</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #ffffff;
            color: #0f172a;
        }
        
        .gradient-hero {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 40%, #1a56db 80%, #3b82f6 100%);
        }
        
        .gradient-accent {
            background: linear-gradient(135deg, #1a56db 0%, #3b82f6 100%);
        }
        
        .gradient-text {
            background: linear-gradient(135deg, #1a56db, #60a5fa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .gradient-text-light {
            background: linear-gradient(135deg, #93c5fd, #dbeafe);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
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
            border-color: #1a56db;
            box-shadow: 0 20px 40px -12px rgba(26, 86, 219, 0.25);
        }
        
        .feature-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            transition: all 0.3s ease;
        }
        
        .feature-card {
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
        }
        
        .feature-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 16px 32px -8px rgba(0, 0, 0, 0.08);
            border-color: #93c5fd;
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
            box-shadow: 0 0 0 3px rgba(26, 86, 219, 0.15);
            border-color: #1a56db;
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
        
        .bg-money {
            background: #f0fdf4;
        }
        
        .border-money {
            border-color: #bbf7d0;
        }
        
        .shadow-money {
            box-shadow: 0 4px 20px rgba(26, 86, 219, 0.15);
        }
        
        .bounce-in {
            animation: bounceIn 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }
        
        @keyframes bounceIn {
            0% { opacity: 0; transform: scale(0.8); }
            100% { opacity: 1; transform: scale(1); }
        }
        
        .float-animation {
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        .glow-effect {
            position: relative;
        }
        
        .glow-effect::after {
            content: '';
            position: absolute;
            inset: -2px;
            border-radius: inherit;
            background: linear-gradient(135deg, #1a56db, #60a5fa, #93c5fd);
            opacity: 0.3;
            filter: blur(8px);
            z-index: -1;
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
                        AmazonBlue<span class="text-blue-600">Capital</span>
                    </span>
                </a>

                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="#features" class="text-sm font-medium transition-colors hover:text-blue-600"
                       :class="scrolled ? 'text-slate-700' : 'text-white'">Features</a>
                    <a href="#plans" class="text-sm font-medium transition-colors hover:text-blue-600"
                       :class="scrolled ? 'text-slate-700' : 'text-white'">Pricing</a>
                    <a href="#testimonials" class="text-sm font-medium transition-colors hover:text-blue-600"
                       :class="scrolled ? 'text-slate-700' : 'text-white'">Testimonials</a>
                    <a href="#faq" class="text-sm font-medium transition-colors hover:text-blue-600"
                       :class="scrolled ? 'text-slate-700' : 'text-white'">FAQ</a>
                    
                    <div class="flex items-center space-x-3 pl-4 border-l border-slate-200">
                        <a href="{{ route('login') }}"
                           class="px-5 py-2 text-sm font-medium rounded-lg transition-all duration-200 bg-blue-600 text-white hover:bg-blue-700 shadow-lg shadow-blue-600/25">
                            Get Funded Now
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
                <a href="#plans" @click="mobileMenuOpen = false" class="block text-2xl font-semibold text-slate-900 hover:text-blue-600 transition-colors">Pricing</a>
                <a href="#testimonials" @click="mobileMenuOpen = false" class="block text-2xl font-semibold text-slate-900 hover:text-blue-600 transition-colors">Testimonials</a>
                <a href="#faq" @click="mobileMenuOpen = false" class="block text-2xl font-semibold text-slate-900 hover:text-blue-600 transition-colors">FAQ</a>
            </div>
            <div class="p-6 border-t border-slate-200 space-y-3">
                <a href="{{ route('login') }}" class="block w-full py-3 text-center text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
                    Get Funded Now
                </a>
                <a href="{{ route('login') }}" class="block w-full py-3 text-center text-sm font-semibold text-slate-700 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                    Sign In
                </a>
            </div>
        </div>
    </div>

    <!-- ========== HERO SECTION ========== -->
    <section class="relative overflow-hidden min-h-screen flex items-center gradient-hero">
        <!-- Decorative elements -->
        <div class="absolute top-20 right-10 w-72 h-72 bg-blue-500/20 rounded-full blur-3xl float-animation"></div>
        <div class="absolute bottom-20 left-10 w-96 h-96 bg-blue-400/20 rounded-full blur-3xl float-animation" style="animation-delay: 3s;"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] bg-blue-500/5 rounded-full blur-3xl"></div>
        
        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <div class="inline-flex items-center px-4 py-2 bg-white/10 backdrop-blur-sm rounded-full text-sm text-white/90 mb-6 border border-white/10">
                        <span class="w-2 h-2 rounded-full bg-green-400 pulse-dot mr-2"></span>
                        <span class="font-medium">24/7 Emergency Fund</span>
                        <span class="mx-2 text-white/30">|</span>
                        <span class="font-medium">Any Amount. Any Reason.</span>
                    </div>
                    <h1 class="text-4xl sm:text-5xl lg:text-7xl font-extrabold text-white leading-tight">
                        Emergency Fund
                        <span class="block text-transparent bg-clip-text bg-gradient-to-r from-blue-200 to-white">For Literally Anything</span>
                    </h1>
                    <p class="text-lg text-white/80 mt-6 max-w-lg">
                        Business cash gap? Medical emergency? School fees? Car repair? Rent? We've got you covered. Fast cash when life happens.
                    </p>
                    <div class="flex flex-wrap gap-4 mt-8">
                        <a href="#plans" class="px-8 py-3.5 bg-white text-blue-600 font-semibold rounded-xl hover:bg-blue-50 transition-all shadow-lg shadow-white/25 flex items-center gap-2">
                            <i class="fas fa-bolt"></i> Get Cash Now
                        </a>
                        <a href="#features" class="px-8 py-3.5 border border-white/20 text-white font-semibold rounded-xl hover:bg-white/10 transition-all flex items-center gap-2">
                            <i class="fas fa-play-circle"></i> See How It Works
                        </a>
                    </div>
                    
                    <!-- Trust badges -->
                    <div class="flex flex-wrap items-center gap-6 mt-10 pt-8 border-t border-white/10">
                        <div class="flex items-center space-x-2 text-white/70">
                            <i class="fas fa-clock text-blue-300 text-lg"></i>
                            <span class="text-sm font-medium">24h Payout</span>
                        </div>
                        <div class="flex items-center space-x-2 text-white/70">
                            <i class="fas fa-shield-alt text-blue-300 text-lg"></i>
                            <span class="text-sm font-medium">100% Confidential</span>
                        </div>
                        <div class="flex items-center space-x-2 text-white/70">
                            <i class="fas fa-globe-africa text-blue-300 text-lg"></i>
                            <span class="text-sm font-medium">East Africa</span>
                        </div>
                    </div>
                </div>
                
                <!-- Hero Right - Stats -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-white/5 backdrop-blur-sm rounded-2xl p-6 border border-white/10 hover:bg-white/10 transition-all">
                        <div class="text-4xl font-extrabold text-white">KES 2B+</div>
                        <p class="text-sm text-white/70 mt-1">Capital Deployed</p>
                        <div class="mt-3 flex items-center text-green-400 text-sm">
                            <i class="fas fa-arrow-up mr-1"></i> 156% growth
                        </div>
                    </div>
                    <div class="bg-white/5 backdrop-blur-sm rounded-2xl p-6 border border-white/10 hover:bg-white/10 transition-all">
                        <div class="text-4xl font-extrabold text-white">24h</div>
                        <p class="text-sm text-white/70 mt-1">Average Payout Time</p>
                        <div class="mt-3 flex items-center text-green-400 text-sm">
                            <i class="fas fa-bolt mr-1"></i> Fastest in market
                        </div>
                    </div>
                    <div class="bg-white/5 backdrop-blur-sm rounded-2xl p-6 border border-white/10 hover:bg-white/10 transition-all">
                        <div class="text-4xl font-extrabold text-white">98%</div>
                        <p class="text-sm text-white/70 mt-1">Client Satisfaction</p>
                        <div class="mt-3 flex items-center text-green-400 text-sm">
                            <i class="fas fa-star mr-1"></i> 4.9/5 rating
                        </div>
                    </div>
                    <div class="bg-white/5 backdrop-blur-sm rounded-2xl p-6 border border-white/10 hover:bg-white/10 transition-all">
                        <div class="text-4xl font-extrabold text-white">500+</div>
                        <p class="text-sm text-white/70 mt-1">Clients Served</p>
                        <div class="mt-3 flex items-center text-blue-300 text-sm">
                            <i class="fas fa-users mr-1"></i> Growing fast
                        </div>
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
                <span class="text-blue-600 text-sm font-semibold tracking-wider uppercase flex items-center justify-center gap-2">
                    <i class="fas fa-bolt"></i> Instant Emergency Cash
                </span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mt-2 mb-4">
                    Money When You Need It Most
                </h2>
                <p class="text-lg text-slate-600">
                    No waiting. No endless paperwork. Just fast cash for any emergency.
                </p>
            </div>

            <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="feature-card bg-white rounded-2xl p-8 text-center group">
                    <div class="feature-icon bg-blue-50 text-blue-600 mx-auto group-hover:bg-blue-600 group-hover:text-white transition-all">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mt-4">Lightning Speed</h3>
                    <p class="text-sm text-slate-600 mt-2">Get cash in your account within 24 hours. No delays, no excuses.</p>
                    <div class="mt-4 text-sm text-blue-600 font-semibold">From application to payout</div>
                </div>
                
                <div class="feature-card bg-white rounded-2xl p-8 text-center group">
                    <div class="feature-icon bg-blue-50 text-blue-600 mx-auto group-hover:bg-blue-600 group-hover:text-white transition-all">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mt-4">Any Reason, Any Amount</h3>
                    <p class="text-sm text-slate-600 mt-2">Business, medical, education, rent, car repair — we fund it all.</p>
                    <div class="mt-4 text-sm text-blue-600 font-semibold">No questions asked</div>
                </div>
                
                <div class="feature-card bg-white rounded-2xl p-8 text-center group">
                    <div class="feature-icon bg-blue-50 text-blue-600 mx-auto group-hover:bg-blue-600 group-hover:text-white transition-all">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mt-4">Apply Anywhere</h3>
                    <p class="text-sm text-slate-600 mt-2">From your phone, laptop, or tablet. No branch visits required.</p>
                    <div class="mt-4 text-sm text-blue-600 font-semibold">100% digital process</div>
                </div>
            </div>
        </div>
    </section>

    <!-- ========== SERVICES SECTION ========== -->
    <section class="py-20 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-12">
                <span class="text-blue-600 text-sm font-semibold tracking-wider uppercase flex items-center justify-center gap-2">
                    <i class="fas fa-list"></i> What We Fund
                </span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mt-2 mb-4">
                    Emergency Cash for Everything
                </h2>
                <p class="text-lg text-slate-600">
                    No matter what life throws at you, we've got your back.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl p-6 border border-slate-200 hover:shadow-lg transition-all flex items-start space-x-4">
                    <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-900">Business Gap</h4>
                        <p class="text-sm text-slate-500">Cash flow emergency</p>
                    </div>
                </div>
                <div class="bg-white rounded-xl p-6 border border-slate-200 hover:shadow-lg transition-all flex items-start space-x-4">
                    <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-heartbeat"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-900">Medical</h4>
                        <p class="text-sm text-slate-500">Hospital & treatment</p>
                    </div>
                </div>
                <div class="bg-white rounded-xl p-6 border border-slate-200 hover:shadow-lg transition-all flex items-start space-x-4">
                    <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-900">Education</h4>
                        <p class="text-sm text-slate-500">School & university fees</p>
                    </div>
                </div>
                <div class="bg-white rounded-xl p-6 border border-slate-200 hover:shadow-lg transition-all flex items-start space-x-4">
                    <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-home"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-900">Rent & Bills</h4>
                        <p class="text-sm text-slate-500">Housing & utilities</p>
                    </div>
                </div>
                <div class="bg-white rounded-xl p-6 border border-slate-200 hover:shadow-lg transition-all flex items-start space-x-4">
                    <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-car"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-900">Vehicle Repair</h4>
                        <p class="text-sm text-slate-500">Car & transport fixes</p>
                    </div>
                </div>
                <div class="bg-white rounded-xl p-6 border border-slate-200 hover:shadow-lg transition-all flex items-start space-x-4">
                    <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-umbrella"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-900">Emergency Travel</h4>
                        <p class="text-sm text-slate-500">Urgent trips & flights</p>
                    </div>
                </div>
                <div class="bg-white rounded-xl p-6 border border-slate-200 hover:shadow-lg transition-all flex items-start space-x-4">
                    <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-tools"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-900">Home Repair</h4>
                        <p class="text-sm text-slate-500">Emergency maintenance</p>
                    </div>
                </div>
                <div class="bg-white rounded-xl p-6 border border-slate-200 hover:shadow-lg transition-all flex items-start space-x-4">
                    <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-900">CRB Clearance</h4>
                        <p class="text-sm text-slate-500">Credit rehabilitation</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ========== PLANS ========== -->
    <section id="plans" class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto">
                <span class="text-blue-600 text-sm font-semibold tracking-wider uppercase flex items-center justify-center gap-2">
                    <i class="fas fa-tag"></i> Simple Pricing
                </span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mt-2 mb-4">
                    Get Cash When You Need It
                </h2>
                <p class="text-lg text-slate-600">
                    No hidden fees. No surprises. Just fast money.
                </p>
            </div>

            <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-6 max-w-5xl mx-auto">
                <!-- Starter -->
                <div class="plan-card bg-white rounded-2xl p-6 shadow-sm border border-slate-200">
                    <div class="text-center">
                        <h3 class="text-lg font-bold text-slate-900">Emergency Lite</h3>
                        <div class="mt-2">
                            <span class="text-3xl font-extrabold text-slate-900">Free</span>
                        </div>
                        <p class="text-sm text-slate-500 mt-1">Essential emergency access</p>
                    </div>
                    <ul class="mt-6 space-y-3 text-sm">
                        <li class="flex items-center text-slate-600">
                            <i class="fas fa-check text-blue-600 mr-3"></i>
                            Up to KES 50,000
                        </li>
                        <li class="flex items-center text-slate-600">
                            <i class="fas fa-check text-blue-600 mr-3"></i>
                            72-hour payout
                        </li>
                        <li class="flex items-center text-slate-600">
                            <i class="fas fa-check text-blue-600 mr-3"></i>
                            Basic support
                        </li>
                    </ul>
                    <a href="#contact" class="mt-6 block w-full py-2.5 text-center text-sm font-semibold border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                        Get Started
                    </a>
                </div>

                <!-- Professional -->
                <div class="plan-card popular bg-white rounded-2xl p-6 shadow-lg relative border-blue-600">
                    <div class="absolute -top-3 left-1/2 transform -translate-x-1/2 px-4 py-1 bg-blue-600 text-white text-xs font-semibold rounded-full flex items-center gap-1">
                        <i class="fas fa-star"></i> Most Popular
                    </div>
                    <div class="text-center mt-2">
                        <h3 class="text-lg font-bold text-slate-900">Emergency Pro</h3>
                        <div class="mt-2">
                            <span class="text-3xl font-extrabold text-slate-900">KES 99</span>
                            <span class="text-sm text-slate-500">/month</span>
                        </div>
                        <p class="text-sm text-slate-500 mt-1">Full emergency coverage</p>
                    </div>
                    <ul class="mt-6 space-y-3 text-sm">
                        <li class="flex items-center text-slate-600">
                            <i class="fas fa-check text-blue-600 mr-3"></i>
                            Up to KES 500,000
                        </li>
                        <li class="flex items-center text-slate-600">
                            <i class="fas fa-check text-blue-600 mr-3"></i>
                            24-hour payout
                        </li>
                        <li class="flex items-center text-slate-600">
                            <i class="fas fa-check text-blue-600 mr-3"></i>
                            Priority support
                        </li>
                        <li class="flex items-center text-slate-600">
                            <i class="fas fa-check text-blue-600 mr-3"></i>
                            CRB clearance included
                        </li>
                    </ul>
                    <a href="#contact" class="mt-6 block w-full py-2.5 text-center text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-all shadow-lg shadow-blue-600/25 flex items-center justify-center gap-2">
                        <i class="fas fa-bolt"></i> Get Funded Now
                    </a>
                </div>

                <!-- Enterprise -->
                <div class="plan-card bg-white rounded-2xl p-6 shadow-sm border border-slate-200">
                    <div class="text-center">
                        <h3 class="text-lg font-bold text-slate-900">Emergency Max</h3>
                        <div class="mt-2">
                            <span class="text-3xl font-extrabold text-slate-900">KES 999</span>
                            <span class="text-sm text-slate-500">/month</span>
                        </div>
                        <p class="text-sm text-slate-500 mt-1">Unlimited emergency power</p>
                    </div>
                    <ul class="mt-6 space-y-3 text-sm">
                        <li class="flex items-center text-slate-600">
                            <i class="fas fa-check text-blue-600 mr-3"></i>
                            Unlimited amount
                        </li>
                        <li class="flex items-center text-slate-600">
                            <i class="fas fa-check text-blue-600 mr-3"></i>
                            12-hour payout
                        </li>
                        <li class="flex items-center text-slate-600">
                            <i class="fas fa-check text-blue-600 mr-3"></i>
                            Concierge support
                        </li>
                        <li class="flex items-center text-slate-600">
                            <i class="fas fa-check text-blue-600 mr-3"></i>
                            All services included
                        </li>
                    </ul>
                    <a href="#contact" class="mt-6 block w-full py-2.5 text-center text-sm font-semibold border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                        Contact Sales
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- ========== TESTIMONIALS ========== -->
    <section id="testimonials" class="py-24 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto">
                <span class="text-blue-600 text-sm font-semibold tracking-wider uppercase flex items-center justify-center gap-2">
                    <i class="fas fa-comment-dots"></i> Real Stories
                </span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mt-2 mb-4">
                    What Our Clients Say
                </h2>
                <p class="text-lg text-slate-600">
                    Real people getting real cash when they needed it most.
                </p>
            </div>

            <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="testimonial-card bg-white rounded-2xl p-6 border border-slate-200 hover:shadow-lg transition-all">
                    <div class="flex items-center gap-1 text-yellow-400 mb-3">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="text-slate-600 text-sm">
                        "I got emergency cash in 12 hours for my business. Saved me from losing a major contract."
                    </p>
                    <div class="mt-4 flex items-center">
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold">
                            JM
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-semibold text-slate-900">James Mwangi</p>
                            <p class="text-xs text-slate-500">Business Owner</p>
                            <div class="flex items-center text-xs text-blue-600 mt-0.5">
                                <i class="fas fa-clock mr-1"></i> Funded in 12h
                            </div>
                        </div>
                    </div>
                </div>

                <div class="testimonial-card bg-white rounded-2xl p-6 border border-slate-200 hover:shadow-lg transition-all">
                    <div class="flex items-center gap-1 text-yellow-400 mb-3">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="text-slate-600 text-sm">
                        "They cleared my CRB listing in 48 hours. Now I can finally get a loan from my bank."
                    </p>
                    <div class="mt-4 flex items-center">
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold">
                            SK
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-semibold text-slate-900">Sarah Kariuki</p>
                            <p class="text-xs text-slate-500">Entrepreneur</p>
                            <div class="flex items-center text-xs text-green-600 mt-0.5">
                                <i class="fas fa-check-circle mr-1"></i> CRB Cleared
                            </div>
                        </div>
                    </div>
                </div>

                <div class="testimonial-card bg-white rounded-2xl p-6 border border-slate-200 hover:shadow-lg transition-all">
                    <div class="flex items-center gap-1 text-yellow-400 mb-3">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="text-slate-600 text-sm">
                        "Used their emergency fund for my daughter's school fees. She didn't miss a single day."
                    </p>
                    <div class="mt-4 flex items-center">
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold">
                            MO
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-semibold text-slate-900">Michael Ochieng</p>
                            <p class="text-xs text-slate-500">Parent</p>
                            <div class="flex items-center text-xs text-blue-600 mt-0.5">
                                <i class="fas fa-graduation-cap mr-1"></i> School Fees Paid
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ========== FAQ ========== -->
    <section id="faq" class="py-24 bg-white">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto">
                <span class="text-blue-600 text-sm font-semibold tracking-wider uppercase flex items-center justify-center gap-2">
                    <i class="fas fa-question-circle"></i> Quick Answers
                </span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mt-2 mb-4">
                    Got Questions? We've Got Answers.
                </h2>
                <p class="text-lg text-slate-600">
                    Everything you need to know about getting emergency cash.
                </p>
            </div>

            <div class="mt-12 space-y-4">
                <details class="faq-item bg-slate-50 rounded-xl p-6 border border-slate-200">
                    <summary class="flex items-center justify-between cursor-pointer">
                        <span class="font-semibold text-slate-900">How fast can I get the money?</span>
                        <i class="fas fa-chevron-down text-blue-600 transition-transform"></i>
                    </summary>
                    <p class="mt-4 text-sm text-slate-600">
                        With our Emergency Pro plan, you get cash within 24 hours. Our Emergency Max plan delivers in just 12 hours.
                    </p>
                </details>

                <details class="faq-item bg-slate-50 rounded-xl p-6 border border-slate-200">
                    <summary class="flex items-center justify-between cursor-pointer">
                        <span class="font-semibold text-slate-900">What can I use the emergency fund for?</span>
                        <i class="fas fa-chevron-down text-blue-600 transition-transform"></i>
                    </summary>
                    <p class="mt-4 text-sm text-slate-600">
                        Literally anything. Business cash gaps, medical emergencies, school fees, rent, car repairs, home maintenance, emergency travel — you name it.
                    </p>
                </details>

                <details class="faq-item bg-slate-50 rounded-xl p-6 border border-slate-200">
                    <summary class="flex items-center justify-between cursor-pointer">
                        <span class="font-semibold text-slate-900">Do I need collateral?</span>
                        <i class="fas fa-chevron-down text-blue-600 transition-transform"></i>
                    </summary>
                    <p class="mt-4 text-sm text-slate-600">
                        No collateral needed for our emergency fund. We believe in getting you cash fast, without the hassle of paperwork.
                    </p>
                </details>

                <details class="faq-item bg-slate-50 rounded-xl p-6 border border-slate-200">
                    <summary class="flex items-center justify-between cursor-pointer">
                        <span class="font-semibold text-slate-900">Is my information kept confidential?</span>
                        <i class="fas fa-chevron-down text-blue-600 transition-transform"></i>
                    </summary>
                    <p class="mt-4 text-sm text-slate-600">
                        Absolutely. We use bank-grade encryption and never share your information with third parties. Your financial situation stays between us.
                    </p>
                </details>

                <details class="faq-item bg-slate-50 rounded-xl p-6 border border-slate-200">
                    <summary class="flex items-center justify-between cursor-pointer">
                        <span class="font-semibold text-slate-900">What if I already have a CRB listing?</span>
                        <i class="fas fa-chevron-down text-blue-600 transition-transform"></i>
                    </summary>
                    <p class="mt-4 text-sm text-slate-600">
                        No problem! We specialize in CRB clearance and rehabilitation. Our team will work directly with credit bureaus to get you cleared.
                    </p>
                </details>
            </div>
        </div>
    </section>

    <!-- ========== CTA SECTION ========== -->
    <section class="py-20 gradient-hero relative overflow-hidden">
        <div class="absolute top-0 right-0 w-96 h-96 bg-blue-500/20 rounded-full blur-3xl float-animation"></div>
        <div class="absolute bottom-0 left-0 w-96 h-96 bg-blue-400/20 rounded-full blur-3xl float-animation" style="animation-delay: 3s;"></div>
        
        <div class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="inline-flex items-center px-4 py-2 bg-white/10 backdrop-blur-sm rounded-full text-sm text-white/90 mb-6 border border-white/10">
                <i class="fas fa-bolt text-yellow-400 mr-2"></i>
                <span class="font-medium">Emergency Cash Available Now</span>
            </div>
            <h2 class="text-3xl sm:text-5xl font-extrabold text-white">
                Don't Wait. Get Funded Today.
            </h2>
            <p class="text-lg text-white/80 mt-4 max-w-2xl mx-auto">
                Life happens. We're here to help. Apply now and get cash in your account within 24 hours.
            </p>
            <div class="mt-8 flex flex-wrap justify-center gap-4">
                <a href="#plans" class="px-8 py-3.5 bg-white text-blue-600 font-semibold rounded-xl hover:bg-blue-50 transition-all shadow-lg shadow-white/25 flex items-center gap-2">
                    <i class="fas fa-bolt"></i> Get Cash Now
                </a>
                <a href="#features" class="px-8 py-3.5 border border-white/20 text-white font-semibold rounded-xl hover:bg-white/10 transition-all flex items-center gap-2">
                    <i class="fas fa-phone-alt"></i> Call Emergency Line
                </a>
            </div>
            <div class="mt-6 flex justify-center items-center gap-6 text-sm text-white/60">
                <span class="flex items-center gap-1"><i class="fas fa-shield-alt text-blue-300"></i> 100% Confidential</span>
                <span class="flex items-center gap-1"><i class="fas fa-clock text-blue-300"></i> 24/7 Support</span>
                <span class="flex items-center gap-1"><i class="fas fa-globe-africa text-blue-300"></i> East Africa</span>
            </div>
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
                    <p class="text-sm">Emergency fund for literally anything. Fast cash when life happens.</p>
                    <div class="mt-4 flex space-x-4">
                        <a href="#" class="text-slate-500 hover:text-blue-400 transition-colors">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <a href="#" class="text-slate-500 hover:text-blue-400 transition-colors">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="text-slate-500 hover:text-blue-400 transition-colors">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                    </div>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-white mb-4">Emergency Services</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="hover:text-blue-400 transition-colors flex items-center gap-1"><i class="fas fa-bolt text-xs"></i> 24h Cash</a></li>
                        <li><a href="#" class="hover:text-blue-400 transition-colors flex items-center gap-1"><i class="fas fa-check-circle text-xs"></i> CRB Clearance</a></li>
                        <li><a href="#" class="hover:text-blue-400 transition-colors flex items-center gap-1"><i class="fas fa-landmark text-xs"></i> Asset Finance</a></li>
                        <li><a href="#" class="hover:text-blue-400 transition-colors flex items-center gap-1"><i class="fas fa-globe-africa text-xs"></i> Commodities</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-white mb-4">Quick Links</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#features" class="hover:text-blue-400 transition-colors">Features</a></li>
                        <li><a href="#plans" class="hover:text-blue-400 transition-colors">Pricing</a></li>
                        <li><a href="#testimonials" class="hover:text-blue-400 transition-colors">Testimonials</a></li>
                        <li><a href="#faq" class="hover:text-blue-400 transition-colors">FAQ</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-white mb-4">Contact</h4>
                    <ul class="space-y-2 text-sm">
                        <li class="flex items-center gap-2"><i class="fas fa-phone-alt text-blue-400 w-4"></i> +254 701 607 959</li>
                        <li class="flex items-center gap-2"><i class="fas fa-envelope text-blue-400 w-4"></i> emergency@amazonbluecapital.com</li>
                        <li class="flex items-center gap-2"><i class="fas fa-map-marker-alt text-blue-400 w-4"></i> Nairobi, Kenya</li>
                    </ul>
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
                <p class="text-xs text-center md:text-left mt-4 text-slate-500">
                    Emergency cash subject to approval. Terms and conditions apply.
                </p>
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