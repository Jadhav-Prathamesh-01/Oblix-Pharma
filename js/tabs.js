document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.service-tab');
    const contents = document.querySelectorAll('.service-content');
    let currentTab = 0;
    let autoRotateInterval;

    console.log('Tabs initialized:', tabs.length);
    console.log('Contents initialized:', contents.length);

    // Function to switch tabs
    function switchTab(index) {
        console.log('Switching to tab:', index);
        
        // Remove active class from all tabs and contents
        tabs.forEach(tab => tab.classList.remove('active'));
        contents.forEach(content => content.classList.remove('active'));

        // Add active class to selected tab and content
        if (tabs[index] && contents[index]) {
            tabs[index].classList.add('active');
            contents[index].classList.add('active');
            currentTab = index;
        }
    }

    // Manual tab click
    tabs.forEach((tab, index) => {
        tab.addEventListener('click', function() {
            console.log('Tab clicked:', index);
            switchTab(index);
            // Reset auto-rotation when user clicks
            clearInterval(autoRotateInterval);
            startAutoRotate();
        });
    });

    // Auto-rotate tabs every 5 seconds
    function startAutoRotate() {
        clearInterval(autoRotateInterval);
        autoRotateInterval = setInterval(function() {
            currentTab = (currentTab + 1) % tabs.length;
            console.log('Auto-rotating to tab:', currentTab);
            switchTab(currentTab);
        }, 5000);
    }

    // Start auto-rotation immediately
    if (tabs.length > 0) {
        startAutoRotate();
    }

    // Pause auto-rotation on hover (optional)
    const servicesSection = document.querySelector('.services-section');
    if (servicesSection) {
        servicesSection.addEventListener('mouseenter', function() {
            console.log('Mouse entered - pausing rotation');
            clearInterval(autoRotateInterval);
        });

        servicesSection.addEventListener('mouseleave', function() {
            console.log('Mouse left - resuming rotation');
            startAutoRotate();
        });
    }
});
