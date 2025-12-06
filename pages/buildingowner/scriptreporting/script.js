const ctx = document.getElementById('myChart');

fetch("script.php")
    .then((response) => response.json())
    .then((data) => {
        const row = data[0];
        const chartData = {
            labels: ['Paid on Time', 'Late Payment'],
            datasets: [{
                label: '# of Payments',
                data: [parseInt(row.paymentontime), parseInt(row.paymentlatetime)],
                backgroundColor: ['#f5e6a2', '#f5c9a2'],
                borderColor: ['#e1c96c', '#e18b6c'],
                borderWidth: 1
            }]
        };

        createChart(chartData, 'bar');
    });

function createChart(chartData, type) {
    new Chart(ctx, {
        type: type,
        data: chartData,
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}
