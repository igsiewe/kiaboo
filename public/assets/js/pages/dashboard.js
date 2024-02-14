$(document).ready(function () {
    var options1 = {
        chart: {
            height: 350,
            type: 'line',
            toolbar: {
                show: false,
            }
        },
        dataLabels: {
            enabled: false
        },
        stroke: {
            curve: 'smooth'
        },
        colors: ['#b3baff','#90e0db'],
        series: [{
            name: 'Withdrawal',
            data: [70, 79, 58 ]
        }, {
            name: 'Deposite',
            data: [81, 62, 64, ]
        }],

        xaxis: {
            type: 'month',
            categories: ["Jan.", "Feb.", "Mar.", "Apr.", "May", "Jun.", "Jul.", "Aug.", "Sep.", "Oct.", "Nov.", "Dec."],
            labels: {
                style: {
                    colors: 'rgba(94, 96, 110, .5)'
                }
            }
        },
        // dataLabels: {
        //     enabled: true,
        // },
        grid: {
            borderColor: 'rgba(94, 96, 110, .5)',
            strokeDashArray: 4
        }
    }

    var chart1 = new ApexCharts(
        document.querySelector("#apex1"),
        options1
    );

    chart1.render();

    var options2 = {
        series: [{
            name: 'Series 1',
            data: [20, 100, 40, 30, 50, 80, 33]
        }],
        chart: {
            height: 337,
            type: 'radar',
            toolbar: {
                show: false,
            }
        },
        dataLabels: {
            enabled: true
        },
        plotOptions: {
            radar: {
                size: 140,
                polygons: {
                    strokeColors: '#e9e9e9',
                    fill: {
                        colors: ['#f8f8f8', '#fff']
                    }
                }
            }
        },
        colors: ['#EE6E83'],
        markers: {
            size: 4,
            colors: ['#fff'],
            strokeColor: '#FF4560',
            strokeWidth: 2,
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return val
                }
            }
        },
        xaxis: {
            categories: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']
        },
        yaxis: {
            tickAmount: 7,
            labels: {
                formatter: function (val, i) {
                    if (i % 2 === 0) {
                        return val
                    } else {
                        return ''
                    }
                }
            }
        }
    };

    var chart2 = new ApexCharts(document.querySelector("#apex2"), options2);
    chart2.render();

    var options3 = {
        series: [{
            name: 'Deposite',
            type: 'line',
            data: [15.4, 12, 20.5, 10.5, 20.5, 26.8, 21.8, 15.6, 22.5, 21.8, 24.8, 26.6]
        }, {
            name: 'Withdrawal',
            type: 'line',
            data: [13.1, 11, 19.1, 12, 17.1, 22.9, 17.5, 15.5, 20.5, 17.8, 22.8, 23.6]
        }, {
            name: 'Revenue',
            type: 'line',
            data: [5, 4, 7, 5, 3, 5, 6, 5, 7, 6, 4,9]
        }],
        chart: {
            height: 350,
            type: 'line',
            stacked: false
        },
        dataLabels: {
            enabled: false
        },
        stroke: {
            width: [1, 1, 4]
        },
        title: {
            text: 'Année 2023',
            align: 'left',
            offsetX: 110
        },
        xaxis: {
            categories: [2009, 2010, 2011, 2012, 2013, 2014, 2015, 2016],
        },
        yaxis: [
            {
                axisTicks: {
                    show: true,
                },
                axisBorder: {
                    show: true,
                    color: '#008FFB'
                },
                labels: {
                    style: {
                        colors: '#008FFB',
                    }
                },
                title: {
                    text: "Deposite",
                    style: {
                        color: '#008FFB',
                    }
                },
                tooltip: {
                    enabled: true
                }
            },
            {
                seriesName: 'Withdrawal',
                opposite: true,
                axisTicks: {
                    show: true,
                },
                axisBorder: {
                    show: true,
                    color: '#00E396'
                },
                labels: {
                    style: {
                        colors: '#00E396',
                    }
                },
                title: {
                    text: "Operating Cashflow",
                    style: {
                        color: '#00E396',
                    }
                },
            },
            {
                seriesName: 'Revenue',
                opposite: true,
                axisTicks: {
                    show: true,
                },
                axisBorder: {
                    show: true,
                    color: '#FEB019'
                },
                labels: {
                    style: {
                        colors: '#FEB019',
                    },
                },
                title: {
                    text: "Revenue",
                    style: {
                        color: '#FEB019',
                    }
                }
            },
        ],
        tooltip: {
            fixed: {
                enabled: true,
                position: 'topLeft', // topRight, topLeft, bottomRight, bottomLeft
                offsetY: 30,
                offsetX: 60
            },
        },
        legend: {
            horizontalAlign: 'center',
            offsetX: 40
        }
    };

    var chart = new ApexCharts(document.querySelector("#apex3"), options3);
    chart.render();
});
