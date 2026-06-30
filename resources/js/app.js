import Chart from 'chart.js/auto';

const mobileMenuButton = document.querySelector('[data-mobile-menu-button]');
const mobileMenu = document.querySelector('[data-mobile-menu]');

window.Chart = Chart;

document.addEventListener('DOMContentLoaded', () => {
    initStatistikMahasiswaCharts(Chart);
});

if (mobileMenuButton && mobileMenu) {
    const openIcon = mobileMenuButton.querySelector('[data-menu-open-icon]');
    const closeIcon = mobileMenuButton.querySelector('[data-menu-close-icon]');

    const closeMobileMenu = () => {
        mobileMenu.classList.add('hidden');
        mobileMenuButton.setAttribute('aria-expanded', 'false');
        openIcon?.classList.remove('hidden');
        closeIcon?.classList.add('hidden');
    };

    mobileMenuButton.addEventListener('click', () => {
        const isOpen = mobileMenuButton.getAttribute('aria-expanded') === 'true';

        mobileMenu.classList.toggle('hidden', isOpen);
        mobileMenuButton.setAttribute('aria-expanded', String(!isOpen));
        openIcon?.classList.toggle('hidden', !isOpen);
        closeIcon?.classList.toggle('hidden', isOpen);
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth >= 768) {
            closeMobileMenu();
        }
    });
}

