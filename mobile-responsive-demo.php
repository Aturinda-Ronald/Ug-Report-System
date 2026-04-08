<?php
/**
 * Mobile Responsive Demo Page
 * Showcases all the responsive features of the Uganda Results System
 */

// Include configuration
require_once __DIR__ . '/config/config.php';

// Set page variables for the header
$pageTitle = 'Mobile Responsive Demo - Uganda Results System';
$pageHeader = [
    'title' => '📱 Mobile Responsive Demo',
    'description' => 'Showcasing mobile-friendly features and responsive design'
];

$breadcrumbs = [
    ['text' => 'Home', 'href' => base_url('')],
    ['text' => 'Mobile Demo', 'href' => '#']
];

$bodyClass = 'demo-page';

// Simulate user session for demo
$_SESSION['user_id'] = 'demo';
$_SESSION['role'] = 'SCHOOL_ADMIN';
$_SESSION['first_name'] = 'Demo User';
$_SESSION['school_name'] = 'Uganda Demo School';

// Include responsive header
include __DIR__ . '/views/layouts/responsive-header.php';
?>

<!-- Demo Page Content -->
<div class="demo-container">

    <!-- Welcome Section -->
    <div class="card mb-3">
        <div class="card-header">
            <h2>🎉 Welcome to the Mobile-Responsive Uganda Results System!</h2>
        </div>
        <div class="card-body">
            <p>This demo showcases the fully responsive design that works perfectly on:</p>
            <div class="row">
                <div class="col-4">
                    <div class="feature-card">
                        <div class="feature-icon">📱</div>
                        <h4>Mobile Phones</h4>
                        <p>Optimized for smartphones with touch-friendly interfaces</p>
                    </div>
                </div>
                <div class="col-4">
                    <div class="feature-card">
                        <div class="feature-icon">📟</div>
                        <h4>Tablets</h4>
                        <p>Perfect layout for iPad and Android tablets</p>
                    </div>
                </div>
                <div class="col-4">
                    <div class="feature-card">
                        <div class="feature-icon">💻</div>
                        <h4>Desktop</h4>
                        <p>Full-featured experience on laptops and desktops</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Demo -->
    <div class="card mb-3">
        <div class="card-header">
            <h3>🧭 Navigation Features</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-6">
                    <h4>📱 Mobile Features</h4>
                    <ul class="feature-list">
                        <li>✅ Hamburger menu for compact navigation</li>
                        <li>✅ Swipe gestures (swipe right from left edge to open menu)</li>
                        <li>✅ Touch-friendly button sizes (44px minimum)</li>
                        <li>✅ Bottom navigation bar for quick access</li>
                        <li>✅ Overlay background when menu is open</li>
                    </ul>
                </div>
                <div class="col-6">
                    <h4>🖥️ Desktop Features</h4>
                    <ul class="feature-list">
                        <li>✅ Full horizontal navigation bar</li>
                        <li>✅ Hover effects and animations</li>
                        <li>✅ Keyboard navigation support</li>
                        <li>✅ Large screen optimization</li>
                        <li>✅ Multi-column layouts</li>
                    </ul>
                </div>
            </div>

            <div class="demo-actions">
                <button class="btn btn-primary" onclick="demonstrateNavigation()">
                    🧭 Test Navigation
                </button>
                <button class="btn btn-success" onclick="showToast('Menu opened!', 'success')">
                    📱 Show Mobile Menu
                </button>
            </div>
        </div>
    </div>

    <!-- Form Demo -->
    <div class="card mb-3">
        <div class="card-header">
            <h3>📝 Responsive Forms</h3>
        </div>
        <div class="card-body">
            <p>All forms are optimized for mobile input with proper spacing and touch targets:</p>

            <form class="demo-form" onsubmit="return demoFormSubmit(event)">
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label for="demo-name">Student Name</label>
                            <input type="text" id="demo-name" class="form-control" placeholder="Enter student name" required>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label for="demo-class">Class</label>
                            <select id="demo-class" class="form-control" required>
                                <option value="">Select Class</option>
                                <option value="s1">Senior 1</option>
                                <option value="s2">Senior 2</option>
                                <option value="s3">Senior 3</option>
                                <option value="s4">Senior 4</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="demo-subjects">Subjects</label>
                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" value="math"> Mathematics
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" value="english"> English
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" value="science"> Science
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" value="history"> History
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="demo-notes">Additional Notes</label>
                    <textarea id="demo-notes" class="form-control" rows="3" placeholder="Enter any additional notes..."></textarea>
                </div>

                <div class="demo-actions">
                    <button type="submit" class="btn btn-primary btn-block">
                        💾 Save Student (Demo)
                    </button>
                    <button type="reset" class="btn btn-secondary">
                        🔄 Reset Form
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Table Demo -->
    <div class="card mb-3">
        <div class="card-header">
            <h3>📊 Responsive Tables</h3>
        </div>
        <div class="card-body">
            <p>Tables automatically adapt to different screen sizes:</p>

            <div class="table-responsive">
                <table class="responsive-table">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Class</th>
                            <th class="hide-mobile">Registration</th>
                            <th>Mathematics</th>
                            <th>English</th>
                            <th class="hide-mobile">Science</th>
                            <th>Average</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td data-label="Student Name">Nakato Sarah</td>
                            <td data-label="Class">Senior 4</td>
                            <td data-label="Registration" class="hide-mobile">2024/001</td>
                            <td data-label="Mathematics">85</td>
                            <td data-label="English">78</td>
                            <td data-label="Science" class="hide-mobile">92</td>
                            <td data-label="Average"><strong>85%</strong></td>
                            <td data-label="Actions">
                                <button class="btn btn-sm btn-primary">View</button>
                            </td>
                        </tr>
                        <tr>
                            <td data-label="Student Name">Musoke David</td>
                            <td data-label="Class">Senior 3</td>
                            <td data-label="Registration" class="hide-mobile">2024/002</td>
                            <td data-label="Mathematics">76</td>
                            <td data-label="English">82</td>
                            <td data-label="Science" class="hide-mobile">88</td>
                            <td data-label="Average"><strong>82%</strong></td>
                            <td data-label="Actions">
                                <button class="btn btn-sm btn-primary">View</button>
                            </td>
                        </tr>
                        <tr>
                            <td data-label="Student Name">Aisha Namukasa</td>
                            <td data-label="Class">Senior 4</td>
                            <td data-label="Registration" class="hide-mobile">2024/003</td>
                            <td data-label="Mathematics">91</td>
                            <td data-label="English">87</td>
                            <td data-label="Science" class="hide-mobile">95</td>
                            <td data-label="Average"><strong>91%</strong></td>
                            <td data-label="Actions">
                                <button class="btn btn-sm btn-primary">View</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="demo-info">
                <p><strong>Mobile Features:</strong></p>
                <ul>
                    <li>📱 Horizontal scrolling on small screens</li>
                    <li>👁️ Less important columns hidden on mobile</li>
                    <li>📋 Data labels shown on very small screens</li>
                    <li>👆 Touch-friendly row selection</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Interactive Features Demo -->
    <div class="card mb-3">
        <div class="card-header">
            <h3>⚡ Interactive Features</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-6">
                    <h4>🎯 Touch & Gestures</h4>
                    <div class="gesture-demo">
                        <div class="gesture-item" onclick="showToast('Tap detected!', 'success')">
                            <span>👆</span>
                            <p>Tap me!</p>
                        </div>
                        <div class="gesture-item" onclick="showToast('Double tap!', 'info')" ondblclick="showToast('Double tap confirmed!', 'success')">
                            <span>👆👆</span>
                            <p>Double tap!</p>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <h4>🌙 Theme Controls</h4>
                    <div class="theme-controls">
                        <button class="btn btn-primary" onclick="toggleDarkMode()">
                            🌙 Toggle Dark Mode
                        </button>
                        <button class="btn btn-success" onclick="showNotifications()">
                            🔔 Show Notifications
                        </button>
                    </div>
                </div>
            </div>

            <div class="feature-grid">
                <div class="feature-item" onclick="demonstrateLoading()">
                    <span class="feature-emoji">⏳</span>
                    <h5>Loading States</h5>
                    <p>Visual feedback during operations</p>
                </div>
                <div class="feature-item" onclick="demonstrateAnimations()">
                    <span class="feature-emoji">✨</span>
                    <h5>Smooth Animations</h5>
                    <p>Fade-in effects and transitions</p>
                </div>
                <div class="feature-item" onclick="demonstrateScrolling()">
                    <span class="feature-emoji">📜</span>
                    <h5>Smart Scrolling</h5>
                    <p>Scroll to top and smooth navigation</p>
                </div>
                <div class="feature-item" onclick="demonstratePWA()">
                    <span class="feature-emoji">📱</span>
                    <h5>PWA Features</h5>
                    <p>Install as mobile app</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Device Testing -->
    <div class="card mb-3">
        <div class="card-header">
            <h3>📱 Device Testing</h3>
        </div>
        <div class="card-body">
            <p>Test the responsive design on different screen sizes:</p>

            <div class="device-simulator">
                <div class="device-buttons">
                    <button class="btn btn-secondary" onclick="simulateDevice('mobile')">📱 Mobile (375px)</button>
                    <button class="btn btn-secondary" onclick="simulateDevice('tablet')">📟 Tablet (768px)</button>
                    <button class="btn btn-secondary" onclick="simulateDevice('desktop')">💻 Desktop (1200px)</button>
                    <button class="btn btn-secondary" onclick="simulateDevice('auto')">🔄 Auto</button>
                </div>

                <div class="device-info" id="device-info">
                    <p><strong>Current Viewport:</strong> <span id="viewport-size"></span></p>
                    <p><strong>Device Type:</strong> <span id="device-type"></span></p>
                    <p><strong>Orientation:</strong> <span id="orientation"></span></p>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- JavaScript for Demo Functions -->
<script>
// Update device info
function updateDeviceInfo() {
    const width = window.innerWidth;
    const height = window.innerHeight;
    const deviceType = width <= 480 ? 'Mobile' : width <= 768 ? 'Tablet' : 'Desktop';
    const orientation = width > height ? 'Landscape' : 'Portrait';

    document.getElementById('viewport-size').textContent = `${width} × ${height}px`;
    document.getElementById('device-type').textContent = deviceType;
    document.getElementById('orientation').textContent = orientation;
}

// Simulate different devices
function simulateDevice(type) {
    const body = document.body;
    body.classList.remove('simulate-mobile', 'simulate-tablet', 'simulate-desktop');

    if (type !== 'auto') {
        body.classList.add(`simulate-${type}`);
        showToast(`Simulating ${type} device`, 'info');
    } else {
        showToast('Auto device detection restored', 'success');
    }

    setTimeout(updateDeviceInfo, 100);
}

// Demo functions
function demonstrateNavigation() {
    const hamburger = document.querySelector('.hamburger');
    if (hamburger) {
        hamburger.click();
        setTimeout(() => {
            showToast('Navigation menu toggled!', 'success');
        }, 500);
    }
}

function demoFormSubmit(event) {
    event.preventDefault();
    const submitBtn = event.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;

    submitBtn.innerHTML = '<span class="loading"></span> Saving...';
    submitBtn.disabled = true;

    setTimeout(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
        showToast('Demo form submitted successfully!', 'success');
    }, 2000);

    return false;
}

function toggleDarkMode() {
    document.body.classList.toggle('dark-mode');
    const isDark = document.body.classList.contains('dark-mode');
    showToast(isDark ? 'Dark mode enabled! 🌙' : 'Light mode enabled! ☀️', 'info');
}

function showNotifications() {
    showToast('Welcome notification! 👋', 'info', 2000);
    setTimeout(() => showToast('Data saved successfully! ✅', 'success', 2000), 1000);
    setTimeout(() => showToast('Warning: Check your data! ⚠️', 'warning', 2000), 2000);
}

function demonstrateLoading() {
    const btn = event.target.closest('.feature-item');
    const originalContent = btn.innerHTML;

    btn.innerHTML = `
        <span class="loading"></span>
        <h5>Loading...</h5>
        <p>Please wait...</p>
    `;

    setTimeout(() => {
        btn.innerHTML = originalContent;
        showToast('Loading demonstration complete!', 'success');
    }, 3000);
}

function demonstrateAnimations() {
    const elements = document.querySelectorAll('.card');
    elements.forEach((el, index) => {
        setTimeout(() => {
            el.style.animation = 'fadeIn 0.5s ease-in';
        }, index * 200);
    });
    showToast('Animation demonstration complete!', 'success');
}

function demonstrateScrolling() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
    showToast('Smooth scrolling to top!', 'info');
}

function demonstratePWA() {
    if ('serviceWorker' in navigator) {
        showToast('PWA features available! You can install this app.', 'info', 4000);
    } else {
        showToast('PWA features not supported in this browser.', 'warning');
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    updateDeviceInfo();

    // Update device info on resize
    window.addEventListener('resize', updateDeviceInfo);

    // Auto-demo some features
    setTimeout(() => {
        showToast('🎉 Welcome to the Mobile Responsive Demo!', 'success', 3000);
    }, 1000);
});
</script>

<!-- Demo-specific Styles -->
<style>
.demo-container {
    max-width: 1200px;
    margin: 0 auto;
}

.feature-card {
    text-align: center;
    padding: 1.5rem;
    border: 2px solid var(--border-color);
    border-radius: var(--border-radius);
    margin-bottom: 1rem;
    transition: var(--transition);
}

.feature-card:hover {
    border-color: var(--secondary-color);
    transform: translateY(-2px);
}

.feature-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.feature-list {
    list-style: none;
    padding-left: 0;
}

.feature-list li {
    padding: 0.5rem 0;
    border-bottom: 1px solid rgba(0,0,0,0.1);
}

.demo-actions {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    margin-top: 1rem;
}

.checkbox-group {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 0.5rem;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: var(--border-radius);
    transition: var(--transition);
}

.checkbox-label:hover {
    background: rgba(52, 152, 219, 0.1);
}

.gesture-demo {
    display: flex;
    gap: 1rem;
    justify-content: center;
}

.gesture-item {
    text-align: center;
    padding: 1rem;
    border: 2px dashed var(--border-color);
    border-radius: var(--border-radius);
    cursor: pointer;
    transition: var(--transition);
    min-width: 80px;
}

.gesture-item:hover {
    border-color: var(--secondary-color);
    background: rgba(52, 152, 219, 0.1);
}

.gesture-item span {
    font-size: 2rem;
    display: block;
    margin-bottom: 0.5rem;
}

.theme-controls {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.feature-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.feature-item {
    text-align: center;
    padding: 1rem;
    background: rgba(52, 152, 219, 0.05);
    border-radius: var(--border-radius);
    cursor: pointer;
    transition: var(--transition);
}

.feature-item:hover {
    background: rgba(52, 152, 219, 0.1);
    transform: translateY(-2px);
}

.feature-emoji {
    font-size: 2rem;
    display: block;
    margin-bottom: 0.5rem;
}

.device-simulator {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: var(--border-radius);
}

.device-buttons {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    margin-bottom: 1rem;
}

.device-info {
    background: white;
    padding: 1rem;
    border-radius: var(--border-radius);
    border: 1px solid var(--border-color);
}

.demo-info {
    background: rgba(39, 174, 96, 0.1);
    padding: 1rem;
    border-radius: var(--border-radius);
    margin-top: 1rem;
}

/* Device simulation styles */
.simulate-mobile {
    max-width: 375px;
    margin: 0 auto;
    border: 3px solid #333;
    border-radius: 20px;
    box-shadow: 0 0 20px rgba(0,0,0,0.3);
}

.simulate-tablet {
    max-width: 768px;
    margin: 0 auto;
    border: 3px solid #555;
    border-radius: 15px;
    box-shadow: 0 0 30px rgba(0,0,0,0.2);
}

.simulate-desktop {
    max-width: 1200px;
    margin: 0 auto;
    border: 2px solid #777;
    border-radius: 5px;
    box-shadow: 0 0 15px rgba(0,0,0,0.1);
}

@media (max-width: 768px) {
    .demo-actions {
        flex-direction: column;
    }

    .demo-actions .btn {
        width: 100%;
    }

    .feature-grid {
        grid-template-columns: 1fr;
    }

    .device-buttons {
        flex-direction: column;
    }

    .gesture-demo {
        flex-direction: column;
        align-items: center;
    }
}
</style>

<?php
// Include responsive footer
include __DIR__ . '/views/layouts/responsive-footer.php';
?>
