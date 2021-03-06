(function ($, Drupal, drupalSettings) {

  'use strict';

  function parseDate(date) {
    var parts = date.split('-');
    return Date.UTC(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
  }

  // Transform an array of {date: xx, events: [...]} objects into an array of events
  function makeEvents(data) {
    var events = [];
    var expressionInfo = drupalSettings.liiweb_legislation.raw_json;

    for (var entry of data) {
      var date = parseDate(entry.date);
      var link;

      // link to read this version
      if (entry.expression_frbr_uri && entry.date != expressionInfo.expression_date) {
        link = '<br/><a style="text-decoration: underline; font-weight: bold" href="' +
               entry.expression_frbr_uri + '">Read version</a>';
      }

      for (var event of entry.events) {
        event.x = date;
        event.link = link;
        events.push(event);

        if (entry.date === expressionInfo.expression_date) {
          event.dataLabels = {
            backgroundColor: "#d6e2f6"
          }
        }

        if (event.event === 'assent') {
          event.name = 'Assent';
          event.label = 'Assented to';
        } else if (event.event === 'publication') {
          event.name = 'Published';
          event.label = 'Published';
        } else if (event.event === 'commencement') {
          // TODO: provisions?
          event.name = 'Commences';
          event.label = 'Commences';
          event.description = 'Comes into force';
        } else if (event.event === 'amendment') {
          event.name = 'Amended'
          event.label = 'Amended by ' + event.amending_title;
        } else if (event.event === 'repeal') {
          event.name = 'Repealed';
          event.label = 'Repealed by ' + event.repealing_title;
        }
      }
    }

    events.push({
      x: Date.now(),
      name: 'Today',
      label: 'Today'
    });

    return events;
  }

  Drupal.behaviors.mybehavior = {
    attach: function (context, settings) {

      var data = makeEvents(settings.liiweb_legislation.timeline.data);

      Highcharts.chart('container', {
        chart: {
          zoomType: 'x',
          type: 'timeline',
          height: 200
        },
        title: {
          text: 'Legislation Timeline'
        },
        tooltip: {
          enabled: false
        },
        xAxis: {
          type: 'datetime',
          visible: false
        },
        yAxis: {
          labels: false,
          title: null,
          labels: {
            enabled: false
          }
        },
        legend: {
          enabled: false
        },
        series: [{
          dataLabels: {
            allowOverlap: false,
            format: '<span style="color:{point.color}">●</span>' +
                    ' <span style="font-weight: bold;"> {point.x:%d %b %Y}</span><br/>{point.label}{point.link}'
          },
          marker: {
            symbol: 'circle'
          },
          data: data
        }]
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
