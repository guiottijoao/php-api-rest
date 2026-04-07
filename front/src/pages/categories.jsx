import PageTitle from "../components/PageTitle/PageTitle.jsx";
import Form from "../components/Form/Form.jsx";
import Table from "../components/Table/Table.jsx";
import { useEffect, useState } from "react";
import { useDispatch, useSelector } from "react-redux";
import {
  fetchCategories,
  deleteCategory,
  createCategory,
  clearError,
} from "../store/slices/categorySlice.js";
import Swal from "sweetalert2";

function Categories() {
  const dispatch = useDispatch();

  const formFields = [
    { name: "name", type: "text", placeholder: "Category name" },
    {
      name: "tax",
      type: "number",
      placeholder: "Category name",
      step: "0.01",
      min: "0",
      max: "100",
      placeholder: "tax",
    },
  ];
  const columns = [
    { key: "business_code", label: "Code" },
    { key: "name", label: "Name" },
    { key: "tax", label: "Tax" },
  ];

  const [form, setForm] = useState({ name: "", tax: "" });

  useEffect(() => {
    dispatch(fetchCategories());
  }, [dispatch]);

  const {
    items: categories,
    loading,
    error,
  } = useSelector((state) => state.categories);

  const activeCategories = categories.filter((c) => c.status === "active");

  if (loading) return <p>Loading...</p>;

  const handleSubmit = async (e) => {
    e.preventDefault();
    const result = await dispatch(createCategory(form));

    if (createCategory.rejected.match(result)) {
      Swal.fire({
        icon: "error",
        title: "Error",
        text: result.payload.message,
      });
    }
    if (createCategory.fulfilled.match(result)) {
      setForm({ name: "", tax: "" });
      dispatch(clearError());
    }
  };

  const handleDeleteCategory = async (id) => {
    const result = await Swal.fire({
      title: "Are you sure?",
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Delete",
      cancelButtonText: "Cancel",
    });

    if (result.isConfirmed) {
      const action = await dispatch(deleteCategory(id));

      if (deleteCategory.rejected.match(action))  {
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
      <PageTitle title={"Categories"} />
      <main className="mainContent">
        <Form
          formFields={formFields}
          onSubmit={handleSubmit}
          form={form}
          setForm={setForm}
          page={"categories"}
          btnLabel="Add category"
        />

        <hr />

        <section className="productSection">
          <Table
            data={activeCategories}
            columns={columns}
            onDelete={handleDeleteCategory}
          />
        </section>
      </main>
    </div>
  );
}

export default Categories;
