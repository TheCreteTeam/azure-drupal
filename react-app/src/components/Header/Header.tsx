
function Header() {
  return (
      <div>
          <nav className="navbar navbar-expand-lg SiteNav">
              <div className="container-fluid">
                  <a className="navbar-brand me-5" href="01 - Home.html"><img src="assets/logo.png"
                                                                              alt="ESAP - European Single Access Point"/></a>
                  <button className="navbar-toggler p-0 border-0" type="button" data-bs-toggle="offcanvas"
                          data-bs-target="#SiteMenu" aria-controls="SiteMenu" aria-label="Toggle navigation">
                      <i className="bi bi-list" aria-hidden="true"></i><span className="visually-hidden">Menu</span>
                  </button>
                  <div className="offcanvas offcanvas-end" id="SiteMenu" aria-labelledby="SiteMenuTitle">
                      <div className="offcanvas-header bg-light">
                          <div className="offcanvas-title TTCaps fw-semibold ps-2" id="SiteMenuTitle">Menu</div>
                          <button type="button" className="btn-close" data-bs-dismiss="offcanvas"
                                  aria-label="Close"></button>
                      </div>
                      <div className="offcanvas-body">
                          <div
                              className="d-flex justify-content-lg-between flex-grow-1 flex-column flex-lg-row align-items-lg-center">
                              <ul className="navbar-nav justify-content-end PrimaryNav border-bottom pb-3 pb-lg-0 mb-3 mb-lg-0">
                                  <li className="nav-item px-1 mx-2"><a className="nav-link link-dark"
                                                                        href="#">About</a></li>
                                  <li className="nav-item px-1 mx-2"><a className="nav-link link-dark" href="#">Documentation
                                      and support</a></li>
                                  <li className="nav-item px-1 mx-2"><a className="nav-link link-dark"
                                                                        href="#">Feedback</a></li>
                              </ul>
                              <ul className="navbar-nav justify-content-end align-items-lg-center pb-3 pb-lg-0 mb-3 mb-lg-0">
                                  <li className="nav-item px-1 mx-1"><a className="nav-link link-dark" href="#"><i
                                      className="bi bi-globe2 me-2 me-lg-0" aria-hidden="true"></i> EN</a></li>
                                  <li className="nav-item px-1 mx-1"><a className="nav-link link-dark" href="#"><i
                                      className="bi bi-rss me-2 me-lg-0" aria-hidden="true"></i> RSS</a></li>
                                  <li className="nav-item px-1 mx-1 dropdown">
                                      <a className="nav-link link-dark dropdown-toggle UserAccount d-none d-lg-block"
                                         href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                          <i className="bi bi-person-circle" aria-hidden="true"></i> <span
                                          className="visually-hidden">User account</span></a>
                                      <ul className="dropdown-menu dropdown-menu-end">
                                          <li>
                                              <div className="dropdown-header">Firstname Lastname <span>User role</span>
                                              </div>
                                          </li>
                                          <li><a className="dropdown-item" href="#"><i
                                              className="bi bi-sliders2 me-2 opacity-50" aria-hidden="true"></i> Account
                                              preferences</a></li>
                                          <li><a className="dropdown-item" href="#"><i
                                              className="bi bi-box-arrow-right me-2 opacity-50"
                                              aria-hidden="true"></i> Log out</a></li>
                                      </ul>
                                  </li>
                              </ul>
                          </div>
                      </div>
                  </div>
              </div>
          </nav>

          <div className="Intro py-4 py-lg-5">
              <div className="container">
                  <div className="row">
                      <div className="col">
                          <h1 className="fs-4 text-center">European Single Access Point<br/><small>Public financial and
                              sustainability-related information about EU companies and EU investment products.</small>
                          </h1>

                          <div className="d-none alert alert-info my-3 alert-dismissible fade show" role="alert">
                              <strong>Important announcement!</strong> Lorem ipsum dolor sit amet, consectetur
                              adipiscing elit. Donec interdum sit amet turpis vel tempus. Aenean volutpat vel odio eget
                              fringilla. Curabitur odio lectus, posuere nec ullamcorper gravida, rutrum non lorem.
                              <button type="button" className="btn-close" data-bs-dismiss="alert"
                                      aria-label="Close"></button>
                          </div>
                      </div>
                  </div>
              </div>
          </div>
      </div>


  );
}

export default Header;