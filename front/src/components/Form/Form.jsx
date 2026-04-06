import styles from "./Form.module.css";

function Form() {
  return (
    <div className={styles.formWrapper}>
      <form>
        <div className={styles.formFields}>
          <input
            name="name"
            type="text"
            placeholder="Category name"
          />
          <input
            name="tax"
            type="number"
            step="0.01"
            min="0"
            max="100"
            placeholder="Tax"
          />
        </div>
        <button className={styles.submitBtn} type="submit">
          Add Category
        </button>
      </form>
    </div>
  );
}

export default Form;
