// ไฟล์: report-chart.js

document.addEventListener("DOMContentLoaded", function() {
    // 1. อ้างอิง Element canvas
    const ctx = document.getElementById('reportChart');

    // 2. ดึงค่าข้อมูลจาก data-attributes ที่เราฝากไว้ใน HTML
    // (แปลงจาก String เป็น Integer ด้วย parseInt)
    const openCount = parseInt(ctx.dataset.open);
    const closedCount = parseInt(ctx.dataset.closed);

    // 3. สร้างกราฟ
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['รอดำเนินการ (Open)', 'แก้ไขแล้ว (Closed)'],
            datasets: [{
                data: [openCount, closedCount], // ใช้ค่าตัวแปรที่เราดึงมา
                backgroundColor: ['#dc3545', '#198754'],
                hoverOffset: 4
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
});