/*
 | Teyaqi Admin Dashboard Charts
 |
 | ApexCharts is bundled locally through Vite and exposed globally
 | so Blade pages can initialize charts from inline scripts.
 */

import ApexCharts from 'apexcharts';

window.ApexCharts = ApexCharts;

/*
 | Global ApexCharts defaults
 */
window.Apex = {
    chart: {
        foreColor: '#94a3b8',
        fontFamily: 'inherit',
        toolbar: {
            show: false
        }
    },

    grid: {
        borderColor: 'rgba(148,163,184,0.18)'
    },

    xaxis: {
        axisBorder: {
            color: 'rgba(148,163,184,0.25)'
        },

        axisTicks: {
            color: 'rgba(148,163,184,0.25)'
        }
    },

    legend: {
        labels: {
            colors: '#94a3b8'
        }
    },

    tooltip: {
        theme: 'dark'
    }
};

/*
 | Tell page-level chart scripts that ApexCharts is ready.
 */
window.chartsReady = true;

document.dispatchEvent(
    new CustomEvent('charts:loaded')
);