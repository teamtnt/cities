import React from 'react';
import {render} from 'react-dom';

class App extends React.Component {
  constructor(props) {
      super(props);
      this.state = {pageTitle : "This is a title"};
    }
  handleSearchInput(e) {
    this.setState({pageTitle: e.target.value});
  }
  render () {
    return <div>
        <h2 className="h2-responsive">{this.state.pageTitle}</h2>
        <h1 id="nenad"></h1>
        <br />
        <div className="md-form input-group">
            <input type="search" onInput={this.handleSearchInput.bind(this)} className="form-control" placeholder="Search for..." />
            <span className="input-group-btn">
                <button className="btn btn-primary btn-lg" type="button">Go!</button>
            </span>
        </div>
        <hr />
        <div id="map-container" className="z-depth-1"></div>
    </div>;
  }
}

render(<App/>, document.getElementById('searchApp'));
