import Chart from 'chart.js/auto';

window.Chart = Chart;

const navigation = document.getElementById('mobile-navigation');
document.querySelector('[data-open-navigation]')?.addEventListener('click', () => {
    navigation.showModal();
    document.body.classList.add('navigation-open');
});
document.querySelector('[data-close-navigation]')?.addEventListener('click', () => navigation.close());
navigation?.addEventListener('click', event => {
    const bounds = navigation.getBoundingClientRect();
    if (event.target === navigation && (event.clientX > bounds.right || event.clientY > bounds.bottom)) navigation.close();
});
navigation?.addEventListener('close', () => document.body.classList.remove('navigation-open'));
