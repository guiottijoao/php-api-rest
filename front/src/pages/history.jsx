import HistoryModal from "../components/HistoryModal/HistoryModal";
import HistoryTable from "../components/HistoryTable/HistoryTable";
import PageTitle from "../components/PageTitle/PageTitle";
import { useDispatch, useSelector } from "react-redux";
import { useEffect, useState } from "react";
import { fetchOrders } from "../store/slices/orderSlice";
import { fetchOrderItems } from "../store/slices/orderItemSlice";

function History() {
  const dispatch = useDispatch();

  const { items: orders, loading: ordersLoading } = useSelector(
    (state) => state.orders,
  );

  const {items: orderItems, loading: orderItemsLoading} = useSelector(
    (state) => state.orderItems
  )

  useEffect(() => {
    dispatch(fetchOrderItems())
    dispatch(fetchOrders())
  }, [dispatch])

  const historyOrders = orders.filter((o) => o.status === 'closed')

  return (
    <div class="container">
      <PageTitle title={"History"} />
      <main class="main-content" id="history-content">
        <HistoryTable order={historyOrders} />

        <HistoryModal />
      </main>
    </div>
  );
}

export default History;
