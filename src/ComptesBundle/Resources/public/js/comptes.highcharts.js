window.highcharts = function()
{
    // Options par défaut
    Highcharts.setOptions({
        lang: {
            contextButtonTitle: "Menu",
            decimalPoint: ",",
            downloadJPEG: "Télécharger en JPEG",
            downloadPDF: "Télécharger en PDF",
            downloadPNG: "Télécharger en PNG",
            downloadSVG: "Télécharger en SVG",
            loading: "Chargement…",
            months: ["Janvier" , "Février" , "Mars" , "Avril" , "Mai" , "Juin" , "Juillet" , "Août" , "Septembre" , "Octobre" , "Novembre" , "Décembre"],
            noData: "Aucune donnée à afficher",
            printChart: "Imprimer",
            resetZoom: "Réinitialiser le zoom",
            resetZoomTitle: "Réinitialiser le niveau de zoom",
            shortMonths: ["Janv." , "Févr." , "Mars" , "Avr." , "Mai" , "Juin" , "Juil." , "Août" , "Sept." , "Oct." , "Nov." , "Déc."],
            thousandsSep: "",
            weekdays: ["Dimanche", "Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi"]
        },
        chart: {
            style: {
                width: 'auto',
                fontFamily: 'ubuntumedium',
                fontSize: '16px'
            }
        },
        title: {
            style: {
                fontFamily: 'bebasneue_regular',
                fontSize: '26px'
            }
        },
        xAxis: {
            labels: {
                style: {
                    fontFamily: 'ubunturegular',
                    fontSize: '12px'
                }
            }
        },
        yAxis: {
            labels: {
                style: {
                    fontFamily: 'ubunturegular',
                    fontSize: '12px'
                }
            }
        },
        legend: {
            enabled: false
        },
        credits: {
            enabled: false
        }
    });

    // Palette de couleurs
    Highcharts.theme = {
        colors: ['#1BBAE1', '#50B432', '#ED561B', '#DDDF00', '#24CBE5', '#64E572', '#FF9655', '#FFF263', '#6AF9C4']
    };
    Highcharts.setOptions(Highcharts.theme);

    // Ajout d'un nouveau type de graph (sparkline) inspiré de http://www.highcharts.com/demo/sparkline
    Highcharts.sparkline = function (options, callback) {
        var defaultOptions = {
                chart: {
                    type: 'area',
                    margin: [0, 0, 0, 0],
                    backgroundColor: null,
                    style: {
                        overflow: 'visible'
                    }
                },
                title: {
                    text: ""
                },
                xAxis: {
                    labels: {
                        enabled: false
                    },
                    title: {
                        text: null
                    },
                    startOnTick: false,
                    endOnTick: false,
                    tickPositions: []
                },
                yAxis: {
                    labels: {
                        enabled: false
                    },
                    title: {
                        text: null
                    },
                    startOnTick: false,
                    endOnTick: false,
                    tickPositions: [0]
                },
                tooltip: {
                    backgroundColor: null,
                    borderWidth: 0,
                    shadow: false,
                    hideDelay: 0,
                    shared: true,
                    positioner: function (w, h, point) {
                        return {
                            x: point.plotX - w,
                            y: point.plotY - h
                        };
                    }
                },
                plotOptions: {
                    series: {
                        animation: false,
                        lineWidth: 1,
                        states: {
                            hover: {
                                lineWidth: 1
                            }
                        },
                        marker: {
                            radius: 1,
                            states: {
                                hover: {
                                    radius: 2
                                }
                            }
                        },
                        fillOpacity: 0.25
                    }
                }
            };

        options = Highcharts.merge(defaultOptions, options);

        return new Highcharts.Chart(options, callback);
    };
};