function Search() {

    return (
        <main>
            <form className="bg-lighter py-5">
                <div className="container">
                    <div className="row">
                        <div className="col-12">
                            <div className="input-group mb-3">
                                <button id="SRCHFreeHelp" className="btn btn-sm border-end FormHelp" type="button"
                                        data-bs-toggle="tooltip" data-bs-title="More information about this field">
                                    <i className="bi bi-info-lg" aria-hidden="true"></i><span
                                    className="visually-hidden">Help with this field</span>
                                </button>
                                <input aria-describedby="SRCHFreeHelp" type="text"
                                       className="form-control form-control-lg"
                                       placeholder="Search by keyword, lorem ipsum or dolor sit amet"
                                       aria-label="Free-text search"/>
                            </div>
                        </div>
                    </div>

                    <div className="row">
                        <div className="col">
                            <a className="btn btn-link link-dark ExpColl ms-1 collapsed" data-bs-toggle="collapse"
                               href="#SearchFilters" role="button" aria-expanded="false" aria-controls="SearchFilters">Search
                                filters</a>
                        </div>

                        <div className="col-12 collapse" id="SearchFilters">
                            <div className="accordion accordion-flush my-3" id="FiltersAccordion">
                                <div className="accordion-item">
                                    <div className="accordion-header">
                                        <button className="accordion-button collapsed" type="button"
                                                data-bs-toggle="collapse" data-bs-target="#FiltersBasic"
                                                aria-expanded="false" aria-controls="FiltersBasic">Basic filters
                                        </button>
                                    </div>
                                    <div className="accordion-collapse collapse" id="FiltersBasic"
                                         data-bs-parent="#FiltersAccordion">
                                        <div className="accordion-body px-0 py-4">
                                            <div className="row">
                                                <div className="col-sm-6">
                                                    <div className="FormComponent mb-3">
                                                        <label htmlFor="SRCHEntityName" className="form-label">Entity
                                                            name:</label>
                                                        <div className="input-group">
                                                            <button id="SRCHEntityNameHelp"
                                                                    className="btn btn-sm border-end FormHelp"
                                                                    type="button"
                                                                    data-bs-toggle="tooltip"
                                                                    data-bs-title="More information about this field">
                                                                <i className="bi bi-info-lg"
                                                                   aria-hidden="true"></i><span
                                                                className="visually-hidden">Help with this field</span>
                                                            </button>
                                                            <input aria-describedby="SRCHEntityNameHelp"
                                                                   id="SRCHEntityName"
                                                                   type="text" className="form-control"/>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div className="col-sm-6">
                                                    <div className="FormComponent mb-3">
                                                        <label htmlFor="SRCHEntitySize" className="form-label">Size of
                                                            the
                                                            Entity:</label>
                                                        <div className="input-group">
                                                            <button id="SRCHEntitySizeHelp"
                                                                    className="btn btn-sm border-end FormHelp"
                                                                    type="button"
                                                                    data-bs-toggle="tooltip"
                                                                    data-bs-title="More information about this field">
                                                                <i className="bi bi-info-lg"
                                                                   aria-hidden="true"></i><span
                                                                className="visually-hidden">Help with this field</span>
                                                            </button>
                                                            <select aria-describedby="SRCHEntitySizeHelp"
                                                                    id="SRCHEntitySize" className="form-select">
                                                                <option></option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div className="col-sm-6">
                                                    <div className="FormComponent mb-3">
                                                        <label htmlFor="SRCHEntityLEI" className="form-label">Entity
                                                            LEI:</label>
                                                        <div className="input-group">
                                                            <button id="SRCHEntityLEIHelp"
                                                                    className="btn btn-sm border-end FormHelp"
                                                                    type="button"
                                                                    data-bs-toggle="tooltip"
                                                                    data-bs-title="More information about this field">
                                                                <i className="bi bi-info-lg"
                                                                   aria-hidden="true"></i><span
                                                                className="visually-hidden">Help with this field</span>
                                                            </button>
                                                            <input aria-describedby="SRCHEntityLEIHelp"
                                                                   id="SRCHEntityLEI"
                                                                   type="text" className="form-control"/>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div className="col-sm-6">
                                                    <div className="FormComponent mb-3">
                                                        <label htmlFor="SRCHLEIStatus" className="form-label">LEI
                                                            status:</label>
                                                        <div className="input-group">
                                                            <button id="SRCHLEIStatusHelp"
                                                                    className="btn btn-sm border-end FormHelp"
                                                                    type="button"
                                                                    data-bs-toggle="tooltip"
                                                                    data-bs-title="More information about this field">
                                                                <i className="bi bi-info-lg"
                                                                   aria-hidden="true"></i><span
                                                                className="visually-hidden">Help with this field</span>
                                                            </button>
                                                            <select aria-describedby="SRCHLEIStatusHelp"
                                                                    id="SRCHLEIStatus"
                                                                    className="form-select">
                                                                <option></option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div className="col-sm-6">
                                                    <div className="FormComponent mb-3">
                                                        <fieldset>
                                                            <legend>Submission date / time:</legend>
                                                            <div className="input-group">
                                                                <button id="SRCHSubmissionDTHelp"
                                                                        className="btn btn-sm border-end FormHelp"
                                                                        type="button" data-bs-toggle="tooltip"
                                                                        data-bs-title="More information about this field">
                                                                    <i className="bi bi-info-lg"
                                                                       aria-hidden="true"></i><span
                                                                    className="visually-hidden">Help with this field</span>
                                                                </button>
                                                                <input aria-describedby="SRCHSubmissionDTHelp"
                                                                       type="text"
                                                                       className="form-control" placeholder="From"
                                                                       aria-label="From"/>
                                                                <button
                                                                    className="btn btn-sm btn-link link-dark bg-white border border-start-0"
                                                                    type="button">
                                                                    <i className="bi bi-calendar4"
                                                                       aria-hidden="true"></i><span
                                                                    className="visually-hidden">Date / time picker</span>
                                                                </button>
                                                                <input aria-describedby="SRCHSubmissionDTHelp"
                                                                       type="text"
                                                                       className="form-control" placeholder="To"
                                                                       aria-label="To"/>
                                                                <button
                                                                    className="btn btn-sm btn-link link-dark bg-white border border-start-0"
                                                                    type="button">
                                                                    <i className="bi bi-calendar4"
                                                                       aria-hidden="true"></i><span
                                                                    className="visually-hidden">Date / time picker</span>
                                                                </button>
                                                            </div>
                                                        </fieldset>
                                                    </div>
                                                </div>

                                                <div className="col-sm-6">
                                                    <div className="FormComponent mb-3">
                                                        <fieldset>
                                                            <legend>Date or period covered:</legend>
                                                            <div className="input-group">
                                                                <button id="SRCHPeriodDTHelp"
                                                                        className="btn btn-sm border-end FormHelp"
                                                                        type="button" data-bs-toggle="tooltip"
                                                                        data-bs-title="More information about this field">
                                                                    <i className="bi bi-info-lg"
                                                                       aria-hidden="true"></i><span
                                                                    className="visually-hidden">Help with this field</span>
                                                                </button>
                                                                <input aria-describedby="SRCHPeriodDTHelp" type="text"
                                                                       className="form-control" placeholder="From"
                                                                       aria-label="From"/>
                                                                <button
                                                                    className="btn btn-sm btn-link link-dark bg-white border border-start-0"
                                                                    type="button">
                                                                    <i className="bi bi-calendar4"
                                                                       aria-hidden="true"></i><span
                                                                    className="visually-hidden">Date / time picker</span>
                                                                </button>
                                                                <input aria-describedby="SRCHPeriodDTHelp" type="text"
                                                                       className="form-control" placeholder="To"
                                                                       aria-label="To"/>
                                                                <button
                                                                    className="btn btn-sm btn-link link-dark bg-white border border-start-0"
                                                                    type="button">
                                                                    <i className="bi bi-calendar4"
                                                                       aria-hidden="true"></i><span
                                                                    className="visually-hidden">Date / time picker</span>
                                                                </button>
                                                            </div>
                                                        </fieldset>
                                                    </div>
                                                </div>

                                                <div className="col-sm-6">
                                                    <div className="FormComponent mb-3">
                                                        <label htmlFor="SRCHInfoType" className="form-label">Type of
                                                            information:</label>
                                                        <div className="input-group">
                                                            <button id="SRCHInfoTypeHelp"
                                                                    className="btn btn-sm border-end FormHelp"
                                                                    type="button"
                                                                    data-bs-toggle="tooltip"
                                                                    data-bs-title="More information about this field">
                                                                <i className="bi bi-info-lg"
                                                                   aria-hidden="true"></i><span
                                                                className="visually-hidden">Help with this field</span>
                                                            </button>
                                                            <select aria-describedby="SRCHInfoTypeHelp"
                                                                    id="SRCHInfoType"
                                                                    className="form-select">
                                                                <option></option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div className="accordion-item">
                                    <div className="accordion-header">
                                        <button className="accordion-button collapsed" type="button"
                                                data-bs-toggle="collapse" data-bs-target="#FiltersAdvanced"
                                                aria-expanded="false" aria-controls="FiltersAdvanced">Advanced filters
                                        </button>
                                    </div>
                                    <div className="accordion-collapse collapse" id="FiltersAdvanced"
                                         data-bs-parent="#FiltersAccordion">
                                        <div className="accordion-body px-0 py-4">
                                            <div className="row">
                                                <div className="col-sm-6">
                                                    <div className="FormComponent mb-3">
                                                        <label htmlFor="SRCHSubmittingEntityName"
                                                               className="form-label">Submitting
                                                            entity name:</label>
                                                        <div className="input-group">
                                                            <button id="SRCHSubmittingEntityNameHelp"
                                                                    className="btn btn-sm border-end FormHelp"
                                                                    type="button"
                                                                    data-bs-toggle="tooltip"
                                                                    data-bs-title="More information about this field">
                                                                <i className="bi bi-info-lg"
                                                                   aria-hidden="true"></i><span
                                                                className="visually-hidden">Help with this field</span>
                                                            </button>
                                                            <input aria-describedby="SRCHSubmittingEntityNameHelp"
                                                                   id="SRCHSubmittingEntityName" type="text"
                                                                   className="form-control"/>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div className="col-sm-6">
                                                    <div className="FormComponent mb-3">
                                                        <label htmlFor="SRCHSubmittingEntityNameLEI"
                                                               className="form-label">Submitting
                                                            entity LEI:</label>
                                                        <div className="input-group">
                                                            <button id="SRCHSubmittingEntityNameLEIHelp"
                                                                    className="btn btn-sm border-end FormHelp"
                                                                    type="button"
                                                                    data-bs-toggle="tooltip"
                                                                    data-bs-title="More information about this field">
                                                                <i className="bi bi-info-lg"
                                                                   aria-hidden="true"></i><span
                                                                className="visually-hidden">Help with this field</span>
                                                            </button>
                                                            <select aria-describedby="SRCHSubmittingEntityNameLEIHelp"
                                                                    id="SRCHSubmittingEntityNameLEI"
                                                                    className="form-select">
                                                                <option></option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div className="col-sm-6">
                                                    <div className="FormComponent mb-3">
                                                        <label htmlFor="SRCHSubmittingEntitySize"
                                                               className="form-label">Size
                                                            of the submitting entity:</label>
                                                        <div className="input-group">
                                                            <button id="SRCHSubmittingEntitySizeHelp"
                                                                    className="btn btn-sm border-end FormHelp"
                                                                    type="button"
                                                                    data-bs-toggle="tooltip"
                                                                    data-bs-title="More information about this field">
                                                                <i className="bi bi-info-lg"
                                                                   aria-hidden="true"></i><span
                                                                className="visually-hidden">Help with this field</span>
                                                            </button>
                                                            <select aria-describedby="SRCHSubmittingEntitySizeHelp"
                                                                    id="SRCHSubmittingEntitySize"
                                                                    className="form-select">
                                                                <option></option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div className="col-sm-6">
                                                    <div className="FormComponent mb-3">
                                                        <label htmlFor="SRCHOfficeCountry" className="form-label">Country
                                                            of
                                                            registered office:</label>
                                                        <div className="input-group">
                                                            <button id="SRCHOfficeCountryHelp"
                                                                    className="btn btn-sm border-end FormHelp"
                                                                    type="button"
                                                                    data-bs-toggle="tooltip"
                                                                    data-bs-title="More information about this field">
                                                                <i className="bi bi-info-lg"
                                                                   aria-hidden="true"></i><span
                                                                className="visually-hidden">Help with this field</span>
                                                            </button>
                                                            <select aria-describedby="SRCHOfficeCountryHelp"
                                                                    id="SRCHOfficeCountry" className="form-select">
                                                                <option></option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div className="col-sm-6">
                                                    <div className="FormComponent mb-3">
                                                        <label htmlFor="SRCHIndustrySectors" className="form-label">Industry
                                                            sector(s):</label>
                                                        <div className="input-group">
                                                            <button id="SRCHIndustrySectorsHelp"
                                                                    className="btn btn-sm border-end FormHelp"
                                                                    type="button"
                                                                    data-bs-toggle="tooltip"
                                                                    data-bs-title="More information about this field">
                                                                <i className="bi bi-info-lg"
                                                                   aria-hidden="true"></i><span
                                                                className="visually-hidden">Help with this field</span>
                                                            </button>
                                                            <select aria-describedby="SRCHIndustrySectorsHelp"
                                                                    id="SRCHIndustrySectors" className="form-select">
                                                                <option></option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div className="col-sm-6">
                                                    <div className="FormComponent mb-3">
                                                        <label htmlFor="SRCHOriginalLang" className="form-label">Original
                                                            language(s):</label>
                                                        <div className="input-group">
                                                            <button id="SRCHOriginalLangHelp"
                                                                    className="btn btn-sm border-end FormHelp"
                                                                    type="button"
                                                                    data-bs-toggle="tooltip"
                                                                    data-bs-title="More information about this field">
                                                                <i className="bi bi-info-lg"
                                                                   aria-hidden="true"></i><span
                                                                className="visually-hidden">Help with this field</span>
                                                            </button>
                                                            <select aria-describedby="SRCHOriginalLangHelp"
                                                                    id="SRCHOriginalLang" className="form-select">
                                                                <option></option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div className="col-sm-6">
                                                    <div className="FormComponent mb-3">
                                                        <label htmlFor="SRCHLegalFW" className="form-label">Legal
                                                            framework:</label>
                                                        <div className="input-group">
                                                            <button id="SRCHLegalFWHelp"
                                                                    className="btn btn-sm border-end FormHelp"
                                                                    type="button"
                                                                    data-bs-toggle="tooltip"
                                                                    data-bs-title="More information about this field">
                                                                <i className="bi bi-info-lg"
                                                                   aria-hidden="true"></i><span
                                                                className="visually-hidden">Help with this field</span>
                                                            </button>
                                                            <select aria-describedby="SRCHLegalFWHelp" id="SRCHLegalFW"
                                                                    className="form-select">
                                                                <option></option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div className="col-sm-6">
                                                    <div className="FormComponent mb-3">
                                                        <label htmlFor="SRCHCollectionBody" className="form-label">Collection
                                                            Body name:</label>
                                                        <div className="input-group">
                                                            <button id="SRCHCollectionBodyHelp"
                                                                    className="btn btn-sm border-end FormHelp"
                                                                    type="button"
                                                                    data-bs-toggle="tooltip"
                                                                    data-bs-title="More information about this field">
                                                                <i className="bi bi-info-lg"
                                                                   aria-hidden="true"></i><span
                                                                className="visually-hidden">Help with this field</span>
                                                            </button>
                                                            <select aria-describedby="SRCHCollectionBodyHelp"
                                                                    id="SRCHCollectionBody" className="form-select">
                                                                <option></option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div className="col-sm-6">
                                                    <div className="FormComponent mb-3">
                                                        <div className="input-group">
                                                            <button id="SRCHVoluntaryInfoHelp"
                                                                    className="btn btn-sm border-end FormHelp"
                                                                    type="button"
                                                                    data-bs-toggle="tooltip"
                                                                    data-bs-title="More information about this field">
                                                                <i className="bi bi-info-lg"
                                                                   aria-hidden="true"></i><span
                                                                className="visually-hidden">Help with this field</span>
                                                            </button>
                                                            <div className="input-group-text border-0 bg-transparent">
                                                                <div className="form-check">
                                                                    <input className="form-check-input" type="checkbox"
                                                                           id="SRCHVoluntaryInfo"
                                                                           aria-describedby="SRCHVoluntaryInfoHelp"/>
                                                                    <label className="form-check-label"
                                                                           htmlFor="SRCHVoluntaryInfo">Voluntary
                                                                        information
                                                                        flag</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div className="FormComponent mb-3">
                                                        <div className="input-group">
                                                            <button id="SRCHHistoricalInfoHelp"
                                                                    className="btn btn-sm border-end FormHelp"
                                                                    type="button"
                                                                    data-bs-toggle="tooltip"
                                                                    data-bs-title="More information about this field">
                                                                <i className="bi bi-info-lg"
                                                                   aria-hidden="true"></i><span
                                                                className="visually-hidden">Help with this field</span>
                                                            </button>
                                                            <div className="input-group-text border-0 bg-transparent">
                                                                <div className="form-check">
                                                                    <input className="form-check-input" type="checkbox"
                                                                           id="SRCHHistoricalInfo"
                                                                           aria-describedby="SRCHHistoricalInfoHelp"/>
                                                                    <label className="form-check-label"
                                                                           htmlFor="SRCHHistoricalInfo">Historical
                                                                        information flag</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div className="accordion-item">
                                    <div className="accordion-header">
                                        <button className="accordion-button collapsed" type="button"
                                                data-bs-toggle="collapse" data-bs-target="#FiltersSpecific"
                                                aria-expanded="false" aria-controls="FiltersSpecific">Specific filters
                                        </button>
                                    </div>
                                    <div className="accordion-collapse collapse" id="FiltersSpecific"
                                         data-bs-parent="#FiltersAccordion">
                                        <div className="accordion-body px-0 py-4">
                                            <div className="row">
                                                <div className="col-sm-6">
                                                    <div className="FormComponent mb-3">
                                                        <label htmlFor="SRCHHomeMS" className="form-label">Home member
                                                            state
                                                            of the entity concerned:</label>
                                                        <div className="input-group">
                                                            <button id="SRCHHomeMSHelp"
                                                                    className="btn btn-sm border-end FormHelp"
                                                                    type="button"
                                                                    data-bs-toggle="tooltip"
                                                                    data-bs-title="More information about this field">
                                                                <i className="bi bi-info-lg"
                                                                   aria-hidden="true"></i><span
                                                                className="visually-hidden">Help with this field</span>
                                                            </button>
                                                            <select aria-describedby="SRCHHomeMSHelp" id="SRCHHomeMS"
                                                                    className="form-select">
                                                                <option></option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div className="col-sm-6">
                                                    <div className="FormComponent mb-3">
                                                        <label htmlFor="SRCHHostMS" className="form-label">Host member
                                                            state
                                                            of the entity concerned:</label>
                                                        <div className="input-group">
                                                            <button id="SRCHHostMSHelp"
                                                                    className="btn btn-sm border-end FormHelp"
                                                                    type="button"
                                                                    data-bs-toggle="tooltip"
                                                                    data-bs-title="More information about this field">
                                                                <i className="bi bi-info-lg"
                                                                   aria-hidden="true"></i><span
                                                                className="visually-hidden">Help with this field</span>
                                                            </button>
                                                            <select aria-describedby="SRCHHostMSHelp" id="SRCHHostMS"
                                                                    className="form-select">
                                                                <option></option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div className="col-sm-6">
                                                    <div className="FormComponent mb-3">
                                                        <label htmlFor="SRCHISIN" className="form-label">ISIN:</label>
                                                        <div className="input-group">
                                                            <button id="SRCHISINHelp"
                                                                    className="btn btn-sm border-end FormHelp"
                                                                    type="button"
                                                                    data-bs-toggle="tooltip"
                                                                    data-bs-title="More information about this field">
                                                                <i className="bi bi-info-lg"
                                                                   aria-hidden="true"></i><span
                                                                className="visually-hidden">Help with this field</span>
                                                            </button>
                                                            <input aria-describedby="SRCHISINHelp" id="SRCHISIN"
                                                                   type="text"
                                                                   className="form-control"/>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div className="col-sm-6">
                                                    <div className="FormComponent mb-3">
                                                        <label htmlFor="SRCHFISN" className="form-label">FISN:</label>
                                                        <div className="input-group">
                                                            <button id="SRCHFISNHelp"
                                                                    className="btn btn-sm border-end FormHelp"
                                                                    type="button"
                                                                    data-bs-toggle="tooltip"
                                                                    data-bs-title="More information about this field">
                                                                <i className="bi bi-info-lg"
                                                                   aria-hidden="true"></i><span
                                                                className="visually-hidden">Help with this field</span>
                                                            </button>
                                                            <input aria-describedby="SRCHFISNHelp" id="SRCHFISN"
                                                                   type="text"
                                                                   className="form-control"/>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div className="col d-flex justify-content-end mt-5 mt-lg-0">
                            {/*<button type="submit" class="btn btn-primary"><i class="bi bi-search me-2"></i> Search</button>*/}
                            <a href="02 - Results.html" className="btn btn-primary"><i
                                className="bi bi-search me-2"></i> Search</a>
                            <button type="reset" className="btn btn-link link-dark ms-3"><i
                                className="bi bi-arrow-clockwise me-2"></i> Clear
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </main>

    );
}

export default Search;