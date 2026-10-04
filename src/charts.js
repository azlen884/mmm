import ApexCharts from 'apexcharts';

window.ApexCharts = ApexCharts;

// Initialize any data-chart elements if present
document.addEventListener('DOMContentLoaded', () => {
    // Dispatch event indicating ApexCharts is loaded
    window.dispatchEvent(new CustomEvent('apexcharts-ready'));
});
