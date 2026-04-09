import HistoryModal from "../components/HistoryModal/HistoryModal";
import HistoryTable from "../components/HistoryTable/HistoryTable";
import PageTitle from "../components/PageTitle/PageTitle";
import { useDispatch, useSelector } from "react-redux";
import { useEffect, useState } from "react";
import { fetchOrders } from "../store/slices/orderSlice";
import { fetchOrderItems } from "../store/slices/orderItemSlice";

function History() {
  const dispatch = useDispatch();

  const [isModalOpen, setModalOpen] = useState(false)
  const [selectedId, setSelectedId] = useState(null)

  const { items: orders, loading: ordersLoading } = useSelector(
    (state) => state.orders,
  );

  const {items: orderItems, loading: orderItemsLoading} = useSelector(
    (state) => state.orderItems
  )

  const handleOpenModal = (id) => {
    setModalOpen(true)
    setSelectedId(id)
  }

  const handleCloseModal = () => {
    setModalOpen(false)
    setSelectedId(null)
  }

  useEffect(() => {
    dispatch(fetchOrderItems())
    dispatch(fetchOrders())
  }, [dispatch])

  if (ordersLoading) return <p>Loading</p>

  const historyOrders = orders.filter((o) => o.status === 'closed')

  return (
    <div className="container">
      <PageTitle title={"History"} />
      <main className="main-content" id="history-content">
        <HistoryTable orders={historyOrders} onSetModal={(id) => handleOpenModal(id)} />

        <HistoryModal orders={historyOrders} isModalOpen={isModalOpen} selectedId={selectedId} onCloseModal={handleCloseModal} />
      </main>
    </div>
  );
}

export default History;
