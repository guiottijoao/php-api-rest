import { Routes, Route } from "react-router-dom";
import Categories from "../pages/categories.jsx";
import Products from "../pages/products.jsx";

function AppRoutes() {
  return (
    <Routes>
      <Route path="/categories" element={<Categories />} />
      <Route path="/products" element={<Products />} />
    </Routes>
  );
}

export default AppRoutes;
