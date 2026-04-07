import PageTitle from "../components/PageTitle/PageTitle.jsx";
import Form from "../components/Form/Form.jsx";
import Table from "../components/Table/Table.jsx";
import { useDispatch, useSelector } from "react-redux";
import { useState, useEffect } from "react";
import { fetchCategories } from "../store/slices/categorySlice.js";
import { fetchProducts } from "../store/slices/productSlice.js";
import {
  createOrderItem,
  fetchOrderItems,
  deleteOrderItem,
  clearError,
} from "../store/slices/orderItemSlice.js";
import Swal from 'sweetalert2'

function Products() {
  const dispatch = useDispatch();

  const formFields = [
    { name: "amount", type: "number", placeholder: "Amount" },
    {
      name: "tax",
      type: "number",
      placeholder: "Category name",
      step: "0.01",
      min: "0",
      max: "100",
      placeholder: "tax",
    },
    {
      name: "price",
      type: "number",
      placeholder: "Price",
      step: "0.01",
      min: "0.1",
      max: "10000000000",
    },
  ];

  const [form, setForm] = useState({
    amount: "",
    product_code: "",
  });

  useEffect(() => {
    dispatch(fetchCategories());
    dispatch(fetchProducts());
    dispatch(fetchOrderItems());
  }, [dispatch]);

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

  const activeCategories = categories.filter((c) => c.status === "active");
  const activeProducts = products.filter((p) => p.status === "active");
  const activeOrderItems = orderItems.filter((o) => o.status === "active");

  const columns = [
    { key: "business_code", label: "Code" },
    { key: "product_code", label: "Product" },
    { key: "amount", label: "Amount" },
    { key: "price", label: "Unit price", format: "currency" },
    { key: "tax", label: "Tax", format: "currency" },
    { key: "total", label: "Total", format: "currency" },
  ];

  if (productsLoading || categoriesLoading || orderItemsLoading)
    return <p>Loading...</p>;

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
      setForm({ amount: "", product_code: "" });
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

      if (deleteOrderItem.rejected.match(action)) {
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
          associatedRegister={activeProducts}
        />

        <hr />

        <section className="productSection">
          <Table
            // findName={findNameById}
            associatedRegister={activeProducts}
            data={activeOrderItems}
            columns={columns}
            onDelete={handleDeleteOrderItem}
          />
        </section>
      </main>
    </div>
  );
}
export default Products;
