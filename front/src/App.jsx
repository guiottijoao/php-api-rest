import './App.css'
import { BrowserRouter } from "react-router-dom";
import AppRoutes from "./routes/index.jsx";
import MainLayout from "./layouts/MainLayout.jsx";

function App() {
  return (
    <BrowserRouter>
      <MainLayout>
        <AppRoutes />
      </MainLayout>
    </BrowserRouter>
  );
}

export default App