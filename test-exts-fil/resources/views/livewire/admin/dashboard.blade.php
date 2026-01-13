<div class="min-h-screen bg-gray-50 dark:bg-slate-800 px-4 md:px-0">
    <x-slot:title>
        {{ t('dashboard') }}
    </x-slot:title>

    {{-- version update alert --}}
    @php
        $latest_version = get_setting('whats-mark.whatsmark_latest_version');
        $current_version = get_setting('whats-mark.wm_version');
    @endphp
    @if ($latest_version != null && $latest_version != $current_version && $current_version <= $latest_version)
        <div class="mb-3">
            <div>
                <x-dynamic-alert type="primary">
                    <p>New Update Available! The WhatsMark script has a new update. <a href="/admin/system-update"
                            class="alert-link underline font-semibold">Click here</a> to update the version.</p>
                </x-dynamic-alert>
            </div>
        </div>
    @endif

    <div class="mb-3" x-cloak x-data="{
        appMode: '{{ app()->environment() }}',
        appDebug: @json(config('app.debug')),
        isVisible() {
            return this.appMode === 'local' && this.appDebug;
        }
    }" x-bind:class="{ 'hidden': !isVisible() }">
        <div x-show="isVisible()">
            <x-dynamic-alert type="warning">
                <x-slot:title class="mb-3">{{ t('development_warning_title') }}</x-slot:title>

                {{ t('development_warning_content') }}
                <ul>
                    <li><strong>{{ t('app_env') }}</strong> <span>{{ t('production') }}</span></li>
                    <li><strong>{{ t('app_debug') }}</strong> <span>{{ t('debug_false') }}</span></li>
                </ul>

                {{ t('development_warning_details') }}
                {{ t('performance_security_tip') }}
            </x-dynamic-alert>
        </div>
    </div>
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div class="mb-4 md:mb-0">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    {{ t('welcome_back') }} {{ auth()->user()->firstname ?? 'Admin' }} ! 👋
                </h1>
                <p class="mt-2 text-gray-600 dark:text-gray-300">
                    {{ t('whatsapp_business_update') }}
                </p>
            </div>
            @if (checkPermission('campaigns.create'))
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('admin.campaigns.save') }}">
                        <x-button.primary>
                            <x-heroicon-o-plus class="w-5 h-5 mr-2" />
                            {{ t('new_campaign') }}
                        </x-button.primary>
                    </a>
                </div>
            @endif

        </div>
    </div>
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">

        @foreach ($stats as $stat)
            <div x-data="{ hover: false }" @mouseenter="hover = true" @mouseleave="hover = false"
                class="transform transition-all duration-200 hover:scale-105">
                <div
                    class="relative overflow-hidden bg-white dark:bg-gray-800 rounded-xl shadow-sm dark:shadow-md hover:shadow-md border border-gray-100 dark:border-gray-700">

                    <div class="absolute inset-0 bg-gradient-to-r opacity-0 transition-opacity duration-300"
                        :class="{
                            'opacity-5': hover,
                            'from-blue-500 to-blue-600 dark:from-blue-700 dark:to-blue-800': '{{ $stat['color'] }}'
                            === 'blue',
                            'from-purple-500 to-purple-600 dark:from-purple-700 dark:to-purple-800': '{{ $stat['color'] }}'
                            === 'purple',
                            'from-green-500 to-green-600 dark:from-green-700 dark:to-green-800': '{{ $stat['color'] }}'
                            === 'green',
                            'from-orange-500 to-orange-600 dark:from-orange-700 dark:to-orange-800': '{{ $stat['color'] }}'
                            === 'orange'
                        }">
                    </div>

                    <div class="relative p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 rounded-lg transition-all duration-300"
                                :class="{
                                    'scale-110': hover,
                                    'bg-blue-50 dark:bg-blue-900 text-blue-600 dark:text-blue-300': '{{ $stat['color'] }}'
                                    === 'blue',
                                    'bg-purple-50 dark:bg-purple-900 text-purple-600 dark:text-purple-300': '{{ $stat['color'] }}'
                                    === 'purple',
                                    'bg-green-50 dark:bg-green-900 text-green-600 dark:text-green-300': '{{ $stat['color'] }}'
                                    === 'green',
                                    'bg-orange-50 dark:bg-orange-900 text-orange-600 dark:text-orange-300': '{{ $stat['color'] }}'
                                    === 'orange'
                                }">
                                @switch($stat['icon'])
                                    @case('message-circle')
                                        <x-heroicon-o-chat-bubble-bottom-center-text class="w-8 h-8" />
                                    @break

                                    @case('users')
                                        <x-heroicon-o-user-circle class="w-8 h-8" />
                                    @break

                                    @case('megaphone')
                                        <x-heroicon-o-megaphone class="w-8 h-8" />
                                    @break

                                    @case('file-text')
                                        <x-heroicon-o-document class="w-8 h-8" />
                                    @break

                                    @default
                                        <x-heroicon-o-question-mark-circle class="w-8 h-8 text-gray-400" />
                                @endswitch
                            </div>

                            <div class="ms-3">
                                <div class="text-gray-600 dark:text-gray-300 text-sm font-medium">
                                    {{ t('total') . $stat['header'] }}</div>
                                <div class="text-gray-900 flex justify-end dark:text-white text-2xl font-bold mt-2">
                                    {{ $stat['header_value'] ?? 0 }}</div>
                            </div>
                        </div>
                        <hr class="my-2">

                        <div class="flex justify-between items-center content-center">
                            <h3 class="text-gray-600 dark:text-gray-300 text-sm font-medium">{{ $stat['title'] }}
                            </h3>
                            <p class="text-gray-900 dark:text-white text-xl font-bold mt-2">{{ $stat['value'] }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Message Statistics Chart --}}
    <div class="mt-8">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 space-y-4 sm:space-y-0">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white text-center">
                    {{ t('message_statistics') }}
                </h3>

                <!-- Time Range Buttons - Livewire only -->
                <div class="flex flex-wrap justify-center sm:justify-end gap-1 sm:gap-2 text-xs sm:text-sm">
                    <button id="btn-today" wire:click="updateTimeRange('today')"
                        class="px-2 py-1 sm:px-3 sm:py-1.5 rounded-md transition-colors duration-200 {{ $timeRange === 'today' ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-900/50 dark:text-indigo-400' : 'text-gray-600 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                        {{ t('today') }}
                    </button>
                    <button id="btn-yesterday" wire:click="updateTimeRange('yesterday')"
                        class="px-2 py-1 sm:px-3 sm:py-1.5 rounded-md transition-colors duration-200 {{ $timeRange === 'yesterday' ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-900/50 dark:text-indigo-400' : 'text-gray-600 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                        {{ t('yesterday') }}
                    </button>
                    <button id="btn-this_week" wire:click="updateTimeRange('this_week')"
                        class="px-2 py-1 sm:px-3 sm:py-1.5 rounded-md transition-colors duration-200 {{ $timeRange === 'this_week' ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-900/50 dark:text-indigo-400' : 'text-gray-600 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                        {{ t('this_week') }}
                    </button>
                    <button id="btn-last_week" wire:click="updateTimeRange('last_week')"
                        class="px-2 py-1 sm:px-3 sm:py-1.5 rounded-md transition-colors duration-200 {{ $timeRange === 'last_week' ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-900/50 dark:text-indigo-400' : 'text-gray-600 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                        {{ t('last_week') }}
                    </button>
                    <button id="btn-month" wire:click="updateTimeRange('month')"
                        class="px-2 py-1 sm:px-3 sm:py-1.5 rounded-md transition-colors duration-200 {{ $timeRange === 'month' ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-900/50 dark:text-indigo-400' : 'text-gray-600 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                        {{ t('month') }}
                    </button>
                </div>

            </div>

            <div wire:loading class="w-full h-72 flex items-center justify-center">
                <div class="flex items-center justify-center space-x-2 animate-pulse">
                    <div class="w-4 h-4 bg-indigo-500 rounded-full"></div>
                    <div class="w-4 h-4 bg-indigo-500 rounded-full"></div>
                    <div class="w-4 h-4 bg-indigo-500 rounded-full"></div>
                </div>
            </div>

            <!-- Chart Display -->
            <div wire:loading.remove>
                <div id="message-stats-chart" class="h-72 w-full"></div>
            </div>
        </div>
    </div>
</div>

</div>
@script
    <script>
        let lastSelectedTimeRange = '{{ $timeRange }}';
        let chartInitialized = false;
        let chartData = @json($chartData);
        let chartUpdateTimeout;

        // Initialize chart when page loads
        setTimeout(() => {
            initChart(chartData);
            chartInitialized = true;
        }, 2000)

        // Function to change time range with immediate visual feedback
        function changeTimeRange(timeRange, buttonElement) {

            // Update button states immediately
            document.querySelectorAll('[id^="btn-"]').forEach(btn => {
                btn.classList.remove('bg-indigo-50', 'text-indigo-600', 'dark:bg-indigo-900/50',
                    'dark:text-indigo-400');
                btn.classList.add('text-gray-600', 'hover:text-indigo-600', 'dark:text-gray-400',
                    'dark:hover:text-indigo-400', 'hover:bg-gray-50', 'dark:hover:bg-gray-700');
            });

            buttonElement.classList.remove('text-gray-600', 'hover:text-indigo-600', 'dark:text-gray-400',
                'dark:hover:text-indigo-400', 'hover:bg-gray-50', 'dark:hover:bg-gray-700');
            buttonElement.classList.add('bg-indigo-50', 'text-indigo-600', 'dark:bg-indigo-900/50',
                'dark:text-indigo-400');

            // Show loading indicator
            const chartElement = document.querySelector('#message-stats-chart');
            if (chartElement) {
                chartElement.style.opacity = '0.5';
            }

            // Store the selected time range for comparison
            lastSelectedTimeRange = timeRange;

            // Set a timeout to force chart update if no event is received
            clearTimeout(chartUpdateTimeout);
            chartUpdateTimeout = setTimeout(function() {
                // Force chart update after 2 seconds if no event received
                forceFetchChartData(timeRange);
            }, 2000);
        }

        // Function to force fetch chart data for the current time range
        function forceFetchChartData(timeRange) {

            // Use a direct fetch request to get the data
            fetch(`/admin/ajax/chart-data?timeRange=${timeRange}`, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    updateChart(data);

                    // Show the chart again
                    const chartElement = document.querySelector('#message-stats-chart');
                    if (chartElement) {
                        chartElement.style.opacity = '1';
                    }
                })
                .catch(error => {
                    console.error('Failed to fetch chart data:', error);

                    // Show the chart again even if error
                    const chartElement = document.querySelector('#message-stats-chart');
                    if (chartElement) {
                        chartElement.style.opacity = '1';
                    }
                });
        }

        // Initialize chart
        function initChart(chartData) {

            // Process the data
            let processedData = processChartData(chartData);
            if (!processedData) return;

            const options = {
                chart: {
                    type: 'area',
                    height: 350,
                    toolbar: {
                        show: false
                    },
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 800,
                        animateGradually: {
                            enabled: true,
                            delay: 150
                        },
                        dynamicAnimation: {
                            enabled: true,
                            speed: 350
                        }
                    }
                },
                colors: ['#4f46e5'],
                fill: {
                    opacity: 0.3
                },
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: '60%',
                        borderRadius: 4
                    },
                },
                dataLabels: {
                    enabled: true
                },
                stroke: {
                    curve: 'smooth',
                    width: 3
                },
                xaxis: {
                    categories: processedData.labels,
                    axisBorder: {
                        show: false
                    },
                    axisTicks: {
                        show: false
                    }
                },
                yaxis: {
                    title: {
                        text: 'Messages'
                    },
                    min: 0,
                    forceNiceScale: true,
                    labels: {
                        formatter: function(val) {
                            return Math.floor(val);
                        }
                    }
                },
                fill: {
                    opacity: 1
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return val + " messages";
                        }
                    }
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'right',
                    offsetY: -10
                },
                grid: {
                    borderColor: '#e5e7eb',
                    strokeDashArray: 4,
                    xaxis: {
                        lines: {
                            show: true
                        }
                    }
                },
                theme: {
                    mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light',
                    palette: 'palette1'
                }
            };

            const chartElement = document.querySelector('#message-stats-chart');
            if (!chartElement) {
                console.error('Chart element not found!');
                return;
            }

            // Destroy existing chart if it exists
            if (window.messageStatsChart) {
                window.messageStatsChart.destroy();
            }

            // Create new chart
            window.messageStatsChart = new ApexCharts(chartElement, {
                ...options,
                series: processedData.series
            });

            window.messageStatsChart.render();

            // Update when theme changes
            const observer = new MutationObserver(mutations => {
                mutations.forEach(mutation => {
                    if (mutation.attributeName === 'class') {
                        const isDarkMode = document.documentElement.classList.contains('dark');
                        window.messageStatsChart.updateOptions({
                            theme: {
                                mode: isDarkMode ? 'dark' : 'light',
                                palette: 'palette1'
                            },
                            grid: {
                                borderColor: isDarkMode ? '#374151' : '#e5e7eb'
                            }
                        });
                    }
                });
            });

            observer.observe(document.documentElement, {
                attributes: true
            });
        }

        // Update existing chart
        function updateChart(newChartData) {
            let processedData = processChartData(newChartData);
            if (!processedData) return;

            if (!window.messageStatsChart) {
                initChart(newChartData);
                return;
            }

            window.messageStatsChart.updateOptions({
                xaxis: {
                    categories: processedData.labels
                }
            });

            window.messageStatsChart.updateSeries(processedData.series);
        }

        // Process chart data
        function processChartData(chartData) {
            // If we need to extract from the complex structure
            let processedData = chartData;

            // Parse if string
            if (typeof chartData === 'string') {
                try {
                    processedData = JSON.parse(chartData);
                } catch (e) {
                    console.error('Failed to parse chart data:', e);
                    return null;
                }
            }

            // Handle array format (Livewire sometimes wraps data in an array)
            if (Array.isArray(processedData) && processedData.length === 1) {
                processedData = processedData[0];
            }

            // Validate data structure
            if (!processedData || !processedData.labels || !processedData.series) {
                console.error('Invalid chart data structure:', processedData);
                return null;
            }

            return processedData;
        }

        // Listen for Livewire events, but don't rely on them exclusively
        document.addEventListener('livewire:load', function() {
            // Register event listener for chart data updates via Livewire event
            Livewire.on('chartDataUpdated', function(newChartData) {
                clearTimeout(chartUpdateTimeout);

                // Show the chart again at full opacity
                const chartElement = document.querySelector('#message-stats-chart');
                if (chartElement) {
                    chartElement.style.opacity = '1';
                }

                // Update the chart with new data
                chartData = newChartData;
                updateChart(newChartData);
            });
        });
    </script>
@endscript
