function HistoryTable() {
  return (
    <div class="table-container">
      <table>
        <tr>
          <th>Code</th>
          <th>Tax</th>
          <th>Total</th>
          <th>Product details</th>
        </tr>
        <tbody id="history-table-body">
          <tr>
            <td>code</td>
            <td>$tax</td>
            <td>$total</td>
            <td>
              <a
                onclick="openOrderModal('<?= $order['code'] ?>')"
                class="view-btn"
              >
                View
              </a>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  );
}

export default HistoryTable;
