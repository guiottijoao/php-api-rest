import { useEffect } from "react";
import styles from "./HistoryModal.module.css";
import { useDispatch, useSelector } from "react-redux";

function HistoryModal({ isModalOpen, selectedId, onCloseModal }) {
  const dispatch = useDispatch();

  const { items: orderItems, loading: orderItemsLoading } = useSelector(
    (state) => state.orderItems,
  );

  const selectedOrderItems = orderItems.filter(
    (oi) => oi.order_code === selectedId,
  );

  return (
    <>
      {isModalOpen == true && (
        <div className={styles.modalOverlay}>
          <div id={styles.orderProductsModal}>
            <nav>
              <h3 className={styles.OrderDetailsTitle}>Order itens</h3>
              <div onClick={onCloseModal} id={styles.closeModalBtn}>
                <p>X</p>
              </div>
            </nav>
            <table className={styles.orderProductsTable}>
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Amount</th>
                  <th>Unit Price</th>
                  <th>Total tax</th>
                </tr>
              </thead>
              <tbody id={styles.orderProductsTableContent}>
                {selectedOrderItems.map((o) => (
                  <tr key={o.code}>
                    <td>{o.product_name}</td>
                    <td>{o.amount}</td>
                    <td>${o.price}</td>
                    <td>${o.tax}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}
    </>
  );
}

export default HistoryModal;
