import "bootstrap/dist/css/bootstrap.min.css"
import './App.css';
import { BrowserRouter, Route, Routes } from "react-router-dom";
import Home from "./Pages/Home";
import Header from "./Components/Header";
import Login from "./Pages/Login";
import NavPrincipal from "./Components/NavPrincipal";
import Footer from "./Components/Footer";
import Cart from "./Pages/Cart";
function App() {
  return (
    <BrowserRouter>
      <>
        <div class="top-bar">
          <p>ENCUENTRA DE TODO EN MEGASANTIAGO - OFERTAS DEL MES</p>
        </div>
      </>
      <Header />
      <NavPrincipal />
      <Routes>
        <Route path="/" exact={true} element={<Home />}></Route>
        <Route path="/cart" exact={true} element={<Cart />}></Route>
        <Route path="/login" exact={true} element={<Login />}></Route>
      </Routes>
      <Footer />
    </BrowserRouter>
  );
}

export default App;
