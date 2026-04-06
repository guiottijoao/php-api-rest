import PageTitle from "../components/PageTitle/PageTitle.jsx";
import Form from "../components/Form/Form.jsx";
import Table from "../components/Table/Table.jsx";
import { useEffect } from "react";
import { useDispatch, useSelector } from "react-redux";
import {
  fetchCategories,
  deleteCategory,
} from "../store/slices/categorySlice.js";
import Swal from "sweetalert2";

function Categories() {
  const dispatch = useDispatch();
  
  const formFields = [
    {name: 'name', type: 'text', placeholder: 'Category name'},
    {name: 'tax', type: 'number', placeholder: 'Category name', step: '0.01', min: '0', max: '100', placeholder: 'tax'}
  ]
  const columns = [
    {key: 'business_code', label: 'Code'},
    {key: 'name', label: 'Name'},
    {key: 'tax', label: 'Tax'},
  ]
  
  const {
    items: categories,
    loading,
    error,
  } = useSelector((state) => state.categories);

  const activeCategories = categories.filter(c => c.status === 'active')

  useEffect(() => {
    dispatch(fetchCategories());
    console.log(activeCategories)
  }, [dispatch]);

  if (loading) return <p>Loading...</p>;
  if (error) {
    return <p>Something went wrong..</p>;
  }

  const handleDeleteCategory = async (id) => {
    const result = await Swal.fire({
      title: "Are you sure?",
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Delete",
      cancelButtonText: "Cancel",
    });

    if (result.isConfirmed) {
      dispatch(deleteCategory(id));
    }
  };

  return (
    <div className="container">
      <PageTitle title={"Categories"} />
      <main className="mainContent">
        <Form formFields={formFields} />

        <hr />

        <section className="productSection">
          <Table data={activeCategories} columns={columns} onDelete={handleDeleteCategory} />
        </section>
      </main>
    </div>
  );
}

export default Categories;
