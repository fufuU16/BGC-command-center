document.addEventListener('DOMContentLoaded', function() {
    const rows = document.querySelectorAll('#busTable tbody tr');

    rows.forEach(row => {
        row.addEventListener('click', function() {
            const busId = this.getAttribute('data-bus-id');
            if (busId) {
                window.location.href = `busDetails.php?bus_id=${encodeURIComponent(busId)}`;
            }
        });
    });

    // Function to search logs
    window.searchLogs = function() {
        const input = document.getElementById('searchInput').value.toLowerCase();
        const rows = document.querySelectorAll('#busTable tbody tr');

        rows.forEach(row => {
            const busId = row.cells[0].textContent.toLowerCase();
            const plateNumber = row.cells[1].textContent.toLowerCase();
            const status = row.cells[3].textContent.toLowerCase();

            if (busId.includes(input) || plateNumber.includes(input) || status.includes(input)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    };

    // Function to export report
    window.exportReport = function() {
        const table = document.getElementById('busTable');
        const rows = Array.from(table.rows);
        const csvContent = rows.map(row => {
            const cells = Array.from(row.cells);
            return cells.map(cell => cell.textContent).join(',');
        }).join('\n');

        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'maintenance_report.csv';
        link.click();
    };
});