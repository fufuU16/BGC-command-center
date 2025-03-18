document.addEventListener('DOMContentLoaded', function () {
    fetchLogs();
});

function fetchLogs() {
    // Fetch logs from the server using Fetch API
    fetch('fetch_logs.php') // Replace with your server endpoint
        .then(response => response.json())
        .then(logs => {
            const tableBody = document.querySelector('#logsTable tbody');
            tableBody.innerHTML = ''; // Clear existing rows

            logs.forEach(log => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${log.driver}</td>
                    <td>${log.bus}</td>
                    <td>${log.date}</td>
                    <td>${log.time}</td>
                    <td>${log.status}</td>
                    <td>${log.moreInfo}</td>
                `;
                tableBody.appendChild(row);
            });
        })
        .catch(error => console.error('Error fetching logs:', error));
}

function searchLogs() {
    const searchInput = document.getElementById('searchInput').value.toLowerCase();
    const tableRows = document.querySelectorAll('#logsTable tbody tr');

    tableRows.forEach(row => {
        const cells = row.querySelectorAll('td');
        const match = Array.from(cells).some(cell => cell.textContent.toLowerCase().includes(searchInput));
        row.style.display = match ? '' : 'none';
    });
}

async function exportReport() {
    exportToExcel();
}

function exportToExcel() {
    const table = document.getElementById('logsTable');
    const wb = XLSX.utils.table_to_book(table, { sheet: "Shift Logs" });

    const ws = wb.Sheets["Shift Logs"];
    const range = XLSX.utils.decode_range(ws['!ref']);

    for (let R = range.s.r + 1; R <= range.e.r; ++R) {
        const dateCellAddress = XLSX.utils.encode_cell({ r: R, c: 2 }); // Date column
        const timeCellAddress = XLSX.utils.encode_cell({ r: R, c: 3 }); // Time column

        const dateCell = ws[dateCellAddress];
        const timeCell = ws[timeCellAddress];

        if (dateCell && dateCell.t === 's' && timeCell && timeCell.t === 's') {
            const date = new Date(dateCell.v + ' ' + timeCell.v);
            if (!isNaN(date.getTime())) {
                dateCell.v = date.toISOString().slice(0, 10); // Format as 'YYYY-MM-DD'
                dateCell.t = 'd'; // Set cell type to date

                timeCell.v = date.toTimeString().slice(0, 8); // Format as 'HH:MM:SS'
                timeCell.t = 's'; // Set cell type to string
            }
        }
    }

    XLSX.writeFile(wb, 'shift_logs.xlsx');
}

function viewDriverList() {
    window.location.href = 'drivers.php';
}