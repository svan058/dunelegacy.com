// Dune Legacy Website JavaScript

// Smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Add download handlers (update with actual release URLs)
document.addEventListener('DOMContentLoaded', () => {
    // TODO: Update these URLs with actual GitHub release URLs
    const downloadLinks = {
        windows: 'https://github.com/dunelegacy/dunelegacy/releases/latest/download/DuneLegacy-Windows-x64.exe',
        windowsZip: 'https://github.com/dunelegacy/dunelegacy/releases/latest/download/DuneLegacy-Windows-x64.zip',
        macos: 'https://github.com/dunelegacy/dunelegacy/releases/latest/download/DuneLegacy-macOS.dmg',
        linuxDeb: 'https://github.com/dunelegacy/dunelegacy/releases/latest/download/DuneLegacy-Linux-x64.deb',
        linuxRpm: 'https://github.com/dunelegacy/dunelegacy/releases/latest/download/DuneLegacy-Linux-x64.rpm',
        linuxTar: 'https://github.com/dunelegacy/dunelegacy/releases/latest/download/DuneLegacy-Linux-x64.tar.gz'
    };
    
    // You can update download links dynamically here if needed
    console.log('Dune Legacy website loaded');
});

