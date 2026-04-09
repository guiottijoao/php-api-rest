import PageTitle from "../components/PageTitle/PageTitle.jsx";
import Form from "../components/Form/Form.jsx";
import Table from "../components/Table/Table.jsx";
import OrderSumary from "../components/OrderSummary/OrderSummary.jsx";
import { useDispatch, useSelector } from "react-redux";
import { useState, useEffect, useMemo } from "react";
import { fetchCategories } from "../store/slices/categorySlice.js";
import {
  fetchOrders,
  cancelOrder,
  finishOrder,
} from "../store/slices/orderSlice.js";
import { fetchProducts, getProductById } from "../store/slices/productSlice.js";
import {
  createOrderItem,
  fetchOrderItems,
  deleteOrderItem,
  clearError,
} from "../store/slices/orderItemSlice.js";
import Swal from "sweetalert2";

function Products() {
  const dispatch = useDispatch();

  const findNameById = (id, data) => {
    const found = data.find((c) => c.code == id);
    return found?.name;
  };

  const formFields = [
    { name: "amount", type: "number", placeholder: "Amount" },
    {
      name: "tax",
      type: "text",
      placeholder: "Category name",
      placeholder: "Tax",
    },
    {
      name: "price",
      type: "text",
      placeholder: "Price",
    },
  ];

  const [form, setForm] = useState({
    amount: "",
    product_code: "",
    price: "",
    tax: "",
  });

  const { items: categories, loading: categoriesLoading } = useSelector(
    (state) => state.categories,
  );

  const { items: products, loading: productsLoading } = useSelector(
    (state) => state.products,
  );

  const {
    items: orderItems,
    loading: orderItemsLoading,
    error,
  } = useSelector((state) => state.orderItems);

  useEffect(() => {
    dispatch(fetchCategories());
    dispatch(fetchProducts());
    dispatch(fetchOrderItems());
    dispatch(fetchOrders());
  }, [dispatch]);

  const activeCategories = categories.filter((c) => c.status === "active");
  const activeProducts = products.filter((p) => p.status === "active");
  const activeOrderItems = useMemo(
    () => orderItems.filter((o) => o.order_status === "open"),
    [orderItems],
  );

  const columns = [
    { key: "product_code", label: "Product" },
    { key: "amount", label: "Amount" },
    { key: "price", label: "Unit price", format: "currency" },
    { key: "tax", label: "Tax", format: "currency" },
    { key: "total", label: "Total", format: "currency" },
  ];

  if (productsLoading || categoriesLoading || orderItemsLoading)
    return <p>Loading...</p>;

  const handleGetProduct = async (id) => {
    const result = await dispatch(getProductById(id));
    if (getProductById.fulfilled.match(result)) {
      dispatch(clearError());
    }
    return result.payload;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    const result = await dispatch(createOrderItem(form));
    if (createOrderItem.rejected.match(result)) {
      Swal.fire({
        icon: "error",
        title: "Error",
        text: result.payload,
      });
    }
    if (createOrderItem.fulfilled.match(result)) {
      setForm({ amount: "", product_code: "", price: "", tax: "" });
      dispatch(fetchOrders());
      dispatch(fetchOrderItems());
      dispatch(clearError());
    }
  };

  const handleDeleteOrderItem = async (id) => {
    const result = await Swal.fire({
      title: "Are you sure?",
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Delete",
      cancelButtonText: "Cancel",
    });

    if (result.isConfirmed) {
      const action = await dispatch(deleteOrderItem(id));
      dispatch(fetchOrders());

      if (deleteOrderItem.rejected.match(action)) {
        Swal.fire({
          icon: "error",
          title: "Error",
          text: action.payload,
        });
      }
    }
  };

  const handleCancelOrder = async (id) => {
    if (!activeOrderItems.length) return;
    const result = await Swal.fire({
      title: "Cancel order?",
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Yes",
      cancelButtonText: "Cancel",
    });

    if (result.isConfirmed) {
      const action = await dispatch(cancelOrder(id));
      dispatch(fetchOrderItems());
      dispatch(fetchOrders());

      if (cancelOrder.rejected.match(action)) {
        Swal.fire({
          icon: "error",
          title: "Error",
          text: action.payload,
        });
      }
    }
  };

  const handleFinishOrder = async (id) => {
    if (!activeOrderItems.length) return;
    const result = await Swal.fire({
      title: "Finish order?",
      icon: "success",
      showCancelButton: true,
      confirmButtonText: "Yes",
      cancelButtonText: "Cancel",
    });

    if (result.isConfirmed) {
      const action = await dispatch(finishOrder(id));
      dispatch(fetchOrderItems());
      dispatch(fetchOrders());

      if (cancelOrder.rejected.match(action)) {
        Swal.fire({
          icon: "error",
          title: "Error",
          text: action.payload,
        });
      }
    }
  };

  return (
    <div className="container">
      <PageTitle title={"Order"} />
      <main className="mainContent">
        <Form
          formFields={formFields}
          onSubmit={handleSubmit}
          form={form}
          setForm={setForm}
          page={"orders"}
          btnLabel="Add to order"
          products={activeProducts}
          categories={activeCategories}
          getProduct={handleGetProduct}
        />

        <hr />

        <section className="productSection">
          <Table
            findName={findNameById}
            products={activeProducts}
            categories={activeCategories}
            data={activeOrderItems}
            columns={columns}
            onDelete={handleDeleteOrderItem}
          />

          <OrderSumary
            onCancel={(id) => handleCancelOrder(id)}
            onFinish={(id) => handleFinishOrder(id)}
          />
        </section>
      </main>
    </div>
  );
}
export default Products;
