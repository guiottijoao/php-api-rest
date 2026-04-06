import Navbar from "../components/Navbar/Navbar.jsx";

function MainLayout({ children }) {
  return (
    <div>
      <Navbar />

      <main>{children}</main>
    </div>
  );
}

export default MainLayout;