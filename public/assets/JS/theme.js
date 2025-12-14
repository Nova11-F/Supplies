function applyDarkTheme() {
    const styleId = 'dark-theme-styles';
    
    const existingStyle = document.getElementById(styleId);
    if (existingStyle) {
        existingStyle.remove();
    }
    
    const style = document.createElement('style');
    style.id = styleId;
    style.textContent = `
        /* Dark Theme Transitions */
        body, .bg-white, .bg-gray-50, .bg-gray-100, .bg-gray-200,
        .text-gray-800, .text-gray-700, .text-gray-600, .text-gray-500 {
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease !important;
        }
        
        /* Body & Main Background */
        body.dark-theme {
            background-color: #0f172a !important;
            color: #e2e8f0 !important;
        }
        
        /* White backgrounds */
        .dark-theme .bg-white {
            background-color: #1e293b !important;
        }
        
        /* Gray backgrounds */
        .dark-theme .bg-gray-50 {
            background-color: #0f172a !important;
        }
        
        .dark-theme .bg-gray-100 {
            background-color: #1e293b !important;
        }
        
        .dark-theme .bg-gray-200 {
            background-color: #334155 !important;
        }
        
        .dark-theme .bg-gray-300 {
            background-color: #475569 !important;
        }
        
        /* Text Colors */
        .dark-theme .text-gray-900,
        .dark-theme .text-gray-800 {
            color: #f1f5f9 !important;
        }
        
        .dark-theme .text-gray-700 {
            color: #e2e8f0 !important;
        }
        
        .dark-theme .text-gray-600 {
            color: #cbd5e1 !important;
        }
        
        .dark-theme .text-gray-500 {
            color: #94a3b8 !important;
        }
        
        .dark-theme .text-gray-400 {
            color: #64748b !important;
        }
        
        .dark-theme .text-black {
            color: #f1f5f9 !important;
        }
        
        /* Border Colors */
        .dark-theme .border-gray-100,
        .dark-theme .border-gray-200 {
            border-color: #334155 !important;
        }
        
        .dark-theme .border-gray-300 {
            border-color: #475569 !important;
        }
        
        .dark-theme .border-white\/20 {
            border-color: rgba(255, 255, 255, 0.1) !important;
        }
        
        /* Sidebar */
        .dark-theme .bg-\\[\\#092363\\] {
            background-color: #0a1628 !important;
        }
        
        .dark-theme .text-\\[\\#e6b949\\] {
            color: #fbbf24 !important;
        }
        
        /* Shadows */
        .dark-theme .shadow-lg,
        .dark-theme .shadow-xl,
        .dark-theme .shadow-md {
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5) !important;
        }
        
        /* Forms & Inputs */
        .dark-theme input[type="text"],
        .dark-theme input[type="email"],
        .dark-theme input[type="password"],
        .dark-theme input[type="tel"],
        .dark-theme input[type="number"],
        .dark-theme input[type="date"],
        .dark-theme input[type="search"],
        .dark-theme textarea,
        .dark-theme select {
            background-color: #0f172a !important;
            color: #e2e8f0 !important;
            border-color: #334155 !important;
        }
        
        .dark-theme input::placeholder,
        .dark-theme textarea::placeholder {
            color: #64748b !important;
        }
        
        .dark-theme input:focus,
        .dark-theme textarea:focus,
        .dark-theme select:focus {
            background-color: #1e293b !important;
            border-color: #3b82f6 !important;
        }
        
        /* Tables */
        .dark-theme table {
            background-color: #1e293b !important;
        }
        
        .dark-theme thead {
            background-color: #0f172a !important;
        }
        
        .dark-theme tbody tr {
            border-bottom-color: #334155 !important;
        }
        
        .dark-theme tbody tr:hover {
            background-color: #334155 !important;
        }
        
        /* Modals */
        .dark-theme .modal-content-box {
            background-color: #1e293b !important;
        }
        
        .dark-theme .modal-backdrop {
            background: rgba(0, 0, 0, 0.85) !important;
        }
        
        /* Notification Dropdown */
        .dark-theme #notificationDropdown {
            background-color: #1e293b !important;
            border-color: #334155 !important;
        }
        
        .dark-theme #notificationDropdown .bg-gray-50 {
            background-color: #0f172a !important;
        }
        
        /* Alert boxes */
        .dark-theme .bg-amber-100 {
            background-color: #451a03 !important;
        }
        
        .dark-theme .bg-blue-100 {
            background-color: #1e3a5f !important;
        }
        
        .dark-theme .bg-red-100 {
            background-color: #450a0a !important;
        }
        
        .dark-theme .bg-green-100 {
            background-color: #14532d !important;
        }
        
        .dark-theme .text-yellow-800,
        .dark-theme .text-yellow-700 {
            color: #fef08a !important;
        }
        
        .dark-theme .text-blue-800,
        .dark-theme .text-blue-700 {
            color: #93c5fd !important;
        }
        
        .dark-theme .text-red-800,
        .dark-theme .text-red-700 {
            color: #fca5a5 !important;
        }
        
        .dark-theme .text-green-800,
        .dark-theme .text-green-700 {
            color: #86efac !important;
        }
        
        /* Brand colors */
        .dark-theme .bg-\\[\\#e6b949\\] {
            background-color: #d4a73f !important;
        }
        
        .dark-theme .text-\\[\\#092363\\] {
            color: #3b82f6 !important;
        }
        
        /* Button colors */
        .dark-theme .bg-blue-500 {
            background-color: #3b82f6 !important;
        }
        
        .dark-theme .bg-red-500 {
            background-color: #ef4444 !important;
        }
        
        .dark-theme .bg-green-500 {
            background-color: #22c55e !important;
        }
        
        .dark-theme .bg-yellow-400 {
            background-color: #fbbf24 !important;
        }
        
        /* Scrollbar */
        .dark-theme ::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }
        
        .dark-theme ::-webkit-scrollbar-track {
            background: #0f172a;
        }
        
        .dark-theme ::-webkit-scrollbar-thumb {
            background: #475569;
            border-radius: 5px;
        }
        
        .dark-theme ::-webkit-scrollbar-thumb:hover {
            background: #64748b;
        }
        
        /* Selection */
        .dark-theme ::selection {
            background-color: #3b82f6;
            color: white;
        }
        
        /* Hover cards */
        .dark-theme .hover\\:shadow-xl:hover {
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6) !important;
        }
        
        /* Keep colored backgrounds readable */
        .dark-theme .bg-\\[\\#092363\\] .text-\\[\\#e6b949\\] {
            color: #fbbf24 !important;
        }
        
        .dark-theme .bg-\\[\\#e6b949\\] .text-\\[\\#092363\\] {
            color: #1e293b !important;
        }
        
        /* Custom modal fix */
        .dark-theme .custom-modal .bg-white {
            background-color: #1e293b !important;
        }
        
        /* Peer states for forms */
        .dark-theme .peer-checked\\:bg-blue-50 {
            background-color: #1e3a5f !important;
        }
    `;
    
    document.head.appendChild(style);
}


function initTheme() {
    
    applyDarkTheme();
    
   
    const savedTheme = localStorage.getItem('theme');
    
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-theme');
        
        const darkRadio = document.querySelector('input[name="theme"][value="dark"]');
        if (darkRadio) darkRadio.checked = true;
    } else {
        document.body.classList.remove('dark-theme');
        
        const lightRadio = document.querySelector('input[name="theme"][value="light"]');
        if (lightRadio) lightRadio.checked = true;
    }
}


function toggleTheme(theme) {
    console.log('Toggling theme to:', theme); // Debug
    
    if (theme === 'dark') {
        document.body.classList.add('dark-theme');
        localStorage.setItem('theme', 'dark');
        showThemeNotification('🌙 Dark theme enabled');
    } else {
        document.body.classList.remove('dark-theme');
        localStorage.setItem('theme', 'light');
        showThemeNotification('☀️ Light theme enabled');
    }
    
    console.log('Body classes:', document.body.className); // Debug
}


function quickToggleTheme() {
    const isDark = document.body.classList.contains('dark-theme');
    toggleTheme(isDark ? 'light' : 'dark');
}


function showThemeNotification(message) {
    let notification = document.getElementById('themeNotification');
    
    if (!notification) {
        notification = document.createElement('div');
        notification.id = 'themeNotification';
        notification.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: #1e293b;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            z-index: 10000;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.3s ease;
            font-size: 14px;
            font-weight: 500;
        `;
        document.body.appendChild(notification);
    }
    
    notification.textContent = message;
    
    setTimeout(() => {
        notification.style.opacity = '1';
        notification.style.transform = 'translateY(0)';
    }, 10);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateY(20px)';
    }, 2000);
}


function createThemeToggleButton() {
    const existing = document.getElementById('floatingThemeToggle');
    if (existing) existing.remove();
    
    const button = document.createElement('button');
    button.id = 'floatingThemeToggle';
    button.onclick = quickToggleTheme;
    
    const isDark = document.body.classList.contains('dark-theme');
    button.innerHTML = `<i class='bx ${isDark ? 'bx-sun' : 'bx-moon'}'></i>`;
    
    button.style.cssText = `
        position: fixed;
        bottom: 20px;
        left: 340px;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: linear-gradient(135deg, #092363 0%, #3b82f6 100%);
        color: white;
        border: none;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        cursor: pointer;
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        transition: all 0.3s ease;
    `;
    
    button.onmouseenter = function() {
        this.style.transform = 'scale(1.1) rotate(15deg)';
    };
    
    button.onmouseleave = function() {
        this.style.transform = 'scale(1) rotate(0deg)';
    };
    
    const originalOnclick = button.onclick;
    button.onclick = function() {
        originalOnclick();
        setTimeout(() => {
            const isDark = document.body.classList.contains('dark-theme');
            this.innerHTML = `<i class='bx ${isDark ? 'bx-sun' : 'bx-moon'}'></i>`;
        }, 100);
    };
    
    document.body.appendChild(button);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTheme);
} else {
    initTheme();
}

document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'D') {
        e.preventDefault();
        quickToggleTheme();
    }
});

window.themeManager = {
    init: initTheme,
    toggle: toggleTheme,
    quickToggle: quickToggleTheme,
    createToggleButton: createThemeToggleButton
};