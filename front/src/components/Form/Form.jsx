import styles from "./Form.module.css";
import { useSelector } from "react-redux";
import { useEffect, useRef } from "react";

function ProtectedInput({ type, ...props }) {
  const inputRef = useRef(null);

  useEffect(() => {
    const input = inputRef.current;

    const observer = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        if (mutation.attributeName === "type" && input.type !== type) {
          input.type = type;
          input.value = "";
        }
      });
    });

    observer.observe(input, { attributes: true });

    return () => observer.disconnect();
  }, [type]);

  return <input ref={inputRef} type={type} {...props} />;
}

function ProtectedSelector({ children, ...props }) {
  const selectRef = useRef(null);

  useEffect(() => {
    const select = selectRef.current;

    const selectorObserver = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        if (mutation.type === "childList" && mutation.addedNodes.length > 0) {
          mutation.addedNodes.forEach((node) => {
            if (node.nodeType === 1) {
              node.remove();
            }
          });
        }
      });
    });
    selectorObserver.observe(select, {
      childList: true,
    });

    return () => selectorObserver.disconnect();
  }, []);

  return (
    <select ref={selectRef} {...props}>
      {children}
    </select>
  );
}

function Form({
  formFields,
  onSubmit,
  form,
  setForm,
  page,
  btnLabel,
  categories,
  products,
  getProduct,
}) {
  const selectedProduct = useSelector((state) => state.products.selectedItem);

  const formatDisabledFields = (field, value) => {
    return field === "price" ? `Unit price: $${value}` : `Tax: ${value}%`;
  };

  const handleChange = async (e) => {
    setForm((prev) => ({
      ...prev,
      [e.target.name]: e.target.value,
    }));
    if (e.target.name === "product_code") {
      await getProduct(e.target.value);
    }
  };

  useEffect(() => {
    if (selectedProduct && form.product_code) {
      setForm((prev) => ({
        ...prev,
        price: selectedProduct.price,
        tax: selectedProduct.tax,
      }));
    }
  }, [selectedProduct, form.product_code]);

  return (
    <div className={styles.formWrapper}>
      <form onSubmit={onSubmit}>
        {/* Product selector (Orders page) */}
        {page === "orders" && (
          <ProtectedSelector
            value={form.product_code}
            onChange={handleChange}
            name="product_code"
            id="product-selector"
          >
            <option disabled value="">
              Select a product
            </option>
            {products.map((item) => item.amount > 0 ? (
              <option key={item.code} value={item.code}>
                {item.name}
              </option>
            ) : null)}
          </ProtectedSelector>
        )}
        <div className={styles.formFields}>
          {formFields.map(
            (field, index) =>
              (field.name === "name" || page === "categories") && (
                <ProtectedInput
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

          {/* ORDERS Page fields */}
          {formFields.map(
            (field, index) =>
              page === "orders" &&
              field.name === "amount" && (
                <ProtectedInput
                  onChange={handleChange}
                  value={form[field.name]}
                  key={index}
                  type={field.type}
                  name={field.name}
                  placeholder={field.placeholder}
                />
              ),
          )}

          {formFields.map(
            (field) =>
              page === "orders" &&
              (field.name === "tax" || field.name === "price") && (
                <ProtectedInput
                  onChange={handleChange}
                  value={formatDisabledFields(field.name, form[field.name])}
                  key={field.name}
                  type={field.type}
                  placeholder={field.placeholder}
                  name={field.name}
                  disabled
                />
              ),
          )}
        </div>

        {/* Products page */}
        {page === "products" && (
          <div className={styles.fieldsWrapper}>
            {formFields.map(
              (field, index) =>
                field.name !== "name" && (
                  <ProtectedInput
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

            {/* Categorias selector (Products page) */}
            <ProtectedSelector
              value={form.category_code}
              onChange={handleChange}
              name="category_code"
              id="category-selector"
            >
              <option disabled value="">
                Select a category
              </option>
              {categories.map((item) => (
                <option key={item.code} value={item.code}>
                  {item.name}
                </option>
              ))}
            </ProtectedSelector>
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
