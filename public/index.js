function initMap() {
    const map = new google.maps.Map(document.getElementById("map"), {
        zoom: 8,
        center: {lat: 52.511603879036855, lng: 13.389393217975504},
    });

    fetch("./data/data-formatted.json")
        .then(response => response.json())
        .then(schools => {
            const infoWindow = new google.maps.InfoWindow();

            addMarkers(map, schools, infoWindow);
        });
}

function addMarkers(map, schools, infoWindow) {
    for (let i = 0; i < schools.data.length; i++) {
        school = schools.data[i];

        if (school.type === 'Grundschule'
            // && !!school.students_non_german_percentage
            // && school.students_non_german_percentage > 10
            // && school.students_non_german_percentage < 40
        ) {
            var marker = new google.maps.Marker({
                position: new google.maps.LatLng(school.lat, school.lng),
                map: map,
                html: getInfoWindowContent(school),
            });

            google.maps.event.addListener(marker, "click", function () {
                infoWindow.setContent(this.html);
                infoWindow.open(map, this);
            });
        }
    }
}

function getInfoWindowContent(school) {
    var contentString = '<div id="content">' +
        '<div><a href="'+
        school.url +
        '" target="_blank">' +
        school.name +
        '</a>' +
        '<br/>non-German language of origin: ' + school.students_non_german_percentage + '%' +
        '<br/>languages: ' + school.languages +
        '</div><br/>'
    ;

    school.inspections.forEach(inspection => {
        contentString = contentString +
            '<div><a href="'+ inspection.url +'" target="_blank">' + inspection.title + '</a></div>'
        ;
    });

    contentString = contentString + '</div>';

    return contentString;
}
