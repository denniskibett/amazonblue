<!DOCTYPE html>
<html lang="en" x-data="{
    mobileMenuOpen: false,
    scrolled: false,
    activeTab: 'features'
}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AmazonBlue Capital | Emergency Fund For Everything</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Outfit', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #ffffff;
            color: #0f172a;
        }
        
        .gradient-hero {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 40%, #2563eb 100%);
        }
        
        .gradient-accent {
            background: linear-gradient(135deg, #2563eb 0%, #1a365d 100%);
        }
        
        .gradient-text {
            background: linear-gradient(135deg, #3b82f6, #60a5fa, #93c5fd);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .gradient-text-dark {
            background: linear-gradient(135deg, #1e3a5f, #2563eb);
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
        
        .section-padding {
            padding: 100px 0;
        }
        
        @media (max-width: 768px) {
            .section-padding {
                padding: 70px 0;
            }
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
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            transition: all 0.3s ease;
        }
        
        .feature-card {
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
        }
        
        .feature-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 16px 32px -8px rgba(0, 0, 0, 0.08);
        }
        
        .testimonial-card {
            transition: all 0.3s ease;
        }
        
        .testimonial-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px -8px rgba(0, 0, 0, 0.06);
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
            bottom: 40px;
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
            font-size: 4rem;
            font-weight: 900;
            line-height: 1;
        }
        
        @media (max-width: 640px) {
            .stat-number {
                font-size: 2.8rem;
            }
        }
        
        .money-particles {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
            pointer-events: none;
        }
        
        .money-particle {
            position: absolute;
            font-size: 20px;
            opacity: 0.08;
            animation: float 20s infinite linear;
        }
        
        @keyframes float {
            0% { transform: translateY(0) rotate(0deg); opacity: 0.05; }
            50% { opacity: 0.15; }
            100% { transform: translateY(-100vh) rotate(720deg); opacity: 0.05; }
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        
        .hover-lift {
            transition: all 0.3s ease;
        }
        
        .hover-lift:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px -12px rgba(0, 0, 0, 0.12);
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
            </div>
        </div>
    </div>

    <!-- ========== HERO SECTION ========== -->
    <section class="relative overflow-hidden min-h-screen flex items-center gradient-hero">
        <!-- Floating money particles -->
        <div class="money-particles">
            <i class="fas fa-coins money-particle" style="left: 5%; animation-duration: 18s; animation-delay: 0s;"></i>
            <i class="fas fa-dollar-sign money-particle" style="left: 15%; animation-duration: 22s; animation-delay: 2s;"></i>
            <i class="fas fa-coins money-particle" style="left: 25%; animation-duration: 16s; animation-delay: 4s;"></i>
            <i class="fas fa-wallet money-particle" style="left: 35%; animation-duration: 20s; animation-delay: 1s;"></i>
            <i class="fas fa-coins money-particle" style="left: 45%; animation-duration: 24s; animation-delay: 3s;"></i>
            <i class="fas fa-dollar-sign money-particle" style="left: 55%; animation-duration: 19s; animation-delay: 5s;"></i>
            <i class="fas fa-coins money-particle" style="left: 65%; animation-duration: 21s; animation-delay: 2s;"></i>
            <i class="fas fa-wallet money-particle" style="left: 75%; animation-duration: 17s; animation-delay: 4s;"></i>
            <i class="fas fa-coins money-particle" style="left: 85%; animation-duration: 23s; animation-delay: 1s;"></i>
            <i class="fas fa-dollar-sign money-particle" style="left: 95%; animation-duration: 20s; animation-delay: 3s;"></i>
        </div>
        
        <!-- Decorative blobs -->
        <div class="absolute top-0 right-0 w-96 h-96 bg-blue-500/20 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 left-0 w-96 h-96 bg-blue-400/20 rounded-full blur-3xl"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-white/5 rounded-full blur-3xl"></div>
        
        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                <div>
                    <div class="inline-flex items-center px-4 py-2 glass-card rounded-full text-sm text-white/90 mb-8">
                        <span class="w-2 h-2 rounded-full bg-green-400 pulse-dot mr-3"></span>
                        Trusted by 500+ clients across East Africa
                    </div>
                    
                    <h1 class="text-4xl sm:text-5xl lg:text-7xl font-extrabold text-white leading-[1.1]">
                        Emergency Fund
                        <span class="block text-transparent bg-clip-text bg-gradient-to-r from-blue-200 via-blue-300 to-white">For Everything</span>
                    </h1>
                    
                    <p class="text-lg text-white/80 mt-6 max-w-lg leading-relaxed">
                        Fast cash when life happens. Medical bills, business gaps, school fees, or just keeping the lights on — we've got your back within 24 hours.
                    </p>
                    
                    <div class="flex flex-wrap gap-4 mt-8">
                        <a href="#plans" class="px-8 py-4 bg-white text-blue-600 font-bold rounded-xl hover:bg-blue-50 transition-all shadow-2xl shadow-white/20 hover:shadow-white/30 transform hover:-translate-y-1">
                            Get Your Emergency Fund <i class="fas fa-arrow-right ml-2"></i>
                        </a>
                        <a href="#features" class="px-8 py-4 border-2 border-white/20 text-white font-semibold rounded-xl hover:bg-white/10 transition-all">
                            See How It Works
                        </a>
                    </div>
                    
                    <!-- Trust badges -->
                    <div class="flex flex-wrap items-center gap-8 mt-12 pt-8 border-t border-white/10">
                        <div class="flex items-center space-x-3 text-white/70">
                            <i class="fas fa-bolt text-2xl text-blue-300"></i>
                            <span class="text-sm font-medium">24-Hour Access</span>
                        </div>
                        <div class="flex items-center space-x-3 text-white/70">
                            <i class="fas fa-shield-alt text-2xl text-blue-300"></i>
                            <span class="text-sm font-medium">100% Confidential</span>
                        </div>
                        <div class="flex items-center space-x-3 text-white/70">
                            <i class="fas fa-globe-africa text-2xl text-blue-300"></i>
                            <span class="text-sm font-medium">East Africa</span>
                        </div>
                    </div>
                </div>
                
                <!-- Hero Right - Stats & Callout -->
                <div class="space-y-6">
                    <!-- Main stat card -->
                    <div class="glass-card rounded-3xl p-8 border border-white/10">
                        <div class="text-6xl font-extrabold text-white">KES 2B+</div>
                        <p class="text-white/70 text-sm mt-2">Capital deployed to emergency situations</p>
                        <div class="mt-4 flex items-center gap-2 text-green-400">
                            <i class="fas fa-arrow-up text-sm"></i>
                            <span class="text-sm font-medium">95% client satisfaction rate</span>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div class="glass-card rounded-2xl p-6 border border-white/10 text-center">
                            <div class="text-3xl font-extrabold text-white">98%</div>
                            <p class="text-xs text-white/70 mt-1">Approval Rate</p>
                        </div>
                        <div class="glass-card rounded-2xl p-6 border border-white/10 text-center">
                            <div class="text-3xl font-extrabold text-white">24h</div>
                            <p class="text-xs text-white/70 mt-1">Average Payout</p>
                        </div>
                        <div class="glass-card rounded-2xl p-6 border border-white/10 text-center">
                            <div class="text-3xl font-extrabold text-white">500+</div>
                            <p class="text-xs text-white/70 mt-1">Lives Impacted</p>
                        </div>
                        <div class="glass-card rounded-2xl p-6 border border-white/10 text-center">
                            <div class="text-3xl font-extrabold text-white">4.9</div>
                            <div class="flex justify-center gap-1 text-yellow-400 text-sm mt-1">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                            <p class="text-xs text-white/70 mt-1">Client Rating</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="scroll-indicator"></div>
    </section>

    <!-- ========== FEATURES ========== -->
    <section id="features" class="section-padding bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto">
                <span class="text-blue-600 text-sm font-semibold tracking-wider uppercase">Your Emergency Fund</span>
                <h2 class="text-4xl sm:text-5xl font-extrabold text-slate-900 mt-3 mb-6 leading-tight">
                    Money When You Need It
                    <span class="block gradient-text-dark">No Questions Asked</span>
                </h2>
                <p class="text-lg text-slate-600 leading-relaxed">
                    Because emergencies don't check your schedule, and neither do we.
                </p>
            </div>

            <!-- Feature Tabs -->
            <div class="mt-12 flex justify-center">
                <div class="inline-flex rounded-xl bg-slate-100 p-1">
                    <button @click="activeTab = 'features'" 
                            class="px-8 py-3 text-sm font-semibold rounded-lg transition-all"
                            :class="activeTab === 'features' ? 'bg-white shadow-md text-slate-900' : 'text-slate-600 hover:text-slate-900'">
                        <i class="fas fa-th-large mr-2"></i> Core Features
                    </button>
                    <button @click="activeTab = 'services'" 
                            class="px-8 py-3 text-sm font-semibold rounded-lg transition-all"
                            :class="activeTab === 'services' ? 'bg-white shadow-md text-slate-900' : 'text-slate-600 hover:text-slate-900'">
                        <i class="fas fa-briefcase mr-2"></i> Services
                    </button>
                </div>
            </div>

            <!-- Features Grid -->
            <div x-show="activeTab === 'features'" class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="feature-card bg-white rounded-2xl p-8">
                    <div class="feature-icon bg-blue-50 text-blue-600">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mt-5">24-Hour Emergency Cash</h3>
                    <p class="text-sm text-slate-600 mt-3 leading-relaxed">Medical emergencies, business gaps, school fees, or just keeping the lights on — funded within a day.</p>
                </div>
                
                <div class="feature-card bg-white rounded-2xl p-8">
                    <div class="feature-icon bg-blue-50 text-blue-600">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mt-5">CRB Cleanup & Clearance</h3>
                    <p class="text-sm text-slate-600 mt-3 leading-relaxed">Ditch the blacklist. We'll clear your credit record fast, so you can get back to building your future.</p>
                </div>
                
                <div class="feature-card bg-white rounded-2xl p-8">
                    <div class="feature-icon bg-blue-50 text-blue-600">
                        <i class="fas fa-landmark"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mt-5">Asset-Powered Lending</h3>
                    <p class="text-sm text-slate-600 mt-3 leading-relaxed">Your car, land, or property is working for you. Get liquidity without selling what matters.</p>
                </div>
                
                <div class="feature-card bg-white rounded-2xl p-8">
                    <div class="feature-icon bg-blue-50 text-blue-600">
                        <i class="fas fa-globe-africa"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mt-5">Commodity Trade Finance</h3>
                    <p class="text-sm text-slate-600 mt-3 leading-relaxed">From agriculture to minerals — we finance East African trade to keep the region moving.</p>
                </div>
                
                <div class="feature-card bg-white rounded-2xl p-8">
                    <div class="feature-icon bg-blue-50 text-blue-600">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mt-5">24/7 Human Support</h3>
                    <p class="text-sm text-slate-600 mt-3 leading-relaxed">Real people, real solutions. No bots, no endless menus — just help when you need it.</p>
                </div>
                
                <div class="feature-card bg-white rounded-2xl p-8">
                    <div class="feature-icon bg-blue-50 text-blue-600">
                        <i class="fas fa-lock"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mt-5">Complete Privacy</h3>
                    <p class="text-sm text-slate-600 mt-3 leading-relaxed">Your financial story stays between us. Zero judgment, zero leaks, zero compromise.</p>
                </div>
            </div>

            <!-- Services Grid -->
            <div x-show="activeTab === 'services'" class="mt-16 grid grid-cols-1 md:grid-cols-2 gap-8" style="display: none;">
                <div class="feature-card bg-white rounded-2xl p-8 flex items-start space-x-5">
                    <div class="w-14 h-14 rounded-2xl bg-blue-50 flex items-center justify-center text-blue-600 text-2xl flex-shrink-0">
                        <i class="fas fa-hand-holding-usd"></i>
                    </div>
                    <div>
                        <h4 class="text-lg font-bold text-slate-900">Emergency Cash Injection</h4>
                        <p class="text-sm text-slate-600 mt-2">10-day bridge financing for urgent needs — medical, business, or personal.</p>
                    </div>
                </div>
                
                <div class="feature-card bg-white rounded-2xl p-8 flex items-start space-x-5">
                    <div class="w-14 h-14 rounded-2xl bg-blue-50 flex items-center justify-center text-blue-600 text-2xl flex-shrink-0">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div>
                        <h4 class="text-lg font-bold text-slate-900">CRB Full Restoration</h4>
                        <p class="text-sm text-slate-600 mt-2">Complete credit bureau rehabilitation so you can borrow again.</p>
                    </div>
                </div>
                
                <div class="feature-card bg-white rounded-2xl p-8 flex items-start space-x-5">
                    <div class="w-14 h-14 rounded-2xl bg-blue-50 flex items-center justify-center text-blue-600 text-2xl flex-shrink-0">
                        <i class="fas fa-home"></i>
                    </div>
                    <div>
                        <h4 class="text-lg font-bold text-slate-900">Asset-First Financing</h4>
                        <p class="text-sm text-slate-600 mt-2">Property, vehicles, luxury assets — unlock their value without selling.</p>
                    </div>
                </div>
                
                <div class="feature-card bg-white rounded-2xl p-8 flex items-start space-x-5">
                    <div class="w-14 h-14 rounded-2xl bg-blue-50 flex items-center justify-center text-blue-600 text-2xl flex-shrink-0">
                        <i class="fas fa-seedling"></i>
                    </div>
                    <div>
                        <h4 class="text-lg font-bold text-slate-900">Trade & Commodity Finance</h4>
                        <p class="text-sm text-slate-600 mt-2">Agricultural and mineral trade financing to keep East African commerce flowing.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ========== HOW IT WORKS ========== -->
    <section class="section-padding bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto">
                <span class="text-blue-600 text-sm font-semibold tracking-wider uppercase">How It Works</span>
                <h2 class="text-4xl sm:text-5xl font-extrabold text-slate-900 mt-3 mb-6">
                    Get Your Emergency Fund
                    <span class="block gradient-text-dark">In Three Simple Steps</span>
                </h2>
            </div>

            <div class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="w-20 h-20 rounded-2xl bg-blue-600 text-white text-3xl flex items-center justify-center mx-auto shadow-lg shadow-blue-600/25">
                        <i class="fas fa-pen"></i>
                    </div>
                    <h4 class="text-xl font-bold text-slate-900 mt-6">1. Tell Us Your Situation</h4>
                    <p class="text-sm text-slate-600 mt-3 leading-relaxed max-w-xs mx-auto">Share what you need and why. We're all ears, zero judgment.</p>
                </div>
                
                <div class="text-center">
                    <div class="w-20 h-20 rounded-2xl bg-blue-600 text-white text-3xl flex items-center justify-center mx-auto shadow-lg shadow-blue-600/25">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h4 class="text-xl font-bold text-slate-900 mt-6">2. Get Approved Fast</h4>
                    <p class="text-sm text-slate-600 mt-3 leading-relaxed max-w-xs mx-auto">Most approvals happen within hours. We move at the speed of urgency.</p>
                </div>
                
                <div class="text-center">
                    <div class="w-20 h-20 rounded-2xl bg-blue-600 text-white text-3xl flex items-center justify-center mx-auto shadow-lg shadow-blue-600/25">
                        <i class="fas fa-hand-holding-usd"></i>
                    </div>
                    <h4 class="text-xl font-bold text-slate-900 mt-6">3. Receive Your Funds</h4>
                    <p class="text-sm text-slate-600 mt-3 leading-relaxed max-w-xs mx-auto">Cash in your account within 24 hours. No delays, no drama.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ========== PLANS ========== -->
    <section id="plans" class="section-padding bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto">
                <span class="text-blue-600 text-sm font-semibold tracking-wider uppercase">Choose Your Plan</span>
                <h2 class="text-4xl sm:text-5xl font-extrabold text-slate-900 mt-3 mb-6">
                    Emergency Access, <span class="gradient-text-dark">Your Way</span>
                </h2>
                <p class="text-lg text-slate-600">Plans built for how you live, work, and need cash.</p>
            </div>

            <div class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                <!-- Starter -->
                <div class="plan-card bg-white rounded-3xl p-8 shadow-sm border-2 border-slate-200">
                    <div class="text-center">
                        <h3 class="text-xl font-bold text-slate-900">Starter</h3>
                        <div class="mt-3">
                            <span class="text-4xl font-extrabold text-slate-900">Free</span>
                        </div>
                        <p class="text-sm text-slate-500 mt-2">Essential emergency access</p>
                    </div>
                    <ul class="mt-8 space-y-4 text-sm">
                        <li class="flex items-center text-slate-600">
                            <i class="fas fa-check text-blue-600 mr-3 w-5"></i>
                            Emergency consultation
                        </li>
                        <li class="flex items-center text-slate-600">
                            <i class="fas fa-check text-blue-600 mr-3 w-5"></i>
                            Basic CRB review
                        </li>
                        <li class="flex items-center text-slate-600">
                            <i class="fas fa-check text-blue-600 mr-3 w-5"></i>
                            Standard support
                        </li>
                        <li class="flex items-center text-slate-600 opacity-50">
                            <i class="fas fa-minus text-slate-300 mr-3 w-5"></i>
                            Priority funding
                        </li>
                    </ul>
                    <a href="#contact" class="mt-8 block w-full py-3.5 text-center text-sm font-semibold border-2 border-slate-300 rounded-xl hover:bg-slate-50 transition-all">
                        Get Started
                    </a>
                </div>

                <!-- Professional -->
                <div class="plan-card popular bg-white rounded-3xl p-8 shadow-xl border-2 border-blue-600 relative">
                    <div class="absolute -top-4 left-1/2 transform -translate-x-1/2 px-6 py-1.5 bg-blue-600 text-white text-xs font-bold rounded-full">
                        Most Popular
                    </div>
                    <div class="text-center mt-4">
                        <h3 class="text-xl font-bold text-slate-900">Professional</h3>
                        <div class="mt-3">
                            <span class="text-4xl font-extrabold text-slate-900">KES 99</span>
                            <span class="text-sm text-slate-500">/month</span>
                        </div>
                        <p class="text-sm text-slate-500 mt-2">Full emergency coverage</p>
                    </div>
                    <ul class="mt-8 space-y-4 text-sm">
                        <li class="flex items-center text-slate-600">
                            <i class="fas fa-check text-blue-600 mr-3 w-5"></i>
                            Priority emergency funding
                        </li>
                        <li class="flex items-center text-slate-600">
                            <i class="fas fa-check text-blue-600 mr-3 w-5"></i>
                            Full CRB resolution
                        </li>
                        <li class="flex items-center text-slate-600">
                            <i class="fas fa-check text-blue-600 mr-3 w-5"></i>
                            Asset financing consultation
                        </li>
                        <li class="flex items-center text-slate-600">
                            <i class="fas fa-check text-blue-600 mr-3 w-5"></i>
                            Premium 24/7 support
                        </li>
                        <li class="flex items-center text-slate-600">
                            <i class="fas fa-check text-blue-600 mr-3 w-5"></i>
                            <span class="font-medium text-blue-600">2x cash on all funding</span>
                        </li>
                    </ul>
                    <a href="#contact" class="mt-8 block w-full py-3.5 text-center text-sm font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition-all shadow-lg shadow-blue-600/25">
                        Get Started
                    </a>
                </div>

                <!-- Enterprise -->
                <div class="plan-card bg-white rounded-3xl p-8 shadow-sm border-2 border-slate-200">
                    <div class="text-center">
                        <h3 class="text-xl font-bold text-slate-900">Enterprise</h3>
                        <div class="mt-3">
                            <span class="text-4xl font-extrabold text-slate-900">KES 999</span>
                            <span class="text-sm text-slate-500">/month</span>
                        </div>
                        <p class="text-sm text-slate-500 mt-2">Unlimited emergency power</p>
                    </div>
                    <ul class="mt-8 space-y-4 text-sm">
                        <li class="flex items-center text-slate-600">
                            <i class="fas fa-check text-blue-600 mr-3 w-5"></i>
                            Unlimited emergency funding
                        </li>
                        <li class="flex items-center text-slate-600">
                            <i class="fas fa-check text-blue-600 mr-3 w-5"></i>
                            Priority CRB clearance
                        </li>
                        <li class="flex items-center text-slate-600">
                            <i class="fas fa-check text-blue-600 mr-3 w-5"></i>
                            Asset & commodity finance
                        </li>
                        <li class="flex items-center text-slate-600">
                            <i class="fas fa-check text-blue-600 mr-3 w-5"></i>
                            Concierge support
                        </li>
                        <li class="flex items-center text-slate-600">
                            <i class="fas fa-check text-blue-600 mr-3 w-5"></i>
                            <span class="font-medium text-blue-600">4x cash on all funding</span>
                        </li>
                    </ul>
                    <a href="#contact" class="mt-8 block w-full py-3.5 text-center text-sm font-semibold border-2 border-slate-300 rounded-xl hover:bg-slate-50 transition-all">
                        Contact Sales
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- ========== TESTIMONIALS ========== -->
    <section id="testimonials" class="section-padding bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto">
                <span class="text-blue-600 text-sm font-semibold tracking-wider uppercase">Testimonials</span>
                <h2 class="text-4xl sm:text-5xl font-extrabold text-slate-900 mt-3 mb-6">
                    Real People, <span class="gradient-text-dark">Real Emergencies</span>
                </h2>
                <p class="text-lg text-slate-600">What our clients say about getting their emergency fund.</p>
            </div>

            <div class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="testimonial-card bg-white rounded-2xl p-8 border border-slate-200 shadow-sm">
                    <div class="flex items-center gap-1 text-yellow-400 mb-4">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="text-slate-600 text-sm leading-relaxed">
                        "AmazonBlue Capital delivered emergency funding within 12 hours when we needed it most. Saved my business."
                    </p>
                    <div class="mt-6 flex items-center">
                        <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-lg">
                            JM
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-bold text-slate-900">James Mwangi</p>
                            <p class="text-xs text-slate-500">CEO, Nairobi Logistics</p>
                        </div>
                    </div>
                </div>

                <div class="testimonial-card bg-white rounded-2xl p-8 border border-slate-200 shadow-sm">
                    <div class="flex items-center gap-1 text-yellow-400 mb-4">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="text-slate-600 text-sm leading-relaxed">
                        "They cleared my CRB listing in 48 hours. Completely transformed my financial standing."
                    </p>
                    <div class="mt-6 flex items-center">
                        <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-lg">
                            SK
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-bold text-slate-900">Sarah Kariuki</p>
                            <p class="text-xs text-slate-500">Entrepreneur</p>
                        </div>
                    </div>
                </div>

                <div class="testimonial-card bg-white rounded-2xl p-8 border border-slate-200 shadow-sm">
                    <div class="flex items-center gap-1 text-yellow-400 mb-4">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="text-slate-600 text-sm leading-relaxed">
                        "The asset financing helped us expand our fleet without tying up our working capital."
                    </p>
                    <div class="mt-6 flex items-center">
                        <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-lg">
                            MO
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-bold text-slate-900">Michael Ochieng</p>
                            <p class="text-xs text-slate-500">Transport Manager</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ========== FAQ ========== -->
    <section id="faq" class="section-padding bg-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto">
                <span class="text-blue-600 text-sm font-semibold tracking-wider uppercase">FAQ</span>
                <h2 class="text-4xl sm:text-5xl font-extrabold text-slate-900 mt-3 mb-6">
                    Questions About <span class="gradient-text-dark">Emergency Funding</span>
                </h2>
                <p class="text-lg text-slate-600">Everything you need to know before you apply.</p>
            </div>

            <div class="mt-16 space-y-4">
                <details class="faq-item bg-slate-50 rounded-2xl p-6 border border-slate-200 hover:border-blue-300 transition-colors">
                    <summary class="flex items-center justify-between cursor-pointer">
                        <span class="font-bold text-slate-900">How quickly can I get emergency funding?</span>
                        <i class="fas fa-chevron-down text-blue-600 transition-transform"></i>
                    </summary>
                    <p class="mt-4 text-sm text-slate-600 leading-relaxed">
                        Most emergency funding requests are processed and paid out within 24 hours. We prioritize urgent cases and can expedite based on your specific situation.
                    </p>
                </details>

                <details class="faq-item bg-slate-50 rounded-2xl p-6 border border-slate-200 hover:border-blue-300 transition-colors">
                    <summary class="flex items-center justify-between cursor-pointer">
                        <span class="font-bold text-slate-900">What makes AmazonBlue different from a bank?</span>
                        <i class="fas fa-chevron-down text-blue-600 transition-transform"></i>
                    </summary>
                    <p class="mt-4 text-sm text-slate-600 leading-relaxed">
                        We're built for speed and urgency. No endless paperwork, no weeks of waiting, no judgment. Just fast, confidential cash when you need it most.
                    </p>
                </details>

                <details class="faq-item bg-slate-50 rounded-2xl p-6 border border-slate-200 hover:border-blue-300 transition-colors">
                    <summary class="flex items-center justify-between cursor-pointer">
                        <span class="font-bold text-slate-900">What if I have a bad credit history?</span>
                        <i class="fas fa-chevron-down text-blue-600 transition-transform"></i>
                    </summary>
                    <p class="mt-4 text-sm text-slate-600 leading-relaxed">
                        That's exactly what we're here for. We work with you to resolve CRB issues and get you back on track. Your past doesn't define your future.
                    </p>
                </details>

                <details class="faq-item bg-slate-50 rounded-2xl p-6 border border-slate-200 hover:border-blue-300 transition-colors">
                    <summary class="flex items-center justify-between cursor-pointer">
                        <span class="font-bold text-slate-900">Is my information kept confidential?</span>
                        <i class="fas fa-chevron-down text-blue-600 transition-transform"></i>
                    </summary>
                    <p class="mt-4 text-sm text-slate-600 leading-relaxed">
                        Absolutely. We maintain strict confidentiality for all client information. Your financial story stays between us — zero judgment, zero leaks.
                    </p>
                </details>

                <details class="faq-item bg-slate-50 rounded-2xl p-6 border border-slate-200 hover:border-blue-300 transition-colors">
                    <summary class="flex items-center justify-between cursor-pointer">
                        <span class="font-bold text-slate-900">How do I get started with my emergency fund?</span>
                        <i class="fas fa-chevron-down text-blue-600 transition-transform"></i>
                    </summary>
                    <p class="mt-4 text-sm text-slate-600 leading-relaxed">
                        Click "Get Started" below, tell us your situation, and we'll get you funded within 24 hours. No hassle, no judgment — just help.
                    </p>
                </details>
            </div>
        </div>
    </section>

    <!-- ========== CTA SECTION ========== -->
    <section class="py-24 gradient-hero relative overflow-hidden">
        <div class="absolute top-0 right-0 w-96 h-96 bg-blue-500/20 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 left-0 w-96 h-96 bg-blue-400/20 rounded-full blur-3xl"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-white/5 rounded-full blur-3xl"></div>
        
        <div class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-white leading-tight">
                Your Emergency Fund Is <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-200 to-white">One Click Away</span>
            </h2>
            <p class="text-lg text-white/80 mt-6 max-w-2xl mx-auto leading-relaxed">
                No judgment. No endless paperwork. Just fast, confidential cash when you need it most.
            </p>
            <div class="mt-10 flex flex-wrap justify-center gap-4">
                <a href="#plans" class="px-10 py-4 bg-white text-blue-600 font-bold rounded-xl hover:bg-blue-50 transition-all shadow-2xl shadow-white/20 hover:shadow-white/30 transform hover:-translate-y-1 text-lg">
                    Get Your Emergency Fund <i class="fas fa-arrow-right ml-3"></i>
                </a>
                <a href="#features" class="px-10 py-4 border-2 border-white/20 text-white font-semibold rounded-xl hover:bg-white/10 transition-all text-lg">
                    Learn More
                </a>
            </div>
            <div class="mt-8 flex justify-center gap-8 text-white/60 text-sm">
                <span><i class="fas fa-shield-alt mr-2"></i> 100% Confidential</span>
                <span><i class="fas fa-bolt mr-2"></i> 24-Hour Payout</span>
                <span><i class="fas fa-star mr-2"></i> 4.9/5 Rating</span>
            </div>
        </div>
    </section>

    <!-- ========== FOOTER ========== -->
    <footer class="bg-slate-900 text-slate-400">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-12">
                <div>
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center">
                            <span class="text-white font-bold text-lg">A</span>
                        </div>
                        <span class="text-xl font-bold text-white">AmazonBlue<span class="text-blue-400">Capital</span></span>
                    </div>
                    <p class="text-sm leading-relaxed">Emergency fund for everything. Fast, confidential, and always here when life happens.</p>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-white mb-4">Emergency Services</h4>
                    <ul class="space-y-3 text-sm">
                        <li><a href="#" class="hover:text-blue-400 transition-colors">24-Hour Cash</a></li>
                        <li><a href="#" class="hover:text-blue-400 transition-colors">CRB Cleanup</a></li>
                        <li><a href="#" class="hover:text-blue-400 transition-colors">Asset Lending</a></li>
                        <li><a href="#" class="hover:text-blue-400 transition-colors">Trade Finance</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-white mb-4">Company</h4>
                    <ul class="space-y-3 text-sm">
                        <li><a href="#" class="hover:text-blue-400 transition-colors">About Us</a></li>
                        <li><a href="#" class="hover:text-blue-400 transition-colors">Contact</a></li>
                        <li><a href="#" class="hover:text-blue-400 transition-colors">Careers</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-white mb-4">Connect</h4>
                    <div class="flex space-x-4">
                        <a href="#" class="w-10 h-10 rounded-lg bg-slate-800 flex items-center justify-center hover:bg-blue-600 transition-colors">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <a href="#" class="w-10 h-10 rounded-lg bg-slate-800 flex items-center justify-center hover:bg-blue-400 transition-colors">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="w-10 h-10 rounded-lg bg-slate-800 flex items-center justify-center hover:bg-blue-600 transition-colors">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                    </div>
                    <p class="text-sm mt-4">
                        <i class="fas fa-phone-alt mr-2 text-blue-400"></i> +254 701 607 959
                    </p>
                </div>
            </div>

            <div class="mt-12 pt-8 border-t border-slate-800">
                <div class="flex flex-col md:flex-row justify-between items-center text-sm">
                    <p>© 2026 AmazonBlue Capital. All rights reserved.</p>
                    <div class="flex flex-wrap gap-6 mt-2 md:mt-0">
                        <a href="#" class="hover:text-blue-400 transition-colors">Privacy Policy</a>
                        <a href="#" class="hover:text-blue-400 transition-colors">Terms of Service</a>
                        <a href="#" class="hover:text-blue-400 transition-colors">Disclaimer</a>
                    </div>
                </div>
                <p class="text-xs text-center md:text-left mt-4 text-slate-500 leading-relaxed">
                    <strong>Disclaimer:</strong> This website is for informational purposes only. All financial services are subject to terms and conditions.
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