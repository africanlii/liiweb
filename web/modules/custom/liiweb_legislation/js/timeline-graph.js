(function ($, Drupal, drupalSettings) {

    'use strict';
  
    Drupal.behaviors.mybehavior = {
      attach: function (context, settings) {

        var data = settings.liiweb_legislation.timeline.data;

        Highcharts.chart('container', {
            chart: {
              zoomType: 'x',
              height: 250
            },
            title: {
              text: 'Legislation Historic Timeline'
            },
            tooltip: {
                formatter: function () {
                    var string = '<b>' + this.point.name + '</b><br>' + this.point.description;
                    return string;
                },
                style: {
                    pointerEvents: 'auto'
                },
                hideDelay: 1500,
                distance: 30
            },
            xAxis: {
              type: 'datetime',
              opposite: true
            },
            yAxis: {
              labels: false,
              title: '',
              gridLineWidth: 0
            },
            plotOptions: {
                column: {
                    pointWidth: 10
                }
            },
            legend: {
              enabled: false
            },
            series: [{
              type: 'column',
              data: data
            }]
        });
      }
    };
  
  })(jQuery, Drupal, drupalSettings);