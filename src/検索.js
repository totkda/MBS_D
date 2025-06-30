    document.getElementById('searchBtn').addEventListener('click', () => {
      const filter = document.getElementById('customer_name').value.trim().toLowerCase();
      const table = document.getElementById('orderTable').getElementsByTagName('tbody')[0];
      const rows = table.rows;

      for (let i = 0; i < rows.length; i++) {
        const orderId = rows[i].cells[1].textContent.toLowerCase();
        const customer = rows[i].cells[3].textContent.toLowerCase();

        if (orderId.includes(filter) || customer.includes(filter)) {
          rows[i].style.display = '';
        } else {
          rows[i].style.display = 'none';
        }
      }
    });