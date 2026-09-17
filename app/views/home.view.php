<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRAC — Cordillera Regional Apiculture Center</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,400;1,700&family=Nunito:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= ROOT ?>/public/assets/css/home.css?v=<?= time() ?>">
   
</head>
<body>

<!-- ══ NAVBAR ══════════════════════════════════════════════ -->
<nav class="navbar" id="navbar">
    <a href="#" class="nav-brand">
        <div class="nav-hex"><img src="<?= ROOT ?>/public/assets/img/cordillera-apiculture-logo.png" alt="Cordillera Regional Apiculture Center"></div>
        <div class="nav-brand-text">
            <div class="nav-brand-name">CRAC</div>
            <div class="nav-brand-sub">Cordillera Regional Apiculture Center</div>
        </div>
    </a>

    <button class="nav-toggle" id="navToggle" onclick="toggleNav()">
        <i class="fas fa-bars" id="navIcon"></i>
    </button>

    <ul class="nav-links" id="navLinks">
        <li><a href="#vmg">Vision & Mission</a></li>
        <li><a href="#history">History</a></li>
        <li><a href="#location">Location</a></li>
        <li><a href="#announcements">Announcements</a></li>
        <li><a href="<?= ROOT ?>/login" class="nav-cta"><i class="fas fa-sign-in-alt"></i> Sign In to HiveSense</a></li>
    </ul>
</nav>

<!-- ══ HERO ════════════════════════════════════════════════ -->
<section class="hero" id="home">
    <div class="hero-hex-1"></div>
    <div class="hero-hex-2"></div>
    <div class="hero-hex-3"></div>

    <div class="hero-content">
        <div class="hero-eyebrow">
            <i class="fas fa-map-marker-alt"></i>
            Cordillera Administrative Region, Philippines
        </div>
        <h1 class="hero-title">
            Cordillera Regional<br>
            <span>Apiculture Center</span>
        </h1>
        <p class="hero-desc">
            A premier center dedicated to the development of apiculture — educating beekeepers, advancing research, and integrating sustainable beekeeping into the farming systems of the Cordillera region.
        </p>
        <div class="hero-actions">
            <a href="#vmg" class="btn-hero-primary">
                <i class="fas fa-leaf"></i> Learn About CRAC
            </a>
            <a href="<?= ROOT ?>/login" class="btn-hero-secondary">
                <i class="fas fa-chart-line"></i> HiveSense Dashboard
            </a>
        </div>
    </div>

    <div class="hero-stats">
        <div class="hero-stat">
            <div class="hero-stat-icon"><i class="fas fa-university"></i></div>
            <div>
                <div class="hero-stat-label">Established</div>
                <div class="hero-stat-value">September 25, 2013</div>
            </div>
        </div>
        <div class="hero-stat">
            <div class="hero-stat-icon"><i class="fas fa-handshake"></i></div>
            <div>
                <div class="hero-stat-label">Partnership</div>
                <div class="hero-stat-value">DMMMSU &amp; BSU</div>
            </div>
        </div>
        <div class="hero-stat">
            <div class="hero-stat-icon"><i class="fas fa-flask"></i></div>
            <div>
                <div class="hero-stat-label">Institute</div>
                <div class="hero-stat-value">NARTDI Center</div>
            </div>
        </div>
        <div class="hero-stat">
            <div class="hero-stat-icon"><i class="fas fa-seedling"></i></div>
            <div>
                <div class="hero-stat-label">Focus</div>
                <div class="hero-stat-value">Sustainable Apiculture</div>
            </div>
        </div>
    </div>
</section>

<!-- ══ VISION MISSION GOAL ══════════════════════════════════ -->
<section class="vmg-section" id="vmg">
    <div class="container">
        <div class="vmg-header reveal">
            <div class="section-label">Our Purpose</div>
            <h2 class="section-title">Vision, <span>Mission</span> & Goal</h2>
            <p class="section-desc">Guiding principles that drive the Cordillera Regional Apiculture Center toward a thriving, sustainable apiculture industry in the region.</p>
        </div>

        <div class="vmg-grid">
            <div class="vmg-card vision reveal reveal-delay-1">
                <div class="vmg-card-icon"><i class="fas fa-eye"></i></div>
                <div class="vmg-card-label">Vision</div>
                <h3 class="vmg-card-title">Our Vision</h3>
                <p class="vmg-card-text">
                    A premier regional apiculture center established for human, material and natural resources development for a competitive apiculture industry.
                </p>
                <div class="vmg-card-bg-letter">V</div>
            </div>

            <div class="vmg-card mission reveal reveal-delay-2">
                <div class="vmg-card-icon"><i class="fas fa-bullseye"></i></div>
                <div class="vmg-card-label">Mission</div>
                <h3 class="vmg-card-title">Our Mission</h3>
                <p class="vmg-card-text">
                    To educate and train would-be beekeepers, apiculturists, and other stakeholders; to conduct researches and extend technologies towards the development of apiculture in the region in collaboration with the concerned government agencies/institutions, non-government organizations/private sector and other apiculture/beekeeping stakeholders.
                </p>
                <div class="vmg-card-bg-letter">M</div>
            </div>

            <div class="vmg-card goal reveal reveal-delay-3">
                <div class="vmg-card-icon"><i class="fas fa-trophy"></i></div>
                <div class="vmg-card-label">Goal</div>
                <h3 class="vmg-card-title">Our Goal</h3>
                <p class="vmg-card-text">
                    To establish apiculture/beekeeping as a sustainable households' complimentary source of income that is integrated into the farming systems in the region.
                </p>
                <div class="vmg-card-bg-letter">G</div>
            </div>
        </div>
    </div>
</section>

<!-- ══ HISTORY ══════════════════════════════════════════════ -->
<section class="history-section" id="history">
    <div class="container">
        <div class="history-inner">
            <div class="history-text">
                <div class="section-label reveal">Our Story</div>
                <h2 class="section-title reveal">The History of <span>CRAC</span></h2>
                <p class="section-desc reveal">
                    The Cordillera Regional Apiculture Center (CRAC) is a Memorandum of Agreement between Don Mariano Marcos Memorial State University (DMMMSU) and Benguet State University (BSU). On September 25, 2013, it was established as a regional center of the National Apiculture Research Training and Development Institute (NARTDI) in the Cordillera Region.
                </p>

                <div class="history-highlight">
                    <div class="history-item reveal reveal-delay-1">
                        <div class="history-item-icon"><i class="fas fa-university"></i></div>
                        <div class="history-item-text">
                            <h4>DMMMSU Partnership</h4>
                            <p>Don Mariano Marcos Memorial State University — co-founding institution of CRAC.</p>
                        </div>
                    </div>
                    <div class="history-item reveal reveal-delay-2">
                        <div class="history-item-icon"><i class="fas fa-graduation-cap"></i></div>
                        <div class="history-item-text">
                            <h4>Benguet State University</h4>
                            <p>BSU provides facilities including the COSARD Building, College of Veterinary Medicine Compound.</p>
                        </div>
                    </div>
                    <div class="history-item reveal reveal-delay-3">
                        <div class="history-item-icon"><i class="fas fa-flask"></i></div>
                        <div class="history-item-text">
                            <h4>NARTDI Regional Center</h4>
                            <p>Designated as the regional center of the National Apiculture Research Training and Development Institute in Cordillera.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="history-visual reveal">
                <div class="history-card-main">
                    <div class="history-card-year">2013</div>
                    <h3 class="history-card-title">Founded as a <span>Regional Center</span></h3>
                    <p class="history-card-desc">
                        Through a Memorandum of Agreement, CRAC was established to serve as the hub for apiculture research, training, and development across the Cordillera Administrative Region.
                    </p>
                    <div class="history-card-badge">
                        <i class="fas fa-calendar-check"></i> September 25, 2013
                    </div>
                </div>
                <div class="history-card-mini">
                    <div class="history-card-mini-label">Powered by HiveSense</div>
                    <div class="history-card-mini-val">Smart Hive Monitoring</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ══ LOCATION ══════════════════════════════════════════════ -->
<section class="location-section" id="location">
    <div class="container">
        <div class="location-inner">
            <div>
                <div class="section-label reveal">Find Us</div>
                <h2 class="section-title reveal">CRAC <span>Locations</span></h2>
                <p class="section-desc reveal" style="margin-bottom:32px;">
                    CRAC operates across two key campuses in the Cordillera region, providing accessible training and research facilities for beekeepers and apiculturists.
                </p>

                <div class="location-cards">
                    <div class="location-card reveal reveal-delay-1">
                        <div class="location-card-header">
                            <div class="location-card-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                            <div class="location-card-title">Training Center</div>
                        </div>
                        <div class="location-card-body">
                            <strong>"Paquito R. Umcalan" CRAC Training Center</strong><br>
                            College of Forestry, Benguet State University
                        </div>
                    </div>

                    <div class="location-card reveal reveal-delay-2">
                        <div class="location-card-header">
                            <div class="location-card-icon"><i class="fas fa-building"></i></div>
                            <div class="location-card-title">Main Office</div>
                        </div>
                        <div class="location-card-body">
                            <strong>Cordillera Center for Animal Research and Development (CCARD)</strong><br>
                            COSARD Building, College of Veterinary Medicine Compound,<br>
                            Benguet State University
                        </div>
                    </div>

                    <div class="location-card reveal reveal-delay-3">
                        <div class="location-card-header">
                            <div class="location-card-icon"><i class="fas fa-map-marker-alt"></i></div>
                            <div class="location-card-title">Region</div>
                        </div>
                        <div class="location-card-body">
                            <strong>Cordillera Administrative Region (CAR)</strong><br>
                            Baguio City and surrounding Cordillera provinces, Philippines
                        </div>
                    </div>
                </div>
            </div>

            <div class="map-wrap reveal">
                <div class="map-pin"><i class="fas fa-map-marker-alt"></i></div>
                <div class="map-label">Benguet State University</div>
                <div class="map-sublabel">La Trinidad, Benguet, Cordillera</div>
                <a href="https://maps.google.com/?q=Benguet+State+University+La+Trinidad+Benguet" target="_blank" rel="noopener" class="map-cta">
                    <i class="fas fa-external-link-alt"></i> Open in Google Maps
                </a>
            </div>
        </div>
    </div>
</section>


<!-- ══ ANNOUNCEMENTS ════════════════════════════════════════ -->
<section class="announcements-section" id="announcements">
    <div class="container">
        <div class="ann-header reveal">
            <div class="section-label">Latest Updates</div>
            <h2 class="section-title">CRAC <span>Announcements</span></h2>
            <p class="section-desc" style="margin:0 auto;text-align:center;">
                Stay up to date with the latest news, events, and updates from the Cordillera Regional Apiculture Center.
            </p>
        </div>
        <div id="annGrid" class="ann-grid">
            <div class="ann-loading"><i class="fas fa-spinner fa-spin" style="margin-right:8px;"></i> Loading announcements...</div>
        </div>
    </div>
</section>

<!-- ══ HIVESENSE CTA ═════════════════════════════════════════ -->
<section class="cta-section">
    <div class="cta-inner reveal">
        <div class="cta-icon"><i class="fas fa-bezier-curve"></i></div>
        <h2 class="cta-title">Monitor Your Hives with <span>HiveSense</span></h2>
        <p class="cta-desc">
            Real-time temperature and humidity tracking, colony inspection records, and health analytics — all in one place. Sign in to access the dashboard.
        </p>
        <a href="<?= ROOT ?>/login" class="cta-btn">
            <i class="fas fa-sign-in-alt"></i> Sign In to HiveSense
        </a>
    </div>
</section>

<!-- ══ FOOTER ════════════════════════════════════════════════ -->
<footer>
    <div class="footer-brand">
        <div class="footer-hex"><i class="fas fa-bezier-curve"></i></div>
        <div>
            <div class="footer-name">CRAC &amp; HiveSense</div>
            <div class="footer-sub">Cordillera Regional Apiculture Center</div>
        </div>
    </div>
    <div class="footer-links">
        <a href="#vmg">Vision &amp; Mission</a>
        <a href="#history">History</a>
        <a href="#location">Location</a>
        <a href="<?= ROOT ?>/login">HiveSense Login</a>
    </div>
    <div class="footer-copy">&copy; <?= date('Y') ?> CRAC. All rights reserved.</div>
</footer>

<script>
    // ── Navbar scroll effect ──
    const navbar = document.getElementById('navbar');
    window.addEventListener('scroll', () => {
        navbar.classList.toggle('scrolled', window.scrollY > 40);
    });

    // ── Mobile nav toggle ──
    function toggleNav() {
        const links = document.getElementById('navLinks');
        const icon  = document.getElementById('navIcon');
        links.classList.toggle('open');
        icon.classList.toggle('fa-bars');
        icon.classList.toggle('fa-times');
    }
    // Close nav on link click
    document.querySelectorAll('.nav-links a').forEach(a => {
        a.addEventListener('click', () => {
            document.getElementById('navLinks').classList.remove('open');
            document.getElementById('navIcon').classList.add('fa-bars');
            document.getElementById('navIcon').classList.remove('fa-times');
        });
    });


    // ── Announcements ─────────────────────────────────────────
    const typeIcon = { info: 'fa-info-circle', success: 'fa-check-circle', warning: 'fa-exclamation-triangle', alert: 'fa-bell' };
    const typeLabel = { info: 'Info', success: 'Update', warning: 'Warning', alert: 'Alert' };

    async function loadAnnouncements() {
        const grid = document.getElementById('annGrid');
        try {
            const res    = await fetch('<?= ROOT ?>/api/announcements_public');
            const result = await res.json();
            if (!result.success || !result.data || result.data.length === 0) {
                grid.innerHTML = '<div class="ann-empty"><i class="fas fa-bullhorn"></i><p>No announcements at this time. Check back later.</p></div>';
                return;
            }
            grid.innerHTML = result.data.map(a => {
                const type  = a.type || 'info';
                const date  = new Date(a.created_at).toLocaleDateString('en-PH', { year:'numeric', month:'long', day:'numeric' });
                const author = a.author ? a.author : 'CRAC Admin';
                return `<div class="ann-card ${type} reveal">
                    <div class="ann-card-stripe"></div>
                    <div class="ann-card-body">
                        <div class="ann-card-meta">
                            <span class="ann-type-badge"><i class="fas ${typeIcon[type]}"></i> ${typeLabel[type]}</span>
                            <span class="ann-date">${date}</span>
                        </div>
                        <div class="ann-card-title">${escHtml(a.title)}</div>
                        <div class="ann-card-text">${escHtml(a.body)}</div>
                    </div>
                    <div class="ann-card-footer">
                        <i class="fas fa-user"></i> Posted by ${escHtml(author)}
                    </div>
                </div>`;
            }).join('');

            // Trigger reveal for dynamically added cards
            document.querySelectorAll('.ann-card.reveal').forEach(el => observer.observe(el));
        } catch(e) {
            grid.innerHTML = '<div class="ann-empty"><i class="fas fa-exclamation-circle"></i><p>Could not load announcements.</p></div>';
        }
    }

    function escHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    loadAnnouncements();

    // ── Scroll reveal ──
    const revealEls = document.querySelectorAll('.reveal');
    const observer  = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                e.target.classList.add('visible');
                observer.unobserve(e.target);
            }
        });
    }, { threshold: 0.12 });
    revealEls.forEach(el => observer.observe(el));
</script>
</body>
</html>
