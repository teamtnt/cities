import React from 'react';
import {render} from 'react-dom';
import axios from 'axios';

class DidYouMeanList extends React.Component {
    render() {
        var suggestions = this.props.suggestions;
        if(suggestions.length == 0) return null; 
        return (
            <div>
                <h5>Did you mean: </h5>
                <ul className="list-group">
                {suggestions.map( (suggestion, key) =>  {
                    return <li key={key} className="list-group-item"><a href="#">{suggestion.city} ({suggestion.country}) {suggestion.latitude} {suggestion.longitude} Population: {suggestion.population}</a></li>;
                })}
                </ul>
                <br/>
            </div>
        )
    }
}

class App extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            city : {
                latitude: 40.7141667,
                longitude: -74.0063889,
                city: 'New York',
                population: 8107916,
                country: 'us'
            },
            searchValue: "",
            suggestions: []
        };
        this.handleChange = this.handleChange.bind(this);
        this.handleSubmit = this.handleSubmit.bind(this);
    }
    componentDidMount() {
        var location = new google.maps.LatLng(this.state.city.latitude, this.state.city.longitude);

        this.map_marker = new google.maps.Marker({
            position: location,
        });

        this.google_map = new google.maps.Map(
            document.getElementById("map-container"),
            {
                center: location,
                zoom: 11
            });

        this.map_marker.setMap(this.google_map);
    }
    setMap(longitude, latitude) {
        var location = new google.maps.LatLng(latitude, longitude);;
        this.google_map.setCenter(location);
        this.map_marker.setPosition(location);
    }
    handleChange(e) {
        this.setState({searchValue: e.target.value});
    }
    handleSubmit(e) {
        e.preventDefault();
        let context = this;
        axios.get('/search', {
            params: {
                city: context.state.searchValue
            }
        })
        .then(function (response) {
            if(response.data.didyoumean) {
                console.log(response.data.data)
                context.setState({suggestions: response.data.data});
            } else {
                context.setState({city: response.data.data, suggestions: []});
                context.setMap(context.state.city.longitude, context.state.city.latitude)
            }
        })
        .catch(function (error) {
            console.log(error);
        });
    }

    render () {
        const mapStyle = {
          height: '300px'
        };
        return <div>
            <h2 className="h2-responsive">Try to search for any city</h2>
            <br />
            <form onSubmit={this.handleSubmit}>
                <div className="md-form input-group">
                    <input value={this.state.searchValue} onChange={this.handleChange} type="search" className="form-control" placeholder="Search for..." />
                    <span className="input-group-btn">
                        <button type="submit" className="btn btn-primary btn-lg">Go!</button>
                    </span>
                </div>
            </form>
            <hr />
            <DidYouMeanList suggestions={this.state.suggestions} />
            <h3>{this.state.city.city} ({this.state.city.country}) {this.state.city.latitude} {this.state.city.longitude} Population: {this.state.city.population}</h3>
            <div id="map-container" className="z-depth-1" style={mapStyle}></div>
        </div>;
    }
}

render(<App/>, document.getElementById('searchApp'));
