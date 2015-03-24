angular
    .module('kanban')
    .directive('wipPopover', WipPopover);

WipPopover.$inject = ['$timeout'];

function WipPopover($timeout) {
    return {
        restrict: 'E',
        templateUrl: 'wip-popover/wip-popover.tpl.html',
        scope: {
            column: '=',
            userIsAdmin: '&userIsAdmin',
            setWipLimit: '&setWipLimit'
        },
        link: function(scope, element, attrs) {
            toggleWipPopover();

            function toggleWipPopover() {
                angular.element('body').click(function (event) {
                    var clicked_element   = angular.element(event.target),
                        clicked_column_id = clicked_element.parents('.column').attr('data-column-id');

                    if (! relatesTo('wip-limit-form')) {
                        $timeout(function() {
                            scope.column.wip_in_edit = false;
                        });

                        if (relatesTo('wip-limit') && clicked_column_id == scope.column.id) {
                            $timeout(function() {
                                scope.column.limit_input = scope.column.limit;
                                scope.column.wip_in_edit = true;

                                $timeout(function() {
                                    angular.element('#wip-limit-input-' + clicked_column_id)[0].focus();
                                });
                            });
                        }
                    }

                    function relatesTo(classname) {
                        return clicked_element.hasClass(classname) || clicked_element.parents('.' + classname).length > 0;
                    }
                });
            }
        }
    };
}