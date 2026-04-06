import PageTitle from "../components/PageTitle/PageTitle.jsx";
import Form from "../components/Form/Form.jsx";
import Table from "../components/Table/Table.jsx";

function Categories() {
  const fields = [
    {name: 'name', type: 'text', placeholder: 'Category name'},
    {name: 'tax', type: 'number', placeholder: 'Category name', step: '0.01', min: '0', max: '100', placeholder: 'tax'}
  ]

  return (
    <div className="container">
      <PageTitle title={"Categories"} />
      <main className="mainContent">
        <Form formFields={fields} />

        <hr />

        <section className="productSection">
          <Table />
        </section>
      </main>
    </div>
  );
}

export default Categories;
