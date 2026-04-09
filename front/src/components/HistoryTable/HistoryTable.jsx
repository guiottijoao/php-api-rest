import styles from './HistoryTable.module.css'

function HistoryTable({ orders, onSetModal }) {
  return (
    <div className="table-container">
      <table>
        <tbody>
          <tr>
            <th>Code</th>
            <th>Tax</th>
            <th>Total</th>
            <th>Product details</th>
          </tr>
        </tbody>
        <tbody id="history-table-body">
          {orders.map((o) => (
            <tr key={o.code}>
              <td>{o.code}</td>
              <td>${o.tax}</td>
              <td>${o.total}</td>
              <td>
                <a onClick={() => onSetModal(o.code)} className={styles.viewBtn}>View</a>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

export default HistoryTable;
