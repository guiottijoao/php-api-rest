import styles from "./Table.module.css";
import formatField from "../../utils/formatField";

function Table({ data, columns, onDelete, categories, products, findName }) {
  const displayNameCell = (row, col) => {
    switch (col.key) {
      case "category_code":
        return <td key={col.key}>{findName(row[col.key], categories)}</td>;
      case "product_code":
        return <td key={col.key}>{findName(row[col.key], products)}</td>;
      default:
        return <td key={col.key}>{formatField(col, row[col.key])}</td>;
    }
  };

  return (
    <div className={styles.tableContainer}>
      <table>
        <thead>
          <tr>
            {columns.map((col) => (
              <th key={col.key}>{col.label}</th>
            ))}
            <th>Actions</th>
          </tr>
        </thead>

        <tbody id="categories-table-body">
          {data.map((row, index) => (
            <tr key={index}>
              {columns.map((col) => displayNameCell(row, col))}
              <td>
                <button
                  onClick={() => onDelete(row["code"])}
                  className="delete-btn"
                >
                  Delete
                </button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
      <div id="categories-empty-state"></div>
    </div>
  );
}

export default Table;
