describe("KanbanColumnService -", function() {
    var $filter,
        KanbanColumnService,
        KanbanFilterValue;

    beforeEach(function() {
        module('kanban', function($provide) {
            $provide.decorator('$filter', function() {
                return jasmine.createSpy("$filter").and.callFake(function() {
                    return function() {};
                });
            });

            $provide.decorator('KanbanFilterValue', function() {
                return {
                    terms: ''
                };
            });
        });

        inject(function(
            _$filter_,
            _KanbanColumnService_,
            _KanbanFilterValue_
        ) {
            $filter             = _$filter_;
            KanbanColumnService = _KanbanColumnService_;
            KanbanFilterValue   = _KanbanFilterValue_;
        });
    });
    describe("moveItem() -", function() {
        describe("Given an item to move, a source column object, a destination column object and a compared_to object", function() {
            var item, source_column, destination_column;
            beforeEach(function() {
                item = {
                    id       : 27,
                    in_column: 4,
                    timeinfo : {
                        4: 'some previous date'
                    }
                };

                source_column = {
                    id          : 4,
                    is_open     : true,
                    fully_loaded: true,
                    content     : [
                        { id: 79 },
                        item,
                        { id: 100 }
                    ],
                    filtered_content: [
                        { id: 79 },
                        item,
                        { id: 100 }
                    ]
                };

                destination_column = {
                    id          : 2,
                    is_open     : true,
                    fully_loaded: true,
                    content     : [
                        { id: 56 },
                        { id: 21 }
                    ],
                    filtered_content: [
                        { id: 56 },
                        { id: 21 }
                    ]
                };
            });

            describe("and given both columns were open and fully loaded", function() {
                it("and were unfiltered, when I move the item from the source column to the destination column, then the item's time and column properties will be updated and the source and destination columns will be updated", function() {
                    var compared_to = {
                        direction: 'after',
                        item_id  : 21
                    };

                    KanbanColumnService.moveItem(
                        item,
                        source_column,
                        destination_column,
                        compared_to
                    );

                    expect(item.in_column).toEqual(2);
                    expect(item.timeinfo[2]).toBeDefined();
                    expect(source_column.content).toEqual([
                        { id: 79 },
                        { id: 100 }
                    ]);
                    expect(source_column.filtered_content).toEqual([
                        { id: 79 },
                        { id: 100 }
                    ]);
                    expect(source_column.filtered_content).not.toBe(source_column.content);
                    expect(destination_column.content).toEqual([
                        { id: 56 },
                        { id: 21 },
                        item
                    ]);
                    expect(destination_column.filtered_content).toEqual([
                        { id: 56 },
                        { id: 21 },
                        item
                    ]);
                    expect(destination_column.filtered_content).not.toBe(destination_column.content);
                });

                it("and were filtered and the item was in the filter, when I move the item from the source column to the destination column, then the item's time and column properties will be updated and the source and destination columns will be updated", function() {
                    var compared_to = {
                        direction: 'before',
                        item_id  : 56
                    };
                    source_column.filtered_content = [
                        item
                    ];
                    destination_column.filtered_content = [
                        { id: 21 }
                    ];

                    KanbanColumnService.moveItem(
                        item,
                        source_column,
                        destination_column,
                        compared_to
                    );

                    expect(item.in_column).toEqual(2);
                    expect(item.timeinfo[2]).toBeDefined();
                    expect(source_column.content).toEqual([
                        { id: 79 },
                        { id: 100 }
                    ]);
                    expect(source_column.filtered_content).toEqual([]);
                    expect(source_column.filtered_content).not.toBe(source_column.content);
                    expect(destination_column.content).toEqual([
                        item,
                        { id: 56 },
                        { id: 21 }
                    ]);
                    expect(destination_column.filtered_content).toEqual([
                        { id: 21 },
                        item
                    ]);
                    expect(destination_column.filtered_content).not.toBe(destination_column.content);
                });
            });

            it("and given the source column was closed, when I move the item from the source column to the destination column, then the item's time and column properties will be updated and the source and destination columns will be updated", function() {
                var compared_to = {
                    direction: 'after',
                    item_id  : 21
                };
                source_column.is_open          = false;
                source_column.filtered_content = [];

                KanbanColumnService.moveItem(
                    item,
                    source_column,
                    destination_column,
                    compared_to
                );

                expect(item.in_column).toEqual(2);
                expect(item.timeinfo[2]).toBeDefined();
                expect(source_column.content).toEqual([
                    { id: 79 },
                    { id: 100 }
                ]);
                expect(source_column.filtered_content).toEqual([]);
                expect(source_column.filtered_content).not.toBe(source_column.content);
                expect(destination_column.content).toEqual([
                    { id: 56 },
                    { id: 21 },
                    item
                ]);
                expect(destination_column.filtered_content).toEqual([
                    { id: 56 },
                    { id: 21 },
                    item
                ]);
                expect(destination_column.filtered_content).not.toBe(destination_column.content);
            });

            it("and given the destination column was closed, when I move the item from the source column to the destination column, then the item's time and column properties will be updated and the source and destination columns will be updated", function() {
                var compared_to = {
                    direction: 'before',
                    item_id  : 56
                };
                destination_column.is_open = false;
                destination_column.filtered_content = [];

                KanbanColumnService.moveItem(
                    item,
                    source_column,
                    destination_column,
                    compared_to
                );

                expect(item.in_column).toEqual(2);
                expect(item.timeinfo[2]).toBeDefined();
                expect(source_column.content).toEqual([
                     { id: 79 },
                     { id: 100 }
                ]);
                expect(source_column.filtered_content).toEqual([
                    { id: 79 },
                    { id: 100 }
                ]);
                expect(source_column.filtered_content).not.toBe(source_column.content);
                expect(destination_column.content).toEqual([
                    item,
                    { id: 56 },
                    { id: 21 }
                ]);
                expect(destination_column.filtered_content).toEqual([]);
                expect(destination_column.filtered_content).not.toBe(destination_column.content);
            });

            it("and given the destination column was closed and not fully loaded, when I move the item from the source column to the destination column, then the item's time and column properties will be updated, the source column will be updated and the destination's nb_items_at_kanban_init property will be updated", function() {
                var compared_to = {
                    direction: 'after',
                    item_id  : 21
                };
                destination_column.is_open                 = false;
                destination_column.fully_loaded            = false;
                destination_column.content                 = [];
                destination_column.filtered_content        = [];
                destination_column.nb_items_at_kanban_init = 2;

                KanbanColumnService.moveItem(
                    item,
                    source_column,
                    destination_column,
                    compared_to
                );

                expect(item.in_column).toEqual(2);
                expect(item.timeinfo[2]).toBeDefined();
                expect(source_column.content).toEqual([
                     { id: 79 },
                     { id: 100 }
                ]);
                expect(source_column.filtered_content).toEqual([
                    { id: 79 },
                    { id: 100 }
                ]);
                expect(source_column.filtered_content).not.toBe(source_column.content);
                expect(destination_column.content).toEqual([]);
                expect(destination_column.filtered_content).toEqual([]);
                expect(destination_column.filtered_content).not.toBe(destination_column.content);
                expect(destination_column.nb_items_at_kanban_init).toEqual(3);
            });

            it("and given the source column was closed and not fully loaded, when I move the item from the source column to the destination column, then the item's time and column properties will be updated, the source column's nb_items_at_kanban_init property will be updated and the destination column will be updated", function() {
                var compared_to = {
                    direction: 'after',
                    item_id  : 56
                };
                source_column.is_open = false;
                source_column.fully_loaded = false;
                source_column.content = [];
                source_column.filtered_content = [];
                source_column.nb_items_at_kanban_init = 3;

                KanbanColumnService.moveItem(
                    item,
                    source_column,
                    destination_column,
                    compared_to
                );

                expect(item.in_column).toEqual(2);
                expect(item.timeinfo[2]).toBeDefined();
                expect(source_column.content).toEqual([]);
                expect(source_column.filtered_content).toEqual([]);
                expect(source_column.filtered_content).not.toBe(source_column.content);
                expect(source_column.nb_items_at_kanban_init).toEqual(2);
                expect(destination_column.content).toEqual([
                    { id: 56 },
                    item,
                    { id: 21 }
                ]);
                expect(destination_column.filtered_content).toEqual([
                    { id: 56 },
                    item,
                    { id: 21 }
                ]);
                expect(destination_column.filtered_content).not.toBe(destination_column.content);
            });

            it("and given the item was already in the destination column's filtered content (because it was drag and dropped there), when I move the item from the source column to the destination column, then the item will not exist twice in the destination column", function() {
                var compared_to = {
                    direction: 'after',
                    item_id  : 21
                };
                destination_column.filtered_content = [
                    { id: 56 },
                    { id: 21 },
                    item
                ];

                KanbanColumnService.moveItem(
                    item,
                    source_column,
                    destination_column,
                    compared_to
                );

                expect(destination_column.filtered_content).toEqual([
                    { id: 56 },
                    { id: 21 },
                    item
                ]);
            });

            it("and given the source column was the backlog, when I move the item from the backlog to the destination column, then the item's kanban time info will also be updated", function() {
                var compared_to = {
                    direction: 'after',
                    item_id  : 56
                };
                item.in_column = 'backlog';
                item.timeinfo = {
                    backlog: 'some previous date'
                };

                KanbanColumnService.moveItem(
                    item,
                    source_column,
                    destination_column,
                    compared_to
                );

                expect(item.in_column).toEqual(2);
                expect(item.timeinfo.kanban).toBeDefined();
                expect(item.timeinfo.backlog).toBeDefined();
            });
        });

        it("Given an item to move, a source column object, a destination column object and NO compared_to object, when I move the item from the source column to the destination column, then the item's time and column properties will be updated, the item will be removed from the source column and appended to the destination column", function() {
            var item = {
                id       : 90,
                in_column: 8,
                timeinfo : {
                    8: 'some previous date'
                }
            };

            var source_column = {
                id          : 8,
                is_open     : true,
                fully_loaded: true,
                content     : [
                    item,
                    { id: 10 },
                    { id: 88 }
                ],
                filtered_content: [
                    item,
                    { id: 10 },
                    { id: 88 }
                ]
            };

            var destination_column = {
                id              : 'archive',
                is_open         : true,
                fully_loaded    : true,
                content         : [],
                filtered_content: []
            };

            var compared_to = null;

            KanbanColumnService.moveItem(
                item,
                source_column,
                destination_column,
                compared_to
            );

            expect(source_column.content).toEqual([
                { id: 10 },
                { id: 88 }
            ]);
            expect(source_column.filtered_content).toEqual([
                { id: 10 },
                { id: 88 }
            ]);
            expect(source_column.filtered_content).not.toBe(source_column.content);
            expect(destination_column.content).toEqual([
                item
            ]);
            expect(destination_column.filtered_content).toEqual([
                item
            ]);
            expect(destination_column.filtered_content).not.toBe(destination_column.content);
        });
    });

    describe('filterItems() -', function() {
        it("Given filter terms that did not match anything, when I filter a column's items, then the InPropertiesFilter will be called and the column's filtered content collection will be emptied", function() {
            var column = {
                content: [
                    { id: 37 }
                ],
                filtered_content: [
                    { id: 37 }
                ]
            };
            var filtered_content_ref = column.filtered_content;

            KanbanFilterValue.terms = 'reagreement';
            KanbanColumnService.filterItems(column);

            expect($filter).toHaveBeenCalledWith('InPropertiesFilter');
            expect(column.filtered_content).toBe(filtered_content_ref);
            expect(column.filtered_content.length).toEqual(0);
        });

        it("Given filter terms that matched items, when I filter a column's items, then the InPropertiesFilter will be called and the column's filtered content collection will be updated", function() {
            var column = {
                content: [
                    { id: 46 },
                    { id: 37 },
                    { id: 62 }
                ],
                filtered_content: [
                    { id: 46 },
                    { id: 37 },
                    { id: 62 }
                ]
            };
            $filter.and.callFake(function() {
                return function() {
                    return [
                        { id: 46 },
                        { id: 62 }
                    ];
                };
            });

            KanbanFilterValue.terms = '6';
            KanbanColumnService.filterItems(column);

            expect($filter).toHaveBeenCalledWith('InPropertiesFilter');
            expect(column.filtered_content).toEqual([
                { id: 46 },
                { id: 62 }
            ]);
        });
    });
});
