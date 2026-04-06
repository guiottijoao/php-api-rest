import { Routes, Route } from "react-router-dom";
import Categories from "../pages/categories.jsx";

function AppRoutes() {
  return (
    <Routes>
      <Route path="/categories" element={<Categories />} />
    </Routes>
  );
}

export default AppRoutes;
