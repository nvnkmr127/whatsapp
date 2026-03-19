@props(['data', 'height' => '400px'])

<div 
    x-data="{
        data: @js($data),
        chart: null,
        initChart() {
            if (typeof Chart === 'undefined') {
                setTimeout(() => this.initChart(), 100);
                return;
            }
            if (this.chart) this.chart.destroy();
            const ctx = this.$refs.canvas.getContext('2d');
            const isDark = document.documentElement.classList.contains('dark');
            
            const gradientSent = ctx.createLinearGradient(0, 0, 0, 400);
            gradientSent.addColorStop(0, 'rgba(37, 211, 102, 0.4)');
            gradientSent.addColorStop(1, 'rgba(37, 211, 102, 0)');

            const gradientReceived = ctx.createLinearGradient(0, 0, 0, 400);
            gradientReceived.addColorStop(0, 'rgba(18, 140, 126, 0.4)');
            gradientReceived.addColorStop(1, 'rgba(18, 140, 126, 0)');

            this.chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: this.data.labels,
                    datasets: this.data.datasets.map((ds, index) => ({
                        ...ds,
                        borderColor: index === 0 ? '#25D366' : '#128C7E',
                        backgroundColor: index === 0 ? gradientSent : gradientReceived,
                        fill: true,
                        tension: 0.4,
                        borderWidth: 4,
                        pointRadius: 0,
                        pointHoverRadius: 6,
                        pointBackgroundColor: index === 0 ? '#25D366' : '#128C7E',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 3,
                    }))
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                display: true,
                                color: isDark ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.05)',
                                drawBorder: false,
                            },
                        },
                        x: {
                            grid: { display: false },
                        }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: isDark ? '#1e293b' : '#fff',
                            titleColor: isDark ? '#f8fafc' : '#1e293b',
                            padding: 15,
                            cornerRadius: 16,
                        }
                    }
                }
            });
        }
    }"
    x-init="initChart()"
    @chart-update.window="data = $event.detail; initChart()"
    class="w-full relative"
    style="height: {{ $height }}"
>
    <canvas x-ref="canvas"></canvas>
</div>
