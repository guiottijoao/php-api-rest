import PageTitle from "../components/PageTitle/PageTitle.jsx";
import Form from "../components/Form/Form.jsx";
import Table from "../components/Table/Table.jsx";

function Categories() {
  return (
    <div className="container">
      <PageTitle title={"Categories"} />
      <main className="mainContent">
        <Form />

        <hr />

        <section className="productSection">
          <Table />
        </section>
      </main>
    </div>
  );
}

export default Categories;
