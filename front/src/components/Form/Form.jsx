import { useState } from "react";
import { useDispatch, useSelector } from "react-redux";
import {
  createCategory,
  clearError,
} from "../../store/slices/categorySlice.js";
import styles from "./Form.module.css";

function Form({ formFields, onSubmit, form, setForm }) {
  const handleChange = (e) => {
    setForm((prev) => ({ ...prev, [e.target.name]: e.target.value }));
  }

  return (
    <div className={styles.formWrapper}>
      <form onSubmit={onSubmit}>
        <div className={styles.formFields}>
          {formFields.map((field, index) => (
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
          ))}
        </div>
        <button className={styles.submitBtn} type="submit">
          Add Category
        </button>
      </form>
    </div>
  );
}

export default Form;
