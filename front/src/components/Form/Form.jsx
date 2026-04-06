import styles from "./Form.module.css";

function Form({formFields}) {
  return (
    <div className={styles.formWrapper}>
      <form>
        <div className={styles.formFields}>
          {formFields.map((field) => <input type={field.type} name={field.name} placeholder={field.placeholder} step={field.step} min={field.min} max={field.max} />)}
        </div>
        <button className={styles.submitBtn} type="submit">
          Add Category
        </button>
      </form>
    </div>
  );
}

export default Form;
