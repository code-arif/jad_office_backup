<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Lystajob – Connect Customers & Local Services Instantly</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg: #0a0e17;
            --bg-darker: #05070f;
            --surface: rgba(30, 41, 59, 0.65);
            --text: #e2e8f0;
            --text-muted: #94a3b8;
            --primary: #22d3ee;       /* cyan/teal main */
            --primary-dark: #0ea5e9;
            --green: #10b981;
            --green-dark: #059669;
            --border: rgba(34, 211, 238, 0.18);
            --glow: rgba(34, 211, 238, 0.25);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, var(--bg-darker) 0%, #0f172a 100%);
            color: var(--text);
            line-height: 1.6;
            min-height: 100vh;
        }

        a { color: var(--primary); text-decoration: none; }

        .container {
            width: 90%;
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }

        /* Header */
        header {
            background: rgba(15, 23, 42, 0.88);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid var(--border);
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.25rem 0;
        }

        .logo {
            font-family: 'Poppins', sans-serif;
            font-size: 1.9rem;
            font-weight: 700;
            background: linear-gradient(90deg, var(--primary), var(--green));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .logo img {
            height: 44px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.4);
        }

        .nav-links {
            display: flex;
            gap: 2.4rem;
            list-style: none;
        }

        .nav-links a {
            color: var(--text-muted);
            font-weight: 500;
            font-size: 1.05rem;
            transition: color 0.25s ease;
        }

        .nav-links a:hover {
            color: white;
        }

        /* Hero */
        .hero {
            padding: 180px 0 140px;
            display: flex;
            align-items: center;
            gap: 5rem;
        }

        .hero-content {
            flex: 1;
        }

        .hero h1 {
            font-size: 3.9rem;
            font-weight: 800;
            line-height: 1.12;
            margin-bottom: 1.2rem;
            background: linear-gradient(90deg, white, var(--primary), var(--green));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero h2 {
            font-size: 2.1rem;
            color: var(--primary);
            margin-bottom: 1.6rem;
        }

        .hero p {
            font-size: 1.22rem;
            color: var(--text-muted);
            max-width: 540px;
            margin-bottom: 2.5rem;
        }

        .app-badges {
            display: flex;
            gap: 1.4rem;
        }

        .app-badge img {
            height: 56px;
            border-radius: 16px;
            transition: transform 0.35s ease, box-shadow 0.35s ease;
        }

        .app-badge:hover img {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
        }

        .hero-image img {
            width: 340px;
            border-radius: 42px;
            border: 2px solid rgba(34,211,238,0.25);
            box-shadow: 0 30px 90px rgba(0,0,0,0.7), 0 0 0 1px rgba(34,211,238,0.2);
            transition: all 0.45s ease;
        }

        .hero-image img:hover {
            transform: scale(1.04) rotate(1.5deg);
        }

        /* Sections */
        section {
            padding: 110px 0;
        }

        .section-header {
            text-align: center;
            margin-bottom: 5.5rem;
        }

        .section-header p {
            text-transform: uppercase;
            letter-spacing: 3.5px;
            font-size: 0.98rem;
            color: var(--text-muted);
            margin-bottom: 1.1rem;
        }

        .section-header h2 {
            font-size: 3.2rem;
            font-weight: 700;
        }

        .divider {
            height: 5px;
            width: 90px;
            background: linear-gradient(90deg, var(--primary), var(--green));
            margin: 2rem auto;
            border-radius: 3px;
        }

        /* Cards */
        .features-grid,
        .steps-grid,
        .user-features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 2.2rem;
        }

        .feature-card,
        .step-item,
        .user-feature,
        .contact-item,
        .faq-item {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 2.4rem;
            transition: all 0.4s ease;
            backdrop-filter: blur(10px);
        }

        .feature-card:hover,
        .step-item:hover,
        .user-feature:hover,
        .contact-item:hover,
        .faq-item:hover {
            transform: translateY(-14px);
            border-color: var(--primary);
            box-shadow: 0 24px 60px var(--glow);
        }

        .feature-icon {
            width: 76px;
            height: 76px;
            background: rgba(34,211,238,0.12);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.6rem;
            font-size: 2rem;
            color: var(--primary);
        }

        .step-number {
            width: 60px;
            height: 60px;
            background: var(--primary);
            color: #0f172a;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            font-weight: bold;
            margin: 0 auto 1.4rem;
        }

        /* Screenshots */
        .screenshot-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 2rem;
        }

        .screenshot {
            border-radius: 28px;
            overflow: hidden;
            border: 2px solid rgba(34,211,238,0.22);
            transition: all 0.45s ease;
            box-shadow: 0 12px 32px rgba(0,0,0,0.4);
        }

        .screenshot:hover {
            transform: scale(1.07);
            border-color: var(--primary);
            box-shadow: 0 30px 80px rgba(34,211,238,0.3);
        }

        .screenshot img {
            width: 100%;
            display: block;
        }

        /* FAQ */
        .faq-container {
            max-width: 860px;
            margin: 0 auto;
        }

        .faq-question {
            padding: 1.5rem 2rem;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .faq-answer {
            padding: 0 2rem 1.8rem;
            max-height: 0;
            overflow: hidden;
            transition: all 0.35s ease;
            color: var(--text-muted);
        }

        .faq-item.active .faq-answer {
            max-height: 400px;
        }

        /* Buttons */
        .btn {
            background: linear-gradient(135deg, var(--primary), #06b6d4);
            color: #0f172a;
            padding: 1.1rem 2.4rem;
            border-radius: 999px;
            font-weight: 600;
            font-size: 1.1rem;
            display: inline-block;
            transition: all 0.35s ease;
            box-shadow: 0 10px 35px var(--glow);
        }

        .btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 60px var(--glow);
        }

        /* Footer */
        footer {
            background: rgba(5,7,15,0.92);
            padding: 7rem 0 4rem;
            text-align: center;
            border-top: 1px solid var(--border);
        }

        .social-links {
            display: flex;
            justify-content: center;
            gap: 1.8rem;
            margin: 2.5rem 0;
        }

        .social-icon {
            width: 54px;
            height: 54px;
            background: rgba(255,255,255,0.06);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .social-icon:hover {
            background: rgba(34,211,238,0.2);
            transform: translateY(-6px);
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .hero {
                flex-direction: column;
                text-align: center;
                padding-top: 150px;
            }
            .hero-image img {
                width: 300px;
            }
        }

        @media (max-width: 768px) {
            .hero h1 { font-size: 3rem; }
            .section-header h2 { font-size: 2.6rem; }
            .nav-links { gap: 1.4rem; font-size: 0.98rem; }
        }

        @media (max-width: 480px) {
            .hero h1 { font-size: 2.6rem; }
            .app-badges { flex-direction: column; align-items: center; }
        }
    </style>
</head>
<body>

    <!-- Header -->
    <header>
        <div class="container">
            <nav>
                <div class="logo">
                    <img src="https://admin.iploy.com.au/uploads/settings/1772446186-H1xgP.png" alt="Lystajob Logo">
                    iPloy
                </div>
                <ul class="nav-links">
                    <li><a href="#about">About</a></li>
                    <li><a href="#features">Features</a></li>
                    <li><a href="#how_it_works">How It Works</a></li>
                    <li><a href="#Advantages">Advantages</a></li>
                    <li><a href="#future-services">Future</a></li>
                    <li><a href="#about-ceo">CEO</a></li>
                    <li><a href="#contact">Contact</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Hero -->
    <section class="hero" id="about">
        <div class="container">
            <div class="hero-content">
                <h1>iPloy</h1>
                <h2>Connect Customers & Local Services – Directly</h2>
                <p>The modern, fair alternative to expensive lead-generation platforms. No middlemen. No overpriced leads. Just instant, transparent connections between people who need help and trusted local providers.</p>
                <div class="app-badges">
                    <a href="#" class="app-badge">
                        <img src="https://static0.anpoimages.com/wordpress/wp-content/uploads/2021/11/TechRadar.jpg?w=1600&h=900&fit=crop" alt="App Store">
                    </a>
                    <a href="#" class="app-badge">
                        <img src="https://photos5.appleinsider.com/gallery/0-139534-App-Store-xl.jpg" alt="Google Play">
                    </a>
                </div>
            </div>
            <div class="hero-image">
                <img src="https://admin.iploy.com.au/uploads/settings/Screenshot_25.png" alt="Lystajob App Home Screen">
            </div>
        </div>
    </section>

    <!-- Stats -->
    <section>
        <div class="container">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 2.5rem; text-align: center;">
                <div>
                    <h3 style="font-size: 3.2rem; color: var(--primary);">4.8</h3>
                    <p>App Store Rating</p>
                </div>
                <div>
                    <h3 style="font-size: 3.2rem; color: var(--primary);">4.7</h3>
                    <p>Google Play Rating</p>
                </div>
                <div>
                    <h3 style="font-size: 3.2rem; color: var(--primary);">1.0</h3>
                    <p>Version</p>
                </div>
                <div>
                    <h3 style="font-size: 3.2rem; color: var(--primary);">5K+</h3>
                    <p>Total Downloads</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section id="features">
        <div class="container">
            <div class="section-header">
                <p>WHY LYSTAJOB</p>
                <h2>Powerful Features for Everyone</h2>
                <div class="divider"></div>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">🗺</div>
                    <h3>Map-Based Search</h3>
                    <p>Find trusted local service providers near you with real-time map view.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">💬</div>
                    <h3>Direct Chat</h3>
                    <p>Talk directly with providers, discuss details and book — no waiting.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">💸</div>
                    <h3>No Lead Fees</h3>
                    <p>Keep 100% of your earnings — only low monthly subscription.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">⚖️</div>
                    <h3>Fair & Transparent</h3>
                    <p>You control pricing, schedule and work — complete freedom.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section id="how_it_works">
        <div class="container">
            <div class="section-header">
                <p>GETTING STARTED</p>
                <h2>How It Works</h2>
                <div class="divider"></div>
            </div>

            <div style="margin: 4rem 0;">
                <h3 style="font-size: 2rem; color: var(--primary); margin-bottom: 2rem; text-align: center;">For Customers</h3>
                <div class="steps-grid">
                    <div class="step-item">
                        <div class="step-number">1</div>
                        <h3>Search Locally</h3>
                        <p>Use the live map to instantly see available providers nearby.</p>
                    </div>
                    <div class="step-item">
                        <div class="step-number">2</div>
                        <h3>Post a Job</h3>
                        <p>Describe what you need — providers can respond right away.</p>
                    </div>
                    <div class="step-item">
                        <div class="step-number">3</div>
                        <h3>Chat & Book</h3>
                        <p>Message directly, agree on details and get the job done.</p>
                    </div>
                </div>
            </div>

            <div style="margin: 6rem 0 0;">
                <h3 style="font-size: 2rem; color: var(--primary); margin-bottom: 2rem; text-align: center;">For Service Providers</h3>
                <div class="steps-grid">
                    <div class="step-item">
                        <div class="step-number">1</div>
                        <h3>Show Your Skills</h3>
                        <p>Create a strong profile and list the services you offer.</p>
                    </div>
                    <div class="step-item">
                        <div class="step-number">2</div>
                        <h3>Find Jobs Fast</h3>
                        <p>See new job posts in your area — respond instantly.</p>
                    </div>
                    <div class="step-item">
                        <div class="step-number">3</div>
                        <h3>Keep Your Money</h3>
                        <p>No lead fees — just one fair monthly subscription.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Advantages -->
    <section id="Advantages">
        <div class="container">
            <div class="section-header">
                <p>OUR EDGE</p>
                <h2>Why Lystajob is Better</h2>
                <div class="divider"></div>
            </div>
            <div class="user-features">
                <div class="user-feature">
                    <h3>⚡️ Instant Connections</h3>
                    <p>Direct messaging — no waiting for leads or replies.</p>
                </div>
                <div class="user-feature">
                    <h3>🗺 True Local Map</h3>
                    <p>See who's really available near you — right now.</p>
                </div>
                <div class="user-feature">
                    <h3>💰 Very Affordable</h3>
                    <p>First month free, then only £5.99/month — no surprises.</p>
                </div>
                <div class="user-feature">
                    <h3>🧾 Zero Lead Charges</h3>
                    <p>Unlike others — you never pay per customer.</p>
                </div>
                <div class="user-feature">
                    <h3>🔒 Full Control</h3>
                    <p>You decide prices, availability and which jobs to take.</p>
                </div>
                <div class="user-feature">
                    <h3>📱 Everything in One App</h3>
                    <p>Search → Chat → Book → Get paid — no extra tools needed.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Future -->
    <section id="future-services" style="background: rgba(15,23,42,0.4); border-radius: 24px; margin: 0 1rem;">
        <div class="container text-center">
            <p>THE FUTURE</p>
            <h2 style="font-size: 3rem;">🌍 The Future of Local Services</h2>
            <p style="font-size: 1.3rem; max-width: 720px; margin: 2rem auto; color: var(--text-muted);">
                Lystajob is more than an app — it's building stronger local communities. 
                Whether you're offering gardening, cleaning, tutoring, plumbing, decorating, 
                car washing or home help — Lystajob gives everyone the chance to connect directly and earn fairly.
            </p>
        </div>
    </section>

    <!-- CEO -->
    <section id="about-ceo">
        <div class="container">
            <div class="section-header">
                <p>OUR STORY</p>
                <h2>Why I Built Lystajob</h2>
                <div class="divider"></div>
            </div>
            <div style="display: flex; gap: 4rem; align-items: center; flex-wrap: wrap; justify-content: center;">
                <div style="flex: 1; min-width: 300px; text-align: center;">
                    <img src="photo_2025-11-15_14-32-58.jpg" alt="CEO" style="max-width: 100%; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.5);">
                </div>
                <div style="flex: 1.6; min-width: 340px;">
                    <p style="font-size: 1.18rem; margin-bottom: 1.6rem;">
                        I created Lystajob because I saw too many good people — friends, family, neighbours — paying huge amounts for leads that often led nowhere.
                    </p>
                    <p style="font-size: 1.18rem; margin-bottom: 1.6rem;">
                        I also remember the day I urgently needed an electrician. I posted in a local Facebook group and had to chase 8–10 people individually, getting slow or no replies.
                    </p>
                    <p style="font-size: 1.18rem; margin-bottom: 1.6rem;">
                        That experience inspired Lystajob: open the app, see real providers nearby on a map, message instantly, and get the job done — quickly and fairly.
                    </p>
                    <p style="font-size: 1.18rem; margin-bottom: 1.6rem;">
                        This platform is for everyone — qualified tradespeople and people offering simple help alike. It's about community, fairness and real opportunity.
                    </p>
                    <p style="font-size: 1.3rem; font-weight: 600; color: var(--primary); margin-top: 2rem;">
                        C. Pika, Founder & CEO
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Screenshots -->
    <section>
        <div class="container">
            <div class="section-header">
                <p>SHOWCASE</p>
                <h2>App in Action</h2>
                <div class="divider"></div>
            </div>
            <div class="screenshot-gallery">
                <div class="screenshot"><img src="https://admin.iploy.com.au/uploads/settings/Screenshot_25.png" alt="Onboarding"></div>
                <div class="screenshot"><img src="https://admin.iploy.com.au/uploads/settings/Screenshot_30.png" alt="Welcome"></div>
                <div class="screenshot"><img src="https://admin.iploy.com.au/uploads/settings/Screenshot_31.png" alt="Login"></div>
                <div class="screenshot"><img src="https://admin.iploy.com.au/uploads/settings/Screenshot_31.png" alt="Provider Profile"></div>
            </div>
        </div>
    </section>

    <!-- FAQ -->
    <section>
        <div class="container">
            <div class="section-header">
                <p>QUESTIONS</p>
                <h2>Frequently Asked Questions</h2>
                <div class="divider"></div>
            </div>
            <div class="faq-container">
                <div class="faq-item">
                    <div class="faq-question">
                        How much does it cost for service providers?
                        <span style="font-size:1.6rem;">+</span>
                    </div>
                    <div class="faq-answer">
                        <p>First month is completely free. After that it's only £5.99 per month — no lead fees, no hidden charges.</p>
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-question">
                        Is it free for customers?
                        <span style="font-size:1.6rem;">+</span>
                    </div>
                    <div class="faq-answer">
                        <p>Yes — 100% free for anyone looking for services.</p>
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-question">
                        How do I get support?
                        <span style="font-size:1.6rem;">+</span>
                    </div>
                    <div class="faq-answer">
                        <p>Through the in-app support or email: iploy.customerqueries@gmail.com</p>
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-question">
                        What kind of services are on iPloy?
                        <span style="font-size:1.6rem;">+</span>
                    </div>
                    <div class="faq-answer">
                        <p>Trades (plumbing, electrical, decorating…), cleaning, gardening, tutoring, hair & beauty, car washing, home help, and much more.</p>
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-question">
                        Is there a contract? Can I cancel anytime?
                        <span style="font-size:1.6rem;">+</span>
                    </div>
                    <div class="faq-answer">
                        <p>No contract. Cancel anytime. We want to earn your trust every month.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact -->
    <section id="contact">
        <div class="container">
            <div class="section-header">
                <h2>Get in Touch</h2>
                <p>Questions, feedback or partnership ideas? We're here.</p>
                <div class="divider"></div>
            </div>
            <div style="text-align: center; max-width: 600px; margin: 0 auto 4rem;">
                <div class="contact-item" style="padding: 2.5rem;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">✉️</div>
                    <h3>Email</h3>
                    <p style="font-size: 1.3rem; margin-top: 1rem;">
                        <a href="mailto:lystajob.customerqueries@gmail.com">iploy.customerqueries@gmail.com</a>
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <h2 style="font-size: 2.6rem; margin-bottom: 1.5rem;">Ready to Start?</h2>
            <div class="app-badges" style="justify-content: center; margin: 2.5rem 0;">
                <a href="#" class="app-badge">
                    <img src="https://static0.anpoimages.com/wordpress/wp-content/uploads/2021/11/TechRadar.jpg?w=1600&h=900&fit=crop" alt="App Store">
                </a>
                <a href="#" class="app-badge">
                    <img src="https://photos5.appleinsider.com/gallery/0-139534-App-Store-xl.jpg" alt="Google Play">
                </a>
            </div>
            <p style="font-size: 1.3rem; margin: 2rem 0 3rem;">Find help. Find work. Build community.<br>Download iPloy today.</p>

            <div class="social-links">
                <!-- You can add real social SVG icons here if you want -->
            </div>

            <p style="color: var(--text-muted); margin-top: 4rem; font-size: 0.95rem;">
                © 2025–2026 Lystajob. All rights reserved.
            </p>
        </div>
    </footer>

    <script>
        // FAQ Accordion
        document.querySelectorAll('.faq-question').forEach(q => {
            q.addEventListener('click', () => {
                const item = q.parentElement;
                item.classList.toggle('active');
                const sign = q.querySelector('span');
                sign.textContent = item.classList.contains('active') ? '−' : '+';
            });
        });

        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    window.scrollTo({
                        top: target.offsetTop - 80,
                        behavior: 'smooth'
                    });
                }
            });
        });
    </script>
</body>
</html>