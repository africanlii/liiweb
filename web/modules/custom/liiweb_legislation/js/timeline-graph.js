(function ($, Drupal, drupalSettings) {

    'use strict';
  
    Drupal.behaviors.mybehavior = {
      attach: function (context, settings) {
        console.log('hello');

        var data = settings.liiweb_legislation.timeline.data;

        Highcharts.chart('container', {
            chart: {
                type: 'timeline'
            },
            accessibility: {
                screenReaderSection: {
                    beforeChartFormat: '<h5>{chartTitle}</h5>' +
                        '<div>{typeDescription}</div>' +
                        '<div>{chartSubtitle}</div>' +
                        '<div>{chartLongdesc}</div>' +
                        '<div>{viewTableButton}</div>'
                },
                point: {
                    valueDescriptionFormat: '{index}. {point.label}. {point.description}.'
                }
            },
            xAxis: {
                visible: false
            },
            yAxis: {
                visible: false
            },
            title: {
                text: 'Legislation Historic Timeline'
            },
            colors: [
                '#5a9d1c',
                '#4a4a4a',
                '#326fd1'
            ],
            series: [{
                data: data
            }]
        });
        
      }
    };
  
  })(jQuery, Drupal, drupalSettings);