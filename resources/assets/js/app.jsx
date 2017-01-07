import React from 'react';
import {render} from 'react-dom';
import axios from 'axios';

class App extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            city : {
                latitude: 40.7141667,
                longitude: -74.0063889,
                city: 'New York',
                country: 'us'
            },
            searchValue: ""
        };
        this.handleChange = this.handleChange.bind(this);
        this.handleSubmit = this.handleSubmit.bind(this);
    }
    componentDidMount() {
        console.log(this.state.city.latitude)
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
            context.setState({city: response.data});
            context.setMap(response.data.longitude, response.data.latitude)
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
            <h2>{this.state.city.city} ({this.state.city.country}) {this.state.city.latitude} {this.state.city.longitude}</h2>
            <div id="map-container" className="z-depth-1" style={mapStyle}></div>
        </div>;
    }
}

render(<App/>, document.getElementById('searchApp'));
