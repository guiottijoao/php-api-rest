import styles from './HistoryModal.module.css'

function HistoryModal() {
  return (
    <dialog id={styles.orderProductsModal}>
      <nav>
        <h3 className={styles.OrderDetailsTitle}>Order itens</h3>
        <div id={styles.closeModalBtn}>
          <p>X</p>
        </div>
      </nav>
      <table className={styles.orderProductsTable}>
        <tbody>
          <tr>
            <th>Name</th>
            <th>Amount</th>
            <th>Unit Price</th>
            <th>Total tax</th>
          </tr>
        </tbody>
        <tbody id={styles.orderProductsTableContent}></tbody>
      </table>
    </dialog>
  );
}

export default HistoryModal;
