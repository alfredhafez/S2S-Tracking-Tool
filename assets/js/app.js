/**
 * S2S Postback Testing Tool - JavaScript Interactions
 */

document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

/**
 * Initialize the application
 */
function initializeApp() {
    initializeNavbar();
    initializeCopyButtons();
    initializeFormValidation();
    initializeScrollEffects();
    initializeTooltips();
    initializeCharts();
}

/**
 * Navbar effects
 */
function initializeNavbar() {
    const navbar = document.querySelector('.navbar');
    if (!navbar) return;
    
    let lastScrollTop = 0;
    
    window.addEventListener('scroll', function() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        if (scrollTop > 100) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
        
        // Hide navbar on scroll down, show on scroll up
        if (scrollTop > lastScrollTop && scrollTop > 200) {
            navbar.style.transform = 'translateY(-100%)';
        } else {
            navbar.style.transform = 'translateY(0)';
        }
        
        lastScrollTop = scrollTop;
    });
}

/**
 * Copy to clipboard functionality
 */
function initializeCopyButtons() {
    document.querySelectorAll('.copy-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const text = this.getAttribute('data-copy') || 
                         this.previousElementSibling?.textContent ||
                         this.parentElement?.querySelector('.code-block')?.textContent;
            
            if (text) {
                copyToClipboard(text.trim());
            }
        });
    });
}

/**
 * Copy text to clipboard
 */
function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(function() {
            showCopyFeedback();
        }).catch(function() {
            fallbackCopyToClipboard(text);
        });
    } else {
        fallbackCopyToClipboard(text);
    }
}

/**
 * Fallback copy method for older browsers
 */
function fallbackCopyToClipboard(text) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-9999px';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
        document.execCommand('copy');
        showCopyFeedback();
    } catch (err) {
        console.error('Failed to copy text: ', err);
        showNotification('Failed to copy to clipboard', 'error');
    }
    
    document.body.removeChild(textArea);
}

/**
 * Show copy feedback
 */
function showCopyFeedback() {
    const btn = event.target;
    const originalText = btn.textContent;
    
    btn.textContent = 'Copied!';
    btn.style.background = 'rgba(34, 197, 94, 0.3)';
    btn.style.color = '#4ade80';
    
    setTimeout(() => {
        btn.textContent = originalText;
        btn.style.background = '';
        btn.style.color = '';
    }, 2000);
}

/**
 * Form validation
 */
function initializeFormValidation() {
    // URL validation
    document.querySelectorAll('input[type="url"]').forEach(input => {
        input.addEventListener('input', function() {
            validateUrl(this);
        });
    });
    
    // Macro validation
    document.querySelectorAll('input[data-validate="no-macros"]').forEach(input => {
        input.addEventListener('input', function() {
            validateNoUnresolvedMacros(this);
        });
    });
    
    // Form submission handling
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
                return false;
            }
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                showLoadingState(submitBtn);
            }
        });
    });
}

/**
 * URL validation
 */
function validateUrl(input) {
    const url = input.value.trim();
    const urlPattern = /^https?:\/\/.+/;
    
    if (url && !urlPattern.test(url)) {
        input.setCustomValidity('Please enter a valid URL starting with http:// or https://');
        input.classList.add('invalid');
    } else {
        input.setCustomValidity('');
        input.classList.remove('invalid');
    }
}

/**
 * Check for unresolved macros
 */
function validateNoUnresolvedMacros(input) {
    const value = input.value;
    const macroPattern = /\{[a-zA-Z_][a-zA-Z0-9_]*\}/g;
    const macros = value.match(macroPattern);
    
    if (macros) {
        input.setCustomValidity(`Contains unresolved macros: ${macros.join(', ')}`);
        input.classList.add('invalid');
    } else {
        input.setCustomValidity('');
        input.classList.remove('invalid');
    }
}

/**
 * Validate entire form
 */
function validateForm(form) {
    const inputs = form.querySelectorAll('input, textarea, select');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.checkValidity()) {
            isValid = false;
            input.classList.add('invalid');
        } else {
            input.classList.remove('invalid');
        }
    });
    
    return isValid;
}

/**
 * Show loading state on button
 */
function showLoadingState(button) {
    const originalText = button.textContent;
    button.disabled = true;
    button.innerHTML = originalText + ' <span class="spinner"></span>';
    
    // Auto-restore after 10 seconds as fallback
    setTimeout(() => {
        if (button.disabled) {
            button.disabled = false;
            button.textContent = originalText;
        }
    }, 10000);
}

/**
 * Scroll effects
 */
function initializeScrollEffects() {
    // Create scroll to top button
    const scrollBtn = document.createElement('button');
    scrollBtn.className = 'scroll-to-top';
    scrollBtn.innerHTML = '↑';
    scrollBtn.setAttribute('aria-label', 'Scroll to top');
    document.body.appendChild(scrollBtn);
    
    // Show/hide scroll to top button
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            scrollBtn.classList.add('visible');
        } else {
            scrollBtn.classList.remove('visible');
        }
    });
    
    // Scroll to top functionality
    scrollBtn.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
    
    // Animate elements on scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animation = 'slideInUp 0.6s ease forwards';
            }
        });
    }, observerOptions);
    
    document.querySelectorAll('.card, .stat-card').forEach(el => {
        observer.observe(el);
    });
}

/**
 * Initialize tooltips
 */
function initializeTooltips() {
    document.querySelectorAll('[title]').forEach(element => {
        element.addEventListener('mouseenter', showTooltip);
        element.addEventListener('mouseleave', hideTooltip);
    });
}

/**
 * Show tooltip
 */
function showTooltip(e) {
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip';
    tooltip.textContent = e.target.getAttribute('title');
    
    // Remove title to prevent default tooltip
    e.target.setAttribute('data-title', e.target.getAttribute('title'));
    e.target.removeAttribute('title');
    
    document.body.appendChild(tooltip);
    
    const rect = e.target.getBoundingClientRect();
    tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
    tooltip.style.top = rect.top - tooltip.offsetHeight - 10 + 'px';
    
    setTimeout(() => tooltip.classList.add('visible'), 10);
}

/**
 * Hide tooltip
 */
function hideTooltip(e) {
    const tooltip = document.querySelector('.tooltip');
    if (tooltip) {
        tooltip.remove();
    }
    
    // Restore title
    const title = e.target.getAttribute('data-title');
    if (title) {
        e.target.setAttribute('title', title);
        e.target.removeAttribute('data-title');
    }
}

/**
 * Initialize charts (if chart library is available)
 */
function initializeCharts() {
    // Placeholder for chart initialization
    // This would integrate with Chart.js or similar library
    console.log('Charts initialized');
}

/**
 * Show notification
 */
function showNotification(message, type = 'info', duration = 5000) {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} notification`;
    notification.textContent = message;
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.zIndex = '9999';
    notification.style.maxWidth = '400px';
    notification.style.animation = 'slideInRight 0.3s ease';
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, duration);
}

/**
 * Auto-refresh functionality
 */
function enableAutoRefresh(intervalSeconds = 30) {
    if (intervalSeconds > 0) {
        setTimeout(() => {
            window.location.reload();
        }, intervalSeconds * 1000);
        
        // Show countdown
        const countdown = document.createElement('div');
        countdown.style.position = 'fixed';
        countdown.style.bottom = '20px';
        countdown.style.left = '20px';
        countdown.style.background = 'rgba(0, 0, 0, 0.7)';
        countdown.style.color = 'white';
        countdown.style.padding = '10px';
        countdown.style.borderRadius = '5px';
        countdown.style.fontSize = '12px';
        countdown.style.zIndex = '1000';
        document.body.appendChild(countdown);
        
        let remaining = intervalSeconds;
        const timer = setInterval(() => {
            remaining--;
            countdown.textContent = `Auto-refresh in ${remaining}s`;
            
            if (remaining <= 0) {
                clearInterval(timer);
            }
        }, 1000);
    }
}

/**
 * Confirmation dialogs
 */
function confirmDelete(itemName) {
    return confirm(`Are you sure you want to delete "${itemName}"? This action cannot be undone.`);
}

/**
 * AJAX helpers
 */
function makeRequest(url, options = {}) {
    const defaultOptions = {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    };
    
    const config = { ...defaultOptions, ...options };
    
    return fetch(url, config)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .catch(error => {
            console.error('Request failed:', error);
            showNotification('Request failed: ' + error.message, 'error');
            throw error;
        });
}

/**
 * Utility functions
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// Add CSS for animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInUp {
        from {
            transform: translateY(30px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
    
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    .tooltip {
        position: absolute;
        background: rgba(0, 0, 0, 0.9);
        color: white;
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 12px;
        white-space: nowrap;
        z-index: 10000;
        opacity: 0;
        transition: opacity 0.3s ease;
        pointer-events: none;
    }
    
    .tooltip.visible {
        opacity: 1;
    }
    
    .tooltip::after {
        content: '';
        position: absolute;
        top: 100%;
        left: 50%;
        margin-left: -5px;
        border-width: 5px;
        border-style: solid;
        border-color: rgba(0, 0, 0, 0.9) transparent transparent transparent;
    }
    
    .invalid {
        border-color: #ef4444 !important;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important;
    }
    
    .notification {
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    }
`;
document.head.appendChild(style);