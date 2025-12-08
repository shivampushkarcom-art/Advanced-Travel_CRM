/**
 * ATC Enhanced Dashboard JavaScript
 * Charts, real-time updates, and interactions
 */

(function($) {
    'use strict';
    
    const ATCDashboard = {
        charts: {},
        
        init: function() {
            this.initCharts();
            this.setupRefresh();
            this.setupFilters();
        },
        
        initCharts: function() {
            if (typeof Chart === 'undefined') {
                console.warn('Chart.js not loaded');
                return;
            }
            
            // Bookings Trend Chart
            this.initBookingsChart();
            
            // Revenue by Service Chart
            this.initRevenueChart();
            
            // Revenue Trend Chart (Analytics)
            this.initRevenueTrendChart();
            
            // Status Distribution Chart (Analytics)
            this.initStatusChart();
        },
        
        initBookingsChart: function() {
            const ctx = document.getElementById('atc-bookings-chart');
            if (!ctx) return;
            
            const stats = window.atcDashboard?.stats || {};
            const recentBookings = stats.recent_bookings || [];
            
            // Get last 7 days data
            const last7Days = [];
            for (let i = 6; i >= 0; i--) {
                const date = new Date();
                date.setDate(date.getDate() - i);
                last7Days.push(date.toISOString().split('T')[0]);
            }
            
            const bookingsByDate = {};
            recentBookings.forEach(booking => {
                const date = booking.created_at.split(' ')[0];
                bookingsByDate[date] = (bookingsByDate[date] || 0) + 1;
            });
            
            const data = last7Days.map(date => bookingsByDate[date] || 0);
            const labels = last7Days.map(date => {
                const d = new Date(date);
                return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            });
            
            this.charts.bookings = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Bookings',
                        data: data,
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        pointBackgroundColor: '#667eea',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            titleFont: { size: 14, weight: '600' },
                            bodyFont: { size: 13 },
                            cornerRadius: 8
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        },
        
        initRevenueChart: function() {
            const ctx = document.getElementById('atc-revenue-chart');
            if (!ctx) return;
            
            const stats = window.atcDashboard?.stats || {};
            const revenueByService = stats.revenue_by_service || [];
            
            const labels = revenueByService.map(s => s.service.charAt(0).toUpperCase() + s.service.slice(1));
            const data = revenueByService.map(s => parseFloat(s.revenue) || 0);
            
            this.charts.revenue = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: [
                            '#667eea',
                            '#764ba2',
                            '#f093fb',
                            '#4facfe',
                            '#00f2fe',
                            '#43e97b',
                            '#fa709a'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                font: { size: 12 }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const currency = window.atcDashboard?.currency || '₹';
                                    return label + ': ' + currency + value.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                }
                            }
                        }
                    }
                }
            });
        },
        
        initRevenueTrendChart: function() {
            const ctx = document.getElementById('atc-revenue-trend-chart');
            if (!ctx) return;
            
            // This will be populated via AJAX when period changes
            this.charts.revenueTrend = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Revenue',
                        data: [],
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    const currency = window.atcDashboard?.currency || '₹';
                                    return currency + value.toLocaleString('en-IN');
                                }
                            }
                        }
                    }
                }
            });
        },
        
        initStatusChart: function() {
            const ctx = document.getElementById('atc-status-chart');
            if (!ctx) return;
            
            const stats = window.atcDashboard?.stats || {};
            const statusBreakdown = stats.status_breakdown || [];
            
            const labels = statusBreakdown.map(s => s.status.charAt(0).toUpperCase() + s.status.slice(1));
            const data = statusBreakdown.map(s => parseInt(s.count) || 0);
            
            this.charts.status = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Bookings',
                        data: data,
                        backgroundColor: [
                            '#f59e0b',
                            '#10b981',
                            '#3b82f6',
                            '#ef4444'
                        ],
                        borderRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        },
        
        setupRefresh: function() {
            $('#atc-refresh-dashboard').on('click', () => {
                const $btn = $(this);
                $btn.prop('disabled', true).text('🔄 Refreshing...');
                
                $.ajax({
                    url: window.atcDashboard.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'atc_get_dashboard_data',
                        nonce: window.atcDashboard.nonce
                    },
                    success: (response) => {
                        if (response.success) {
                            location.reload();
                        }
                    },
                    complete: () => {
                        $btn.prop('disabled', false).text('🔄 Refresh');
                    }
                });
            });
        },
        
        setupFilters: function() {
            $('#atc-analytics-period').on('change', function() {
                const period = $(this).val();
                
                $.ajax({
                    url: window.atcDashboard.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'atc_get_analytics_data',
                        nonce: window.atcDashboard.nonce,
                        period: period
                    },
                    success: (response) => {
                        if (response.success && ATCDashboard.charts.revenueTrend) {
                            const dailyData = response.data.daily_data || [];
                            const labels = dailyData.map(d => {
                                const date = new Date(d.date);
                                return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                            });
                            const data = dailyData.map(d => parseFloat(d.revenue) || 0);
                            
                            ATCDashboard.charts.revenueTrend.data.labels = labels;
                            ATCDashboard.charts.revenueTrend.data.datasets[0].data = data;
                            ATCDashboard.charts.revenueTrend.update();
                        }
                    }
                });
            });
        }
    };
    
    // Initialize on DOM ready
    $(document).ready(function() {
        ATCDashboard.init();
    });
    
})(jQuery);

