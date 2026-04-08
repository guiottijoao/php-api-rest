import PageTitle from "../components/PageTitle/PageTitle.jsx";
import Form from "../components/Form/Form.jsx";
import Table from "../components/Table/Table.jsx";
import { useEffect, useState } from "react";
import { useDispatch, useSelector } from "react-redux";
import {
  fetchProducts,
  deleteProduct,
  createProduct,
  clearError,
} from "../store/slices/productSlice.js";
import { fetchCategories } from "../store/slices/categorySlice.js";
import Swal from "sweetalert2";

function Products() {
  const dispatch = useDispatch();

  const findNameById = (id, data) => {
    const found = data.find((c) => c.code == id);
    return found?.name;
  };

  const formFields = [
    { name: "name", type: "text", placeholder: "Product name" },
    { name: "amount", type: "number", placeholder: "Amount" },
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
    name: "",
    amount: "",
    price: "",
    category_code: "",
  });

  useEffect(() => {
    dispatch(fetchCategories());
    dispatch(fetchProducts());
  }, [dispatch]);

  const { items: categories, loading: categoriesLoading } = useSelector(
    (state) => state.categories,
  );

  const {
    items: products,
    loading: productsLoading,
    error,
  } = useSelector((state) => state.products);

  const activeCategories = categories.filter((c) => c.status === "active");
  const activeProducts = products.filter((p) => p.status === "active");

  const columns = [
    { key: "business_code", label: "Code" },
    { key: "name", label: "Product" },
    { key: "amount", label: "Amount" },
    { key: "price", label: "Unit price", format: "currency" },
    { key: "category_code", label: "Category" },
  ];

  if (productsLoading || categoriesLoading) return <p>Loading...</p>;

  const handleSubmit = async (e) => {
    e.preventDefault();
    const result = await dispatch(createProduct(form));

    if (createProduct.rejected.match(result)) {
      Swal.fire({
        icon: "error",
        title: "Error",
        text: result.payload,
      });
    }
    if (createProduct.fulfilled.match(result)) {
      setForm({ name: "", amount: "", price: "", category_code: "" });
      dispatch(clearError());
    }
  };

  const handleDeleteProduct = async (id) => {
    const result = await Swal.fire({
      title: "Are you sure?",
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Delete",
      cancelButtonText: "Cancel",
    });

    if (result.isConfirmed) {
      const action = await dispatch(deleteProduct(id));

      if (deleteProduct.rejected.match(action)) {
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
      <PageTitle title={"Products"} />
      <main className="mainContent">
        <Form
          formFields={formFields}
          onSubmit={handleSubmit}
          form={form}
          setForm={setForm}
          page={"products"}
          btnLabel="Add product"
          categories={activeCategories}
        />

        <hr />

        <section className="productSection">
          <Table
            findName={findNameById}
            categories={activeCategories}
            data={activeProducts}
            columns={columns}
            onDelete={handleDeleteProduct}
          />
        </section>
      </main>
    </div>
  );
}
export default Products;
