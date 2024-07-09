function Footer() {
  return (
      <footer className="footer mt-auto py-3 py-lg-5 px-3 bg-light">
          <div className="d-flex flex-column flex-lg-row justify-content-between align-items-center">
              <ul className="nav">
                  <li className="nav-item"><a className="nav-link link-dark" href="#">Disclaimer</a> </li>
                  <li className="nav-item"><a className="nav-link link-dark" href="#">Legal notice</a> </li>
              </ul>
              <div className="text-center text-lg-end">
                  <img src="assets/logo_esma.png" alt="European Securities and Markets Authority" className="FLogoESMA mx-3 my-4 my-lg-0"/>
                      <img src="assets/logo_ec_hor.svg" alt="European Commission" className="FLogoEC mx-3"/>
              </div>
          </div>
      </footer>
  );
}

export default Footer;