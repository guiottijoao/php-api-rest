import { Routes, Route } from "react-router-dom";
import Categories from "../pages/categories.jsx";
import Products from "../pages/products.jsx";
import Orders from '../pages/orders.jsx';

function AppRoutes() {
  return (
    <Routes>
      <Route path="/" element={<Orders />} />
      <Route path="/products" element={<Products />} />
      <Route path="/categories" element={<Categories />} />
    </Routes>
  );
}

export default AppRoutes;
