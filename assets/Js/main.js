
    // Example: Searching the Table
    function searchPatients(query) {
        let rows = document.querySelectorAll('tbody tr');
        rows.forEach(row => {
            let name = row.children[0].textContent.toLowerCase();
            row.style.display = name.includes(query.toLowerCase()) ? "" : "none";
        });
    }
    // Example: Searching the Table
function searchStaff(query) {
    let rows = document.querySelectorAll("tbody tr");
    query = query.toLowerCase();

    rows.forEach(row => {
        // Column 1 contains full staff information block
        let staffColumn = row.children[1].textContent.toLowerCase();

        if (staffColumn.includes(query)) {
            row.style.display = "";
        } else {
            row.style.display = "none";
        }
    });
}

  
