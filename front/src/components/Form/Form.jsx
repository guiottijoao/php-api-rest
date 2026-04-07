import styles from "./Form.module.css";

function Form({ formFields, onSubmit, form, setForm, page, btnLabel, associatedRegister }) {
  const handleChange = (e) => {
    setForm((prev) => ({ ...prev, [e.target.name]: e.target.value }));
  };

  return (
    <div className={styles.formWrapper}>
      <form onSubmit={onSubmit}>
        <div className={styles.formFields}>
          {/* Layout: if the form is on page products there is a wrapper and the 'name' field is outside this wrapper */}
          {formFields.map(
            (field, index) =>
              (field.name === "name" || page !== "products") && (
                <input
                  onChange={handleChange}
                  value={form[field.name]}
                  key={index}
                  type={field.type}
                  name={field.name}
                  placeholder={field.placeholder}
                  step={field.step}
                  min={field.min}
                  max={field.max}
                />
              ),
          )}
        </div>

        {page === "products" && (
          <div className={styles.fieldsWrapper}>
            {formFields.map(
              (field, index) =>
                field.name !== "name" && (
                  <input
                    onChange={handleChange}
                    value={form[field.name]}
                    key={index}
                    type={field.type}
                    name={field.name}
                    placeholder={field.placeholder}
                    step={field.step}
                    min={field.min}
                    max={field.max}
                  />
                ),
            )}
            <select defaultValue="" onChange={handleChange} name="category_code" id="category-selector">
              <option disabled value="">Select a category</option>
              {associatedRegister.map(item => 
              <option key={item.code} value={item.code}>{item.name}</option>
              )}
            </select>
          </div>
        )}
        <button className={styles.submitBtn} type="submit">
          {btnLabel}
        </button>
      </form>
    </div>
  );
}

export default Form;
