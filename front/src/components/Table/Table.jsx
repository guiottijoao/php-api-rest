import styles from "./Table.module.css";

function Table({ data, columns, onDelete }) {
  return (
    <div className={styles.tableContainer}>
      <table>
        <thead>
          <tr>
            {columns.map(col => (
              <th key={col.key} >{col.label}</th>
            ))}
            <th>Actions</th>
          </tr>
        </thead>
        
        <tbody id="categories-table-body">
          {data.map((row, index) => (
            <tr key={index}>
              {columns.map(col => (
                <td key={col.key}>{row[col.key]}</td>
              ))}
              <td ><button onClick={() => onDelete(row['code'])} className='delete-btn'>Delete</button></td>
            </tr>
          ))}
        </tbody>
      </table>
      <div id="categories-empty-state"></div>
    </div>
  );
}

export default Table;