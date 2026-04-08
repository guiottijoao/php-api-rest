import { useSelector } from "react-redux";


function OrderSummary({ onCancel }) {
  const activeOrder = useSelector(state => state.orders.items.find(o => o.status === 'open'))

  return (
    <div className="summary">
      <div className="summary-values">
        <div className="summary-info">
          <p id="total-order-tax">Tax: ${activeOrder?.tax} </p>
        </div>
        <div className="summary-info">
          <p id="total-order-price">Total: ${activeOrder?.total} </p>
        </div>
      </div>

      <form className="actions">
        <button
        type="button"
          onClick={() => onCancel(activeOrder?.code)}
          className="cancel-btn"
          id="cancel-btn"
        >
          Cancel
        </button>
        <button
          // onclick="return confirm('Finish order?')"
          // formaction="actions/orders/finishOrder.php"
          id="finish-btn"
        >
          Finish
        </button>
      </form>
    </div>
  );
}

export default OrderSummary;
