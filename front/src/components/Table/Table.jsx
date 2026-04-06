import styles from "./Table.module.css";

function Table() {
  return (
    <div class={styles.tableContainer}>
      <table>
        <tr>
          <th>Code</th>
          <th>Category</th>
          <th>Tax</th>
          <th>Actions</th>
        </tr>
        <tbody id="categories-table-body">
          {/* foreach ($categories as $cat): */}
          <tr>
            <td></td>
            <td></td>
            <td></td>
            <td>
              <a
                className="delete-btn"
                onclick="return confirm('Delete category?')"
              >
                Delete
              </a>
            </td>
          </tr>
        </tbody>
      </table>
      <div id="categories-empty-state"></div>
    </div>
  );
}

export default Table;
