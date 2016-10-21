describe("ReportsModalController -", function() {
    var ReportsModalController,
        $scope,
        $q,
        $modalInstance,
        SharedPropertiesService,
        DiagramRestService,
        kanban_id,
        kanban_label;

    beforeEach(function() {
        module('kanban');

        var $controller,
            $rootScope;

        inject(function(
            _$controller_,
            _$q_,
            _$rootScope_,
            _SharedPropertiesService_,
            _DiagramRestService_
        ) {
            $controller             = _$controller_;
            $q                      = _$q_;
            $rootScope              = _$rootScope_;
            SharedPropertiesService = _SharedPropertiesService_;
            DiagramRestService      = _DiagramRestService_;
        });

        $scope = $rootScope.$new();

        $modalInstance = jasmine.createSpy("$modalInstance");
        kanban_id    = 2;
        kanban_label = "Italy Kanban";
        spyOn(SharedPropertiesService, "getKanban").and.returnValue({
            id     : kanban_id,
            label  : kanban_label,
            columns: [],
            backlog: {
                id    : 'backlog',
                label : 'Backlog'
            },
            archive: {
                id    : 'archive',
                label : 'Archive'
            }
        });
        spyOn(DiagramRestService, "getCumulativeFlowDiagram").and.returnValue($q(angular.noop));

        ReportsModalController = $controller('ReportsModalController', {
            $scope                 : $scope,
            $modalInstance         : $modalInstance,
            SharedPropertiesService: SharedPropertiesService,
            DiagramRestService     : DiagramRestService
        });
    });

    describe("init() -", function() {
        it("when the controller is created, then the cumulative flow diagram data for last week will be retrieved, a loading flag will be set and Chart.js data will be set", function() {
            var cumulative_flow_data = {
                columns: [
                    {
                        id    : 'backlog',
                        label : 'Backlog',
                        values: [
                            {
                                start_date        : '2012-12-07',
                                kanban_items_count: 4
                            }, {
                                start_date        : '2012-09-02',
                                kanban_items_count: 5
                            }
                        ]
                    }, {
                        id    : 'archive',
                        label : 'Archive',
                        values: [
                            {
                                start_date        : '2012-12-07',
                                kanban_items_count: 3
                            }, {
                                start_date        : '2012-09-02',
                                kanban_items_count: 9
                            }
                        ]
                    }
                ]
            };
            DiagramRestService.getCumulativeFlowDiagram.and.returnValue($q.when(cumulative_flow_data));

            ReportsModalController.init();
            expect(ReportsModalController.loading).toBe(true);

            $scope.$apply();
            expect(ReportsModalController.loading).toBe(false);

            var YYYY_MM_DD_regexp = /^\d{4}-\d{2}-\d{2}$/;
            var interval_between_points = 1;
            expect(DiagramRestService.getCumulativeFlowDiagram).toHaveBeenCalledWith(
                kanban_id,
                jasmine.stringMatching(YYYY_MM_DD_regexp),
                jasmine.stringMatching(YYYY_MM_DD_regexp),
                interval_between_points
            );

            expect(ReportsModalController.kanban_label).toEqual(kanban_label);
            expect(ReportsModalController.data).toEqual([
                {
                    id      : 'backlog',
                    label   : 'Backlog',
                    values  : [
                        { start_date: '2012-12-07', kanban_items_count: 4 },
                        { start_date: jasmine.any(String), kanban_items_count: 5 }
                    ]
                }, {
                    id    : 'archive',
                    label : 'Archive',
                    values: [
                        { start_date: '2012-12-07', kanban_items_count: 3 },
                        { start_date: jasmine.any(String), kanban_items_count: 9 }
                    ]
                }
            ]);
        });
    });
});
