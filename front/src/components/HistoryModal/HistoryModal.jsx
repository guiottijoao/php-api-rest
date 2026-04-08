function HistoryModal() {
  return (
    <dialog id="order-products-modal">
      <nav>
        <h3 class="order-details-title">Order itens</h3>
        <div id="close-modal-btn">
          <p>X</p>
        </div>
      </nav>
      <table class="order-products-table">
        <tr>
          <th>Name</th>
          <th>Amount</th>
          <th>Unit Price</th>
          <th>Total tax</th>
        </tr>
        <tbody id="order-products-table-content"></tbody>
      </table>
    </dialog>
  );
}

export default HistoryModal;
