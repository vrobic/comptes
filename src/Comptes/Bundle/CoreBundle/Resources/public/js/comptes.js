$(function(){

    // Highcharts **************************************************************
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
        }
    });

    Highcharts.theme = {
        colors: ['#1BBAE1', '#50B432', '#ED561B', '#DDDF00', '#24CBE5', '#64E572', '#FF9655', '#FFF263', '#6AF9C4']
    };
    Highcharts.setOptions(Highcharts.theme);

    // Écouteurs ***************************************************************

    // Confirmation de suppression
    $('form :submit[value="delete"]').click(function(){
        return confirm("Supprimer ?");
    });

    // Cocher/décocher tout
    $('form .check-all, form .uncheck-all').click(function(){
        var targetName = $(this).data('target-name'),
            check = $(this).hasClass('check-all');
        $(this).closest('form').find('input[name="'+targetName+'"]').prop('checked', check);
    });

});