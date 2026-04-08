<?php
declare(strict_types=1);

// Include configuration and bootstrap
require_once __DIR__ . '/../config/config.php';

// If user is already logged in, redirect to appropriate dashboard
if (is_logged_in()) {
    $role = get_user_role();

    switch ($role) {
        case 'SUPER_ADMIN':
            redirect(base_url('super/'));
            break;
        case 'SCHOOL_ADMIN':
        case 'STAFF':
            redirect(base_url('admin/'));
            break;
        case 'STUDENT':
            redirect(base_url('student/'));
            break;
        default:
            // Clear invalid session
            session_destroy();
            break;
    }
}

// Page variables
$pageTitle = 'Home - Uganda Results System';
$pageDescription = 'Uganda High School Results and Report Card Management System';
$bodyClass = 'landing-page';

// Include header
include __DIR__ . '/../views/layouts/header.php';
?>

<!-- Page-only style overrides (professional fonts, cleaner layout, modern footer) -->
<style>
  /* Scope everything to this page to avoid touching other screens */
  .landing-page {
    --bg: #0c1117;
    --panel: #111824;
    --line:rgb(78, 78, 78);
    --text: #e7eef6;
    --muted: #9ab1c6;
    --primary: #00b383;
    --primary-ink: #062017;
    --link:rgb(194, 204, 201);

    /* Professional, crisp system stack */
    font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, Arial, "Noto Sans", "Ubuntu", "Cantarell", "Helvetica Neue", sans-serif;
    color: var(--text);
    background: var(--bg);
  }

  /* Optional polish for the header/hero without changing your structure */
  .landing-page .navbar { border-bottom: 1px solid var(--line); }
  .landing-page .hero { border-bottom: 1px solid var(--line); }
  .landing-page .hero .hero-buttons .btn {
    border-radius: 12px;
    font-weight: 700;
  }
  .landing-page .btn-primary {
    background: linear-gradient(90deg, #00C4CC, #00CC66);
    color: var(--primary-ink) !important;
    border: none;
    box-shadow: 0 10px 28px rgba(0, 204, 102, .24);
  }
  .landing-page .btn-outline {
    border-color: var(--line);
  }

  /* ===== Modern, readable footer ===== */
  .landing-page footer.site-footer-modern {
    border-top: 1px solid var(--line);
    background: #0f1622; /* slightly brighter than page bg for contrast */
  }
  .landing-page .footer-wrap {
    max-width: 1120px;
    margin: 0 auto;
    padding: 22px 16px 18px;
  }
  .landing-page .footer-grid {
    display: grid;
    gap: 16px;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    align-items: start;
  }
  .landing-page .fbrand {
    display: flex; align-items: center; gap: 10px; margin-bottom: 10px;
  }
  .landing-page .fbrand svg { width: 28px; height: 28px; }
  .landing-page .fintro { color: var(--muted); margin: 0; }

  .landing-page .fcol h4 {
    margin: 0 0 8px;
    font-size: 13px;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: var(--muted);
  }
  .landing-page .flist {
    list-style: none; padding: 0; margin: 0;
  }
  .landing-page .flist li {
    display: flex; align-items: center; gap: 8px;
    margin: 8px 0;
  }
  .landing-page .flist a {
    color: var(--text);
    text-decoration: none;
  }
  .landing-page .flist a:hover {
    color: var(--link);
  }
  .landing-page .ico {
    display: inline-flex;
    width: 28px; height: 28px;
    align-items: center; justify-content: center;
    border-radius: 8px;
    background: #152433;
    border: 1px solid var(--line);
  }
  .landing-page .ico svg {
    width: 16px; height: 16px; color: var(--muted);
  }
  .landing-page .ico:hover svg { color: var(--text); }

  .landing-page .footer-bottom {
    margin-top: 14px;
    padding-top: 12px;
    border-top: 1px solid var(--line);
    color: var(--muted);
    font-size: 12px;
    text-align: center;
  }

  /* Make existing footer classes play nice without changing your markup elsewhere */
  .landing-page footer.bg-primary { background: #0f1622; }
  .landing-page .text-secondary { color: var(--muted); }
  .landing-page .hover\:text-primary:hover { color: var(--link); }

  /* ====================  READABILITY PATCH FOR #about  ==================== */
  .landing-page #about{
    background:#0f1622;  /* darker base for strong contrast */
  }
  .landing-page #about h2,
  .landing-page #about .text-3xl{ color:#eef5ff; }      /* headings bright */
  .landing-page #about p,
  .landing-page #about .text-lg,
  .landing-page #about .text-secondary,
  .landing-page #about li{ color:#d4e1f1; }            /* readable body */
  .landing-page #about .text-success{ color:#00CC66 !important; } /* green ticks */

  /* right-hand stat cards */
  .landing-page #about .card{
    background:#121a28 !important;
    border:1px solid #223146 !important;
    border-radius:14px !important;
    box-shadow:0 10px 24px rgba(0,0,0,.25);
  }
  .landing-page #about .text-primary-600{ color:#57e5bf !important; } /* numbers */

  /* scroll reveal (only this section) */
  .landing-page #about .reveal{
    opacity:0; transform:translateY(12px);
    transition:opacity .55s ease, transform .55s ease;
  }
  .landing-page #about .reveal.in{ opacity:1; transform:none; }
</style>

    <!-- Header -->
    <header class="landing-header">
        <nav class="navbar">
            <div class="container">
                <div class="flex justify-between items-center">
                    <div class="navbar-brand">
                        <svg width="32" height="32" viewBox="0 0 64 64" fill="currentColor">
                            <circle cx="32" cy="32" r="30" fill="currentColor"/>
                            <rect x="16" y="18" width="20" height="24" rx="2" fill="white"/>
                            <rect x="18" y="20" width="16" height="20" fill="#f3f4f6"/>
                            <line x1="20" y1="24" x2="32" y2="24" stroke="#6b7280" stroke-width="1"/>
                            <line x1="20" y1="27" x2="32" y2="27" stroke="#6b7280" stroke-width="1"/>
                            <line x1="20" y1="30" x2="30" y2="30" stroke="#6b7280" stroke-width="1"/>
                            <text x="24" y="36" text-anchor="middle" fill="#059669" font-family="Arial" font-size="6" font-weight="bold">A</text>
                        </svg>
                        <span><?php echo APP_NAME; ?></span>
                    </div>

                    <div class="navbar-nav">
                        <a href="#features">Features</a>
                        <a href="#about">About</a>
                        <a href="<?php echo base_url('auth/login-admin.php'); ?>" class="btn btn-outline btn-sm">Admin Login</a>
                        <a href="<?php echo base_url('auth/login-student.php'); ?>" class="btn btn-primary btn-sm">Student Login</a>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <div class="hero-text">
                    <h1>Uganda High School Results & Report Card System</h1>
                    <p class="hero-subtitle">
                        A comprehensive multi-tenant system for managing student results, generating report cards, and tracking academic performance across Uganda's secondary schools.
                    </p>
                    <div class="hero-buttons">
                        <a href="<?php echo base_url('auth/login-student.php'); ?>" class="btn btn-primary btn-xl">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                            </svg>
                            Student Portal
                        </a>
                        <a href="<?php echo base_url('auth/login-admin.php'); ?>" class="btn btn-outline btn-xl">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                            </svg>
                            School Administration
                        </a>
                    </div>
                </div>
                <div class="hero-image">
                    <div class="hero-card">
                        <div class="card">
                            <div class="card-header">
                                <h3>Sample Report Card</h3>
                            </div>
                            <div class="card-body">
                                <div class="grid gap-3">
                                    <div class="flex justify-between items-center">
                                        <span class="font-medium">Mathematics</span>
                                        <span class="text-success font-semibold">A (85%)</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="font-medium">English</span>
                                        <span class="text-success font-semibold">B (78%)</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="font-medium">Physics</span>
                                        <span class="text-success font-semibold">A (92%)</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="font-medium">Chemistry</span>
                                        <span class="text-success font-semibold">B (81%)</span>
                                    </div>
                                </div>
                                <div class="mt-4 pt-4 border-t text-center">
                                    <div class="font-semibold text-primary-600">Position: 3rd in class</div>
                                    <div class="text-success font-bold">Division I</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features">
        <div class="container">
            <div class="section-header">
                <h2>Key Features</h2>
                <p>Everything you need to manage school results efficiently</p>
            </div>

            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h-2V7h2v10zm4 0h-2v-4h2v4zm2.5 2.25l1.41-1.41L15 12.42V7H9v5.42l1.91 1.91 1.41-1.41L11 11.59V8h2v3.59l1.32 1.32z"/>
                        </svg>
                    </div>
                    <h3>Multi-Tenant Architecture</h3>
                    <p>Each school operates independently with their own data, users, and customizations while sharing the same secure platform.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82zM12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/>
                        </svg>
                    </div>
                    <h3>Uganda Curriculum Aligned</h3>
                    <p>Pre-configured with O-Level (UCE) and A-Level (UACE) subjects, grading scales, and division rules according to Uganda's education system.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zM14 2v6h6"/></svg>
                    </div>
                    <h3>Comprehensive Report Cards</h3>
                    <p>Generate professional report cards with school branding, QR code verification, and multiple layout options for printing.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M13 3a9 9 0 1 0 9 9h-2a7 7 0 1 1-7-7V3z"/></svg>
                    </div>
                    <h3>Real-time Grade Calculation</h3>
                    <p>Automatic computation of totals, grades, points, positions, and divisions based on configurable grading scales and rules.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M16 4c0-1.11.89-2 2-2s2 .89 2 2-.89 2-2 2-2-.89-2-2zm4 18v-6h2.5l-2.54-7.63A2.997 2.997 0 0 0 17.11 7H16.5l-.09-.4c-.32-1.49-1.65-2.6-3.24-2.6s-2.92 1.11-3.24 2.6L9.84 7H9.1c-1.35 0-2.53.88-2.92 2.16L3.5 16H6v6h4v-6h4v6h6z"/></svg>
                    </div>
                    <h3>Role-based Access</h3>
                    <p>Different access levels for super admins, school administrators, teachers, and students with appropriate permissions.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M17 1H7c-1.1 0-2 .9-2 2v18c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V3c0-1.1-.9-2-2-2zM7 4V3h10v1H7zm0 14V6h10v12H7z"/></svg>
                    </div>
                    <h3>Mobile Responsive</h3>
                    <p>Works seamlessly on all devices - desktop, tablet, and mobile phones with a modern, intuitive interface.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/></svg>
                    </div>
                    <h3>Analytics & Insights</h3>
                    <p>Detailed analytics on student performance, class statistics, grade distributions, and pass rates.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 16l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z"/></svg>
                    </div>
                    <h3>Secure & Auditable</h3>
                    <p>Complete audit logs, secure authentication, and role-based permissions ensure data security and accountability.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="about py-8 bg-secondary">
        <div class="container">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
                <div>
                    <h2 class="text-3xl font-bold mb-4">Built for Uganda's Education System</h2>
                    <p class="text-lg mb-6">
                        This system is specifically designed to meet the needs of Uganda's secondary schools,
                        with deep understanding of the local curriculum, grading systems, and administrative requirements.
                    </p>
                    <ul class="space-y-2">
                        <li class="flex items-center gap-2">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" class="text-success">
                                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                            </svg>
                            UCE and UACE grade scales
                        </li>
                        <li class="flex items-center gap-2">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" class="text-success">
                                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                            </svg>
                            Division calculation rules
                        </li>
                        <li class="flex items-center gap-2">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" class="text-success">
                                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                            </svg>
                            Standard subject offerings
                        </li>
                        <li class="flex items-center gap-2">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" class="text-success">
                                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                            </svg>
                            Term-based academic calendar
                        </li>
                        <li class="flex items-center gap-2">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" class="text-success">
                                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                            </svg>
                            Class and stream organization
                        </li>
                        <li class="flex items-center gap-2">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" class="text-success">
                                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                            </svg>
                            Principal and subsidiary subjects
                        </li>
                    </ul>
                </div>
                <div class="grid gap-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <div class="text-3xl font-bold text-primary-600">1000+</div>
                            <div class="text-secondary">Students Supported</div>
                        </div>
                    </div>
                    <div class="card text-center">
                        <div class="card-body">
                            <div class="text-3xl font-bold text-primary-600">50+</div>
                            <div class="text-secondary">Schools Ready</div>
                        </div>
                    </div>
                    <div class="card text-center">
                        <div class="card-body">
                            <div class="text-3xl font-bold text-primary-600">100%</div>
                            <div class="text-secondary">Uganda Compliant</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer (modernized, readable, with icons) -->
    <footer class="site-footer-modern bg-primary py-8 border-t">
        <div class="footer-wrap container">
            <div class="footer-grid">
                <div>
                    <div class="fbrand">
                        <svg viewBox="0 0 64 64" fill="currentColor" aria-hidden="true">
                            <circle cx="32" cy="32" r="30" fill="currentColor"/>
                            <rect x="16" y="18" width="20" height="24" rx="2" fill="white"/>
                            <rect x="18" y="20" width="16" height="20" fill="#f3f4f6"/>
                            <line x1="20" y1="24" x2="32" y2="24" stroke="#6b7280" stroke-width="1"/>
                            <line x1="20" y1="27" x2="32" y2="27" stroke="#6b7280" stroke-width="1"/>
                            <line x1="20" y1="30" x2="30" y2="30" stroke="#6b7280" stroke-width="1"/>
                        </svg>
                        <strong><?php echo APP_NAME; ?></strong>
                    </div>
                    <p class="fintro">Empowering Uganda's education system with modern technology.</p>
                </div>

                <div>
                    <h4>System</h4>
                    <ul class="flist">
                        <li>
                            <span class="ico">
                                <!-- shield icon -->
                                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l7 4v6c0 5-3.8 9.4-7 10-3.2-.6-7-5-7-10V6l7-4z"/></svg>
                            </span>
                            <a href="<?php echo base_url('auth/login-admin.php'); ?>">Admin Login</a>
                        </li>
                        <li>
                            <span class="ico">
                                <!-- user icon -->
                                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5zm0 2c-4.2 0-8 2.1-8 5v1h16v-1c0-2.9-3.8-5-8-5z"/></svg>
                            </span>
                            <a href="<?php echo base_url('auth/login-student.php'); ?>">Student Login</a>
                        </li>
                        <li>
                            <span class="ico">
                                <!-- star/feature icon -->
                                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87L18.18 21 12 17.77 5.82 21 7 14.14 2 9.27l6.91-1.01z"/></svg>
                            </span>
                            <a href="#features">Features</a>
                        </li>
                    </ul>
                </div>

                <div>
                    <h4>Support</h4>
                    <ul class="flist">
                        <li>
                            <span class="ico">
                                <!-- mail -->
                                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4a2 2 0 0 0-2 2v1l10 6 10-6V6a2 2 0 0 0-2-2zm0 6.2L12 16 4 10.2V18a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2z"/></svg>
                            </span>
                            <a href="mailto:support@uganda-results.com">Contact Support</a>
                        </li>
                        <li>
                            <span class="ico">
                                <!-- info -->
                                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 1 0 10 10A10.011 10.011 0 0 0 12 2zm1 15h-2v-6h2zm0-8h-2V7h2z"/></svg>
                            </span>
                            <a href="#about">About</a>
                        </li>
                        <li>
                            <span class="ico">
                                <!-- file/docs -->
                                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zM14 2v6h6"/></svg>
                            </span>
                            <a href="#">Documentation</a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <div>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</div>
                <div style="margin-top:4px;">Version <?php echo APP_VERSION; ?></div>
            </div>
        </div>
    </footer>

<!-- Scroll-reveal for the #about section only -->
<script>
(function(){
  const about = document.getElementById('about');
  if(!about) return;

  const reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  const targets = [
    about.querySelector('h2'),
    ...about.querySelectorAll('p.text-lg'),
    ...about.querySelectorAll('li'),
    ...about.querySelectorAll('.card')
  ].filter(Boolean);

  targets.forEach(el => el.classList.add('reveal'));

  if (reduce) { targets.forEach(el => el.classList.add('in')); return; }

  const io = new IntersectionObserver((entries) => {
    entries.forEach(en => {
      if (en.isIntersecting) {
        en.target.classList.add('in');
        io.unobserve(en.target);
      }
    });
  }, { threshold: 0.12 });

  targets.forEach(el => io.observe(el));
})();
</script>

<?php
// Include footer
include __DIR__ . '/../views/layouts/footer.php';
?>

<!-- Paste this where you want the chat to load (end of <body> is fine) -->
<script src="https://chat.klcdc.org/public/widget.js"
        data-site="0003W5E11264SGSF"
        data-base="https://chat.klcdc.org"></script>
