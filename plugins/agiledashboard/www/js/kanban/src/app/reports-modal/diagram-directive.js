angular
    .module('kanban')
    .directive('graph', Graph);

Graph.$inject = [
    '$window',
    'd3',
    'moment',
    'gettextCatalog'
];

function Graph(
    $window,
    d3,
    moment,
    gettextCatalog
) {
    return {
        restrict: 'E',
        scope: {
            data: '='
        },
        link: function (scope, element) {

            function parseData(data) {
                var parsed_data = [];
                _.forEach(data, function (column) {
                    _.defaults(column, { activated: true });

                    _.forEach(column.values, function (value, value_index) {
                        if (! parsed_data[value_index]) {
                            parsed_data[value_index] = {};
                        }

                        if (column.activated) {
                            parsed_data[value_index][column.id] = value.kanban_items_count;
                        } else {
                            parsed_data[value_index][column.id] = 0;
                        }
                        parsed_data[value_index].date = value.start_date;
                    });
                });

                return parsed_data;
            }

            function total(data) {
                return _.reduce(data, function(sum, value) {
                    return (! isNaN(value)) ? sum + value : sum;
                }, 0);
            }

            function updateLegend(d3_column_data, d3_legend_element) {
                if (d3_column_data.activated) {
                    d3_column_data.activated = false;
                    d3_legend_element.style('text-decoration', 'line-through');
                } else {
                    d3_column_data.activated = true;
                    d3_legend_element.style('text-decoration', 'none');
                }
            }

            function chart() {
                chart.init();

                function resize() {
                    var margin = { top: 20, right: 50, bottom: 30, left: 50 };
                    var width  = angular.element('.chart')[0].clientWidth - chart.legendWidth() - margin.left - margin.right;
                    var height = angular.element('.chart')[0].clientHeight - chart.axisHeight() - margin.top - margin.bottom;

                    chart.resize(height, width);

                    chart.svg().attr("width", chart.width() + margin.left + margin.right)
                        .attr("height", chart.height() + margin.top + margin.bottom);

                    chart.g().attr("transform", "translate(" + margin.left + "," + margin.top + ")");

                    chart.redraw();
                }

                angular.element($window).bind('resize', function() {
                    resize();
                });

                return chart;
            }

            chart.init = function () {
                var margin = { top: 20, right: 50, bottom: 30, left: 50 };

                /// moment.js Date format
                var localized_format = gettextCatalog.getString('MM/DD');

                chart.localizedFormat(localized_format);
                chart.bisectDate(d3.bisector(function(d) { return moment(d.date).valueOf(); }).left);

                chart.legendWidth(120);
                chart.axisHeight(20);

                chart.width(angular.element('.chart')[0].clientWidth - chart.legendWidth() - margin.left - margin.right);
                chart.height(angular.element('.chart')[0].clientHeight - chart.axisHeight() - margin.top - margin.bottom);

                chart.svg(d3.select(element[0]).append("svg")
                    .attr("width", chart.width() + margin.left + margin.right)
                    .attr("height", chart.height() + margin.top + margin.bottom));

                chart.g(chart.svg().append("g")
                    .attr("transform", "translate(" + margin.left + "," + margin.top + ")"));

                chart.initData(scope.data);

                chart.initX();
                chart.initYMax();
                chart.initY();

                chart.initColor();
                chart.initStack();
                chart.initArea();

                chart.initGraph();
                chart.initLegend();
                chart.initTooltip();

                chart.initAreaEvents();
                chart.initLegendEvents();
            };

            chart.initData = function (data) {
                var stack_data = parseData(data);
                chart.stackData(stack_data);
                chart.columns(data);

                var keys = _.map(data, function(column) {
                    return column.id;
                });

                chart.keys(keys);
            };

            chart.initColor = function () {
                var schemeCategory20cWithoutLightest = [
                    '#3182bd',
                    '#6baed6',
                    '#9ecae1',
                    // '#c6dbef',
                    '#e6550d',
                    '#fd8d3c',
                    '#fdae6b',
                    // '#fdd0a2',
                    '#31a354',
                    '#74c476',
                    '#a1d99b',
                    // '#c7e9c0',
                    '#756bb1',
                    '#9e9ac8',
                    '#bcbddc',
                    // '#dadaeb',
                    '#636363',
                    '#969696',
                    '#bdbdbd'
                    // '#d9d9d9'
                ];

                var color_scale = d3.scaleOrdinal()
                    .range(schemeCategory20cWithoutLightest);

                chart.colorScale(color_scale);

                var color_domain = _.map(chart.columns(), function (data, index, columns) {
                    return (columns.length - 1) - index;
                });
                chart.colorScale().domain(color_domain);
            };

            chart.initX = function () {
                var first_column = _.first(chart.columns());

                var time_scale_extent = d3.extent(first_column.values, function(d) {
                    return moment(d.start_date).toDate();
                });

                var x_scale = d3.scaleTime()
                    .domain(time_scale_extent)
                    .range([0, chart.width()]);

                chart.xScale(x_scale);

                var x_axis = d3.axisBottom()
                    .scale(x_scale)
                    .tickFormat(function(d) { return moment(d).format(chart.localizedFormat()); });

                chart.xAxis(x_axis);
            };

            chart.initY = function () {
                var y_scale = d3.scaleLinear()
                    .domain([0, chart.yMax()])
                    .range([chart.height(), 0]);

                chart.yScale(y_scale);

                var y_axis = d3.axisLeft()
                    .scale(y_scale);

                chart.yAxis(y_axis);
            };

            chart.initYMax = function () {
                var first_column = _.first(chart.columns());

                var max_kanban_items_count = d3.max(first_column.values, function(data_point, index) {
                    return sumKanbanItemsCountsForOneDay(index);
                });

                chart.yMax(max_kanban_items_count);

                function sumKanbanItemsCountsForOneDay(day_index) {
                    var columns_activated = _.filter(chart.columns(), { activated: true });

                    return columns_activated.reduce(function(previous_sum, current_column) {
                        return previous_sum + current_column.values[day_index].kanban_items_count;
                    }, 0);
                }
            };

            chart.initGraph = function () {
                chart.g().append('g')
                    .attr('class', 'axis x-axis')
                    .attr('transform', 'translate(0, ' + chart.height() + ')')
                    .call(chart.xAxis());

                chart.g().append('g')
                    .attr('class', 'axis y-axis')
                    .call(chart.yAxis());

                chart.g().selectAll('.area')
                    .data(chart.stack()(chart.stackData()))
                    .enter()
                    .append('path')
                    .attr('id', function (d) { return 'area_' + d.key; })
                    .attr('class', 'area')
                    .attr('fill', function(d, i) { return chart.colorScale()(i); })
                    .attr('d', chart.area());

                chart.g().append('line')
                    .attr('class', 'guide-line')
                    .classed('guide-line-displayed', false)
                    .classed('guide-line-undisplayed', true);

                chart.g().selectAll('.y-axis')
                    .append('text')
                    .attr('class', 'y-axis-label')
                    .attr('text-anchor', 'middle')
                    .attr('transform', 'translate(-35,'+(chart.height() / 2)+')rotate(-90)')
                    .text(gettextCatalog.getString('Nb. of cards'));
            };

            chart.initAreaEvents = function() {
                chart.g().selectAll('.area')
                    .on('mouseover', function(d) {
                        d3.select('#area_' + d.key).classed('hover', true);
                        var column = _.find(chart.columns(), { id: d.key });
                        if (column) {
                            column.hover = true;
                        }
                    })
                    .on('mousemove', function () {
                        var data_set = getDataSet(d3.mouse(this)[0]);

                        if (data_set) {
                            chart.g().select('.guide-line')
                                .attr('x1', chart.xScale()(moment(data_set.date).toDate()))
                                .attr('y1', chart.height())
                                .attr('x2', chart.xScale()(moment(data_set.date).toDate()))
                                .attr('y2', 0)
                                .classed('guide-line-displayed', true)
                                .classed('guide-line-undisplayed', false);

                            chart.tooltip().classed('tooltip-displayed', true)
                                .classed('tooltip-undisplayed', false);

                            var position = getTooltipPosition(d3.mouse(this)[0], d3.mouse(this)[1]);

                            chart.tooltip().html(constructTooltipContent(data_set))
                                .style('left', (position.left) + 'px')
                                .style('top', (position.top) + 'px');
                        }
                    })
                    .on('mouseout', function(d) {
                        if (d3.event.relatedTarget && d3.event.relatedTarget.nodeName !== 'line' &&
                            d3.event.relatedTarget.id !== 'tooltip_' + d.key &&
                            d3.event.relatedTarget.id !== 'area_' + d.key) {
                            d3.select('#area_' + d.key).classed('hover', false);
                            var column = _.find(chart.columns(), {id: d.key});
                            if (column) {
                                column.hover = false;
                            }

                            chart.tooltip().classed('tooltip-displayed', false)
                                .classed('tooltip-undisplayed', true);

                            chart.g().select('.guide-line')
                                .classed('guide-line-displayed', false)
                                .classed('guide-line-undisplayed', true);
                        }
                    });

                function getTooltipPosition(mouse_x, mouse_y) {
                    var position = {
                        left: mouse_x + 100,
                        top: mouse_y - 50
                    };

                    var last_x_date          = chart.stackData()[chart.stackData().length - 1].date;
                    var position_x_last_date = chart.xScale()(moment(last_x_date).toDate());
                    var tooltip_width        = d3.select('#tooltip').node().getBoundingClientRect().width;

                    if (position.left + tooltip_width >= position_x_last_date) {
                        position.left = mouse_x - tooltip_width;
                    }

                    return position;
                }

                function getDataSet(coordinate_x) {
                    var x_value           = chart.xScale().invert(coordinate_x),
                        index             = chart.bisectDate()(chart.stackData(), moment(x_value).valueOf()),
                        data_set_min      = chart.stackData()[index - 1],
                        data_set_max      = chart.stackData()[index],
                        data_set_min_diff = 0,
                        data_set_max_diff = 0;

                    if (data_set_min) {
                        data_set_min_diff = moment(x_value).diff(moment(data_set_min.date));
                    }

                    if (data_set_max) {
                        data_set_max_diff = moment(data_set_max.date).diff(moment(x_value));
                    }

                    return (data_set_min_diff > data_set_max_diff ? data_set_max : data_set_min);
                }

                function constructTooltipContent(data) {
                    var tooltip = d3.select(document.createElement('div'))
                        .attr('class', 'tooltip-content');

                    tooltip.append('div')
                        .attr('class', 'row-date')
                        .text(moment(data.date).format(chart.localizedFormat()));

                    var tooltip_content_row = tooltip.selectAll('.tooltip-content-row')
                        .data(_.filter(chart.columns(), { activated: true }))
                        .enter().append('div')
                        .attr('id', function(d) { return 'tooltip_' + d.id; })
                        .attr('class', 'tooltip-content-row')
                        .classed('hover', function(d) { return d.hover; });

                    tooltip_content_row.append('div')
                        .attr('class', 'row-legend')
                        .style('background-color', function(d) {
                            var index = _.findIndex(chart.columns(), {id: d.id});
                            return chart.colorScale()(chart.columns().length - 1 - index);
                        });

                    tooltip_content_row.append('div')
                        .attr('class', 'row-label')
                        .text(function (d) { return d.label; });

                    tooltip_content_row.append('div')
                        .attr('class', 'row-value')
                        .text(function (d) { return data[d.id]; });

                    var tooltip_content_total = tooltip.append('div')
                        .attr('class', 'tooltip-content-row');

                    tooltip_content_total.append('div')
                        .attr('class', 'row-legend')
                        .style('background-color', '#FFFFFF');

                    tooltip_content_total.append('div')
                        .attr('class', 'row-label row-total-label')
                        .text('Total');

                    tooltip_content_total.append('div')
                        .attr('class', 'row-value row-total-value')
                        .text(total(data));

                    return tooltip.node().outerHTML;
                }
            };

            chart.initLegendEvents = function() {
                d3.selectAll('.legend-value')
                    .on('click', function(d) {
                        updateLegend(d, d3.select(this));
                        chart.redraw();
                    })
                    .on('mouseover', function(d) {
                        d3.select('#area_' + d.id).classed('hover', true);
                    })
                    .on('mouseout', function(d) {
                        d3.select('#area_' + d.id).classed('hover', false);
                    });
            };

            chart.initStack = function() {
                var stack = d3.stack()
                    .keys(keys)
                    .order(d3.stackOrderNone)
                    .offset(d3.stackOffsetNone);

                chart.stack(stack);
            };

            chart.initArea = function() {
                var area = d3.area()
                    .x(function(d) { return chart.xScale()(moment(d.data.date).toDate()); })
                    .y0(function(d) { return chart.yScale()(d[0]); })
                    .y1(function(d) { return chart.yScale()(d[1]); });

                chart.area(area);
            };

            chart.initLegend = function() {
                var svg_legend = d3.select(element[0]).append('div')
                    .attr('id', 'legend')
                    .append('ul');

                var legend = svg_legend.selectAll('.legend-value')
                    .data(chart.columns().reverse())
                    .enter().append('li')
                    .attr('id', function(d) { return 'legend_' + d.id; })
                    .attr('class', 'legend-value')
                    .style('text-decoration', function(d) {
                        if (d.activated) {
                            return 'none';
                        } else {
                            return 'line-through';
                        }
                    });

                legend.append('span')
                    .attr('class', 'legend-value-color')
                    .style('background-color', function(d, i) { return chart.colorScale()(chart.columns().length - 1 - i); });

                legend.append('span')
                    .text(function(d) { return d.label; });
            };

            chart.initTooltip = function () {
                var tooltip = d3.select(element[0]).append('div')
                    .attr('id', 'tooltip')
                    .classed('tooltip-displayed', false)
                    .classed('tooltip-undisplayed', true);

                chart.tooltip(tooltip);
            };

            chart.resize = function (height, width) {
                if (arguments.length) {
                    chart.height(height);
                    chart.width(width);
                }
                return chart;
            };

            chart.redraw = function () {
                chart.initData(chart.columns());
                chart.initYMax();

                chart.xScale().range([0, chart.width()]);
                chart.yScale().range([chart.height(), 0]);

                chart.yScale().domain([0, chart.yMax()]);

                chart.g().selectAll('.x-axis')
                    .attr('transform', 'translate(0, ' + chart.height() + ')')
                    .call(chart.xAxis());

                chart.g().selectAll('.y-axis')
                    .call(chart.yAxis());

                chart.g().selectAll('.area')
                    .data(chart.stack()(chart.stackData()))
                    .attr('d', chart.area());

                chart.g().selectAll('.y-axis-label')
                    .attr('transform', 'translate(-35,'+(chart.height() / 2)+')rotate(-90)');
            };

            chart.svg = function (new_svg) {
                if (!arguments.length) {
                    return svg;
                }
                svg = new_svg;
                return chart;
            };

            chart.g = function (new_g) {
                if (!arguments.length) {
                    return g;
                }
                g = new_g;
                return chart;
            };

            chart.stack = function (new_stack) {
                if (!arguments.length) {
                    return stack;
                }
                stack = new_stack;
                return chart;
            };

            chart.area = function (new_area) {
                if (!arguments.length) {
                    return area;
                }
                area = new_area;
                return chart;
            };

            chart.line = function (new_line) {
                if (!arguments.length) {
                    return line;
                }
                line = new_line;
                return chart;
            };

            chart.graph = function (new_graph) {
                if (!arguments.length) {
                    return graph;
                }
                graph = new_graph;
                return chart;
            };

            chart.width = function (new_width) {
                if (!arguments.length) {
                    return width;
                }
                width = new_width;
                return chart;
            };

            chart.height = function (new_height) {
                if (!arguments.length) {
                    return height;
                }
                height = new_height;
                return chart;
            };

            chart.colorScale = function (new_color_scale) {
                if (!arguments.length) {
                    return color_scale;
                }
                color_scale = new_color_scale;
                return chart;
            };

            chart.yMax = function (new_y_max) {
                if (!arguments.length) {
                    return yMax;
                }
                yMax = new_y_max;
                return chart;
            };

            chart.xAxis = function (new_x_axis) {
                if (!arguments.length) {
                    return xAxis;
                }
                xAxis = new_x_axis;
                return chart;
            };

            chart.yAxis = function (new_y_axis) {
                if (!arguments.length) {
                    return yAxis;
                }
                yAxis = new_y_axis;
                return chart;
            };

            chart.xScale = function (new_x_scale) {
                if (!arguments.length) {
                    return xScale;
                }
                xScale = new_x_scale;
                return chart;
            };

            chart.yScale = function (new_y_scale) {
                if (!arguments.length) {
                    return yScale;
                }
                yScale = new_y_scale;
                return chart;
            };

            chart.columns = function (new_columns) {
                if (!arguments.length) {
                    return columns;
                }
                columns = new_columns;
                return chart;
            };

            chart.stackData = function (new_stack_data) {
                if (!arguments.length) {
                    return stack_data;
                }
                stack_data = new_stack_data;
                return chart;
            };

            chart.keys = function (new_keys) {
                if (!arguments.length) {
                    return keys;
                }
                keys = new_keys;
                return chart;
            };

            chart.legendWidth = function (new_legend_width) {
                if (!arguments.length) {
                    return legend_width;
                }
                legend_width = new_legend_width;
                return chart;
            };

            chart.axisHeight = function (new_axis_height) {
                if (!arguments.length) {
                    return axis_height;
                }
                axis_height = new_axis_height;
                return chart;
            };

            chart.tooltip = function (new_tooltip) {
                if (!arguments.length) {
                    return tooltip;
                }
                tooltip = new_tooltip;
                return chart;
            };

            chart.bisectDate = function (new_bisect_date) {
                if (!arguments.length) {
                    return bisect_date;
                }
                bisect_date = new_bisect_date;
                return chart;
            };

            chart.localizedFormat = function (new_localized_format) {
                if (!arguments.length) {
                    return localized_format;
                }
                localized_format = new_localized_format;
                return chart;
            };

            chart();
        }
    };
}