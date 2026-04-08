import { Routes, Route } from "react-router-dom";
import Categories from "../pages/categories.jsx";
import Products from "../pages/products.jsx";
import Orders from "../pages/orders.jsx";
import History from "../pages/history.jsx";

function AppRoutes() {
  return (
    <Routes>
      <Route path="/" element={<Orders />} />
      <Route path="/products" element={<Products />} />
      <Route path="/categories" element={<Categories />} />
      <Route path="/history" element={<History />} />
    </Routes>
  );
}

export default AppRoutes;
