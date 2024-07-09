import React, {useEffect, useState} from 'react';
import logo from './logo.svg';
import './App.css';
import {Person} from "./types";
import ArticleComponent from "./components/ArticleComponent";
import PersonComponent from "./components/PersonComponent";
import Header from "./components/Header/Header";
import Search from "./components/Search/Search";
import Results from "./components/Results/Results";
import Footer from "./components/Footer/Footer";

function App() {


  return (
    <div className="App">
      <div className="App">
          <Header />
          <Search />
          <Results />
          <Footer />
          {/*<h1>React App</h1>*/}
        {/*<PersonComponent />*/}
        {/*<ArticleComponent />*/}
      </div>
    </div>
  );
}

export default App;
