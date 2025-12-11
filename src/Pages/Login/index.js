import React, { useState } from "react";

const AuthPage = () => {
  const [showLoginPass, setShowLoginPass] = useState(false);
  const [showRegPass, setShowRegPass] = useState(false);

  const handleLoginSubmit = (e) => {
    e.preventDefault();
    console.log("Login enviado");
  };

  const handleRegisterSubmit = (e) => {
    e.preventDefault();
    console.log("Registro enviado");
  };

  return (
    <main className="auth-page">
      <section className="auth-container">

        {/* ACCEDER */}
        <div className="auth-card">
          <h2>Acceder</h2>

          <form id="formLogin" className="auth-form" onSubmit={handleLoginSubmit}>

            <label htmlFor="login-usuario">
              Nombre de usuario o correo electrónico <span className="req">*</span>
            </label>
            <input type="text" id="login-usuario" required />

            <label htmlFor="login-clave">
              Contraseña <span className="req">*</span>
            </label>

            <div className="auth-password-wrapper">
              <input
                type={showLoginPass ? "text" : "password"}
                id="login-clave"
                required
              />
              <button
                type="button"
                className="btn-eye"
                onClick={() => setShowLoginPass(!showLoginPass)}
              >
                👁
              </button>
            </div>

            <div className="auth-row">
              <button type="submit" className="btn-auth-primary">
                ACCESO
              </button>

              <label className="remember-check">
                <input type="checkbox" />
                <span>Recuérdame</span>
              </label>
            </div>

          </form>
        </div>

        {/* REGISTRO */}
        <div className="auth-card">
          <h2>Registrarse</h2>

          <form
            id="formRegistro"
            className="auth-form"
            onSubmit={handleRegisterSubmit}
         >

            <label htmlFor="reg-correo">
              Dirección de correo electrónico <span className="req">*</span>
            </label>
            <input type="email" id="reg-correo" required />

            <label htmlFor="reg-clave">
              Contraseña <span className="req">*</span>
            </label>

            <div className="auth-password-wrapper">
              <input
                type={showRegPass ? "text" : "password"}
                id="reg-clave"
                required
              />
              <button
                type="button"
                className="btn-eye"
                onClick={() => setShowRegPass(!showRegPass)}
              >
                👁
              </button>
            </div>

            <button type="submit" className="btn-auth-primary">
              REGISTRARSE
            </button>
          </form>
        </div>

      </section>
    </main>
  );
};

export default AuthPage;
