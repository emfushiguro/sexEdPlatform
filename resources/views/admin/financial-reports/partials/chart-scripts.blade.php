<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    (function () {
        const canvas = document.getElementById('financialTrendChart');
        if (!canvas || typeof Chart === 'undefined') {
            return;
        }

        const trend = @json($trend);
        const labels = trend.labels || [];
        const gross = trend.gross || [];
        const net = trend.net || [];

        new Chart(canvas.getContext('2d'), {
            type: 'line',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Gross Revenue',
                        data: gross,
                        borderColor: '#730DB1',
                        backgroundColor: 'rgba(115, 13, 177, 0.15)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.25,
                    },
                    {
                        label: 'Net Revenue',
                        data: net,
                        borderColor: '#3B0CB1',
                        backgroundColor: 'rgba(59, 12, 177, 0.08)',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.25,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                    },
                },
            },
        });
    })();
</script>
