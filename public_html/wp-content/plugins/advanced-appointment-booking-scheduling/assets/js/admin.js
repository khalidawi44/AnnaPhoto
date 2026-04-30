document.addEventListener('DOMContentLoaded', function () {
    const url = new URL(window.location.href);

    // Existing URL cleaning for staff/services
    if (url.searchParams.has('edit_staff')) {
        url.searchParams.delete('edit_staff');
    }
    if (url.searchParams.get('tab') === 'staff') {
        url.searchParams.delete('tab');
    }
    if (url.searchParams.get('tab') === 'services') {
        url.searchParams.delete('tab');
    }
    window.history.replaceState({}, document.title, url.toString());

    // Smooth tab transitions
    const tabLinks = document.querySelectorAll('.nav-link[data-bs-toggle="tab"]');
    tabLinks.forEach(link => {
        link.addEventListener('shown.bs.tab', function (e) {
            const targetPane = document.querySelector(e.target.getAttribute('data-bs-target'));
            targetPane.style.animation = 'fadeIn 0.6s ease-out';
        });
    });

    // Dashboard count animations
    const counters = document.querySelectorAll('.dashboard-item p[id]');
    const animateCounters = () => {
        counters.forEach(counter => {
            const target = parseInt(counter.textContent);
            const increment = target / 200;
            let current = 0;
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    counter.textContent = target;
                    clearInterval(timer);
                } else {
                    counter.textContent = Math.floor(current);
                }
            }, 10);
        });
    };

    // Trigger animation on dashboard tab or load
    if (document.querySelector('#nav-dashboard.active')) {
        setTimeout(animateCounters, 100);
    }

    // Table row highlights and status badges
    document.querySelectorAll('.widefat tbody tr').forEach(row => {
        row.addEventListener('mouseenter', () => row.style.transform = 'scale(1.01)');
        row.addEventListener('mouseleave', () => row.style.transform = 'scale(1)');
    });

    // Add icons to dashboard items (dynamically)
    const dashboardItems = {
        'total-appointments': 'bi-calendar3-week',
        'total-services': 'bi-gear',
        'approved-appointments': 'bi-check-circle',
        'total-customers': 'bi-people'
    };
    Object.entries(dashboardItems).forEach(([id, icon]) => {
        const item = document.querySelector(`#${id}`);
        if (item && item.parentElement) {
            const iconEl = document.createElement('i');
            iconEl.className = `bi ${icon} dashboard-icon`;
            item.parentElement.insertBefore(iconEl, item);
        }
    });

    document.querySelectorAll('.form-table input, .form-table select, .form-table textarea').forEach(el => {
        el.addEventListener('focus', function() {
            this.parentElement.style.transform = 'scale(1.02)';
        });
        el.addEventListener('blur', function() {
            this.parentElement.style.transform = 'scale(1)';
        });
    });

    document.addEventListener('change', function(e) {
        if (e.target.matches('select[name="appointment_status"]')) {
            const status = e.target.value;
            e.target.style.background = status === 'approved' ? '#d4edda' : 
                                        status === 'pending' ? '#fff3cd' : '#f8d7da';
            e.target.style.color = status === 'approved' ? '#155724' : 
                                   status === 'pending' ? '#856404' : '#721c24';
        }
    });
});

