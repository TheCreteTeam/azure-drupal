function Results() {
    return (
        <div className="SearchResults bg-white py-5">
            <div className="container">
                <div className="row mb-5">
                    <div className="col-12 col-lg-6">
                        <strong>1-10</strong> of <strong>123</strong> results.
                    </div>
                    <div className="col-12 col-md-6 d-flex justify-content-end mt-3 mt-lg-0">
                        <div className="ResultsConfig">
                            Sort by:
                            <select className="fw-bold border-0 border-bottom ms-1"
                                    aria-label="Results sorting options" defaultValue={'Relevance'}>
                                <option value={'Relevance'}>Relevance</option>
                                <option value={'date'}>Submission date</option>
                                <option value={'Period'}>Period covered</option>
                                <option value={'Information'}>Information type</option>
                            </select>
                        </div>
                        <div className="ResultsConfig ms-5">
                            Show:
                            <select className="fw-bold border-0 border-bottom ms-1"
                                    aria-label="Number of results configuration" defaultValue={10}>
                                <option value={10}>10</option>
                                <option value={20}>20</option>
                                <option value={100}>100</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div className="row">
                    <div className="col">

                        <div className="card rounded-1 SearchResult mb-5">
                            <div className="card-body">
                                <div className="card-title fs-5 fw-semibold">Title lorem ipsum dolor sit amet</div>
                                <p className="card-text">Description lorem ipsum dolor sit amet, consectetur adipiscing
                                    elit. Phasellus venenatis, dolor id porta sodales, leo velit cursus lectus, quis
                                    molestie nulla nisi eu velit. Vestibulum aliquet pellentesque justo at
                                    tincidunt. </p>
                            </div>
                            <div className="card-footer bg-lighter p-0 d-flex justify-content-between">
                                <div className="d-flex justify-content-start">
                                    <button type="button" className="btn btn-lg btn-light rounded-0 border-end"
                                            data-bs-toggle="tooltip" data-bs-title="Download the dataset">
                                        <i className="bi bi-download" aria-hidden="true"></i><span
                                        className="visually-hidden">Download the dataset</span></button>
                                    <button type="button" className="btn btn-lg btn-light rounded-0 border-end"
                                            data-bs-toggle="tooltip" data-bs-title="Add the dataset to cart">
                                        <i className="bi bi-bag-plus" aria-hidden="true"></i><span
                                        className="visually-hidden">Add the dataset to cart</span></button>
                                </div>

                                <div className="d-flex justify-content-end align-items-center">
                                    <div className="SRInfo border-start text-info-emphasis" data-bs-toggle="tooltip"
                                         data-bs-title="Information in this dataset was received with a qualified electronic seal. Date / time when the data were checked as valid for the last time by ESAP">
                                        <span className="visually-hidden">Date / time when the data were checked as valid for the last time by ESAP:</span>
                                        <i className="bi bi-patch-check-fill me-1"
                                           aria-hidden="true"></i> 23/01/2024 <small
                                        className="d-none d-md-inline">12:34</small>
                                    </div>

                                    <div className="SRInfo border-start text-info-emphasis" data-bs-toggle="tooltip"
                                         data-bs-title="Information in this dataset relates to historical data which pre-dates ESAP">
                                        <span className="visually-hidden">Information in this dataset relates to historical data which pre-dates ESAP</span>
                                        <i className="bi bi-clock-history" aria-hidden="true"></i>
                                    </div>

                                    <div className="SRInfo border-start text-info-emphasis" data-bs-toggle="tooltip"
                                         data-bs-title="Information in this dataset is voluntary">
                                        <span
                                            className="visually-hidden">Information in this dataset is voluntary</span>
                                        <i className="bi bi-person-raised-hand" aria-hidden="true"></i>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <div className="card rounded-1 SearchResult">
                            <div className="card-body">
                                <div className="card-title fs-5 fw-semibold">Title lorem ipsum dolor sit amet</div>
                                <p className="card-text">Description lorem ipsum dolor sit amet, consectetur adipiscing
                                    elit. Phasellus venenatis, dolor id porta sodales, leo velit cursus lectus, quis
                                    molestie nulla nisi eu velit. Vestibulum aliquet pellentesque justo at
                                    tincidunt. </p>
                            </div>
                            <div className="card-footer bg-lighter p-0 d-flex justify-content-between">
                                <div className="d-flex justify-content-start">
                                    <button type="button" className="btn btn-lg btn-light rounded-0 border-end"
                                            data-bs-toggle="tooltip" data-bs-title="Download the dataset">
                                        <i className="bi bi-download" aria-hidden="true"></i><span
                                        className="visually-hidden">Download the dataset</span></button>
                                    <button type="button" className="btn btn-lg btn-light rounded-0 border-end"
                                            data-bs-toggle="tooltip" data-bs-title="Add the dataset to cart">
                                        <i className="bi bi-bag-plus" aria-hidden="true"></i><span
                                        className="visually-hidden">Add the dataset to cart</span></button>
                                </div>

                                <div className="d-flex justify-content-end align-items-center">
                                    <div className="SRInfo border-start text-info-emphasis" data-bs-toggle="tooltip"
                                         data-bs-title="Information in this dataset was received with a qualified electronic seal. Date / time when the data were checked as valid for the last time by ESAP">
                                        <span className="visually-hidden">Date / time when the data were checked as valid for the last time by ESAP:</span>
                                        <i className="bi bi-patch-check-fill me-1"
                                           aria-hidden="true"></i> 23/01/2024 <small
                                        className="d-none d-md-inline">12:34</small>
                                    </div>

                                    <div className="SRInfo border-start text-info-emphasis" data-bs-toggle="tooltip"
                                         data-bs-title="Information in this dataset relates to historical data which pre-dates ESAP">
                                        <span className="visually-hidden">Information in this dataset relates to historical data which pre-dates ESAP</span>
                                        <i className="bi bi-clock-history" aria-hidden="true"></i>
                                    </div>

                                    <div className="SRInfo border-start text-info-emphasis" data-bs-toggle="tooltip"
                                         data-bs-title="Information in this dataset is voluntary">
                                        <span
                                            className="visually-hidden">Information in this dataset is voluntary</span>
                                        <i className="bi bi-person-raised-hand" aria-hidden="true"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div className="row">
                    <div className="col">
                        <div className="Pagination d-flex justify-content-end mt-5 py-2 border-top">
                            <button type="button" className="btn btn-sm btn-outline-light link-dark border-0 mx-1"
                                    disabled><i className="bi bi-chevron-bar-left" aria-hidden="true"></i> First
                            </button>
                            <button type="button" className="btn btn-sm btn-outline-light link-dark border-0 mx-1"
                                    disabled><i className="bi bi-chevron-left" aria-hidden="true"></i> Previous
                            </button>
                            <button type="button"
                                    className="btn btn-sm btn-outline-light link-dark border-0 mx-1">Next <i
                                className="bi bi-chevron-right" aria-hidden="true"></i></button>
                            <button type="button"
                                    className="btn btn-sm btn-outline-light text-dark border-0 mx-1">Last <i
                                className="bi bi-chevron-bar-right" aria-hidden="true"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    );
}


export default Results;